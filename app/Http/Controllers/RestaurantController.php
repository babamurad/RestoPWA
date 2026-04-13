<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Menu\Models\Category;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function home(): View|string
    {
        // Fetch unique category names from all active restaurants
        $uniqueCategoryNames = Category::where('is_active', true)
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->get();

        $icons = [
            'Пицца' => '🍕',
            'Суши' => '🍣',
            'Роллы' => '🍱',
            'Бургеры' => '🍔',
            'Паста' => '🍝',
            'Салаты' => '🥗',
            'Закуски' => '🍟',
            'Супы' => '🍜',
            'Сеты' => '🍱',
            'Боулы' => '🥣',
            'Десерты' => '🍰',
            'Кофе' => '☕',
            'Выпечка' => '🥐',
        ];

        $categories = $uniqueCategoryNames->map(function ($cat, $index) use ($icons) {
            return (object) [
                'id' => $index + 1,
                'name' => $cat->name,
                'icon' => $icons[$cat->name] ?? '🍴',
            ];
        });

        $popularRestaurants = Restaurant::where('is_active', true)->limit(10)->get();

        $restaurants = Restaurant::with(['categories' => function ($q) {
            $q->where('is_active', true)->with(['products' => function ($pq) {
                $pq->where('is_available', true);
            }]);
        }])->where('is_active', true)->get();

        return view('home', compact('categories', 'popularRestaurants', 'restaurants'));
    }

    public function index(): View|string
    {
        $restaurants = Restaurant::with(['categories' => function ($q) {
            $q->where('is_active', true)->with(['products' => function ($pq) {
                $pq->where('is_available', true);
            }]);
        }])->where('is_active', true)->get();

        return view('restaurants.index', compact('restaurants'));
    }

    public function show(Restaurant $restaurant): View
    {
        $restaurant->load(['categories' => function ($q) {
            $q->where('is_active', true)->orderBy('sort_order')->with(['products' => function ($pq) {
                $pq->where('is_available', true);
            }]);
        }]);

        return view('restaurants.show', compact('restaurant'));
    }
}
