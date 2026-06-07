<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Отслеживание заказа</title>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased">
    <div x-data="orderTracker('{{ $orderId }}', '{{ $signedApiUrl }}')" x-init="init()" class="min-h-screen bg-gray-50 dark:bg-slate-950">
        {{-- Header (Mobile & Desktop) --}}
        <header class="sticky top-0 z-40 bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800/40 transition-all">
            <div class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl px-4 h-14 md:h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('orders.index') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600 dark:text-slate-300"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    <h1 class="text-lg md:text-xl font-bold text-slate-900 dark:text-slate-100 truncate mt-0.5">Заказ #{{ substr($orderId, 0, 8) }}</h1>
                </div>
                <span 
                    class="px-3 py-1 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider"
                    :class="statusClasses[currentStatus]"
                    x-text="getStatusText(currentStatus)"
                ></span>
            </div>
        </header>

        <main class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl px-4 py-6 md:py-10">
            <div class="md:grid md:grid-cols-3 md:gap-8">
                {{-- Left: Map & Status --}}
                <div class="md:col-span-2 space-y-6">
                    {{-- Map --}}
                    <div x-show="currentStatus === 'delivering' || currentStatus === 'ready'" 
                         x-transition
                         x-cloak
                         class="bg-white/60 dark:bg-slate-900/60 rounded-3xl overflow-hidden shadow-xl shadow-black/5 dark:shadow-black/30 border border-slate-200 dark:border-slate-800/40 h-64 md:h-96 relative">
                        <div id="map" class="w-full h-full"></div>
                        <div class="absolute top-4 left-4 z-[400] bg-white/80 dark:bg-slate-950/80 backdrop-blur-md px-3 py-1.5 rounded-2xl border border-slate-200 dark:border-slate-800/40 shadow-xl shadow-black/5 dark:shadow-black/30">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-1">Курьер в пути</p>
                            <p class="text-xs font-bold text-slate-900 dark:text-slate-100">Будет через 12-15 мин</p>
                        </div>
                    </div>

                    {{-- Status Timeline --}}
                    @php
                        $initialHistory = $order->statusHistory->map(fn ($h) => [
                            'status' => $h->to_status === 'delivered' ? 'completed' : $h->to_status,
                            'time' => $h->created_at->timezone(config('app.timezone', 'UTC'))->format('H:i')
                        ])->toArray();

                        $hasPending = collect($initialHistory)->contains('status', 'pending');
                        if (!$hasPending) {
                            array_unshift($initialHistory, [
                                'status' => 'pending',
                                'time' => $order->created_at->timezone(config('app.timezone', 'UTC'))->format('H:i')
                            ]);
                        }
                    @endphp
                    <div class="bg-white/60 dark:bg-slate-900/60 rounded-3xl p-6 shadow-xl shadow-black/5 dark:shadow-black/30 border border-slate-200 dark:border-slate-800/40">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-6">Статус заказа</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach(['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'completed'] as $index => $status)
                                @php
                                    $historyItem = collect($initialHistory)->firstWhere('status', $status);
                                    $statusTime = $historyItem ? $historyItem['time'] : '';
                                @endphp
                                <div class="flex items-center gap-4 group">
                                    <div 
                                        class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm transition-all duration-500"
                                        :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus) ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'bg-slate-50 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700/30'"
                                    >
                                        <template x-if="statusOrder.indexOf('{{ $status }}') < statusOrder.indexOf(currentStatus)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="animate-scale-in"><polyline points="20 6 9 17 4 12"/></svg>
                                        </template>
                                        <template x-if="statusOrder.indexOf('{{ $status }}') >= statusOrder.indexOf(currentStatus)">
                                            <span class="font-bold">{{ $index + 1 }}</span>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold transition-colors"
                                           :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus) ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'"
                                        >
                                            @switch($status)
                                                @case('pending') Принят @break
                                                @case('confirmed') Подтвержден @break
                                                @case('preparing') Готовится @break
                                                @case('ready') Готов @break
                                                @case('delivering') В пути @break
                                                @case('completed') Доставлен @break
                                            @endswitch
                                        </p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate" 
                                           x-show="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus)"
                                           x-text="getStatusTime('{{ $status }}') || '{{ $statusTime }}'"
                                        >
                                            {{ $statusTime }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right Column: Delivery Details & Items --}}
                <div class="mt-6 md:mt-0 space-y-6">
                    {{-- Destination --}}
                    <div class="bg-white/60 dark:bg-slate-900/60 rounded-3xl p-6 shadow-xl shadow-black/5 dark:shadow-black/30 border border-slate-200 dark:border-slate-800/40">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <h3 class="font-bold text-slate-900 dark:text-slate-100">Доставка</h3>
                        </div>
                        @php
                            $addr = $order->address ?? [];
                        @endphp
                        @if(!empty($addr['address']))
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 leading-relaxed">{{ $addr['address'] }}</p>
                        @endif
                        @if(!empty($addr['manual_address']))
                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 leading-relaxed">{{ $addr['manual_address'] }}</p>
                        @endif
                        @if(!empty($addr['landmark']) || !empty($addr['entrance']) || !empty($addr['floor']) || !empty($addr['apartment']))
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @if(!empty($addr['landmark']))<span class="inline-flex items-center px-2 py-0.5 bg-orange-500/10 rounded-lg text-xs font-medium text-orange-400 border border-orange-500/20">📍 {{ $addr['landmark'] }}</span>@endif
                                @if(!empty($addr['entrance']))<span class="inline-flex items-center px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300">п. {{ $addr['entrance'] }}</span>@endif
                                @if(!empty($addr['floor']))<span class="inline-flex items-center px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300">эт. {{ $addr['floor'] }}</span>@endif
                                @if(!empty($addr['apartment']))<span class="inline-flex items-center px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300">кв. {{ $addr['apartment'] }}</span>@endif
                            </div>
                        @endif
                        @if(!empty($addr['courier_comment']))
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic mt-2">«{{ $addr['courier_comment'] }}»</p>
                        @endif
                        @if(!empty($addr['lat']) && !empty($addr['lon']))
                            <div class="flex gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/40">
                                <a href="https://www.google.com/maps?q={{ $addr['lat'] }},{{ $addr['lon'] }}" target="_blank" rel="noopener noreferrer" class="flex-1 py-2 bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 border border-slate-200 dark:border-slate-700/30 transition-all text-center flex items-center justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 1-6 6"/><path d="M18 8a6 6 0 0 0-6-6"/><path d="M18 8H6"/><path d="M6 8a6 6 0 0 0 6 6"/><path d="M6 8a6 6 0 0 1 6-6"/><path d="M12 2v12"/></svg>
                                    Открыть в карте
                                </a>
                                <button onclick="navigator.clipboard.writeText('{{ $addr['lat'] }}, {{ $addr['lon'] }}')" class="flex-1 py-2 bg-slate-50 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 border border-slate-200 dark:border-slate-700/30 transition-all text-center flex items-center justify-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    Координаты
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Order Content --}}
                    <div class="bg-white/60 dark:bg-slate-900/60 rounded-3xl p-6 shadow-xl shadow-black/5 dark:shadow-black/30 border border-slate-200 dark:border-slate-800/40 overflow-hidden">
                        <h3 class="font-bold text-slate-900 dark:text-slate-100 mb-4">Состав заказа</h3>
                        <div class="space-y-4 max-h-64 overflow-y-auto scrollbar-hide pr-2">
                            @foreach($order->items as $item)
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800/50 flex-shrink-0 overflow-hidden">
                                        <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=100' }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate leading-tight">{{ $item['name'] ?? $item['product_name'] ?? 'Товар' }}</p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Кол-во: {{ $item['quantity'] ?? 1 }}</p>
                                    </div>
                                    @php
                                        $itemPrice = isset($item['total_price']) 
                                            ? $item['total_price'] / 100 
                                            : (isset($item['unit_price']) 
                                                ? ($item['unit_price'] / 100) * ($item['quantity'] ?? 1) 
                                                : (isset($item['price']) 
                                                    ? $item['price'] * ($item['quantity'] ?? 1) 
                                                    : 0));
                                    @endphp
                                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ number_format($itemPrice, 0, '.', ' ') }} ₽</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800/40 flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-500 uppercase tracking-widest">Итого</span>
                            <span class="text-xl font-bold text-orange-500">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                        </div>
                    </div>

                    {{-- Help Button --}}
                    <button class="w-full py-4 px-6 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-bold rounded-2xl border border-slate-300 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-all shadow-lg shadow-black/5 dark:shadow-black/30 flex items-center justify-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600 dark:text-slate-300"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-14.7 8.38 8.38 0 0 1 3.8.9L21 3.5Z"/></svg>
                        Написать в поддержку
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        function orderTracker(orderId, signedApiUrl) {
            return {
                orderId: orderId,
                signedApiUrl: signedApiUrl,
                currentStatus: '{{ $order->status }}' === 'delivered' ? 'completed' : '{{ $order->status }}',
                statusOrder: ['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'completed'],
                statusClasses: {
                    'pending': 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20',
                    'confirmed': 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
                    'preparing': 'bg-orange-500/10 text-orange-400 border border-orange-500/20',
                    'ready': 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
                    'delivering': 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20',
                    'completed': 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                    'delivered': 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                    'cancelled': 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                },
                statusHistory: @json($initialHistory),
                map: null,
                courierMarker: null,
                restaurantMarker: null,
                destinationMarker: null,

                init() {
                    this.subscribeToOrderChannel();
                    
                    if (this.currentStatus === 'delivering') {
                        this.initMap();
                        this.subscribeToTrackingChannel();
                    }

                    // Polling fallback for guests or connection issues
                    if (this.signedApiUrl) {
                        setInterval(() => this.pollStatus(), 5000);
                    }
                },

                async pollStatus() {
                    try {
                        const response = await fetch(this.signedApiUrl);
                        const data = await response.json();
                        if (data.status) {
                            const normalizedStatus = data.status === 'delivered' ? 'completed' : data.status;
                            if (normalizedStatus !== this.currentStatus) {
                                this.currentStatus = normalizedStatus;
                            }
                        }
                        if (data.status_history) {
                            this.statusHistory = data.status_history.map(h => ({
                                status: h.status === 'delivered' ? 'completed' : h.status,
                                time: this.formatTime(h.timestamp)
                            }));
                        }
                    } catch (e) {
                        console.error('Polling failed:', e);
                    }
                },

                subscribeToOrderChannel() {
                    if (typeof Echo !== 'undefined') {
                        Echo.private('orders.' + this.orderId)
                            .listen('order.status.updated', (event) => {
                                const normalizedStatus = event.status === 'delivered' ? 'completed' : event.status;
                                this.currentStatus = normalizedStatus;
                                this.showNotification('Статус заказа изменен', this.getStatusText(normalizedStatus));
                                this.pollStatus();
                            });
                    }
                },

                subscribeToTrackingChannel() {
                    if (typeof Echo !== 'undefined') {
                        Echo.channel('tracking.' + this.orderId)
                            .listen('courier.location.updated', (event) => {
                                this.updateCourierLocation(event.lat, event.lon, event.heading);
                            });
                    }
                },

                initMap() {
                    this.$nextTick(() => {
                        const address = @json($order->address ?? []);
                        const destLat = address.lat || 55.7558;
                        const destLon = address.lon || 37.6173;

                        this.map = L.map('map').setView([destLat, destLon], 14);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                            attribution: '© OpenStreetMap contributors © CARTO'
                        }).addTo(this.map);

                        this.destinationMarker = L.marker([destLat, destLon])
                            .addTo(this.map)
                            .bindPopup('Адрес доставки');

                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition((position) => {
                                const restLat = position.coords.latitude;
                                const restLon = position.coords.longitude;

                                this.restaurantMarker = L.marker([restLat, restLon])
                                    .addTo(this.map)
                                    .bindPopup('Ресторан');
                            });
                        }
                    });
                },

                updateCourierLocation(lat, lon, heading) {
                    if (!this.map) return;

                    const icon = L.divIcon({
                        className: 'courier-marker',
                        html: `<div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center shadow-lg" style="transform: rotate(${heading || 0}deg)">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2L2 18h16L10 2z"/>
                            </svg>
                        </div>`,
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });

                    if (this.courierMarker) {
                        this.courierMarker.setLatLng([lat, lon]);
                        this.courierMarker.setIcon(icon);
                    } else {
                        this.courierMarker = L.marker([lat, lon], { icon: icon })
                            .addTo(this.map)
                            .bindPopup('Курьер');
                    }

                    this.map.setView([lat, lon], 14);
                },

                getStatusText(status) {
                    const texts = {
                        'pending': 'Ожидает подтверждения',
                        'confirmed': 'Подтвержден',
                        'preparing': 'Готовится',
                        'ready': 'Готов к выдаче',
                        'delivering': 'В пути',
                        'completed': 'Доставлен',
                        'delivered': 'Доставлен',
                        'cancelled': 'Отменен'
                    };
                    return texts[status] || status;
                },

                formatTime(isoString) {
                    if (!isoString) return '';
                    const date = new Date(isoString);
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${hours}:${minutes}`;
                },

                getStatusTime(status) {
                    const historyItem = this.statusHistory.find(h => h.status === status);
                    return historyItem ? historyItem.time : '';
                },

                showNotification(title, body) {
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification(title, { body });
                    }
                }
            };
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .courier-marker {
            background: transparent;
            border: none;
        }
        /* Premium dark theme for Leaflet controls and popups */
        .dark .leaflet-bar a {
            background-color: #0f172a !important; /* bg-slate-900 */
            color: #cbd5e1 !important; /* text-slate-300 */
            border-bottom: 1px solid #1e293b !important; /* border-slate-800 */
        }
        .leaflet-bar a:hover {
            background-color: #1e293b !important;
        }
        .dark .leaflet-bar {
            border: 1px solid #1e293b !important;
        }
        .dark .leaflet-popup-content-wrapper, .dark .leaflet-popup-tip {
            background-color: #0f172a !important; /* bg-slate-900 */
            color: #cbd5e1 !important; /* text-slate-300 */
            border: 1px solid #1e293b !important; /* border-slate-800 */
            border-radius: 12px !important;
        }
    </style>
</body>
</html>
