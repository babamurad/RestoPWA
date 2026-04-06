@php
    $statusConfig = [
        'pending' => ['label' => 'Ожидает', 'color' => 'bg-amber-50 text-amber-600 border-amber-100'],
        'accepted' => ['label' => 'Принят', 'color' => 'bg-blue-50 text-blue-600 border-blue-100'],
        'preparing' => ['label' => 'Готовится', 'color' => 'bg-indigo-50 text-indigo-600 border-indigo-100'],
        'ready' => ['label' => 'Готов', 'color' => 'bg-cyan-50 text-cyan-600 border-cyan-100'],
        'on_the_way' => ['label' => 'В пути', 'color' => 'bg-orange-50 text-orange-600 border-orange-100'],
        'delivered' => ['label' => 'Доставлен', 'color' => 'bg-green-50 text-green-600 border-green-100'],
        'cancelled' => ['label' => 'Отменен', 'color' => 'bg-red-50 text-red-600 border-red-100'],
    ];
@endphp

<x-layouts.app>
    <x-slot:title>Мои заказы - RestoPWA</x-slot:title>

    <div class="bg-gray-50 min-h-screen pb-24">
        <div class="max-w-lg mx-auto bg-white min-h-screen shadow-xl relative">
            
            {{-- Header --}}
            <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100">
                <div class="flex items-center gap-3 px-4 h-14">
                    <a href="{{ route('home') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    <h1 class="flex-1 text-lg font-bold text-gray-900 truncate mt-0.5">Мои заказы</h1>
                </div>
            </header>

            <main class="px-4 py-6" x-data="{ 
                pendingOrders: [],
                async init() {
                    if (window.CartService) {
                        this.pendingOrders = await window.CartService.getPendingOrders();
                        window.addEventListener('online', async () => {
                            this.pendingOrders = await window.CartService.getPendingOrders();
                        });
                    }
                }
            }">
                
                {{-- Pending Orders from IndexedDB --}}
                <template x-for="order in pendingOrders" :key="order.id">
                    <div class="mb-4 bg-amber-50 rounded-2xl border border-amber-100 overflow-hidden animate-pulse">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest italic">Ожидает отправки</span>
                                        <span class="text-[10px] text-amber-200">•</span>
                                        <span class="text-[10px] font-bold text-amber-500" x-text="new Date(order.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-900 leading-tight">Заказ в очереди (офлайн)</h3>
                                </div>
                                <div class="flex items-center px-2 py-1 rounded-full bg-amber-100 text-amber-600 text-[10px] font-bold uppercase tracking-wider">
                                    Синхронизация
                                </div>
                            </div>

                            <div class="flex items-end justify-between mt-2 pt-3 border-t border-amber-100">
                                <span class="text-xl font-bold text-gray-900" x-text="(order.payload.total).toLocaleString() + ' ₽'"></span>
                                <div class="flex items-center gap-2 text-amber-600 text-xs font-bold">
                                    Отправим при сети
                                    <svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                
                <div class="space-y-4">
                    @forelse($orders as $order)
                        @php $status = $statusConfig[$order->status] ?? $statusConfig['pending']; @endphp
                        <div class="p-4 bg-white rounded-2xl border border-gray-100 shadow-sm card-hover touch-feedback group">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">№{{ substr($order->id, 0, 8) }}</span>
                                        <span class="text-[10px] text-gray-300">•</span>
                                        <span class="text-[10px] font-bold text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                                    </div>
                                    <h4 class="font-bold text-gray-900 group-hover:text-orange-500 transition-colors truncate">Заказ из ресторана</h4>
                                </div>
                                <div class="flex items-center px-2 py-1 rounded-full border {{ $status['color'] }}">
                                    <span class="text-[10px] font-bold uppercase tracking-wider">{{ $status['label'] }}</span>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center gap-1.5 overflow-hidden">
                                @foreach(array_slice((array)($order->items ?? []), 0, 4) as $item)
                                    <div class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden flex-shrink-0">
                                        <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=100' }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                                @if(count($order->items) > 4)
                                    <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-[10px] font-bold text-gray-400 border border-gray-100">
                                        +{{ count($order->items) - 4 }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-50">
                                <span class="font-bold text-gray-900">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                                <a href="{{ route('order.track', $order->id) }}" class="flex items-center gap-1 text-orange-500 font-bold text-sm hover:underline">
                                    Подробнее
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        {{-- Empty State --}}
                        <div class="flex flex-col items-center justify-center py-20 animate-slide-up">
                            <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mb-6 shadow-inner text-orange-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Заказов пока нет</h2>
                            <p class="text-gray-400 text-center mt-2 mb-8 max-w-[240px] text-sm leading-relaxed">Когда вы сделаете свой первый заказ, он появится в этом списке</p>
                            <a href="{{ route('home') }}" class="px-8 py-4 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-lg shadow-orange-200 transition-all active:scale-95">
                                К покупкам
                            </a>
                        </div>
                    @endforelse
                </div>

            </main>

            <x-bottom-nav active="orders" />

        </div>
    </div>
</x-layouts.app>
