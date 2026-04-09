<?php

declare(strict_types=1);

namespace App\Livewire\Order;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Vendor\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CheckoutWizard extends Component
{
    public int $currentStep = 1;

    public string $vendorId = '';

    public ?Restaurant $restaurant = null;

    public array $address = [];

    public ?string $deliveryTime = null;

    public bool $isAsap = true;

    public string $paymentMethod = 'card';

    public string $comment = '';

    public array $cartItems = [];

    public float $cartTotal = 0;

    public float $deliveryFee = 0;

    public float $finalTotal = 0;

    public bool $isOffline = false;

    public bool $isProcessing = false;

    public ?Order $createdOrder = null;

    public ?string $error = null;
    
    public array $priceChanges = [];

    public array $unavailableItems = [];

    private OrderService $orderService;

    private GeoService $geoService;

    public function boot(): void
    {
        $this->orderService = app(OrderService::class);
        $this->geoService = app(GeoService::class);
        $this->isOffline = ! app()->runningInConsole() && ! request()->ajax();
    }

    public function mount(): void
    {
        $savedAddress = session('current_address');
        if ($savedAddress) {
            $this->address = $savedAddress;
        }

        $vendorId = session('current_vendor_id');
        if ($vendorId) {
            $this->vendorId = $vendorId;
            $this->restaurant = Restaurant::find($vendorId);

            if ($this->restaurant && $this->address) {
                $this->deliveryFee = (float) ($this->restaurant->delivery_fee ?? 0);
            }
        }

        $this->calculateTotals();
    }

    public function updatedIsAsap(bool $value): void
    {
        if ($value) {
            $this->deliveryTime = null;
        }
    }

    public function validateStep(): bool
    {
        $this->error = null;

        return match ($this->currentStep) {
            1 => $this->validateAddress(),
            2 => $this->validateTime(),
            3 => $this->validatePayment(),
            4 => true, // Verification step handled via Alpine/JS
            5 => true,
            default => false,
        };
    }

    private function validateAddress(): bool
    {
        if (empty($this->address['address'] ?? '')) {
            $this->error = 'Выберите адрес доставки';

            return false;
        }

        if (empty($this->address['lat'] ?? '') || empty($this->address['lon'] ?? '')) {
            $this->error = 'Координаты адреса не определены';

            return false;
        }

        if ($this->restaurant) {
            $isInZone = $this->geoService->isPointInDeliveryZone(
                (float) $this->address['lat'],
                (float) $this->address['lon'],
                $this->restaurant->id
            );

            if (! $isInZone) {
                $this->error = 'Адрес находится за пределами зоны доставки';

                return false;
            }
        }

        return true;
    }

    private function validateTime(): bool
    {
        if (! $this->isAsap && empty($this->deliveryTime)) {
            $this->error = 'Выберите время доставки';

            return false;
        }

        if (! $this->isAsap && $this->restaurant) {
            $scheduledTime = Carbon::parse($this->deliveryTime);
            if (! $this->orderService->isWithinWorkingHours($this->restaurant, $scheduledTime)) {
                $this->error = 'Ресторан не работает в выбранное время';

                return false;
            }
        }

        return true;
    }

    private function validatePayment(): bool
    {
        if (! in_array($this->paymentMethod, ['card', 'cash', 'sbp'])) {
            $this->error = 'Выберите способ оплаты';

            return false;
        }

        return true;
    }

    public function nextStep(): void
    {
        if ($this->validateStep()) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->error = null;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 5) {
            return;
        }

        if ($step > $this->currentStep) {
            for ($i = $this->currentStep; $i < $step; $i++) {
                if (! $this->validateStep()) {
                    return;
                }
            }
        }

        $this->currentStep = $step;
        $this->error = null;
    }

    public function submitOrder(): void
    {
        if (! $this->validateStep()) {
            return;
        }

        $this->isProcessing = true;
        $this->error = null;

        try {
            $userId = Auth::check() ? Auth::id() : null;

            $orderData = [
                'vendor_id' => $this->vendorId,
                'user_id' => $userId,
                'address' => $this->address,
                'items' => $this->cartItems,
                'total' => $this->finalTotal,
                'delivery_fee' => $this->deliveryFee,
                'delivery_time' => $this->isAsap ? 'asap' : $this->deliveryTime,
                'payment_method' => $this->paymentMethod,
                'comment' => $this->comment,
                'created_via' => 'web',
                'is_offline' => $this->isOffline,
            ];

            if ($this->isOffline) {
                $this->handleOfflineOrder($orderData);
            } else {
                $this->handleOnlineOrder($orderData);
            }

        } catch (\Exception $e) {
            $this->error = 'Ошибка при создании заказа: '.$e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    private function handleOnlineOrder(array $orderData): void
    {
        $this->createdOrder = $this->orderService->createOrder($orderData);

        if ($this->paymentMethod === 'card') {
            $this->processPayment();
        }

        $this->clearCart();
        $this->dispatch('cart-cleared');
    }

    private function handleOfflineOrder(array $orderData): void
    {
        $pendingOrders = session('pending_orders', []);
        $pendingOrders[] = $orderData;
        session(['pending_orders' => $pendingOrders]);

        $this->createdOrder = (object) [
            'id' => 'pending_'.uniqid(),
            'status' => 'pending',
            'is_offline' => true,
        ];

        $this->dispatch('order-queued', ['order' => $orderData]);

        $this->js(<<<'JS'
            if ('serviceWorker' in navigator && 'SyncManager' in window) {
                navigator.serviceWorker.ready.then(function(swReg) {
                    swReg.sync.register('order-sync').catch(function(err) {
                        console.log('Background sync registration failed:', err);
                    });
                });
            }
        JS);

        $this->clearCart();
        $this->dispatch('cart-cleared');
    }

    private function processPayment(): void
    {
        // Placeholder for payment gateway integration
        // In real implementation, this would call PaymentGateway::process()
    }

    private function clearCart(): void
    {
        session()->forget('cart_'.$this->vendorId);
        session()->forget('current_address');
        $this->cartItems = [];
        $this->cartTotal = 0;
        $this->finalTotal = 0;
    }

    public function calculateTotals(): void
    {
        $this->cartTotal = array_sum(array_map(function ($item) {
            return ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }, $this->cartItems));

        $this->finalTotal = $this->cartTotal + $this->deliveryFee;
    }

    public function setConflicts(array $priceChanges, array $unavailableItems): void
    {
        $this->priceChanges = $priceChanges;
        $this->unavailableItems = $unavailableItems;
    }

    public function updateCartData(array $items, float $total): void
    {
        $this->cartItems = $items;
        $this->cartTotal = $total;
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.order.checkout-wizard');
    }
}
