<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    public function index(Request $request)
    {
        $adsQuery = Ad::query()
            ->with(['category.parent'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')));

        if ($request->filled('main_category')) {
            $mainCategoryId = $request->integer('main_category');
            $adsQuery->whereHas('category', function ($query) use ($mainCategoryId) {
                $query->where('id', $mainCategoryId)
                    ->orWhere('parent_id', $mainCategoryId);
            });
        }

        if ($request->filled('sub_category')) {
            $adsQuery->where('category_id', $request->integer('sub_category'));
        }

        if ($request->filled('sort')) {
            $adsQuery->leftJoin('categories as c', 'c.id', '=', 'ads.category_id')
                ->leftJoin('categories as p', 'p.id', '=', 'c.parent_id')
                ->select('ads.*');

            $sort = $request->string('sort')->toString();
            if ($sort === 'main_category_asc') {
                $adsQuery->orderByRaw("coalesce(p.name, c.name) asc");
            } elseif ($sort === 'main_category_desc') {
                $adsQuery->orderByRaw("coalesce(p.name, c.name) desc");
            } elseif ($sort === 'sub_category_asc') {
                $adsQuery->orderBy('c.name');
            } elseif ($sort === 'sub_category_desc') {
                $adsQuery->orderByDesc('c.name');
            }
        } else {
            $adsQuery->orderByDesc('created_at');
        }

        $ads = $adsQuery->paginate(20)->withQueryString();

        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();
        $subCategories = Category::whereNotNull('parent_id')->orderBy('name')->get();

        return view('admin.ads.index', compact('ads', 'categories', 'subCategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'image_url' => ['required', 'string', 'max:2048'],
            'link' => ['nullable', 'string', 'max:2048'],
            'placement' => ['required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        Ad::create($data);

        return redirect()->back()->with('status', 'Ad created.');
    }

    public function toggle(Ad $ad)
    {
        $ad->update(['status' => $ad->status === 'active' ? 'paused' : 'active']);

        return redirect()->back()->with('status', 'Ad status updated.');
    }
}
