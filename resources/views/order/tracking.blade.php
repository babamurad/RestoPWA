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
        <div class="max-w-lg mx-auto bg-white min-h-screen shadow-lg">
            <div class="sticky top-0 bg-white border-b px-4 py-3 z-10">
                <div class="flex items-center justify-between">
                    <h1 class="text-lg font-semibold">Заказ #{{ $orderId }}</h1>
                    <span 
                        class="px-3 py-1 rounded-full text-sm font-medium"
                        :class="statusClasses[currentStatus]"
                        x-text="currentStatus"
                    ></span>
                </div>
            </div>

            <div x-show="currentStatus === 'delivering'" class="h-64">
                <div id="map" class="w-full h-full"></div>
            </div>

            <div class="p-4 space-y-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium mb-3">Статус заказа</h3>
                    <div class="space-y-3">
                        @foreach(['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'completed'] as $index => $status)
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm"
                                    :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus) ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500'"
                                >
                                    <span x-show="statusOrder.indexOf('{{ $status }}') < statusOrder.indexOf(currentStatus)">✓</span>
                                    <span x-show="statusOrder.indexOf('{{ $status }}') >= statusOrder.indexOf(currentStatus)">{{ $index + 1 }}</span>
                                </div>
                                <span 
                                    class="text-sm"
                                    :class="statusOrder.indexOf('{{ $status }}') <= statusOrder.indexOf(currentStatus) ? 'text-gray-900 font-medium' : 'text-gray-500'"
                                >
                                    @switch($status)
                                        @case('pending') Ожидает @break
                                        @case('confirmed') Подтвержден @break
                                        @case('preparing') Готовится @break
                                        @case('ready') Готов @break
                                        @case('delivering') В пути @break
                                        @case('completed') Доставлен @break
                                    @endswitch
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium mb-2">Адрес доставки</h3>
                    <p class="text-gray-700">{{ $order->address['address'] ?? 'Не указан' }}</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium mb-2">Состав заказа</h3>
                    <div class="space-y-2">
                        @foreach($order->items as $item)
                            <div class="flex justify-between text-sm">
                                <span>{{ $item['name'] ?? 'Товар' }} x{{ $item['quantity'] ?? 1 }}</span>
                                <span>{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, '.', ' ') }} ₽</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t mt-3 pt-3 flex justify-between font-medium">
                        <span>Итого</span>
                        <span>{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
                    </div>
                </div>
            </div>
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
