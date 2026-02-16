<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $businesses = Business::query()
            ->where('is_approved', true)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->with(['category', 'owner'])
            ->paginate($request->integer('per_page', 15));

        return response()->json($businesses);
    }

    public function show(Business $business)
    {
        if (!$business->is_approved || $business->isExpired()) {
            return response()->json(['message' => 'Business not available.'], 403);
        }

        $business->increment('views');
        $business->load(['category', 'owner', 'images', 'reviews']);

        return response()->json($business);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
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
            'category_id' => ['sometimes', 'exists:categories,id'],
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
            ->paginate($request->integer('per_page', 15));

        return response()->json($businesses);
    }
}
