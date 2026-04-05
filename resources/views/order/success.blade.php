<x-layouts.app>
    <x-slot:title>Заказ оформлен - RestoPWA</x-slot:title>

    <div class="max-w-lg mx-auto bg-gray-50 min-h-screen shadow-xl relative pb-24">
        
        {{-- Header --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100/50">
            <div class="flex items-center gap-3 px-4 h-14">
                <a href="{{ route('home') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="flex-1 text-lg font-bold text-gray-900">Заказ оформлен</h1>
            </div>
        </header>

        <main class="px-4 py-8">
            
            {{-- Success Animation --}}
            <div class="flex flex-col items-center justify-center mb-8 animate-slide-up">
                <div class="relative">
                    <div class="w-28 h-28 bg-green-100 rounded-full flex items-center justify-center animate-bounce-once">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="absolute -inset-4 bg-green-100/50 rounded-full animate-ping"></div>
                </div>
                
                <h2 class="text-2xl font-black text-gray-900 mt-6 text-center">Заказ принят!</h2>
                <p class="text-gray-500 text-center mt-2 max-w-[280px] leading-relaxed">
                    Мы уже начали готовить ваш заказ. Следите за статусом в реальном времени.
                </p>
            </div>

            {{-- Order Details Card --}}
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden animate-slide-up mb-6" style="animation-delay: 0.1s">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">Номер заказа</span>
                            <p class="text-lg font-black text-gray-900 mt-0.5">№{{ substr($order->id, 0, 8) }}</p>
                        </div>
                        <div class="px-3 py-1.5 rounded-full border bg-green-50 text-green-600 border-green-200">
                            <span class="text-[10px] font-black uppercase tracking-widest">Новый</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-50 pt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Оформлен</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Оплата</span>
                            <span class="text-sm font-semibold text-gray-900 capitalize">
                                @switch($order->payment_method ?? 'card')
                                    @case('card')
                                        Картой онлайн
                                        @break
                                    @case('cash')
                                        Наличными
                                        @break
                                    @case('courier')
                                        Курьеру картой
                                        @break
                                    @default
                                        Картой онлайн
                                @endswitch
                            </span>
                        </div>
                        @if($order->delivery_time)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Время доставки</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $order->delivery_time }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Order Items Preview --}}
                <div class="bg-gray-50/50 p-4 border-t border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">В заказе</span>
                    <div class="flex items-center gap-2 overflow-x-auto mt-2 pb-1 scrollbar-hide">
                        @foreach($order->items as $item)
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-white border border-gray-100 overflow-hidden flex items-center justify-center">
                                <span class="text-xs font-bold text-gray-600">{{ $item['quantity'] }}x</span>
                            </div>
                        @endforeach
                        <span class="text-sm font-semibold text-gray-500 ml-2">{{ count($order->items) }} поз.</span>
                    </div>
                </div>

                {{-- Total --}}
                <div class="p-4 bg-orange-50/30 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">Итого к оплате</span>
                        <span class="text-xl font-black text-orange-600">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                    </div>
                </div>
            </div>

            {{-- Delivery Address --}}
            @if($order->address)
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 animate-slide-up mb-6" style="animation-delay: 0.15s">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-orange-100 text-orange-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Адрес доставки</span>
                        <p class="font-semibold text-gray-900 mt-1">
                            {{ $order->address['street'] ?? '' }}{{ $order->address['house'] ? ', ' . $order->address['house'] : '' }}
                            @if($order->address['apartment'])
                                <span class="text-gray-500">, кв. {{ $order->address['apartment'] }}</span>
                            @endif
                        </p>
                        @if($order->address['comment'])
                            <p class="text-sm text-gray-500 mt-1">{{ $order->address['comment'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Track Order Button --}}
            <a href="{{ route('order.track', $order->id) }}" 
               class="flex items-center justify-center gap-3 w-full py-4 px-6 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/30 transition-all touch-feedback active:scale-[0.98] animate-slide-up mb-4" 
               style="animation-delay: 0.2s">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                Отслеживать заказ
            </a>

            {{-- Back to Home --}}
            <a href="{{ route('home') }}" 
               class="flex items-center justify-center gap-2 w-full py-4 px-6 border border-gray-200 text-gray-700 font-bold rounded-2xl hover:bg-gray-50 transition-all animate-slide-up" 
               style="animation-delay: 0.25s">
                Вернуться на главную
            </a>

        </main>

        <x-bottom-nav />

    </div>
</x-layouts.app>
