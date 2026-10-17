<?php

declare(strict_types=1);

namespace App\Domains\Review\Http\Controllers;

use App\Domains\Order\Models\Order;
use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    use ApiResponses;

    /** Statuses after which the customer has received the order. */
    public const REVIEWABLE_STATUSES = [Order::STATUS_DELIVERED, 'completed'];

    /**
     * POST /api/v1/orders/{id}/review — one review per own completed order.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $order = Order::find($id);

        // Same answer for a missing and a foreign order: don't reveal other users' orders.
        if (! $order || (string) $order->user_id !== (string) $request->user()->id) {
            return $this->error('Order not found', 404);
        }

        if (! in_array($order->status, self::REVIEWABLE_STATUSES, true)) {
            return $this->error('Order is not completed yet', 422);
        }

        if (Review::where('order_id', $order->id)->exists()) {
            return $this->error('Order already reviewed', 409);
        }

        try {
            $review = Review::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'restaurant_id' => $order->vendor_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);
        } catch (QueryException) {
            // Unique order_id: a concurrent duplicate submission
            return $this->error('Order already reviewed', 409);
        }

        return $this->success(self::present($review), null, 201);
    }

    /**
     * GET /api/v1/restaurants/{vendor}/reviews — public, newest first, hidden excluded.
     */
    public function index(Restaurant $vendor): JsonResponse
    {
        $reviews = Review::visible()
            ->where('restaurant_id', $vendor->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews->getCollection()->map(fn (Review $r) => self::present($r))->values(),
            'meta' => [
                'rating' => $vendor->rating,
                'review_count' => $vendor->review_count,
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Public shape: only the author's first name, never contacts.
     */
    public static function present(Review $review): array
    {
        $firstName = Str::of((string) $review->user?->name)->trim()->explode(' ')->first();

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'author' => $firstName !== '' ? $firstName : 'Покупатель',
            'vendor_reply' => $review->vendor_reply,
            'replied_at' => $review->replied_at?->toIso8601String(),
            'created_at' => $review->created_at?->toIso8601String(),
        ];
    }
}
