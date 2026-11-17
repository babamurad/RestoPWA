<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Http\Controllers;

use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Public restaurant catalog of the customer app. */
class RestaurantController
{
    private const LIST_COLUMNS = [
        'id', 'slug', 'name', 'image', 'cover_image', 'rating', 'review_count', 'delivery_time',
        'delivery_fee', 'min_order', 'is_active', 'description', 'is_paused', 'timezone', 'tags',
    ];

    /**
     * GET /api/v1/restaurants?search=&tag=
     * search: part of the restaurant's name or description, or of the name of one of its dishes.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:40',
        ]);

        $query = Restaurant::active()->with('schedules')->select(self::LIST_COLUMNS);

        $search = trim((string) ($validated['search'] ?? ''));
        if ($search !== '') {
            self::teachSqliteUnicodeLower();

            // LOWER + LIKE works the same on PostgreSQL and SQLite; mb_strtolower handles Cyrillic,
            // which SQLite's own LOWER() does not, hence both spellings are compared
            $needles = array_unique(['%'.self::escapeLike(mb_strtolower($search)).'%', '%'.self::escapeLike($search).'%']);

            $query->where(function (Builder $q) use ($needles) {
                foreach ($needles as $needle) {
                    $q->orWhereRaw("LOWER(restaurants.name) LIKE ? ESCAPE '!'", [$needle])
                        ->orWhereRaw("LOWER(restaurants.description) LIKE ? ESCAPE '!'", [$needle])
                        ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                            ->from('products')
                            ->whereColumn('products.vendor_id', 'restaurants.id')
                            ->where('products.is_available', true)
                            ->whereRaw("LOWER(products.name) LIKE ? ESCAPE '!'", [$needle]));
                }
            });
        }

        if (! empty($validated['tag'])) {
            $query->whereJsonContains('tags', $validated['tag']);
        }

        $restaurants = $query->get()
            ->each(fn (Restaurant $r) => $r->setAppends(['is_open_now', 'delivery_fee_from'])->makeHidden(['schedules']));

        return response()->json($restaurants);
    }

    /** GET /api/v1/restaurants/{vendor} */
    public function show(Restaurant $vendor): JsonResponse
    {
        $vendor->load('schedules');
        $vendor->setAppends(['is_open_now', 'delivery_fee_from']);

        return response()->json($vendor);
    }

    /**
     * SQLite's LOWER() only knows ASCII, so "плов" would never find "Плов" there (PostgreSQL, which
     * production runs on, has no such limit). Give SQLite a Unicode-aware one so that local runs
     * and tests behave like production.
     */
    private static function teachSqliteUnicodeLower(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() === 'sqlite') {
            $connection->getPdo()->sqliteCreateFunction('lower', static fn (?string $value) => $value === null ? null : mb_strtolower($value), 1);
        }
    }

    /** "!" is the LIKE escape character: a backslash inside SQL text trips up PDO's parser on PostgreSQL */
    private static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
