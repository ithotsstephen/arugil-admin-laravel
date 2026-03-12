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
        $query = Business::query()->with(['category', 'owner']);

        // free text search
        $query->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                if ($search === '') {
                    return;
                }

                $term = mb_strtolower($search, 'UTF-8');
                $like = "%{$term}%";

                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(COALESCE(whatsapp, \'\')) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(COALESCE(name, \'\')) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(COALESCE(keywords::text, \'\')) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(COALESCE(services::text, \'\')) LIKE ?', [$like]);

                    $q->orWhereHas('category', function ($cq) use ($like) {
                        $cq->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    $q->orWhereHas('city', function ($cq) use ($like) {
                        $cq->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    $q->orWhereHas('district', function ($cq) use ($like) {
                        $cq->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });

                    $q->orWhereHas('area', function ($cq) use ($like) {
                        $cq->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });
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
            ;

            // Sorting
            $allowedSorts = [
                'name' => 'businesses.name',
                'category' => 'categories.name',
                'owner' => 'users.name',
                'is_approved' => 'businesses.is_approved',
                'is_featured' => 'businesses.is_featured',
                'expiry_date' => 'businesses.expiry_date',
            ];

            $sort = $request->string('sort')->toString();
            $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

            if (isset($allowedSorts[$sort])) {
                // if sorting by related table, join the table
                if ($sort === 'category') {
                    $query->leftJoin('categories', 'categories.id', '=', 'businesses.category_id')
                        ->select('businesses.*')
                        ->orderBy($allowedSorts[$sort], $direction);
                } elseif ($sort === 'owner') {
                    $query->leftJoin('users', 'users.id', '=', 'businesses.user_id')
                        ->select('businesses.*')
                        ->orderBy($allowedSorts[$sort], $direction);
                } else {
                    $query->orderBy($allowedSorts[$sort], $direction);
                }
            } else {
                $query->orderByDesc('created_at');
            }

            $businesses = $query->paginate(20)->withQueryString();

            return view('admin.businesses.index', compact('businesses'));
    }

    public function create()
    {
        $categories = \App\Models\Category::with('children')->whereNull('parent_id')->get();
        $states = \App\Models\State::orderBy('name')->get();
        $areas = collect();
        return view('admin.businesses.create', compact('categories', 'states', 'areas'));
    }

    /**
     * Save a partial section of the business form via AJAX.
     * Returns the business id so the UI can continue updating the same draft.
     */
    public function partialSave(\Illuminate\Http\Request $request): JsonResponse
    {
        $section = $request->input('section');
        $businessId = $request->input('business_id');

        $map = [
            'basic' => ['name', 'category_id', 'keywords', 'about_title', 'description', 'years_of_business'],
            'location' => ['state_id', 'city_id', 'district_id', 'area_id', 'address', 'latitude', 'longitude'],
            'contact' => ['phone', 'whatsapp', 'email', 'website'],
            'social' => ['facebook', 'instagram', 'twitter', 'linkedin'],
            'media' => ['image_url', 'owner_image_url'],
            'products' => ['products'],
            'payments' => ['payments'],
            'details' => ['services', 'offers'],
            'status' => ['expiry_date', 'is_approved'],
        ];

        if (!isset($map[$section])) {
            return response()->json(['success' => false, 'message' => 'Unknown section'], 400);
        }

        $allowed = $map[$section];
        $fields = [];

        foreach ($allowed as $key) {
            if ($request->has($key)) {
                $fields[$key] = $request->input($key);
            }
        }

        // special handling: keywords -> array
        if (isset($fields['keywords']) && is_string($fields['keywords'])) {
            $keywords = collect(explode(',', $fields['keywords']))->map(fn($k) => trim($k))->filter()->take(12)->values()->all();
            $fields['keywords'] = $keywords;
        }

        // handle common file uploads for media section
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('businesses', 'public');
            $fields['image_url'] = '/storage/' . $path;
        }

        if ($request->hasFile('owner_image_file')) {
            $path = $request->file('owner_image_file')->store('businesses/owners', 'public');
            $fields['owner_image_url'] = '/storage/' . $path;
        }

        $fields['user_id'] = auth()->id();

        try {
            if ($businessId) {
                $business = Business::find($businessId);
                if (! $business) {
                    return response()->json(['success' => false, 'message' => 'Business not found'], 404);
                }
                $business->update($fields);
            } else {
                // Ensure required non-null fields have safe defaults for draft creation (DB may enforce NOT NULL)
                if (empty($fields['category_id'])) {
                    $firstCategoryId = \App\Models\Category::orderBy('id')->value('id');
                    if ($firstCategoryId) {
                        $fields['category_id'] = $firstCategoryId;
                    } else {
                        // As a last resort, use 1 to avoid DB constraint; caller should correct later
                        $fields['category_id'] = 1;
                    }
                }

                // Ensure required non-null columns have safe defaults for drafts
                if (empty($fields['name'])) {
                    $fields['name'] = 'Untitled Business - ' . now()->format('Y-m-d H:i:s');
                }

                // Provide minimal required values where necessary
                $business = Business::create($fields);
            }
        } catch (\Throwable $e) {
            \Log::error('Partial save failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }

        // If products were submitted in this partial save, persist them to products table
        if ($request->has('products')) {
            foreach ($request->input('products') as $index => $p) {
                // update existing
                if (!empty($p['existing_id'])) {
                    $prod = $business->products()->whereKey($p['existing_id'])->first();
                    if ($prod) {
                        // handle uploaded image for existing product
                        if ($request->hasFile("products.{$index}.image_file")) {
                            if (!empty($prod->image_url) && str_starts_with($prod->image_url, '/storage/')) {
                                \Storage::disk('public')->delete(str_replace('/storage/', '', $prod->image_url));
                            }
                            $path = $request->file("products.{$index}.image_file")->store('businesses/products', 'public');
                            $prod->image_url = '/storage/' . $path;
                        } elseif (!empty($p['image_url'])) {
                            $prod->image_url = $p['image_url'];
                        }

                        $prod->name = $p['name'] ?? $prod->name;
                        $prod->price = $p['price'] ?? $prod->price;
                        $prod->description = $p['description'] ?? $prod->description;
                        $prod->save();
                    }
                    continue;
                }

                // skip empty entries
                if (empty($p['name']) && empty($p['price']) && empty($p['description']) && !$request->hasFile("products.{$index}.image_file")) {
                    continue;
                }

                $imageUrl = null;
                if ($request->hasFile("products.{$index}.image_file")) {
                    $path = $request->file("products.{$index}.image_file")->store('businesses/products', 'public');
                    $imageUrl = '/storage/' . $path;
                } elseif (!empty($p['image_url'])) {
                    $imageUrl = $p['image_url'];
                }

                $business->products()->create([
                    'name' => $p['name'] ?? null,
                    'price' => $p['price'] ?? null,
                    'description' => $p['description'] ?? null,
                    'image_url' => $imageUrl,
                ]);
            }
        }

        return response()->json(['success' => true, 'business_id' => $business->id, 'message' => 'Section saved']);
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
            'products' => ['nullable', 'array', 'max:12'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.image_file' => ['nullable', 'image', 'max:5120'],
            'products.*.image_url' => ['nullable', 'url', 'max:2048'],
            'products.*.price' => ['nullable', 'string', 'max:255'],
            'products.*.description' => ['nullable', 'string'],
            'existing_payments' => ['nullable', 'array'],
            'existing_payments.*.id' => ['required', 'exists:business_payments,id'],
            'existing_payments.*.amount' => ['required', 'numeric', 'min:0'],
            'existing_payments.*.paid_at' => ['required', 'date'],
            'existing_payments.*.description' => ['nullable', 'string'],
            'existing_payments.*.transaction_id' => ['nullable', 'string', 'max:255'],
            'delete_payments' => ['nullable', 'array'],
            'delete_payments.*' => ['required', 'exists:business_payments,id'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'products' => ['nullable', 'array', 'max:12'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.image_file' => ['nullable', 'image', 'max:5120'],
            'products.*.image_url' => ['nullable', 'url', 'max:2048'],
            'products.*.price' => ['nullable', 'string', 'max:255'],
            'products.*.description' => ['nullable', 'string'],
        ]);

        // Handle keywords: split by comma, trim, limit to 12
        if (!empty($data['keywords'])) {
            $keywords = collect(explode(',', $data['keywords']))
                ->map(fn($k) => trim($k))
                ->filter()
                ->take(12)
                ->values()
                ->all();
            $data['keywords'] = $keywords;
        } else {
            $data['keywords'] = [];
        }


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
        // Geofence is managed centrally; remove any per-business value
        unset($data['geofence_radius']);
        unset($data['image_file']);
        unset($data['owner_image_file']);

        $business = Business::create($data);

        // Debug: log product payload in local environment to trace missing saves
        if (app()->environment('local')) {
            try {
                \Log::error('Business store - products payload', [
                    'products' => $request->input('products'),
                    'files' => array_keys($request->allFiles()),
                    'existing_products' => $request->input('existing_products'),
                ]);
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        }

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

        // Update existing products
        if ($request->has('existing_products')) {
            foreach ($request->input('existing_products') as $prodData) {
                $prod = $business->products()->whereKey($prodData['id'])->first();
                if (! $prod) continue;

                $update = [];
                $update['name'] = $prodData['name'] ?? $prod->name;
                $update['price'] = $prodData['price'] ?? $prod->price;
                $update['description'] = $prodData['description'] ?? $prod->description;

                if (!empty($prodData['image_url'])) {
                    $update['image_url'] = $prodData['image_url'];
                }

                $prod->update($update);
            }
        }

        // Delete products if requested
        if ($request->filled('delete_products')) {
            $business->products()->whereIn('id', $request->input('delete_products'))->get()->each(function ($p) {
                if (!empty($p->image_url) && str_starts_with($p->image_url, '/storage/')) {
                    \Storage::disk('public')->delete(str_replace('/storage/', '', $p->image_url));
                }
                $p->delete();
            });
        }

        // Handle new products and image uploads for existing ones
        if ($request->has('products')) {
            foreach ($request->input('products') as $index => $p) {
                // if this is an existing product id, update image if uploaded
                if (!empty($p['existing_id'])) {
                    $prod = $business->products()->whereKey($p['existing_id'])->first();
                    if ($prod) {
                        if ($request->hasFile("products.{$index}.image_file")) {
                            // delete old file
                            if (!empty($prod->image_url) && str_starts_with($prod->image_url, '/storage/')) {
                                \Storage::disk('public')->delete(str_replace('/storage/', '', $prod->image_url));
                            }
                            $path = $request->file("products.{$index}.image_file")->store('businesses/products', 'public');
                            $prod->update(['image_url' => '/storage/' . $path]);
                        }
                        $prod->update([
                            'name' => $p['name'] ?? $prod->name,
                            'price' => $p['price'] ?? $prod->price,
                            'description' => $p['description'] ?? $prod->description,
                        ]);
                    }
                    continue;
                }

                if (empty($p['name']) && empty($p['price']) && empty($p['description']) && !$request->hasFile("products.{$index}.image_file")) {
                    continue;
                }

                $imageUrl = null;
                if ($request->hasFile("products.{$index}.image_file")) {
                    $path = $request->file("products.{$index}.image_file")->store('businesses/products', 'public');
                    $imageUrl = '/storage/' . $path;
                } elseif (!empty($p['image_url'])) {
                    $imageUrl = $p['image_url'];
                }

                $business->products()->create([
                    'name' => $p['name'] ?? null,
                    'price' => $p['price'] ?? null,
                    'description' => $p['description'] ?? null,
                    'image_url' => $imageUrl,
                ]);
            }
        }

        // Update existing products
        if ($request->has('existing_products')) {
            foreach ($request->input('existing_products') as $prodData) {
                $prod = $business->products()->whereKey($prodData['id'])->first();
                if (! $prod) continue;

                $update = [];
                $update['name'] = $prodData['name'] ?? $prod->name;
                $update['price'] = $prodData['price'] ?? $prod->price;
                $update['description'] = $prodData['description'] ?? $prod->description;

                if (!empty($prodData['image_url'])) {
                    $update['image_url'] = $prodData['image_url'];
                }

                // image file handled below via products uploads
                $prod->update($update);
            }
        }

        // Delete products if requested
        if ($request->filled('delete_products')) {
                $business->products()->whereIn('id', $request->input('delete_products'))->get()->each(function ($p) {
                if (!empty($p->image_url) && str_starts_with($p->image_url, '/storage/')) {
                    \Storage::disk('public')->delete(str_replace('/storage/', '', $p->image_url));
                }
                $p->delete();
            });
        }

        // Handle new products and image uploads for existing ones
        if ($request->has('products')) {
            foreach ($request->input('products') as $index => $p) {
                // if this is an existing product id, update image if uploaded
                if (!empty($p['existing_id'])) {
                    $prod = $business->products()->whereKey($p['existing_id'])->first();
                    if ($prod) {
                        if ($request->hasFile("products.{$index}.image_file")) {
                            // delete old file
                            if (!empty($prod->image_url) && str_starts_with($prod->image_url, '/storage/')) {
                                \Storage::disk('public')->delete(str_replace('/storage/', '', $prod->image_url));
                            }
                            $path = $request->file("products.{$index}.image_file")->store('businesses/products', 'public');
                            $prod->update(['image_url' => '/storage/' . $path]);
                        }
                        $prod->update([
                            'name' => $p['name'] ?? $prod->name,
                            'price' => $p['price'] ?? $prod->price,
                            'description' => $p['description'] ?? $prod->description,
                        ]);
                    }
                    continue;
                }

                if (empty($p['name']) && empty($p['price']) && empty($p['description']) && !$request->hasFile("products.{$index}.image_file")) {
                    continue;
                }

                $imageUrl = null;
                if ($request->hasFile("products.{$index}.image_file")) {
                    $path = $request->file("products.{$index}.image_file")->store('businesses/products', 'public');
                    $imageUrl = '/storage/' . $path;
                } elseif (!empty($p['image_url'])) {
                    $imageUrl = $p['image_url'];
                }

                $business->products()->create([
                    'name' => $p['name'] ?? null,
                    'price' => $p['price'] ?? null,
                    'description' => $p['description'] ?? null,
                    'image_url' => $imageUrl,
                ]);
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

        // Geofence is managed centrally; remove any per-business value
        unset($data['geofence_radius']);
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

    public function destroy(Business $business)
    {
        // Delete gallery images files and records
        if ($business->relationLoaded('images') === false) {
            $business->load('images');
        }

        foreach ($business->images as $image) {
            if (str_starts_with($image->image_url, '/storage/')) {
                \Storage::disk('public')->delete(str_replace('/storage/', '', $image->image_url));
            }
            $image->delete();
        }

        // Delete payments
        if ($business->relationLoaded('payments') === false) {
            $business->load('payments');
        }
        $business->payments()->delete();

        // Delete main and owner images if stored locally
        if (!empty($business->image_url) && str_starts_with($business->image_url, '/storage/')) {
            \Storage::disk('public')->delete(str_replace('/storage/', '', $business->image_url));
        }

        if (!empty($business->owner_image_url) && str_starts_with($business->owner_image_url, '/storage/')) {
            \Storage::disk('public')->delete(str_replace('/storage/', '', $business->owner_image_url));
        }

        $business->delete();

        return redirect()->route('admin.businesses.index')->with('status', 'Business deleted.');
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
