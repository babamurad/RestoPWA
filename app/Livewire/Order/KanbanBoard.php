<?php

declare(strict_types=1);

namespace App\Livewire\Order;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Vendor\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class KanbanBoard extends Component
{
    public string $vendorId = '';

    protected function getListeners(): array
    {
        if (empty($this->vendorId)) {
            return [];
        }

        return [
            "echo-private:restaurant.{$this->vendorId},.order.status.updated" => 'loadOrders',
        ];
    }

    public array $columns = [
        'pending' => [
            'id' => 'pending',
            'title' => 'Новые',
            'statuses' => ['pending'],
            'orders' => [],
        ],
        'cooking' => [
            'id' => 'cooking',
            'title' => 'Готовятся',
            'statuses' => ['confirmed', 'cooking', 'preparing'],
            'orders' => [],
        ],
        'ready' => [
            'id' => 'ready',
            'title' => 'Готовы',
            'statuses' => ['ready', 'ready_for_pickup'],
            'orders' => [],
        ],
        'delivering' => [
            'id' => 'delivering',
            'title' => 'В доставке',
            'statuses' => ['delivering'],
            'orders' => [],
        ],
        'completed' => [
            'id' => 'completed',
            'title' => 'Завершены',
            'statuses' => ['completed', 'delivered'],
            'orders' => [],
        ],
    ];

    private OrderService $orderService;

    public function boot(): void
    {
        if (Filament::hasTenancy() && $tenant = Filament::getTenant()) {
            $this->vendorId = $tenant->id;
        } else {
            $tenant = app('tenant');
            $this->vendorId = $tenant?->id ?? '';

            if (empty($this->vendorId) && auth()->check()) {
                $restaurant = Restaurant::where('vendor_id', auth()->id())->first();
                $this->vendorId = $restaurant?->id ?? '';
            }
        }

        $this->orderService = app(OrderService::class);
    }

    public function mount(): void
    {
        $this->loadOrders();
    }

    public function loadOrders(): void
    {
        if (empty($this->vendorId)) {
            foreach ($this->columns as &$column) {
                $column['orders'] = [];
            }

            return;
        }

        $orders = Order::where('vendor_id', $this->vendorId)
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($this->columns as $key => &$column) {
            $column['orders'] = $orders->filter(function ($order) use ($column) {
                return in_array($order->status, $column['statuses']);
            })->values()->toArray();
        }
    }

    public function moveOrder(string $orderId, string $newColumnId): void
    {
        $order = Order::where('id', $orderId)
            ->where('vendor_id', $this->vendorId)
            ->first();

        if (! $order) {
            return;
        }

        $newStatus = match ($newColumnId) {
            'pending' => OrderService::STATUS_PENDING,
            'cooking' => OrderService::STATUS_CONFIRMED,
            'ready' => OrderService::STATUS_READY,
            'delivering' => OrderService::STATUS_DELIVERING,
            'completed' => OrderService::STATUS_COMPLETED,
            default => null,
        };

        if ($newStatus === null || $order->status === $newStatus) {
            return;
        }

        if ($reason = $this->forbiddenMove($order, $newColumnId)) {
            Notification::make()->title('Нельзя переместить заказ')->body($reason)->warning()->send();
            $this->loadOrders();

            return;
        }

        $oldStatus = $order->status;

        $this->orderService->updateStatus($orderId, $newStatus, [
            'changed_via' => 'kanban',
            'changed_by' => auth()->id(),
            'previous_column' => $this->findColumnByStatus($oldStatus),
            'new_column' => $newColumnId,
        ]);

        Log::info('Order status changed via kanban', [
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'vendor_id' => $this->vendorId,
            'user_id' => auth()->id(),
        ]);

        $this->loadOrders();
    }

    /**
     * Columns an order may not be dragged to. A delivery order is finished by the
     * courier ("delivered"), which pays the courier and counts for payouts; closing
     * it here would skip both. A pickup order is never out for delivery.
     */
    private function forbiddenMove(Order $order, string $column): ?string
    {
        if ($column === 'completed' && ! $order->isPickup()) {
            return 'Заказ с доставкой завершает курьер, когда доставит его клиенту.';
        }

        if ($column === 'delivering' && $order->isPickup()) {
            return 'Это самовывоз: клиент заберёт заказ сам. Когда выдадите — переместите в «Завершены».';
        }

        return null;
    }

    private function findColumnByStatus(string $status): ?string
    {
        foreach ($this->columns as $key => $column) {
            if (in_array($status, $column['statuses'])) {
                return $key;
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.order.kanban-board');
    }
}
