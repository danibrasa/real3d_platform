<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            Gate::authorize('manage-blog');
            return $next($request);
        });
    }

    public function index()
    {
        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->get();

        return view('admin.blog.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        BlogCategory::create($validated);

        return redirect()->route('admin.blog.categories.index')
            ->with('success', 'Categoria creada exitosamente.');
    }

    public function update(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return redirect()->route('admin.blog.categories.index')
            ->with('success', 'Categoria actualizada.');
    }

    public function destroy(BlogCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.blog.categories.index')
            ->with('success', 'Categoria eliminada.');
    }
}
