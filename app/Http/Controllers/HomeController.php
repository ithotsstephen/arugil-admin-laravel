<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $businesses = Business::query()
            ->where('is_approved', true)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->with(['category'])
            ->when($request->filled('category'), fn ($query) => 
                $query->where('category_id', $request->integer('category'))
            )
            ->when($request->filled('search'), fn ($query) => 
                $query->where('name', 'like', '%' . $request->string('search') . '%')
            )
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('home.index', compact('businesses', 'categories'));
    }

    public function show(Business $business)
    {
        if (!$business->is_approved || $business->isExpired()) {
            abort(404);
        }

        $business->increment('views');
        $business->load(['category', 'owner', 'images', 'reviews.user']);

        $relatedBusinesses = Business::query()
            ->where('category_id', $business->category_id)
            ->where('id', '!=', $business->id)
            ->where('is_approved', true)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->limit(4)
            ->get();

        return view('home.show', compact('business', 'relatedBusinesses'));
    }

    public function offers()
    {
        $businesses = Business::query()
            ->where('is_approved', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->whereNotNull('offers')
            ->with(['category'])
            ->get();

        $offers = collect();

        foreach ($businesses as $business) {
            if (!is_array($business->offers)) {
                continue;
            }

            foreach ($business->offers as $offer) {
                if (empty($offer['start_date']) || empty($offer['end_date'])) {
                    continue;
                }

                $startDate = Carbon::parse($offer['start_date']);
                $endDate = Carbon::parse($offer['end_date']);

                if (!now()->between($startDate, $endDate)) {
                    continue;
                }

                $offers->push([
                    'business' => $business,
                    'image_url' => $offer['image_url'] ?? null,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
            }
        }

        $offers = $offers->sortByDesc(fn ($offer) => $offer['end_date'])->values();

        $perPage = 12;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $offers = new LengthAwarePaginator(
            $offers->forPage($page, $perPage)->values(),
            $offers->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return view('home.offers', compact('offers'));
    }
}
