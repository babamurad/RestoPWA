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
        <h3 class="text-lg font-semibold mb-2">Зоны доставки</h3>
        <p class="text-gray-600 text-sm mb-4">Нарисуйте область доставки на карте. Координаты будут сохранены автоматически.</p>
        
        <div id="delivery-map-wrapper" style="height: 450px; width: 100%; margin-bottom: 1rem; position: relative;" class="rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div id="delivery-map-loading" style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f9fafb; color: #9ca3af; font-size: 0.875rem;">
                Загрузка карты...
            </div>
            <div id="delivery-map" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0;"></div>
        </div>
        
        <input type="hidden" name="delivery_zones" id="delivery_zones" value="{{ is_array($restaurant->delivery_zones) ? json_encode($restaurant->delivery_zones) : $restaurant->delivery_zones }}">
        
        <div class="flex flex-wrap gap-2">
            <button type="button" id="start-draw" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                Нарисовать зону
            </button>
            <button type="button" id="edit-points" class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-xs font-bold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">
                Редактировать точки
            </button>
            <button type="button" id="clear-map" class="inline-flex items-center px-4 py-2 bg-white text-red-600 text-xs font-bold rounded-xl border border-red-100 hover:bg-red-50 transition-all ml-auto">
                Очистить
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
<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={{ config('services.yandex_maps.js_key') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    ymaps.ready(function() {
        const mapContainer = document.getElementById('delivery-map');
        const loadingDiv = document.getElementById('delivery-map-loading');
        
        // Скрываем заглушку "Загрузка..."
        if (loadingDiv) loadingDiv.style.display = 'none';
        
        const map = new ymaps.Map(mapContainer, {
            center: [39.0886, 63.5593], // Туркменабат
            zoom: 12,
            controls: ['zoomControl', 'fullscreenControl']
        });

        const polygon = new ymaps.Polygon([[]], {
            hintContent: 'Зона доставки'
        }, {
            fillColor: '#FF6B3555',
            strokeColor: '#FF6B35',
            strokeWidth: 3,
            editorDrawingCursor: 'crosshair'
        });

        map.geoObjects.add(polygon);

        const zonesInput = document.getElementById('delivery_zones');
        const existingZones = zonesInput.value;

        if (existingZones) {
            try {
                const data = JSON.parse(existingZones);
                if (data && data.type === 'MultiPolygon' && data.coordinates[0]) {
                    const coords = data.coordinates[0][0].map(p => [p[1], p[0]]);
                    polygon.geometry.setCoordinates([coords]);
                } else if (data && data.type === 'Polygon') {
                    const coords = data.coordinates[0].map(p => [p[1], p[0]]);
                    polygon.geometry.setCoordinates([coords]);
                }
                
                const loadedCoords = polygon.geometry.getCoordinates();
                if (loadedCoords && loadedCoords[0] && loadedCoords[0].length > 0) {
                    map.setBounds(polygon.geometry.getBounds(), { checkZoomRange: true });
                }
            } catch (e) {
                console.error('Failed to parse existing delivery zones:', e);
            }
        }

        polygon.events.add('geometrychange', updateState);

        function updateState() {
            const coords = polygon.geometry.getCoordinates()[0];
            if (!coords || coords.length < 3) {
                zonesInput.value = '';
                return;
            }

            const geojsonCoords = coords.map(p => [p[1], p[0]]);
            
            if (geojsonCoords.length > 0) {
                const first = geojsonCoords[0];
                const last = geojsonCoords[geojsonCoords.length - 1];
                if (first[0] !== last[0] || first[1] !== last[1]) {
                    geojsonCoords.push([first[0], first[1]]);
                }
            }

            zonesInput.value = JSON.stringify({
                type: 'MultiPolygon',
                coordinates: [[geojsonCoords]]
            });
        }

        document.getElementById('start-draw').addEventListener('click', function() {
            polygon.editor.startDrawing();
        });

        document.getElementById('edit-points').addEventListener('click', function() {
            polygon.editor.startEditing();
        });

        document.getElementById('clear-map').addEventListener('click', function() {
            if (confirm('Очистить зону доставки?')) {
                polygon.geometry.setCoordinates([[]]);
                zonesInput.value = '';
            }
        });
    });

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
