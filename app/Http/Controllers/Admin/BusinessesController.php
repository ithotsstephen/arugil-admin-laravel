<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        $states = \App\Models\State::orderBy('name')->get();
        $areas = collect();
        return view('admin.businesses.create', compact('categories', 'states', 'areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_image_url' => ['nullable', 'url', 'max:2048'],
            'owner_image_file' => ['nullable', 'image', 'max:5120'],
            'years_of_business' => ['nullable', 'integer', 'min:0', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
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
            'payments' => ['nullable', 'array'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.paid_at' => ['nullable', 'date'],
            'payments.*.description' => ['nullable', 'string'],
            'payments.*.transaction_id' => ['nullable', 'string', 'max:255'],
            'existing_payments' => ['nullable', 'array'],
            'existing_payments.*.id' => ['required', 'exists:business_payments,id'],
            'existing_payments.*.amount' => ['required', 'numeric', 'min:0'],
            'existing_payments.*.paid_at' => ['required', 'date'],
            'existing_payments.*.description' => ['nullable', 'string'],
            'existing_payments.*.transaction_id' => ['nullable', 'string', 'max:255'],
            'delete_payments' => ['nullable', 'array'],
            'delete_payments.*' => ['required', 'exists:business_payments,id'],
        ]);

        // Handle hero image upload
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('businesses', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        // Handle owner image upload
        if ($request->hasFile('owner_image_file')) {
            $path = $request->file('owner_image_file')->store('businesses/owners', 'public');
            $data['owner_image_url'] = '/storage/' . $path;
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
        unset($data['owner_image_file']);

        $business = Business::create($data);

        if ($request->has('payments')) {
            $payments = collect($request->input('payments'))
                ->filter(function ($payment) {
                    return !empty($payment['amount']) && !empty($payment['paid_at']);
                })
                ->map(function ($payment) {
                    return [
                        'amount' => $payment['amount'],
                        'paid_at' => $payment['paid_at'],
                        'description' => $payment['description'] ?? null,
                        'transaction_id' => $payment['transaction_id'] ?? null,
                    ];
                })
                ->values()
                ->all();

            if (!empty($payments)) {
                $business->payments()->createMany($payments);
            }
        }

        if ($request->has('existing_payments')) {
            foreach ($request->input('existing_payments') as $paymentData) {
                $payment = $business->payments()->whereKey($paymentData['id'])->first();

                if ($payment) {
                    $payment->update([
                        'amount' => $paymentData['amount'],
                        'paid_at' => $paymentData['paid_at'],
                        'description' => $paymentData['description'] ?? null,
                        'transaction_id' => $paymentData['transaction_id'] ?? null,
                    ]);
                }
            }
        }

        if ($request->filled('delete_payments')) {
            $business->payments()
                ->whereIn('id', $request->input('delete_payments'))
                ->delete();
        }

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
        $states = \App\Models\State::orderBy('name')->get();
        $cities = $business->state_id ? \App\Models\City::where('state_id', $business->state_id)->orderBy('name')->get() : collect();
        $areas = \App\Models\Area::query()
            ->when($business->city_id, fn ($q) => $q->where('city_id', $business->city_id))
            ->when($business->district_id, fn ($q) => $q->where('district_id', $business->district_id))
            ->orderBy('name')
            ->get();
        $districts = $business->state_id ? \App\Models\District::where('state_id', $business->state_id)->orderBy('name')->get() : collect();
        return view('admin.businesses.edit', compact('business', 'categories', 'states', 'cities', 'districts', 'areas'));
    }

    public function update(Request $request, Business $business)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_image_url' => ['nullable', 'url', 'max:2048'],
            'owner_image_file' => ['nullable', 'image', 'max:5120'],
            'years_of_business' => ['nullable', 'integer', 'min:0', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
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
            'payments' => ['nullable', 'array'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.paid_at' => ['nullable', 'date'],
            'payments.*.description' => ['nullable', 'string'],
            'payments.*.transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        // Handle hero image upload
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('businesses', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        // Handle owner image upload
        if ($request->hasFile('owner_image_file')) {
            $path = $request->file('owner_image_file')->store('businesses/owners', 'public');
            $data['owner_image_url'] = '/storage/' . $path;
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
        unset($data['owner_image_file']);
        $business->update($data);

        if ($request->has('payments')) {
            $payments = collect($request->input('payments'))
                ->filter(function ($payment) {
                    return !empty($payment['amount']) && !empty($payment['paid_at']);
                })
                ->map(function ($payment) {
                    return [
                        'amount' => $payment['amount'],
                        'paid_at' => $payment['paid_at'],
                        'description' => $payment['description'] ?? null,
                        'transaction_id' => $payment['transaction_id'] ?? null,
                    ];
                })
                ->values()
                ->all();

            if (!empty($payments)) {
                $business->payments()->createMany($payments);
            }
        }

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

    public function deletePayment(BusinessPayment $payment)
    {
        $payment->delete();

        return response()->json(['success' => true]);
    }

    public function updatePayment(Request $request, BusinessPayment $payment): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment->update($data);

        return response()->json(['success' => true]);
    }
}
