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
        $ads = Ad::query()
            ->with('category')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories = Category::with('children')->whereNull('parent_id')->orderBy('name')->get();

        return view('admin.ads.index', compact('ads', 'categories'));
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
