<?php

declare(strict_types=1);

namespace App\Livewire\Cart;

use Livewire\Component;
use Livewire\Attributes\On;

class CartDrawer extends Component
{
    public bool $isOpen = false;
    public array $items = [];
    public int $totalQuantity = 0;
    public float $totalPrice = 0.0;
    public string $currentVendorId = '';
    public bool $isOffline = false;

    protected $listeners = [
        'open-cart' => 'openCart',
        'close-cart' => 'closeCart',
        'cart-updated' => 'refreshCart',
        'browser-online' => 'syncWithServer',
        'browser-offline' => 'setOffline',
    ];

    public function mount(): void
    {
        $this->isOffline = !app()->runningInConsole() && !request()->ajax();
        $this->dispatch('request-cart-state');
    }

    #[On('cart-state')]
    public function setCartState(array $state): void
    {
        $this->items = $state['items'] ?? [];
        $this->totalQuantity = $state['totalQuantity'] ?? 0;
        $this->totalPrice = $state['totalPrice'] ?? 0.0;
        $this->currentVendorId = $state['vendorId'] ?? '';
    }

    public function openCart(): void
    {
        $this->isOpen = true;
    }

    public function closeCart(): void
    {
        $this->isOpen = false;
    }

    public function refreshCart(): void
    {
        $this->dispatch('request-cart-state');
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $this->dispatch('cart-update-quantity', itemId: $itemId, quantity: $quantity);
    }

    public function removeItem(int $itemId): void
    {
        $this->dispatch('cart-remove-item', itemId: $itemId);
    }

    public function clearCart(): void
    {
        $this->dispatch('cart-clear');
    }

    public function checkout(): void
    {
        $this->dispatch('cart-checkout');
    }

    public function addItem(string $productId, array $modifiers = [], int $price): void
    {
        $this->dispatch('cart-add-item', [
            'productId' => $productId,
            'vendorId' => $this->currentVendorId,
            'modifiers' => $modifiers,
            'price' => $price,
        ]);
    }

    #[On('browser-online')]
    public function syncWithServer(): void
    {
        $this->isOffline = false;
        $this->dispatch('sync-pending-orders');
    }

    #[On('browser-offline')]
    public function setOffline(): void
    {
        $this->isOffline = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.cart.cart-drawer');
    }
}
