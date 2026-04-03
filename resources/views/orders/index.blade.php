@php
    $statusConfig = [
        'pending' => ['label' => 'Ожидает', 'color' => 'bg-amber-100 text-amber-600 border-amber-200'],
        'accepted' => ['label' => 'Принят', 'color' => 'bg-blue-100 text-blue-600 border-blue-200'],
        'preparing' => ['label' => 'Готовится', 'color' => 'bg-indigo-100 text-indigo-600 border-indigo-200'],
        'ready' => ['label' => 'Готов', 'color' => 'bg-cyan-100 text-cyan-600 border-cyan-200'],
        'on_the_way' => ['label' => 'В пути', 'color' => 'bg-orange-100 text-orange-600 border-orange-200'],
        'delivered' => ['label' => 'Доставлен', 'color' => 'bg-green-100 text-green-600 border-green-200'],
        'cancelled' => ['label' => 'Отменен', 'color' => 'bg-red-100 text-red-600 border-red-200'],
    ];
@endphp

<x-layouts.app>
    <x-slot:title>Мои заказы - RestoPWA</x-slot:title>

    <div class="max-w-lg mx-auto bg-gray-50 min-h-screen shadow-xl relative pb-24">
        
        {{-- Header --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100/50 transition-all duration-300">
            <div class="flex items-center gap-3 px-4 h-14">
                <a href="{{ route('profile.edit') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="flex-1 text-lg font-bold text-gray-900 truncate">Мои заказы</h1>
            </div>
        </header>

        <main class="px-4 py-6">
            
            @forelse($orders as $order)
                @php $status = $statusConfig[$order->status] ?? $statusConfig['pending']; @endphp
                <div class="mb-4 bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden animate-slide-up hover:border-orange-100 transition-all group">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">№{{ substr($order->id, 0, 8) }}</span>
                                    <span class="text-[10px] font-bold text-gray-300">•</span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <h3 class="text-lg font-black text-gray-900 group-hover:text-orange-500 transition-colors leading-tight">Заказ из ресторана</h3>
                            </div>
                            <div class="flex items-center px-3 py-1 rounded-full border {{ $status['color'] }}">
                                <span class="text-[10px] font-black uppercase tracking-widest">{{ $status['label'] }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 overflow-x-auto pb-4 scrollbar-hide">
                            @foreach($order->items as $item)
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden relative group/img">
                                    <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=100' }}" 
                                         class="w-full h-full object-cover grayscale-[0.2] transition-all group-hover/img:grayscale-0">
                                    <div class="absolute inset-x-0 bottom-0 bg-black/40 text-[8px] text-white font-bold text-center py-0.5">{{ $item['quantity'] }}x</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-end justify-between mt-2 pt-4 border-t border-gray-50">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Сумма заказа</span>
                                <span class="text-xl font-black text-gray-900">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                            </div>
                            
                            <a href="{{ route('order.track', $order->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 text-white text-xs font-bold rounded-2xl hover:bg-orange-600 transition-all shadow-lg active:scale-95">
                                Отследить
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center py-20 animate-slide-up">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-gray-900">У вас пока нет заказов</h2>
                    <p class="text-gray-400 text-center mt-2 mb-8 max-w-[260px] leading-relaxed text-sm">Самое время порадовать себя чем-нибудь вкусным из наших ресторанов!</p>
                    <a href="{{ route('home') }}" class="px-8 py-4 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/30 transition-all touch-feedback active:scale-95">
                        Начать покупки
                    </a>
                </div>
            @endforelse

        </main>

        <x-bottom-nav active="orders" />

    </div>
</x-layouts.app>
