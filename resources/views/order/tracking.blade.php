<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отслеживание заказа</title>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div x-data="orderTracker('{{ $orderId }}')" x-init="init()" class="min-h-screen">
    <div x-data="orderTracker('{{ $orderId }}')" x-init="init()" class="min-h-screen bg-gray-50">
        {{-- Header (Mobile & Desktop) --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100 transition-all">
            <div class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl px-4 h-14 md:h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('orders.index') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    <h1 class="text-lg md:text-xl font-bold text-gray-900 truncate mt-0.5">Заказ #{{ substr($orderId, 0, 8) }}</h1>
                </div>
                <span 
                    class="px-3 py-1 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider"
                    :class="statusClasses[currentStatus]"
                    x-text="currentStatus"
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
                         class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 h-64 md:h-96 relative">
                        <div id="map" class="w-full h-full"></div>
                        <div class="absolute top-4 left-4 z-[400] bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-lg border border-gray-100 shadow-sm">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Курьер в пути</p>
                            <p class="text-xs font-bold text-gray-900">Будет через 12-15 мин</p>
                        </div>
                    </div>

                    {{-- Status Timeline --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Статус заказа</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach(['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'completed'] as $index => $status)
                                <div class="flex items-center gap-4 group">
                                    <div 
                                        class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm transition-all duration-500"
                                        :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus) ? 'bg-orange-500 text-white shadow-lg shadow-orange-200' : 'bg-gray-50 text-gray-300 border border-gray-100'"
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
                                           :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus) ? 'text-gray-900' : 'text-gray-300'"
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
                                        <p class="text-[10px] text-gray-400 font-medium truncate" x-show="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus)">
                                            {{ now()->subMinutes(60 - $index * 10)->format('H:i') }}
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
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <h3 class="font-bold text-gray-900">Доставка</h3>
                        </div>
                        <p class="text-sm font-medium text-gray-600 leading-relaxed">{{ $order->address['address'] ?? 'Адрес не указан' }}</p>
                    </div>

                    {{-- Order Content --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 overflow-hidden">
                        <h3 class="font-bold text-gray-900 mb-4">Состав заказа</h3>
                        <div class="space-y-4 max-h-64 overflow-y-auto scrollbar-hide pr-2">
                            @foreach($order->items as $item)
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex-shrink-0 overflow-hidden">
                                        <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=100' }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $item['name'] ?? 'Товар' }}</p>
                                        <p class="text-[10px] text-gray-400 font-medium">Кол-во: {{ $item['quantity'] ?? 1 }}</p>
                                    </div>
                                    <p class="text-xs font-bold text-gray-900">{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, '.', ' ') }} ₽</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6 pt-6 border-t border-gray-50 flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-400 uppercase tracking-widest">Итого</span>
                            <span class="text-xl font-bold text-orange-500">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                        </div>
                    </div>

                    {{-- Help Button --}}
                    <button class="w-full py-4 px-6 bg-gray-900 text-white font-bold rounded-2xl hover:bg-gray-800 transition-all shadow-lg flex items-center justify-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-14.7 8.38 8.38 0 0 1 3.8.9L21 3.5Z"/></svg>
                        Написать в поддержку
                    </button>
                </div>
            </div>
        </main>
    </div>
    </div>

    <script>
        function orderTracker(orderId) {
            return {
                orderId: orderId,
                currentStatus: '{{ $order->status }}',
                statusOrder: ['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'completed'],
                statusClasses: {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'confirmed': 'bg-blue-100 text-blue-800',
                    'preparing': 'bg-orange-100 text-orange-800',
                    'ready': 'bg-purple-100 text-purple-800',
                    'delivering': 'bg-indigo-100 text-indigo-800',
                    'completed': 'bg-green-100 text-green-800',
                    'cancelled': 'bg-red-100 text-red-800',
                },
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
                },

                subscribeToOrderChannel() {
                    if (typeof Echo !== 'undefined') {
                        Echo.private('orders.' + this.orderId)
                            .listen('order.status.updated', (event) => {
                                this.currentStatus = event.status;
                                this.showNotification('Статус заказа изменен', this.getStatusText(event.status));
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

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap'
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
                        'cancelled': 'Отменен'
                    };
                    return texts[status] || status;
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
        .courier-marker {
            background: transparent;
            border: none;
        }
    </style>
</body>
</html>
