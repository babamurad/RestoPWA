<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers\Api;

use App\Domains\Order\DTO\OrderSubmitDTO;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Validators\OrderPreconditionValidator;
use App\Enums\OrderRejectReason;
use App\Http\Traits\ApiResponses;
use App\Support\PIIMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController
{
    use ApiResponses;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderPreconditionValidator $preconditionValidator,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $traceId = (string) ($request->header('X-Trace-Id') ?? str()->uuid());

        try {
            $validated = $request->validate([
                'vendor_id' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|string',
                'items.*.product_name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|integer|min:0',
                'items.*.total_price' => 'required|integer|min:0',
                'items.*.modifiers' => 'nullable|array',
                'items.*.image' => 'nullable|string',
                'address' => 'required|array',
                'address.lat' => 'required|numeric',
                'address.lon' => 'required|numeric',
                'address.address' => 'required|string',
                'address.name' => 'required|string',
                'address.phone' => 'required|string',
                'address.house' => 'nullable|string',
                'address.apartment' => 'nullable|string',
                'total' => 'required|integer|min:0',
                'delivery_fee' => 'nullable|integer|min:0',
                'delivery_time' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'comment' => 'nullable|string',
                'is_offline' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[API Order] Validation failed', [
                'trace_id' => $traceId,
                'reason' => OrderRejectReason::VALIDATION->value,
                'errors' => $e->errors(),
            ]);

            return $this->errorResponse(
                reason: OrderRejectReason::VALIDATION,
                details: $e->errors(),
                traceId: $traceId,
            );
        }

        $user = $request->user();

        if (! $user) {
            return $this->errorResponse(
                reason: OrderRejectReason::UNAUTHORIZED,
                traceId: $traceId,
            );
        }

        // Precondition checks
        $preconditionReason = $this->preconditionValidator->validate($validated);
        if ($preconditionReason !== null) {
            Log::warning('[API Order] Precondition check failed', [
                'trace_id' => $traceId,
                'reason' => $preconditionReason->value,
                'user_id' => $user->id,
                'vendor_id' => $validated['vendor_id'],
            ]);

            return $this->errorResponse(
                reason: $preconditionReason,
                traceId: $traceId,
            );
        }

        Log::info('[API Order] store started', [
            'trace_id' => $traceId,
            'user_id' => $user->id,
            'vendor_id' => $validated['vendor_id'],
            'total' => $validated['total'],
            'item_count' => count($validated['items']),
        ]);

        // Idempotency check
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $existingOrder = Order::where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingOrder) {
                Log::info('[API Order] Duplicate order detected', [
                    'trace_id' => $traceId,
                    'order_id' => $existingOrder->id,
                ]);

                return $this->success([
                    'order_id' => $existingOrder->id,
                    'status' => $existingOrder->status,
                    'redirect_url' => route('order.success', $existingOrder->id),
                    'is_duplicate' => true,
                ]);
            }
        }

        // Build DTO
        $validated['idempotency_key'] = $idempotencyKey;
        $dto = OrderSubmitDTO::fromArray($validated, $traceId);

        try {
            $order = $this->orderService->createOrder($dto->toOrderServiceData($user->id));

            Log::info('[API Order] Order created successfully', [
                'trace_id' => $traceId,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'vendor_id' => $order->vendor_id,
            ]);

            return $this->success([
                'order_id' => $order->id,
                'status' => $order->status,
                'redirect_url' => url('/order/success/'.$order->id),
            ], null, 201);

        } catch (\Exception $e) {
            Log::error('[API Order] Creation failed', [
                'trace_id' => $traceId,
                'reason' => OrderRejectReason::SERVER_ERROR->value,
                'exception' => $e->getMessage(),
                'payload_summary' => PIIMasker::maskOrderPayload([
                    'vendor_id' => $dto->vendorId,
                    'item_count' => count($dto->items),
                    'total' => $dto->total,
                ]),
            ]);

            return $this->errorResponse(
                reason: OrderRejectReason::SERVER_ERROR,
                traceId: $traceId,
            );
        }
    }

    private function errorResponse(
        OrderRejectReason $reason,
        array $details = [],
        string $traceId = '',
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $reason->userMessage(),
            'reason' => $reason->value,
            'details' => $details,
            'trace_id' => $traceId,
        ], $reason->httpStatus() > 0 ? $reason->httpStatus() : 422);
    }
}
