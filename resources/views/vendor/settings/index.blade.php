@extends('vendor.layout.app')

@section('title', 'Настройки')

@section('content')
<h2 class="text-2xl font-bold mb-6">Настройки</h2>

<form method="POST" action="{{ route('vendor.settings.update') }}" class="space-y-6">
    @csrf
    
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Часы работы</h3>
        
        <div id="working-hours" class="space-y-4">
            @php
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $dayLabels = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
                $hours = $restaurant->settings['working_hours'] ?? [];
            @endphp
            
            @foreach($days as $index => $day)
                <div class="flex items-center gap-4">
                    <span class="w-32 font-medium">{{ $dayLabels[$index] }}</span>
                    <input type="time" name="working_hours[{{ $index }}][start]" 
                           value="{{ $hours[$index]['start'] ?? '09:00' }}"
                           class="border rounded px-3 py-2 w-32"
                           {{ ($hours[$index]['is_day_off'] ?? false) ? 'disabled' : '' }}>
                    <span>до</span>
                    <input type="time" name="working_hours[{{ $index }}][end]" 
                           value="{{ $hours[$index]['end'] ?? '22:00' }}"
                           class="border rounded px-3 py-2 w-32"
                           {{ ($hours[$index]['is_day_off'] ?? false) ? 'disabled' : '' }}>
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="working_hours[{{ $index }}][is_day_off]" 
                               value="1"
                               {{ ($hours[$index]['is_day_off'] ?? false) ? 'checked' : '' }}
                               class="mr-2 day-off-checkbox"
                               data-index="{{ $index }}">
                        Выходной
                    </label>
                    <input type="hidden" name="working_hours[{{ $index }}][day]" value="{{ $day }}">
                </div>
            @endforeach
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Зоны доставки</h3>
        <p class="text-gray-600 mb-4">Нарисуйте полигон на карте для обозначения зоны доставки</p>
        
        <div id="delivery-map" style="height: 400px; width: 100%; margin-bottom: 1rem;"></div>
        
        <input type="hidden" name="delivery_zones" id="delivery_zones" value="{{ $restaurant->delivery_zones }}">
        
        <div class="flex gap-2">
            <button type="button" id="clear-map" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded">
                Очистить карту
            </button>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Настройки комиссии</h3>
        
        <div class="flex items-center gap-2 text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Текущая комиссия платформы: <strong>15%</strong> от каждого заказа</span>
        </div>
    </div>
    
    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">Сохранить настройки</button>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('delivery-map').setView([55.7558, 37.6173], 12);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    let drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);
    
    const drawControl = new L.Control.Draw({
        draw: {
            polygon: {
                allowIntersection: false,
                showArea: true
            },
            polyline: false,
            circle: false,
            rectangle: true,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: drawnItems
        }
    });
    map.addControl(drawControl);
    
    map.on(L.Draw.Event.CREATED, function(e) {
        drawnItems.clearLayers();
        drawnItems.addLayer(e.layer);
        updateDeliveryZones();
    });
    
    map.on(L.Draw.Event.EDITED, function(e) {
        updateDeliveryZones();
    });
    
    map.on(L.Draw.Event.DELETED, function(e) {
        updateDeliveryZones();
    });
    
    function updateDeliveryZones() {
        const geoJson = drawnItems.toGeoJSON();
        document.getElementById('delivery_zones').value = JSON.stringify(geoJson);
    }
    
    document.getElementById('clear-map').addEventListener('click', function() {
        drawnItems.clearLayers();
        document.getElementById('delivery_zones').value = '';
    });
    
    const existingZones = document.getElementById('delivery_zones').value;
    if (existingZones) {
        try {
            const geoJson = JSON.parse(existingZones);
            const layer = L.geoJSON(geoJson).getLayers()[0];
            if (layer) {
                drawnItems.addLayer(layer);
                map.fitBounds(layer.getBounds());
            }
        } catch (e) {
            console.error('Error loading delivery zones:', e);
        }
    }
    
    document.querySelectorAll('.day-off-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const index = this.dataset.index;
            const inputs = this.closest('div').querySelectorAll('input[type="time"]');
            inputs.forEach(function(input) {
                input.disabled = this.checked;
            }.bind(this));
        });
    });
});
</script>
@endpush
@endsection
