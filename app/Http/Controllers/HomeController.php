<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use Illuminate\Http\Request;

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
}
