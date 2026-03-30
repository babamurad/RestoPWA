@php
    $formattedPrice = number_format($totalPrice, 0, '.', ' ');
    $canCheckout = !empty($items) && !$isOffline;
@endphp

<div x-data="{ isOpen: @entangle('isOpen'), isOffline: @entangle('isOffline') }" 
     @open-cart.window="isOpen = true"
     @close-cart.window="isOpen = false"
     x-on:keydown.escape.window="isOpen = false">
    
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 z-40"
         @click="isOpen = false">
    </div>

    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-xl z-50 flex flex-col">
        
        <div class="flex items-center justify-between p-4 border-b">
            <h2 class="text-lg font-semibold">Корзина</h2>
            <button @click="isOpen = false" class="p-2 hover:bg-gray-100 rounded-full">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        @if($isOffline)
            <div class="bg-amber-50 border-b border-amber-200 p-3 text-amber-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>Офлайн режим. Заказ будет отправлен при подключении.</span>
            </div>
        @endif

        <div class="flex-1 overflow-y-auto p-4">
            @if(empty($items))
                <div class="flex flex-col items-center justify-center h-full text-gray-500">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <p>Корзина пуста</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($items as $item)
                        <div class="flex gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium">{{ $item['productName'] ?? 'Товар' }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ number_format($item['price'] / 100, 2, '.', ' ') }} ₽
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="updateQuantity({{ $item['id'] ?? 0 }}, {{ ($item['quantity'] ?? 1) - 1 }})"
                                        class="w-8 h-8 flex items-center justify-center bg-gray-200 hover:bg-gray-300 rounded">
                                    -
                                </button>
                                <span class="w-8 text-center">{{ $item['quantity'] ?? 1 }}</span>
                                <button wire:click="updateQuantity({{ $item['id'] ?? 0 }}, {{ ($item['quantity'] ?? 1) + 1 }})"
                                        class="w-8 h-8 flex items-center justify-center bg-gray-200 hover:bg-gray-300 rounded">
                                    +
                                </button>
                                <button wire:click="removeItem({{ $item['id'] ?? 0 }})"
                                        class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if(!empty($items))
            <div class="border-t p-4 space-y-4">
                <div class="flex justify-between text-lg font-semibold">
                    <span>Итого:</span>
                    <span>{{ $formattedPrice }} ₽</span>
                </div>
                <div class="flex gap-2">
                    <button wire:click="clearCart"
                            class="flex-1 py-3 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Очистить
                    </button>
                    <button wire:click="checkout"
                            @if(!$canCheckout) disabled @endif
                            class="flex-1 py-3 px-4 rounded-lg {{ $canCheckout ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}">
                        {{ $isOffline ? 'Оформить (офлайн)' : 'Оформить' }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
