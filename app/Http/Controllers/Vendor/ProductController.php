<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Domains\Menu\Models\Product;
use App\Domains\Menu\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = app('tenant')->id;
        
        $products = Product::where('vendor_id', $vendorId)
            ->when($request->category, fn($q) => $q->where('category_id', $request->category))
            ->when($request->available !== null, fn($q) => $q->where('is_available', $request->available))
            ->with('category')
            ->paginate(20);
            
        $categories = Category::where('vendor_id', $vendorId)->get();
        
        return view('vendor.products.index', compact('products', 'categories'));
    }
    
    public function create()
    {
        $vendorId = app('tenant')->id;
        $categories = Category::where('vendor_id', $vendorId)->get();
        
        return view('vendor.products.create', compact('categories'));
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'weight_g' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
            'modifiers' => 'nullable|array',
        ]);
        
        $data['vendor_id'] = app('tenant')->id;
        $data['is_available'] = $request->boolean('is_available', true);
        
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        
        Product::create($data);
        
        return redirect()->route('vendor.products.index')->with('success', 'Товар создан');
    }
    
    public function edit(Product $product)
    {
        $this->authorizeProduct($product);
        $categories = Category::where('vendor_id', app('tenant')->id)->get();
        
        return view('vendor.products.edit', compact('product', 'categories'));
    }
    
    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($product);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'weight_g' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
            'modifiers' => 'nullable|array',
        ]);
        
        $data['is_available'] = $request->boolean('is_available', true);
        
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        
        $product->update($data);
        
        return redirect()->route('vendor.products.index')->with('success', 'Товар обновлен');
    }
    
    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);
        $product->delete();
        
        return back()->with('success', 'Товар удален');
    }
    
    private function authorizeProduct(Product $product): void
    {
        if ($product->vendor_id !== app('tenant')->id) {
            abort(403);
        }
    }
}
