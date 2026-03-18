<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/svg+xml'],
            'icon_svg' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        // If an SVG file was uploaded, read its contents and store raw SVG
        if ($request->hasFile('icon_file')) {
            $svg = file_get_contents($request->file('icon_file')->getRealPath());
            $data['icon_svg'] = $svg;
            // clear icon URL if any
            $data['icon'] = $data['icon'] ?? null;
        }

        // If raw svg text provided in form, prefer it
        if (!empty($data['icon_svg'])) {
            // keep as-is
        }

        Category::create($data);

        return redirect()->back()->with('status', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'icon_file' => ['nullable', 'file', 'mimetypes:image/svg+xml'],
            'icon_svg' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->hasFile('icon_file')) {
            $svg = file_get_contents($request->file('icon_file')->getRealPath());
            $data['icon_svg'] = $svg;
            $data['icon'] = $data['icon'] ?? null;
        }

        // If raw svg text present, use it
        if (!empty($data['icon_svg'])) {
            // leave as-is
        }

        $category->update($data);

        return redirect()->back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->back()->with('status', 'Category deleted.');
    }
}
