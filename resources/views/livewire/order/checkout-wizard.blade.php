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
    priceChanges: @entangle('priceChanges'),
    unavailableItems: @entangle('unavailableItems'),
    conflictsConfirmed: @js(empty($priceChanges) && empty($unavailableItems)),
    isSyncing: false,
    multiVendorConflict: false,
    multiVendorIds: [],
    
    async init() {
        if (this.cartItems.length === 0) {
            const vendorId = '{{ $vendorId }}';
            const items = await window.CartService.getCartByVendor(vendorId);
            this.cartItems = items;
            this.$wire.set('cartItems', items);
            this.$wire.calculateTotals();
        }

        this.checkMultiVendorConflict();

        window.addEventListener('connectivity-changed', (e) => {
            this.isOffline = e.detail.isOffline;
        });

        window.addEventListener('submit-order-failed', (e) => {
            const detail = e.detail || {};
            const reason = detail.reason || 'validation';
            const messages = {
                multi_vendor: 'В корзине товары из разных ресторанов. Очистите корзину или перейдите к выбору ресторана.',
                empty_cart: 'Корзина пуста. Добавьте товары для оформления заказа.',
                no_vendor: 'Ресторан не выбран. Вернитесь в меню и выберите ресторан.',
                network: 'Проблема с подключением. Проверьте интернет и повторите.',
                validation: detail.message || 'Проверьте правильность заполнения полей.',
            };
            this.error = messages[reason] || detail.message || 'Произошла ошибка. Попробуйте ещё раз.';
            console.warn('[CheckoutWizard] submit-order-failed:', reason, detail);
        });
    },

    async checkMultiVendorConflict() {
        const allItems = await window.CartService.getAllItems();
        const vendorIds = [...new Set(allItems.map(item => String(item.vendorId)))];
        if (vendorIds.length > 1) {
            this.multiVendorConflict = true;
            this.multiVendorIds = vendorIds;
        } else {
            this.multiVendorConflict = false;
            this.multiVendorIds = [];
        }
    },

    async handleClearCartAndContinue() {
        if (confirm('Очистить корзину и продолжить с текущим рестораном?')) {
            await window.CartService.clearAllCarts();
            this.multiVendorConflict = false;
            this.error = null;
            window.location.reload();
        }
    },

    handleGoToRestaurantSelection() {
        window.location.href = '{{ route('restaurants.index') }}';
    },

    async handleNext() {
        if (this.currentStep === 3) {
            this.isSyncing = true;
            try {
                const vendorId = '{{ $vendorId }}';
                const syncData = await window.CartService.syncWithServer(vendorId, this.cartItems);
                
                if (syncData) {
                    this.$wire.setConflicts(syncData.price_changes, syncData.unavailable_items);
                    
                    const updatedItems = await window.CartService.getCartByVendor(vendorId);
                    this.cartItems = updatedItems;
                    this.$wire.updateCartData(updatedItems, syncData.subtotal);
                    
                    if (syncData.price_changes.length > 0 || syncData.unavailable_items.length > 0) {
                        this.conflictsConfirmed = false;
                    } else {
                        this.conflictsConfirmed = true;
                    }
                }
            } catch (e) {
                this.$wire.set('error', 'Ошибка синхронизации с сервером. Проверьте соединение.');
                return;
            } finally {
                this.isSyncing = false;
            }
        }
        this.$wire.nextStep();
    },
}" dusk="checkout-wizard" class="max-w-lg mx-auto bg-white min-h-screen relative font-inter">

    {{-- Success State --}}
    @if($createdOrder)
        <div class="px-6 py-12 text-center animate-slide-up">
            <div class="w-20 h-20 bg-green-50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $isOffline ? 'Заказ сохранен!' : 'Заказ оформлен!' }}
            </h2>
            
            <p class="text-gray-500 mb-8 leading-relaxed px-4">
                @if($isOffline)
                    Мы сохранили ваш заказ локально. Он будет отправлен в ресторан автоматически, как только восстановится соединение.
                @else
                    Ваш заказ #{{ $createdOrder->id }} успешно принят и уже передается в ресторан.
                @endif
            </p>

            <div class="space-y-3">
                <a href="{{ route('order.track', $createdOrder->id ?? 0) }}" 
                    class="block w-full py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-200 hover:bg-orange-600 transition-all btn-press">
                    Отследить заказ
                </a>
                <a href="{{ route('home') }}" 
                    class="block w-full py-4 bg-gray-50 text-gray-600 font-bold rounded-2xl hover:bg-gray-100 transition-all btn-press">
                    На главную
                </a>
            </div>
        </div>
    @else
        {{-- Header --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100">
            <div class="flex items-center gap-3 px-4 h-14">
                <button onclick="history.back()" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <h1 class="flex-1 text-lg font-bold text-gray-900 mt-0.5">Оформление</h1>
                
                {{-- Steps Progress --}}
                <div class="flex gap-1.5 pr-2">
                    @for($i = 1; $i <= 5; $i++)
                        <div class="w-2 h-2 rounded-full transition-all duration-300 {{ $currentStep >= $i ? 'bg-orange-500' : 'bg-gray-200' }}"></div>
                    @endfor
                </div>
            </div>
        </header>

        <main class="px-4 py-6 space-y-6 pb-32">
            {{-- Multi-Vendor Recovery Banner --}}
            <template x-if="multiVendorConflict">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-3 animate-shake">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-amber-600 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-amber-800">В корзине товары из разных ресторанов</p>
                            <p class="text-xs text-amber-600 mt-1">Оформить заказ можно только из одного ресторана за раз.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="handleClearCartAndContinue()"
                            class="flex-1 py-2.5 bg-amber-500 text-white text-sm font-bold rounded-xl hover:bg-amber-600 transition-all">
                            Очистить и продолжить
                        </button>
                        <button @click="handleGoToRestaurantSelection()"
                            class="flex-1 py-2.5 bg-white text-amber-700 text-sm font-bold rounded-xl border border-amber-200 hover:bg-amber-50 transition-all">
                            Выбрать ресторан
                        </button>
                    </div>
                </div>
            </template>

            @if($error)
                <div class="p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 text-sm font-medium flex items-center gap-3 animate-shake">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ $error }}</span>
                </div>
            @endif

            @switch($currentStep)
                @case(1)
                    {{-- Delivery Address --}}
                    <section class="space-y-4 animate-slide-up">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">Адрес доставки</h3>
                            <button wire:click="$dispatch('open-address-selector')" class="text-sm font-bold text-orange-500 hover:text-orange-600">Изменить</button>
                        </div>
                        
                        @if(!empty($address['address']) || !empty($address['lat']))
                            <div class="p-4 bg-orange-50 rounded-2xl border border-orange-100 space-y-2">
                                @if(!empty($address['address']))
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-orange-500 shadow-sm shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 truncate">{{ $address['address'] }}</p>
                                            <p class="text-xs text-orange-400 font-medium mt-0.5">Адрес доставки</p>
                                        </div>
                                    </div>
                                @endif
                                @if(!empty($address['manual_address']))
                                    <p class="text-sm text-gray-700 ml-[52px]">{{ $address['manual_address'] }}</p>
                                @endif
                                @if(!empty($address['landmark']) || !empty($address['entrance']) || !empty($address['floor']) || !empty($address['apartment']))
                                    <div class="flex flex-wrap gap-2 ml-[52px]">
                                        @if(!empty($address['landmark']))<span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100"><svg class="w-3 h-3 mr-1 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>{{ $address['landmark'] }}</span>@endif
                                        @if(!empty($address['entrance']))<span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100">п. {{ $address['entrance'] }}</span>@endif
                                        @if(!empty($address['floor']))<span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100">эт. {{ $address['floor'] }}</span>@endif
                                        @if(!empty($address['apartment']))<span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100">кв. {{ $address['apartment'] }}</span>@endif
                                    </div>
                                @endif
                                @if(!empty($address['courier_comment']))
                                    <p class="text-xs text-gray-500 italic ml-[52px]">«{{ $address['courier_comment'] }}»</p>
                                @endif
                            </div>
                        @else
                            <button wire:click="$dispatch('open-address-selector')" 
                                class="w-full p-8 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center gap-3 text-gray-400 hover:border-orange-200 hover:text-orange-500 transition-all group">
                                <div class="w-12 h-12 rounded-full bg-gray-50 group-hover:bg-orange-50 flex items-center justify-center transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                </div>
                                <span class="font-bold text-sm">Выбрать адрес на карте</span>
                            </button>
                        @endif
                    </section>
                    @break

                @case(2)
                    {{-- Time Selection --}}
                    <section class="space-y-4 animate-slide-up">
                        <h3 class="text-lg font-bold text-gray-900">Время доставки</h3>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <button wire:click="$set('isAsap', true)" 
                                class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all {{ $isAsap ? 'border-orange-500 bg-orange-50' : 'border-gray-50 hover:bg-gray-50' }}">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $isAsap ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-gray-900">Как можно скорее</p>
                                    <p class="text-xs text-gray-400 font-medium">Примерно 35–50 мин</p>
                                </div>
                                @if($isAsap)
                                    <div class="ml-auto text-orange-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                @endif
                            </button>

                            <button wire:click="$set('isAsap', false)" 
                                class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all {{ !$isAsap ? 'border-orange-500 bg-orange-50' : 'border-gray-50 hover:bg-gray-50' }}">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ !$isAsap ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </div>
                                <div class="text-left flex-1">
                                    <p class="font-bold text-gray-900">Выбрать время</p>
                                    @if(!$isAsap)
                                        <input type="time" wire:model="deliveryTime" class="mt-2 block w-full bg-white border-gray-200 rounded-xl text-sm font-bold focus:ring-orange-500 focus:border-orange-500">
                                    @endif
                                </div>
                            </button>
                        </div>
                    </section>
                    @break

                @case(3)
                    {{-- Your Contacts --}}
                    <section class="space-y-6 animate-slide-up">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">{{ __('checkout.summary.contacts') }}</h3>
                        </div>

                        <div class="space-y-4">
                            {{-- Name field with inline error --}}
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-700">{{ __('checkout.summary.name_label') }}</label>
                                <input type="text" wire:model.live="name" name="name" placeholder="Иван Иванов"
                                    class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all {{ $nameError ? 'ring-2 ring-red-300 bg-red-50' : '' }}">
                                @if($nameError)
                                    <p class="text-xs text-red-500 font-medium px-2">{{ $nameError }}</p>
                                @endif
                            </div>

                            {{-- Phone field with inline error and dynamic helper --}}
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-700">{{ __('checkout.summary.phone_label') }}</label>
                                <input type="tel" wire:model.live="phone"
                                    @if($phoneMode === 'strict_region') x-mask="+\9\9399999999" @endif
                                    placeholder="{{ $phoneExample }}"
                                    class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all {{ $phoneError ? 'ring-2 ring-red-300 bg-red-50' : '' }}">
                                @if($phoneError)
                                    <p class="text-xs text-red-500 font-medium px-2">{{ $phoneError }}</p>
                                @else
                                    <p class="text-[10px] text-gray-400 font-medium px-2">{{ $phoneHelperText }}. {{ __('checkout.summary.comment_placeholder') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="pt-4">
                            <label class="block text-sm font-bold text-gray-900 mb-2">{{ __('checkout.summary.comment_label') }}</label>
                            <textarea wire:model.live="comment"
                                x-on:input="$wire.set('comment', $event.target.value)"
                                placeholder="{{ __('checkout.summary.comment_placeholder') }}"
                                class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-medium focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all"
                                rows="3"
                                maxlength="{{ \App\Support\PhoneNormalizer::maxCommentLength() }}"></textarea>
                            <p class="text-[10px] text-gray-400 font-medium px-2 mt-1" x-text="'{{ __('checkout.validation.comment.too_long', ['max' => \App\Support\PhoneNormalizer::maxCommentLength()]) }}'"></p>
                        </div>
                    </section>
                    @break

                @case(4)
                    {{-- Cart Verification / Conflict Resolution --}}
                    <section class="space-y-6 animate-slide-up">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Проверка корзины</h3>
                        </div>

                        @if(empty($priceChanges) && empty($unavailableItems))
                            <div class="p-8 text-center bg-green-50 rounded-3xl border border-green-100">
                                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 text-green-500 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <p class="font-bold text-green-800">Все товары доступны!</p>
                                <p class="text-sm text-green-600 mt-1">Цены актуальны, можно продолжать.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @if(!empty($priceChanges))
                                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                        <div class="flex items-center gap-2 mb-3 text-amber-700 font-bold text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                            <span>Изменение цен</span>
                                        </div>
                                        <div class="bg-white rounded-xl overflow-hidden border border-amber-100 divide-y divide-gray-50">
                                            @foreach($priceChanges as $change)
                                                <div class="p-3 flex justify-between items-center text-sm">
                                                    <span class="text-gray-600 font-medium">{{ $change['name'] }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-400 line-through">{{ number_format($change['old_price'], 0, '.', ' ') }}</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="M5 12h14l-7-7M12 19l7-7"/></svg>
                                                        <span class="font-bold text-gray-900">{{ number_format($change['new_price'], 0, '.', ' ') }} ₽</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($unavailableItems))
                                    <div class="p-4 bg-red-50 rounded-2xl border border-red-100">
                                        <div class="flex items-center gap-2 mb-3 text-red-700 font-bold text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                            <span>Недоступные товары</span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($unavailableItems as $uItem)
                                                <div class="flex items-center gap-3 p-2 bg-white/50 rounded-lg">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="font-bold text-gray-900 text-sm truncate">{{ $uItem['name'] }}</p>
                                                        <p class="text-[10px] text-red-500 font-bold uppercase">{{ $uItem['reason'] }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-red-400 mt-3 font-medium italic">Эти товары были удалены из вашего заказа автоматически.</p>
                                    </div>
                                @endif

                                <div class="pt-2">
                                    <button x-on:click="conflictsConfirmed = true" 
                                        class="w-full py-4 rounded-2xl font-bold transition-all flex items-center justify-center gap-3"
                                        :class="conflictsConfirmed ? 'bg-green-500 text-white' : 'bg-orange-100 text-orange-600 hover:bg-orange-200'">
                                        <template x-if="conflictsConfirmed">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </template>
                                        <span x-text="conflictsConfirmed ? 'Изменения приняты' : 'Принять изменения и продолжить'"></span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <button @click="window.location.href = '{{ route('restaurants.show', $vendorId) }}'" 
                                class="flex-1 py-4 bg-gray-50 text-gray-500 font-bold rounded-2xl hover:bg-gray-100 transition-all">
                                Вернуться в меню
                            </button>
                        </div>
                    </section>
                    @break

                @case(5)
                    {{-- Confirmation / Summary --}}
                    <section class="space-y-6 animate-slide-up">
                        <h3 class="text-lg font-bold text-gray-900">Подтверждение заказа</h3>
                        
                        {{-- Address Summary Card --}}
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Доставка по адресу</p>
                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $address['address'] ?? 'Не выбран' }}</p>
                                </div>
                                <button wire:click="goToStep(1)" class="text-xs font-bold text-orange-500">Изм.</button>
                            </div>
                            
                            <div class="h-px bg-gray-50"></div>

                            <div class="flex items-start gap-12">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Время</p>
                                        <p class="font-bold text-gray-900 text-sm">{{ $isAsap ? 'Как можно скорее' : $deliveryTime }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Оплата</p>
                                        <p class="font-bold text-gray-900 text-sm uppercase">{{ $paymentMethod }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Order Items --}}
                        <div class="space-y-3">
                            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest">Ваш заказ</h4>
                            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden divide-y divide-gray-50">
                                @foreach($cartItems as $item)
                                    <div class="p-3 flex items-center gap-3">
                                        @if($item['image'] ?? false)
                                            <img src="{{ $item['image'] }}" class="w-12 h-12 rounded-lg object-cover">
                                        @else
                                            <div class="w-12 h-12 rounded-lg bg-gray-50 flex items-center justify-center text-gray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/></svg>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm truncate">{{ $item['productName'] }}</p>
                                            <p class="text-xs text-gray-400 font-medium">x{{ $item['quantity'] }}</p>
                                        </div>
                                        <p class="font-bold text-gray-900 text-sm">{{ number_format($item['price'] * $item['quantity'], 0, '.', ' ') }} ₽</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Totals --}}
                        <div class="bg-gray-50 rounded-2xl p-4 space-y-2">
                            <div class="flex justify-between text-sm text-gray-500 font-medium">
                                <span>Сумма заказа</span>
                                <span>{{ number_format($cartTotal, 0, '.', ' ') }} ₽</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 font-medium">
                                <span>Доставка</span>
                                <span class="{{ $deliveryFee == 0 ? 'text-green-500' : '' }} font-bold">
                                    {{ $deliveryFee == 0 ? 'Бесплатно' : number_format($deliveryFee, 0, '.', ' ') . ' ₽' }}
                                </span>
                            </div>
                            <div class="h-px bg-gray-200 my-2"></div>
                            <div class="flex justify-between text-gray-900">
                                <span class="text-lg font-bold">Итого</span>
                                <span class="text-2xl font-black">{{ number_format($finalTotal, 0, '.', ' ') }} ₽</span>
                            </div>
                        </div>

                        @if($isOffline)
                            <div class="p-4 bg-amber-50 border border-amber-100 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </div>
                                <p class="text-xs font-bold text-amber-700">Офлайн режим: заказ будет отправлен автоматически при входе в сеть.</p>
                            </div>
                        @endif
                    </section>
                    @break
            @endswitch
        </main>

        {{-- Footer Actions --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 pb-screen-safe">
            <div class="max-w-lg mx-auto space-y-3">
                {{-- Error Banner (below button, never inside) --}}
                <template x-if="error && currentStep === 5">
                    <div class="p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-xs font-medium flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span x-text="error"></span>
                    </div>
                </template>

                <div class="flex gap-3">
                    @if($currentStep > 1)
                        <button wire:click="prevStep" 
                            class="px-6 py-4 bg-gray-50 text-gray-400 font-bold rounded-2xl hover:bg-gray-100 transition-all btn-press">
                            Назад
                        </button>
                    @endif
                    
                    @if($currentStep < 5)
                        <button @click="handleNext()" :disabled="isSyncing || (currentStep === 4 && !conflictsConfirmed)"
                            class="flex-1 px-6 py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-200 hover:bg-orange-600 transition-all btn-press flex items-center justify-center gap-3 disabled:opacity-50">
                            <span x-show="!isSyncing" x-text="currentStep === 4 ? 'К оплате' : 'Продолжить'"></span>
                            <div x-show="isSyncing" class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                        </button>
                    @else
                        <button wire:click="submitOrder" wire:loading.attr="disabled" :disabled="!conflictsConfirmed || isProcessing"
                            dusk="checkout-submit-button"
                            class="flex-1 px-6 py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-xl shadow-orange-200 hover:bg-orange-600 transition-all btn-press flex items-center justify-center gap-3 disabled:opacity-50">
                            <span wire:loading.remove>Подтвердить заказ</span>
                            <div wire:loading class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
