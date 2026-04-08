<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $query = Restaurant::with('vendor');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $restaurants = $query->orderBy('name')->paginate(20);

        return view('admin.restaurants.index', compact('restaurants'));
    }

    public function create()
    {
        return view('admin.restaurants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:restaurants,slug',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $validated['vendor_id'] = auth()->user()->vendor_id ?? 1;

        Restaurant::create($validated);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Ресторан успешно создан');
    }

    public function edit(Restaurant $restaurant)
    {
        return view('admin.restaurants.edit', compact('restaurant'));
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('restaurants', 'slug')->ignore($restaurant->id)],
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $restaurant->update($validated);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Ресторан успешно обновлён');
    }

    public function destroy(Restaurant $restaurant)
    {
        $restaurant->delete();

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Ресторан удалён');
    }
}
