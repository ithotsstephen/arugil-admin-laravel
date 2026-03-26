<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        // Validate inputs to avoid invalid or malicious values
        $request->validate([
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer',
            'filter.category' => 'nullable|integer',
            'featured' => 'nullable',
            'filter.featured' => 'nullable',
            'sort' => 'nullable|string|max:50',
        ]);

        $cacheKey = 'business_search_' . md5($request->fullUrl());

        // limit per_page to prevent excessive load (validated already)
        $perPage = (int) $request->input('per_page', 15);

        // Default sort and whitelist allowed fields to prevent SQL injection
        $sort = $request->input('sort', '-created_at');
        $allowedSort = ['created_at', 'likes_count', 'name'];

        // Cache for 60 minutes
        $result = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($request, $perPage, $sort, $allowedSort) {
            $query = Business::query()
                ->where('is_approved', true)
                ->where(function($q) {
                    $q->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
                });

            // Accept both legacy params and `filter[...]` style
            $filters = $request->input('filter', []);
            if (!is_array($filters)) {
                $filters = [];
            }

            $categoryId = $filters['category'] ?? $request->input('category_id');
            if ($categoryId) {
                // Include businesses in the category AND all its subcategories
                $categoryIds = \App\Models\Category::where('id', (int) $categoryId)
                    ->orWhere('parent_id', (int) $categoryId)
                    ->pluck('id');
                $query->whereIn('category_id', $categoryIds);
            }

            // Normalize featured boolean from filter[...] or top-level param
            $featured = null;
            if (array_key_exists('featured', $filters)) {
                $featured = filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } elseif ($request->has('featured')) {
                $featured = $request->boolean('featured');
            }

            if ($featured === true) {
                $query->where('is_featured', true);
            }

            // Lightweight search: name, keywords, category
            if ($request->filled('q')) {
                $search = trim($request->string('q')->toString());
                if ($search !== '') {
                    $term = mb_strtolower($search, 'UTF-8');
                    $like = "%{$term}%";

                    $query->where(function ($q) use ($like) {
                                        $q->whereRaw('LOWER(name) LIKE ?', [$like])
                                            ->orWhereRaw('LOWER(COALESCE(keywords::text, \'\')) LIKE ?', [$like])
                                            ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$like])
                                            ->orWhereHas('category', function ($cq) use ($like) {
                                                    $cq->whereRaw('LOWER(name) LIKE ?', [$like]);
                                            });
                    });
                }
            }

            // Sorting: support -field desc syntax and friendly aliases, but whitelist fields
            $sortRequested = $sort;
            if ($sortRequested === 'latest') {
                $sortRequested = '-created_at';
            } elseif ($sortRequested === 'popular') {
                $sortRequested = '-likes_count';
            } elseif ($sortRequested === 'name') {
                $sortRequested = 'name';
            }

            $direction = 'asc';
            if (str_starts_with($sortRequested, '-')) {
                $direction = 'desc';
                $sortRequested = ltrim($sortRequested, '-');
            }

            // If requested field is not allowed, fall back to created_at desc
            if (!in_array($sortRequested, $allowedSort, true)) {
                $query->orderByDesc('created_at');
            } else {
                if ($sortRequested === 'likes_count') {
                    $query->withCount('likes')->orderBy($sortRequested, $direction);
                } else {
                    $query->orderBy($sortRequested, $direction);
                }
            }
            $paginator = $query->with(['category', 'owner'])->withCount('likes')->paginate($perPage);

            return $paginator;
        });

        // $result is a LengthAwarePaginator instance
        $payload = [
            'success' => true,
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
                'next_page_url' => $result->nextPageUrl(),
                'prev_page_url' => $result->previousPageUrl(),
            ],
        ];

        return response()->json($payload)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function show(\Illuminate\Http\Request $request, Business $business)
    {
        if (!$business->is_approved || $business->isExpired()) {
            return response()->json(['message' => 'Business not available.'], 403);
        }

        $business->increment('views');
        $business->load(['category', 'owner', 'images', 'reviews', 'products']);
        $business->loadCount('likes');
            $user = $request->user();
        $payload = $business->toArray();
        $payload['liked_by_user'] = $business->isLikedBy($user);

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($this->categoryRequiresSubcategory((int) $value)) {
                        $fail('Please select a subcategory instead of the main category.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'image', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $business = Business::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'is_approved' => false,
        ]));

        $disk = Storage::disk(config('filesystems.default'));

        if ($request->hasFile('image')) {
            $path = $disk->putFile('businesses/'.$business->id, $request->file('image'), 'public');
            $business->update(['image_url' => $disk->url($path)]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $disk->putFile('businesses/'.$business->id.'/gallery', $file, 'public');
                BusinessImage::create([
                    'business_id' => $business->id,
                    'image_url' => $disk->url($path),
                ]);
            }
        }

        return response()->json($business, 201);
    }

    public function update(Request $request, Business $business)
    {
        $user = $request->user();
        if ($business->user_id !== $user->id && !$user->hasRole('super_admin', 'moderator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'category_id' => [
                'sometimes',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($this->categoryRequiresSubcategory((int) $value)) {
                        $fail('Please select a subcategory instead of the main category.');
                    }
                },
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'whatsapp' => ['sometimes', 'string', 'max:50'],
            'address' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric'],
            'longitude' => ['sometimes', 'numeric'],
            'image_url' => ['sometimes', 'string', 'max:2048'],
            'image' => ['nullable', 'image', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_approved' => ['sometimes', 'boolean'],
        ]);

        $business->update($data);

        $disk = Storage::disk(config('filesystems.default'));

        if ($request->hasFile('image')) {
            $path = $disk->putFile('businesses/'.$business->id, $request->file('image'), 'public');
            $business->update(['image_url' => $disk->url($path)]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $disk->putFile('businesses/'.$business->id.'/gallery', $file, 'public');
                BusinessImage::create([
                    'business_id' => $business->id,
                    'image_url' => $disk->url($path),
                ]);
            }
        }

        return response()->json($business);
    }

    private function categoryRequiresSubcategory(int $categoryId): bool
    {
        return \App\Models\Category::whereKey($categoryId)
            ->whereHas('children')
            ->exists();
    }

    public function featured(Request $request)
    {
        $businesses = Business::query()
            ->where('is_approved', true)
            ->where('is_featured', true)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->with(['category', 'owner'])
            ->withCount('likes')
            ->paginate($request->integer('per_page', 15));

        return response()->json($businesses);
    }

    public function nearby(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'radius' => ['nullable', 'numeric'],
        ]);

        $radius = $data['radius'] ?? 10;
        $lat = $data['lat'];
        $lng = $data['lng'];

        $businesses = Business::query()
            ->select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance',
                [$lat, $lng, $lat]
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_approved', true)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->withCount('likes')
            ->paginate($request->integer('per_page', 15));

    }

    public function like(Request $request, Business $business)
    {
        $user = $request->user();

        if (!$business->is_approved || $business->isExpired()) {
            return response()->json(['message' => 'Business not available.'], 403);
        }

        \App\Models\BusinessLike::firstOrCreate([
            'business_id' => $business->id,
            'user_id' => $user->id,
        ]);

        $business->loadCount('likes');

        return response()->json([
            'likes_count' => $business->likes_count,
            'liked' => true,
        ]);
    }

    public function unlike(Request $request, Business $business)
    {
        $user = $request->user();

        \App\Models\BusinessLike::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->delete();

        $business->loadCount('likes');

        return response()->json([
            'likes_count' => $business->likes_count,
            'liked' => false,
        ]);

        return response()->json($businesses);
    }
}
