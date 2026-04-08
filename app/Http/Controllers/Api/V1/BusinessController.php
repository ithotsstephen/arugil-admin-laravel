<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Business;
use App\Models\BusinessImage;
use App\Models\Category;
use App\Services\OpenAiEmbeddingSemanticSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BusinessController extends Controller
{
    private const MIN_SEMANTIC_SIMILARITY = 0.72;

    public function __construct(
        private OpenAiEmbeddingSemanticSearchService $semanticSearch,
    ) {
    }

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

        // limit per_page to prevent excessive load (validated already)
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        if ($request->filled('q')) {
            $searchQuery = trim($request->string('q')->toString());
            $query = $this->approvedBusinessesQuery();

            // Accept both legacy params and `filter[...]` style
            $filters = $request->input('filter', []);
            if (! is_array($filters)) {
                $filters = [];
            }

            $categoryId = $filters['category'] ?? $request->input('category_id');
            $this->applyCategoryFilters($query, $categoryId ? (int) $categoryId : null, null);

            $featured = null;
            if (array_key_exists('featured', $filters)) {
                $featured = filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } elseif ($request->has('featured')) {
                $featured = $request->boolean('featured');
            }

            if ($featured === true) {
                $query->where('is_featured', true);
            }

            return $this->paginatedResponse(
                $this->searchResultsPaginator($query, $searchQuery, $perPage, $page)
            );
        }

        $cacheKey = 'business_search_' . md5($request->fullUrl());

        // Default sort and whitelist allowed fields to prevent SQL injection
        $sort = $request->input('sort', '-created_at');
        $allowedSort = ['created_at', 'likes_count', 'name'];

        // Cache for 60 minutes
        $result = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($request, $perPage, $sort, $allowedSort) {
            $query = $this->approvedBusinessesQuery();

            // Accept both legacy params and `filter[...]` style
            $filters = $request->input('filter', []);
            if (! is_array($filters)) {
                $filters = [];
            }

            $categoryId = $filters['category'] ?? $request->input('category_id');
            $this->applyCategoryFilters($query, $categoryId ? (int) $categoryId : null, null);

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
            $paginator = $query
                ->with(['category.parent', 'owner', 'area', 'district'])
                ->withCount('likes')
                ->paginate($perPage)
                ->withQueryString();

            return $paginator;
        });

        return $this->paginatedResponse($result)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function byArea(Request $request, string $area)
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'district_id' => [
                Rule::requiredIf($area === 'all'),
                'nullable',
                'integer',
                'exists:districts,id',
            ],
        ]);

        $paginator = $this->approvedBusinessesQuery()
            ->tap(function (Builder $query) use ($area, $validated) {
                if ($area === 'all') {
                    $query->where('district_id', $validated['district_id']);

                    return;
                }

                $query->where('area_id', Area::query()->findOrFail($area)->id);
            })
            ->tap(function (Builder $query) use ($validated) {
                $this->applyCategoryFilters(
                    $query,
                    $validated['category_id'] ?? null,
                    $validated['subcategory_id'] ?? null
                );
            })
            ->with(['category.parent', 'owner', 'area', 'district'])
            ->withCount('likes')
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return $this->paginatedResponse($paginator);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim($validated['q']);
        $perPage = (int) ($validated['per_page'] ?? 20);
        $page = (int) ($validated['page'] ?? 1);

        return $this->paginatedResponse(
            $this->searchResultsPaginator($this->approvedBusinessesQuery(), $search, $perPage, $page)
        );
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
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
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
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
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
        return Category::whereKey($categoryId)
            ->whereHas('children')
            ->exists();
    }

    private function approvedBusinessesQuery(): Builder
    {
        return Business::query()
            ->where('is_approved', true)
            ->where(function (Builder $query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
    }

    private function applyCategoryFilters(Builder $query, ?int $categoryId, ?int $subcategoryId): void
    {
        if ($subcategoryId) {
            $query->where('category_id', $subcategoryId);

            return;
        }

        if (! $categoryId) {
            return;
        }

        $categoryIds = Category::query()
            ->where('id', $categoryId)
            ->orWhere('parent_id', $categoryId)
            ->pluck('id');

        $query->whereIn('category_id', $categoryIds);
    }

    private function applyKeywordFallbackSearchFilter(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $like = '%' . mb_strtolower($search, 'UTF-8') . '%';
        $keywordsExpression = $this->searchableJsonTextExpression('keywords');
        $servicesExpression = $this->searchableJsonTextExpression('services');

        $query->where(function (Builder $builder) use ($like, $keywordsExpression, $servicesExpression) {
            $builder->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$like])
                ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$like])
                ->orWhereRaw("LOWER({$keywordsExpression}) LIKE ?", [$like])
                ->orWhereRaw("LOWER({$servicesExpression}) LIKE ?", [$like])
                ->orWhereHas('category', function (Builder $categoryQuery) use ($like) {
                    $categoryQuery->whereRaw("LOWER(COALESCE(name, '')) LIKE ?", [$like]);
                });
        });
    }

    private function searchResultsPaginator(Builder $baseQuery, string $search, int $perPage, int $page): LengthAwarePaginator
    {
        $search = trim($search);

        if ($search === '') {
            $paginator = $this->paginateCollection(collect(), $perPage, $page);
            $this->logSearchOutcome($search, false, 0, false, 0);

            return $paginator;
        }

        $semanticPathUsed = false;
        $keywordFallbackUsed = false;
        $semanticMatchesCount = 0;

        if ($search !== '' && $this->semanticSearch->isConfigured()) {
            $semanticCandidates = $this->semanticCandidates($baseQuery);

            if ($semanticCandidates->isNotEmpty()) {
                $semanticPathUsed = true;

                $semanticMatches = $this->semanticSearch
                    ->rankBusinesses($search, $semanticCandidates)
                    ->filter(fn (Business $business) => (float) $business->getAttribute('semantic_score') >= self::MIN_SEMANTIC_SIMILARITY)
                    ->values();

                $semanticMatchesCount = $semanticMatches->count();

                if ($semanticMatchesCount > 0) {
                    $paginator = $this->paginateCollection($semanticMatches, $perPage, $page);
                    $this->logSearchOutcome($search, $semanticPathUsed, $semanticMatchesCount, $keywordFallbackUsed, $paginator->total());

                    return $paginator;
                }
            }
        }

        $keywordFallbackUsed = true;
        $keywordQuery = clone $baseQuery;
        $this->applyKeywordFallbackSearchFilter($keywordQuery, $search);

        $like = '%' . mb_strtolower($search, 'UTF-8') . '%';

        $paginator = $keywordQuery
            ->with(['category.parent', 'owner', 'area', 'district'])
            ->withCount('likes')
            ->orderByRaw("CASE WHEN LOWER(COALESCE(businesses.name, '')) LIKE ? THEN 0 ELSE 1 END", [$like])
            ->orderBy('businesses.name')
            ->paginate($perPage)
            ->withQueryString();

        $this->logSearchOutcome($search, $semanticPathUsed, $semanticMatchesCount, $keywordFallbackUsed, $paginator->total());

        return $paginator;
    }

    private function semanticCandidates(Builder $baseQuery): Collection
    {
        $relations = ['category.parent', 'owner', 'area', 'district', 'city'];

        return (clone $baseQuery)
            ->with($relations)
            ->withCount('likes')
            ->get()
            ->values();
    }

    private function logSearchOutcome(
        string $search,
        bool $semanticPathUsed,
        int $semanticMatchesCount,
        bool $keywordFallbackUsed,
        int $finalResultCount
    ): void {
        Log::info('Business search executed', [
            'search_query' => $search,
            'semantic_path_used' => $semanticPathUsed,
            'semantic_matches_above_threshold' => $semanticMatchesCount,
            'keyword_fallback_used' => $keywordFallbackUsed,
            'final_result_count' => $finalResultCount,
        ]);
    }

    private function paginateCollection(Collection $items, int $perPage, int $page): LengthAwarePaginator
    {
        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return $paginator->appends(request()->query());
    }

    private function searchableJsonTextExpression(string $column): string
    {
        $driver = Business::query()->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            return "COALESCE({$column}::text, '')";
        }

        return "COALESCE({$column}, '')";
    }

    private function paginatedResponse(LengthAwarePaginator $paginator)
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ]);
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
