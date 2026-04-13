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

        $query = Restaurant::where('is_active', true);

        if ($search = request('search')) {
            $searchLower = mb_strtolower($search, 'UTF-8');
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereHas('categories', function ($qc) use ($searchLower) {
                      $qc->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                  })
                  ->orWhereHas('categories.products', function ($qp) use ($searchLower) {
                      $qp->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                         ->where('is_available', true);
                  });
            });
            $popularRestaurants = collect(); // Hide popular restaurants when searching
        } else {
            $popularRestaurants = Restaurant::where('is_active', true)->limit(10)->get();
        }

        $restaurants = $query->paginate(20)->withQueryString();

        $view = view('home', compact('categories', 'popularRestaurants', 'restaurants'));

        if (request()->ajax()) {
            return $view->fragment('search-results');
        }

        return $view;
    }

    public function index(): View|string
    {
        $query = Restaurant::where('is_active', true);

        if ($search = request('search')) {
            $searchLower = mb_strtolower($search, 'UTF-8');
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereHas('categories', function ($qc) use ($searchLower) {
                      $qc->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                  })
                  ->orWhereHas('categories.products', function ($qp) use ($searchLower) {
                      $qp->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                         ->where('is_available', true);
                  });
            });
        }

        $restaurants = $query->paginate(20)->withQueryString();

        $view = view('restaurants.index', compact('restaurants'));

        if (request()->ajax()) {
            return $view->fragment('search-results');
        }

        return $view;
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
