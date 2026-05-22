<?php

declare(strict_types=1);

namespace App\Livewire\Order;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Vendor\Models\Restaurant;
use App\Support\PIIMasker;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CheckoutWizard extends Component
{
    #[On('address-selected')]
    public function handleAddressSelected(
        string $address,
        float $lat,
        float $lon,
        string $source = 'map_pin',
        ?string $provider = null,
        string $manual_address = '',
        string $landmark = '',
        string $entrance = '',
        string $floor = '',
        string $apartment = '',
        string $courier_comment = '',
    ): void {
        $this->address = [
            'address' => $address,
            'lat' => $lat,
            'lon' => $lon,
            'source' => $source,
            'provider' => $provider,
            'manual_address' => $manual_address,
            'landmark' => $landmark,
            'entrance' => $entrance,
            'floor' => $floor,
            'apartment' => $apartment,
            'courier_comment' => $courier_comment,
        ];

        if ($this->restaurant) {
            $this->deliveryFee = (float) ($this->restaurant->delivery_fee ?? 0);
        }

        $this->calculateTotals();
    }

    public int $currentStep = 1;
    public string $traceId = '';

    public string $vendorId = '';

    public ?Restaurant $restaurant = null;

    public array $address = [];

    public ?string $deliveryTime = null;

    public bool $isAsap = true;

    public string $paymentMethod = 'card';

    public ?string $comment = '';

    public string $name = '';

    public string $phone = '';

    public string $phoneError = '';

    public string $nameError = '';

    public array $cartItems = [];

    public float $cartTotal = 0;

    public float $deliveryFee = 0;

    public float $finalTotal = 0;

    public bool $isOffline = false;

    public bool $isProcessing = false;

    public ?object $createdOrder = null;

    public ?string $error = null;

    public array $priceChanges = [];

    public array $unavailableItems = [];

    // Shared between frontend and blade
    public string $phoneMode = '';
    public string $phoneExample = '';
    public string $phoneHelperText = '';

    private OrderService $orderService;

    private GeoService $geoService;

    public function boot(): void
    {
        $this->orderService = app(OrderService::class);
        $this->geoService = app(GeoService::class);
    }

    public function mount(): void
    {
        $this->traceId = (string) (request()->query('trace_id') ?? str()->uuid());
        \Illuminate\Support\Facades\Log::info('[Checkout] Wizard mounted', [
            'trace_id' => $this->traceId,
            'user_id' => Auth::id(),
        ]);

        // Load phone validation policy info for frontend
        $this->phoneMode = PhoneNormalizer::getMode();
        $this->phoneExample = PhoneNormalizer::getExample();
        $this->phoneHelperText = PhoneNormalizer::getHelperText();

        $savedAddress = session('current_address');
        if ($savedAddress) {
            $this->address = $savedAddress;
        }

        $vendorId = session('current_vendor_id') ?? request()->query('vendor_id');

        if (empty($vendorId)) {
            \Illuminate\Support\Facades\Log::warning('[Checkout] No vendorId in session or request', ['trace_id' => $this->traceId]);
            session()->flash('error', 'Ресторан не выбран. Пожалуйста, вернитесь в меню.');
            $this->redirect('/', navigate: true);
            return;
        }

        $this->vendorId = (string) $vendorId;
        session(['current_vendor_id' => $this->vendorId]);
        
        $this->restaurant = Restaurant::find($this->vendorId);

        if (! $this->restaurant) {
            \Illuminate\Support\Facades\Log::error('[Checkout] Restaurant not found', [
                'trace_id' => $this->traceId,
                'vendor_id' => $this->vendorId,
            ]);
            session()->flash('error', 'Ресторан не найден.');
            $this->redirect('/', navigate: true);
            return;
        }

        if ($this->address) {
            $this->deliveryFee = (float) ($this->restaurant->delivery_fee ?? 0);
        }

        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->phone = $user->phone ?? '';
        }

        $this->calculateTotals();
    }

    public function updatedIsAsap(bool $value): void
    {
        if ($value) {
            $this->deliveryTime = null;
        }
    }

    /**
     * Live validation/normalization on phone input change.
     * Shows inline error immediately, so the user isn't surprised at step submit.
     */
    public function updatedPhone($value): void
    {
        if (empty($value)) {
            $this->phone = '';
            $this->phoneError = '';
            return;
        }

        $normalized = PhoneNormalizer::normalize($value);
        $this->phone = $normalized;

        if (!empty($this->phone)) {
            $result = PhoneNormalizer::validate($this->phone);
            $this->phoneError = $result['valid'] ? '' : $result['message'];
        } else {
            $this->phoneError = '';
        }
    }

    /**
     * Live validation on name input change.
     */
    public function updatedName($value): void
    {
        if (config('checkout.phone.require_name', true) && empty(trim($value))) {
            $this->nameError = __('checkout.validation.name.required');
        } else {
            $this->nameError = '';
        }
    }

    /**
     * Validate name field.
     */
    private function validateName(): bool
    {
        if (config('checkout.phone.require_name', true) && empty(trim($this->name))) {
            $this->nameError = __('checkout.validation.name.required');
            return false;
        }

        $this->nameError = '';
        return true;
    }

    /**
     * Validate phone field using shared policy.
     */
    private function validatePhone(): bool
    {
        if (empty($this->phone)) {
            $this->phoneError = __('checkout.validation.phone.required');
            return false;
        }

        $result = PhoneNormalizer::validate($this->phone);

        if (! $result['valid']) {
            $this->phoneError = $result['message'];

            \Illuminate\Support\Facades\Log::warning('[Checkout] Phone validation failed', [
                'trace_id' => $this->traceId,
                'reason' => $result['reason'],
                'phone_masked' => PIIMasker::maskPhone($this->phone),
            ]);

            return false;
        }

        $this->phoneError = '';
        return true;
    }

    /**
     * Validate comment length.
     */
    private function validateComment(): bool
    {
        $max = PhoneNormalizer::maxCommentLength();

        if ($this->comment && mb_strlen($this->comment) > $max) {
            $this->error = __('checkout.validation.comment.too_long', ['max' => $max]);
            return false;
        }

        return true;
    }

    private function validateContacts(): bool
    {
        $this->error = null;
        $this->phoneError = '';
        $this->nameError = '';

        $nameOk = $this->validateName();
        $phoneOk = $this->validatePhone();

        if (! $nameOk || ! $phoneOk) {
            return false;
        }

        if (! $this->validateComment()) {
            return false;
        }

        // Save contacts to address for OrderService/OrderModel
        $this->address['name'] = trim($this->name);
        $this->address['phone'] = $this->phone;

        return true;
    }

    private function validateStep(): bool
    {
        $this->error = null;

        return match ($this->currentStep) {
            1 => $this->validateAddress(),
            2 => $this->validateTime(),
            3 => $this->validateContacts(),
            4 => true,
            default => true,
        };
    }

    private function validateAddress(): bool
    {
        if (empty($this->address['lat'] ?? '') || empty($this->address['lon'] ?? '')) {
            $this->error = 'Выберите адрес на карте';
            return false;
        }

        $hasText = ! empty($this->address['address'] ?? '')
            || ! empty($this->address['manual_address'] ?? '')
            || ! empty($this->address['landmark'] ?? '')
            || ! empty($this->address['courier_comment'] ?? '');

        if (! $hasText) {
            $this->error = 'Добавьте ориентир или адрес для курьера';
            return false;
        }

        if ($this->restaurant) {
            $checkResult = $this->geoService->checkDeliveryZone(
                (float) $this->address['lat'],
                (float) $this->address['lon'],
                $this->restaurant->id
            );

            \Illuminate\Support\Facades\Log::info('[CheckoutWizard] Delivery zone check status', [
                'trace_id' => $this->traceId,
                'vendor_id' => $this->restaurant->id,
                'lat' => $this->address['lat'],
                'lon' => $this->address['lon'],
                'result_status' => $checkResult->status,
                'allowed' => $checkResult->isAllowed(),
            ]);

            if (! $checkResult->isAllowed()) {
                $this->error = $checkResult->messageForUser();
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
        if (! in_array($this->paymentMethod, ['card', 'cash', 'sbp'], true)) {
            $this->error = 'Выберите способ оплаты';
            return false;
        }

        return true;
    }

    public function nextStep(): void
    {
        $oldStep = $this->currentStep;
        if ($this->validateStep()) {
            $this->currentStep++;
            \Illuminate\Support\Facades\Log::info('[Checkout] Step advanced', [
                'trace_id' => $this->traceId,
                'from' => $oldStep,
                'to' => $this->currentStep,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::warning('[Checkout] Step validation failed', [
                'trace_id' => $this->traceId,
                'step' => $oldStep,
                'error' => $this->error,
                'phone_error' => $this->phoneError,
                'name_error' => $this->nameError,
            ]);
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->error = null;
            $this->phoneError = '';
            $this->nameError = '';
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
        $this->phoneError = '';
        $this->nameError = '';
    }

    public function submitOrder(): void
    {
        \Illuminate\Support\Facades\Log::info('[Checkout] Submit order initiated', [
            'trace_id' => $this->traceId,
            'vendor_id' => $this->vendorId,
            'step' => $this->currentStep,
            'is_offline' => $this->isOffline,
        ]);

        if (empty($this->vendorId)) {
            $this->error = 'Ресторан не выбран';
            \Illuminate\Support\Facades\Log::warning('[Checkout] Submit failed: no vendorId', ['trace_id' => $this->traceId]);
            return;
        }

        if (! $this->validateStep()) {
            \Illuminate\Support\Facades\Log::warning('[Checkout] Submit failed: step validation', [
                'trace_id' => $this->traceId,
                'error' => $this->error,
            ]);
            return;
        }

        if (empty($this->cartItems)) {
            $this->error = 'Корзина пуста';
            \Illuminate\Support\Facades\Log::warning('[Checkout] Submit failed: empty cart', ['trace_id' => $this->traceId]);
            return;
        }

        if (empty($this->address['lat']) || empty($this->address['lon'])) {
            $this->error = 'Не указаны координаты доставки';
            \Illuminate\Support\Facades\Log::warning('[Checkout] Submit failed: no coordinates', ['trace_id' => $this->traceId]);
            return;
        }

        if (! $this->validatePayment()) {
            \Illuminate\Support\Facades\Log::warning('[Checkout] Submit failed: payment validation', [
                'trace_id' => $this->traceId,
                'error' => $this->error,
            ]);
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
                'trace_id' => $this->traceId,
            ];

            // PII-safe log of payload summary
            \Illuminate\Support\Facades\Log::info('[Checkout] Order payload summary (PII-masked)', [
                'trace_id' => $this->traceId,
                'payload_summary' => PIIMasker::maskOrderPayload([
                    'vendor_id' => $orderData['vendor_id'],
                    'item_count' => count($orderData['items']),
                    'total' => $orderData['total'],
                    'name' => $orderData['address']['name'] ?? '',
                    'phone' => $orderData['address']['phone'] ?? '',
                    'address' => $orderData['address']['address'] ?? '',
                    'comment' => $orderData['comment'] ?? '',
                ]),
            ]);

            if ($this->isOffline) {
                $this->handleOfflineOrder($orderData);
            } else {
                $this->handleOnlineOrder($orderData);
            }

            \Illuminate\Support\Facades\Log::info('[Checkout] Submit order successful', [
                'trace_id' => $this->traceId,
                'order_id' => $this->createdOrder->id ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            $this->error = 'Ошибка при создании заказа. Попробуйте ещё раз.';
            \Illuminate\Support\Facades\Log::error('[Checkout] Submit order exception', [
                'trace_id' => $this->traceId,
                'exception' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
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
        $orderData['id'] = 'OFFLINE-' . time();
        $orderData['status'] = 'pending';
        $this->createdOrder = (object) $orderData;

        $pendingOrders = session('pending_orders', []);
        $pendingOrders[] = $orderData;
        session(['pending_orders' => $pendingOrders]);

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
    }

    private function clearCart(): void
    {
        session()->forget('cart_' . $this->vendorId);
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
        return view('livewire.order.checkout-wizard')
            ->layout('components.layouts.app');
    }
}
