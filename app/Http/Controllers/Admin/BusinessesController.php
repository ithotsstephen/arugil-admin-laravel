<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessesController extends Controller
{
    public function index(Request $request)
    {
        $businesses = Business::query()
            ->with(['category', 'owner'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('whatsapp', 'like', '%' . $search . '%')
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->string('status')->toString();
                if ($status === 'approved') {
                    $query->where('is_approved', true);
                }
                if ($status === 'pending') {
                    $query->where('is_approved', false);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return view('admin.businesses.index', compact('businesses'));
    }

    public function create()
    {
        $categories = \App\Models\Category::with('children')->whereNull('parent_id')->get();
        return view('admin.businesses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'years_of_business' => ['nullable', 'integer', 'min:0', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'services' => ['nullable', 'array'],
            'services.*.title' => ['required', 'string', 'max:255'],
            'services.*.description' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'expiry_date' => ['nullable', 'date'],
            'is_approved' => ['nullable', 'boolean'],
        ]);

        // Handle hero image upload
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('businesses', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        // Handle offer images
        if ($request->has('offers')) {
            $offers = $request->input('offers');
            foreach ($offers as $index => $offer) {
                if ($request->hasFile("offers.{$index}.image_file")) {
                    $path = $request->file("offers.{$index}.image_file")->store('offers', 'public');
                    $offers[$index]['image_url'] = '/storage/' . $path;
                    unset($offers[$index]['image_file']);
                }
                unset($offers[$index]['image_type']);
            }
            $data['offers'] = $offers;
        }

        $data['user_id'] = auth()->id();
        $data['is_approved'] = $request->boolean('is_approved', false);
        $data['expiry_date'] = $data['expiry_date'] ?? now()->addYear();
        unset($data['image_file']);

        $business = Business::create($data);

        // Handle gallery images
        if ($request->has('gallery')) {
            foreach ($request->input('gallery') as $index => $gallery) {
                $imageUrl = null;
                
                if ($request->hasFile("gallery.{$index}.image_file")) {
                    $path = $request->file("gallery.{$index}.image_file")->store('businesses/gallery', 'public');
                    $imageUrl = '/storage/' . $path;
                } elseif (!empty($gallery['image_url'])) {
                    $imageUrl = $gallery['image_url'];
                }
                
                if ($imageUrl) {
                    $business->images()->create(['image_url' => $imageUrl]);
                }
            }
        }

        return redirect()->route('admin.businesses.index')->with('status', 'Business created.');
    }

    public function edit(Business $business)
    {
        $categories = \App\Models\Category::with('children')->whereNull('parent_id')->get();
        return view('admin.businesses.edit', compact('business', 'categories'));
    }

    public function update(Request $request, Business $business)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'years_of_business' => ['nullable', 'integer', 'min:0', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'services' => ['nullable', 'array'],
            'services.*.title' => ['required', 'string', 'max:255'],
            'services.*.description' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'expiry_date' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'is_approved' => ['nullable', 'boolean'],
        ]);

        // Handle hero image upload
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('businesses', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        // Handle offer images
        if ($request->has('offers')) {
            $offers = $request->input('offers');
            
            foreach ($offers as $index => $offer) {
                // If uploading a new file
                if ($request->hasFile("offers.{$index}.image_file")) {
                    $path = $request->file("offers.{$index}.image_file")->store('offers', 'public');
                    $offers[$index]['image_url'] = '/storage/' . $path;
                    unset($offers[$index]['image_file']);
                } 
                // If no new file and no URL provided, use existing image
                elseif (empty($offer['image_url']) && !empty($offer['existing_image'])) {
                    $offers[$index]['image_url'] = $offer['existing_image'];
                }
                
                unset($offers[$index]['image_type']);
                unset($offers[$index]['existing_image']);
            }
            $data['offers'] = $offers;
        }

        unset($data['image_file']);
        $business->update($data);

        // Handle gallery images
        if ($request->has('gallery')) {
            foreach ($request->input('gallery') as $index => $gallery) {
                $imageUrl = null;
                
                if ($request->hasFile("gallery.{$index}.image_file")) {
                    $path = $request->file("gallery.{$index}.image_file")->store('businesses/gallery', 'public');
                    $imageUrl = '/storage/' . $path;
                } elseif (!empty($gallery['image_url'])) {
                    $imageUrl = $gallery['image_url'];
                }
                
                if ($imageUrl) {
                    $business->images()->create(['image_url' => $imageUrl]);
                }
            }
        }

        return redirect()->route('admin.businesses.index')->with('status', 'Business updated.');
    }

    public function approve(Business $business)
    {
        $business->update(['is_approved' => true]);

        return redirect()->back()->with('status', 'Business approved.');
    }

    public function reject(Business $business)
    {
        $business->update(['is_approved' => false]);

        return redirect()->back()->with('status', 'Business rejected.');
    }

    public function feature(Business $business)
    {
        $business->update(['is_featured' => !$business->is_featured]);

        return redirect()->back()->with('status', 'Business feature status updated.');
    }

    public function deleteGalleryImage($id)
    {
        $image = \App\Models\BusinessImage::findOrFail($id);
        
        // Delete file if it's stored locally
        if (str_starts_with($image->image_url, '/storage/')) {
            \Storage::disk('public')->delete(str_replace('/storage/', '', $image->image_url));
        }
        
        $image->delete();

        return response()->json(['success' => true]);
    }
}
