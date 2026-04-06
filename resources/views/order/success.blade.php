<x-layouts.app>
    <x-slot:title>Заказ оформлен - RestoPWA</x-slot:title>

    <div class="bg-gray-50 min-h-screen flex flex-col items-center justify-center px-6 pb-24">
        <div class="max-w-lg w-full text-center">
            
            {{-- Success Animation --}}
            <div class="relative mb-8">
                <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center animate-scale-in">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="absolute inset-0 w-24 h-24 mx-auto bg-green-500/20 rounded-full animate-ping" style="animation-duration: 2s;"></div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2 animate-slide-up">Заказ оформлен!</h1>
            <p class="text-gray-500 mb-8 animate-slide-up" style="animation-delay: 0.1s">Ваш заказ успешно принят и скоро будет передан в работу</p>

            {{-- Order Info Card --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm mb-8 animate-slide-up" style="animation-delay: 0.2s">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-bold text-green-600">Заказ принят</span>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span class="font-medium">Номер заказа</span>
                        <span class="font-bold text-gray-900">#{{ substr($order->id, 0, 8) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span class="font-medium">Примерное время</span>
                        <span class="font-bold text-gray-900">30-45 мин</span>
                    </div>
                    <div class="flex justify-between text-gray-600 border-t border-gray-50 pt-3">
                        <span class="font-medium">Сумма заказа</span>
                        <span class="font-bold text-orange-500">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="space-y-3 animate-slide-up" style="animation-delay: 0.3s">
                <a href="{{ route('order.track', $order->id) }}" class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/20 transition-all touch-feedback active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span>Отследить заказ</span>
                </a>
                <a href="{{ route('home') }}" class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-white text-gray-700 font-bold rounded-2xl border border-gray-200 hover:bg-gray-50 transition-all touch-feedback active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>На главную</span>
                </a>
            </div>

        </div>
    </div>

    <style>
        @keyframes scale-in {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-scale-in { animation: scale-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .animate-slide-up { opacity: 0; animation: slide-up 0.4s ease-out forwards; }
    </style>
</x-layouts.app>
