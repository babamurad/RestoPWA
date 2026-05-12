<div x-data="{
    state: @entangle($getStatePath()),
    map: null,
    polygon: null,
    isDrawing: false,

    init() {
        if (typeof ymaps === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={{ config('services.yandex_maps.js_key') }}';
            script.onload = () => this.initMap();
            document.head.appendChild(script);
        } else {
            this.initMap();
        }
    },

    initMap() {
        ymaps.ready(() => {
            const mapContainer = this.$refs.map;
            this.map = new ymaps.Map(mapContainer, {
                center: [39.0886, 63.5593], // Туркменабат
                zoom: 12,
                controls: ['zoomControl', 'fullscreenControl']
            });

            this.polygon = new ymaps.Polygon([], {
                hintContent: 'Зона доставки'
            }, {
                fillColor: '#FF6B3555',
                strokeColor: '#FF6B35',
                strokeWidth: 3,
                editorDrawingCursor: 'crosshair'
            });

            this.map.geoObjects.add(this.polygon);

            if (this.state) {
                this.loadFromState();
            }

            this.polygon.events.add('geometrychange', () => {
                this.updateState();
            });
            
            // Если полигон уже есть, центрируем карту по нему
            if (this.polygon.geometry.getCoordinates().length > 0) {
                this.map.setBounds(this.polygon.geometry.getBounds());
            }
        });
    },

    loadFromState() {
        try {
            let data = typeof this.state === 'string' ? JSON.parse(this.state) : this.state;
            if (data && data.type === 'MultiPolygon' && data.coordinates[0]) {
                // GeoJSON [lon, lat] -> Yandex [lat, lon]
                const coords = data.coordinates[0][0].map(p => [p[1], p[0]]);
                this.polygon.geometry.setCoordinates([coords]);
            } else if (data && data.type === 'Polygon') {
                const coords = data.coordinates[0].map(p => [p[1], p[0]]);
                this.polygon.geometry.setCoordinates([coords]);
            }
        } catch (e) {
            console.error('Failed to parse delivery zone JSON', e);
        }
    },

    updateState() {
        const coords = this.polygon.geometry.getCoordinates()[0];
        if (!coords || coords.length < 3) {
            this.state = null;
            return;
        }

        // Yandex [lat, lon] -> GeoJSON [lon, lat]
        const geojsonCoords = coords.map(p => [p[1], p[0]]);
        
        // Замыкаем полигон для GeoJSON если нужно
        if (geojsonCoords.length > 0) {
            const first = geojsonCoords[0];
            const last = geojsonCoords[geojsonCoords.length - 1];
            if (first[0] !== last[0] || first[1] !== last[1]) {
                geojsonCoords.push([first[0], first[1]]);
            }
        }

        this.state = JSON.stringify({
            type: 'MultiPolygon',
            coordinates: [[geojsonCoords]]
        });
    },

    startDrawing() {
        if (!this.polygon) return;
        this.isDrawing = true;
        this.polygon.editor.startDrawing();
    },

    stopDrawing() {
        if (!this.polygon) return;
        this.isDrawing = false;
        this.polygon.editor.stopDrawing();
    },

    editPoints() {
        if (!this.polygon) return;
        this.polygon.editor.startEditing();
    },

    clearMap() {
        if (!this.polygon) return;
        if (confirm('Очистить зону доставки?')) {
            this.polygon.geometry.setCoordinates([]);
            this.state = null;
        }
    }
}" class="delivery-zone-map-wrapper">
    <div x-ref="map" style="height: 450px; width: 100%;" class="rounded-2xl border border-gray-200 shadow-sm overflow-hidden" wire:ignore>
        <div class="flex items-center justify-center h-full bg-gray-50 text-gray-400 text-sm">
            Загрузка карты...
        </div>
    </div>
    
    <div class="mt-3 flex flex-wrap gap-2">
        <button type="button" @click="startDrawing()" 
                class="inline-flex items-center px-4 py-2 bg-orange-500 text-white text-xs font-bold rounded-xl hover:bg-orange-600 transition-all shadow-sm shadow-orange-100">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            Нарисовать зону
        </button>
        
        <button type="button" @click="editPoints()" 
                class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-xs font-bold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all">
            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Редактировать точки
        </button>

        <button type="button" @click="clearMap()" 
                class="inline-flex items-center px-4 py-2 bg-white text-red-600 text-xs font-bold rounded-xl border border-red-100 hover:bg-red-50 transition-all ml-auto">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Очистить
        </button>
    </div>
    
    <div class="mt-2 text-[10px] text-gray-400 font-medium bg-gray-50 p-2 rounded-lg border border-gray-100">
        <p>💡 <b>Совет:</b> Нажмите «Нарисовать зону», кликайте на карте для создания углов. Завершите двойным кликом на последней точке. <br>
        Вы можете перетаскивать существующие точки в режиме «Редактировать».</p>
    </div>
</div>
