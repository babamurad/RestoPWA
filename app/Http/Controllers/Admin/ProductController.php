<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Menu\Models\Product;
use App\Domains\Menu\Models\Category;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'restaurant']);
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('status')) {
            $query->where('is_available', $request->status === 'available');
        }
        
        $products = $query->orderBy('name')->paginate(20);
        $restaurants = Restaurant::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.index', compact('products', 'restaurants', 'categories'));
    }

    public function create()
    {
        $restaurants = Restaurant::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.create', compact('restaurants', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'restaurant_id' => 'required|exists:restaurants,id',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string|max:500',
            'is_available' => 'boolean',
            'modifiers' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Товар создан');
    }

    public function edit(Product $product)
    {
        $restaurants = Restaurant::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.edit', compact('product', 'restaurants', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'restaurant_id' => 'required|exists:restaurants,id',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string|max:500',
            'is_available' => 'boolean',
            'modifiers' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Товар обновлён');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Товар удалён');
    }
}
