<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\UserPaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => $request->user()->paymentMethods()->orderByDesc('is_default')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'card_mask' => 'required|string|max:20',
            'card_type' => 'nullable|string|max:50',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default) {
            $request->user()->paymentMethods()->update(['is_default' => false]);
        }

        $method = $request->user()->paymentMethods()->create($validated);

        return response()->json([
            'message' => 'Способ оплаты добавлен',
            'data' => $method
        ], 201);
    }

    public function destroy(Request $request, UserPaymentMethod $payment_method)
    {
        if ($payment_method->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment_method->delete();

        return response()->json(['message' => 'Способ оплаты удален']);
    }
}
