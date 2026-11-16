<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Profile;

use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The customer's favourite restaurants. */
class FavoriteController extends Controller
{
    use ApiResponses;

    /** GET /api/v1/profile/favorites — ids of the favourite restaurants */
    public function index(Request $request): JsonResponse
    {
        return $this->success($request->user()->favoriteRestaurants()->pluck('restaurants.id')->values());
    }

    /** PUT /api/v1/profile/favorites/{id} */
    public function store(Request $request, string $id): JsonResponse
    {
        $restaurant = Restaurant::active()->findOrFail($id);

        $request->user()->favoriteRestaurants()->syncWithoutDetaching([$restaurant->id => ['created_at' => now()]]);

        return $this->success(['id' => $restaurant->id, 'is_favorite' => true]);
    }

    /** DELETE /api/v1/profile/favorites/{id} */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->favoriteRestaurants()->detach($id);

        return $this->success(['id' => $id, 'is_favorite' => false]);
    }
}
