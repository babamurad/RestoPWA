<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Menu\Models\Category;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('restaurant');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        
        $categories = $query->orderBy('name')->paginate(20);
        $restaurants = Restaurant::orderBy('name')->get();
        
        return view('admin.categories.index', compact('categories', 'restaurants'));
    }

    public function create()
    {
        $restaurants = Restaurant::orderBy('name')->get();
        $parentCategories = Category::orderBy('name')->get();
        
        return view('admin.categories.create', compact('restaurants', 'parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'restaurant_id' => 'required|exists:restaurants,id',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория создана');
    }

    public function edit(Category $category)
    {
        $restaurants = Restaurant::orderBy('name')->get();
        $parentCategories = Category::where('id', '!=', $category->id)->orderBy('name')->get();
        
        return view('admin.categories.edit', compact('category', 'restaurants', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'restaurant_id' => 'required|exists:restaurants,id',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория обновлена');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория удалена');
    }
}
