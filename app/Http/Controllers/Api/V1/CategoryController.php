<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    public function businesses(Request $request, Category $category)
    {
        $businesses = $category->businesses()
            ->where('is_approved', true)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', now());
            })
            ->with(['category', 'owner'])
            ->paginate($request->integer('per_page', 15));

        return response()->json($businesses);
    }
}
