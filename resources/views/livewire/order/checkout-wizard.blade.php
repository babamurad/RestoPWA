<div x-data="{
    currentStep: @entangle('currentStep'),
    isProcessing: @entangle('isProcessing'),
    error: @entangle('error'),
    createdOrder: @entangle('createdOrder'),
    isOffline: @entangle('isOffline'),
    address: @entangle('address'),
    deliveryTime: @entangle('deliveryTime'),
    isAsap: @entangle('isAsap'),
    paymentMethod: @entangle('paymentMethod'),
    comment: @entangle('comment'),
    cartItems: @entangle('cartItems'),
    cartTotal: @entangle('cartTotal'),
    deliveryFee: @entangle('deliveryFee'),
    finalTotal: @entangle('finalTotal'),
}" class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

    @if($createdOrder)
        <div class="p-8 text-center">
            @if($isOffline)
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Заказ сохранен</h2>
                <p class="text-gray-600 mb-4">Заказ будет отправлен при подключении к интернету</p>
            @else
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Заказ оформлен!</h2>
                <p class="text-gray-600 mb-2">Номер заказа: {{ $createdOrder->id ?? 'N/A' }}</p>
            @endif
            <button wire:click="$set('createdOrder', null)" class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Вернуться в меню
            </button>
        </div>
    @else
        <div class="border-b">
            <div class="flex">
                @foreach(['Адрес', 'Время', 'Оплата', 'Подтверждение'] as $index => $stepName)
                    <div class="flex-1 py-3 px-4 text-center relative">
                        <div class="flex items-center justify-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors
                                {{ $currentStep > $index + 1 ? 'bg-green-500 text-white' : '' }}
                                {{ $currentStep === $index + 1 ? 'bg-blue-600 text-white' : '' }}
                                {{ $currentStep < $index + 1 ? 'bg-gray-200 text-gray-500' : '' }}">
                                @if($currentStep > $index + 1)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                        </div>
                        <p class="text-xs mt-1 {{ $currentStep === $index + 1 ? 'text-blue-600 font-medium' : 'text-gray-500' }}">{{ $stepName }}</p>
                        @if($index < 3)
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 w-full h-0.5 bg-gray-200 -z-10"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            @if($error)
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $error }}</span>
                </div>
            @endif

            @switch($currentStep)
                @case(1)
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Адрес доставки</h3>
                        
                        @if(!empty($address['address']))
                            <div class="p-4 bg-gray-50 rounded-lg border">
                                <p class="font-medium">{{ $address['address'] }}</p>
                                <p class="text-sm text-gray-500 mt-1">Координаты: {{ $address['lat'] ?? 'N/A' }}, {{ $address['lon'] ?? 'N/A' }}</p>
                            </div>
                        @else
                            <p class="text-gray-500">Адрес не выбран</p>
                        @endif

                        <button wire:click="$dispatch('open-address-selector')" class="text-blue-600 hover:underline text-sm">
                            Изменить адрес
                        </button>
                    </div>
                    @break

                @case(2)
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Время доставки</h3>
                        
                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" wire:model="isAsap" :value="true" class="w-4 h-4 text-blue-600">
                            <div>
                                <p class="font-medium">Как можно скорее</p>
                                <p class="text-sm text-gray-500">Ориентировочно 30-45 минут</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" wire:model="isAsap" :value="false" class="w-4 h-4 text-blue-600">
                            <div class="flex-1">
                                <p class="font-medium">Ко времени</p>
                                <input 
                                    type="datetime-local" 
                                    wire:model="deliveryTime"
                                    class="mt-1 w-full px-3 py-2 border rounded-lg text-sm"
                                    :disabled="isAsap"
                                >
                            </div>
                        </label>
                    </div>
                    @break

                @case(3)
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Способ оплаты</h3>
                        
                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="paymentMethod === 'card' ? 'border-blue-500 bg-blue-50' : ''">
                            <input type="radio" wire:model="paymentMethod" value="card" class="w-4 h-4 text-blue-600">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <div>
                                    <p class="font-medium">Картой онлайн</p>
                                    <p class="text-sm text-gray-500">Visa, MasterCard, МИР</p>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="paymentMethod === 'cash' ? 'border-blue-500 bg-blue-50' : ''">
                            <input type="radio" wire:model="paymentMethod" value="cash" class="w-4 h-4 text-blue-600">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <div>
                                    <p class="font-medium">Наличными</p>
                                    <p class="text-sm text-gray-500">При получении</p>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50" :class="paymentMethod === 'sbp' ? 'border-blue-500 bg-blue-50' : ''">
                            <input type="radio" wire:model="paymentMethod" value="sbp" class="w-4 h-4 text-blue-600">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <p class="font-medium">СБП</p>
                                    <p class="text-sm text-gray-500">Система быстрых платежей</p>
                                </div>
                            </div>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий к заказу</label>
                            <textarea 
                                wire:model="comment" 
                                rows="3" 
                                class="w-full px-3 py-2 border rounded-lg resize-none"
                                placeholder="Не забудьте сдачу, домофон не работает..."
                            ></textarea>
                        </div>
                    </div>
                    @break

                @case(4)
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Подтверждение заказа</h3>
                        
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Адрес:</span>
                                <span class="font-medium text-right max-w-[60%]">{{ $address['address'] ?? 'Не выбран' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Время:</span>
                                <span class="font-medium">{{ $isAsap ? 'Как можно скорее' : $deliveryTime }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Оплата:</span>
                                <span class="font-medium">
                                    @switch($paymentMethod)
                                        @case('card') Картой онлайн @break
                                        @case('cash') Наличными @break
                                        @case('sbp') СБП @break
                                    @endswitch
                                </span>
                            </div>
                            @if($comment)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Комментарий:</span>
                                    <span class="font-medium text-right max-w-[60%]">{{ $comment }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>Товары ({{ count($cartItems) }})</span>
                                <span>{{ number_format($cartTotal, 0, '.', ' ') }} ₽</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Доставка</span>
                                <span>{{ number_format($deliveryFee, 0, '.', ' ') }} ₽</span>
                            </div>
                            <div class="flex justify-between text-lg font-semibold pt-2 border-t">
                                <span>Итого</span>
                                <span>{{ number_format($finalTotal, 0, '.', ' ') }} ₽</span>
                            </div>
                        </div>

                        @if($isOffline)
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm">
                                Офлайн режим. Заказ будет отправлен при подключении к сети.
                            </div>
                        @endif
                    </div>
                    @break
            @endswitch
        </div>

        <div class="border-t p-4 flex gap-3">
            @if($currentStep > 1)
                <button 
                    wire:click="prevStep"
                    class="flex-1 py-3 px-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    Назад
                </button>
            @endif

            @if($currentStep < 4)
                <button 
                    wire:click="nextStep"
                    class="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Далее
                </button>
            @else
                <button 
                    wire:click="submitOrder"
                    wire:loading.attr="disabled"
                    class="flex-1 py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove>Оформить заказ</span>
                    <span wire:loading>Обработка...</span>
                </button>
            @endif
        </div>
    @endif
</div>
