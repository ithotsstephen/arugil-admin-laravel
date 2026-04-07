<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request, Business $business)
    {
        $products = $business->products()->paginate($request->integer('per_page', 12));
        return response()->json($products);
    }

    public function show(Business $business, BusinessProduct $product)
    {
        if ($product->business_id !== $business->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($product);
    }

    public function store(Request $request, Business $business)
    {
        $user = $request->user();
        if ($business->user_id !== $user->id && ! $user->hasRole('super_admin', 'moderator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $imageUrl = $data['image_url'] ?? null;
        $disk = Storage::disk(config('filesystems.default'));

        if ($request->hasFile('image')) {
            $path = $disk->putFile('businesses/'.$business->id.'/products', $request->file('image'), 'public');
            $imageUrl = $disk->url($path);
        }

        $product = $business->products()->create([
            'name' => $data['name'],
            'price' => $data['price'] ?? null,
            'description' => $data['description'] ?? null,
            'image_url' => $imageUrl,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Business $business, BusinessProduct $product)
    {
        $user = $request->user();
        if ($business->user_id !== $user->id && ! $user->hasRole('super_admin', 'moderator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($product->business_id !== $business->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $disk = Storage::disk(config('filesystems.default'));

        if ($request->hasFile('image')) {
            if (!empty($product->image_url) && str_starts_with($product->image_url, $disk->url(''))) {
                // best-effort delete previous file if stored locally
            }
            $path = $disk->putFile('businesses/'.$business->id.'/products', $request->file('image'), 'public');
            $data['image_url'] = $disk->url($path);
        }

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Request $request, Business $business, BusinessProduct $product)
    {
        $user = $request->user();
        if ($business->user_id !== $user->id && ! $user->hasRole('super_admin', 'moderator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($product->business_id !== $business->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $product->delete();

        return response()->json(['success' => true]);
    }
}
