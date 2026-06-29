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
            mapContainer.innerHTML = ''; // Очищаем надпись Загрузка карты
            this.map = new ymaps.Map(mapContainer, {
                center: [39.0886, 63.5593], // Туркменабат
                zoom: 12,
                controls: ['zoomControl', 'fullscreenControl']
            });

            this.polygon = new ymaps.Polygon([[]], {
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
            this.polygon.geometry.setCoordinates([[]]);
            this.state = null;
        }
    }
}" class="delivery-zone-map-wrapper">
    <div x-ref="map" style="height: 450px; width: 100%; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.3); overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" wire:ignore>
        <div style="display: flex; align-items: center; justify-content: center; height: 100%; background-color: #f9fafb; color: #9ca3af; font-size: 0.875rem;">
            Загрузка карты...
        </div>
    </div>
    
    <div style="margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <x-filament::button 
            type="button" 
            color="warning" 
            size="sm" 
            icon="heroicon-m-pencil-square" 
            x-on:click="startDrawing()"
        >
            Нарисовать зону
        </x-filament::button>
        
        <x-filament::button 
            type="button" 
            color="gray" 
            size="sm" 
            icon="heroicon-m-pencil" 
            x-on:click="editPoints()"
        >
            Редактировать точки
        </x-filament::button>

        <x-filament::button 
            type="button" 
            color="danger" 
            size="sm" 
            icon="heroicon-m-trash" 
            x-on:click="clearMap()"
        >
            Очистить
        </x-filament::button>
    </div>
    
    <div style="margin-top: 0.5rem; font-size: 0.75rem; font-weight: 500; color: #9ca3af; background-color: #f9fafb; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #f3f4f6;">
        <p>💡 <b>Совет:</b> Нажмите «Нарисовать зону», кликайте на карте для создания углов. Завершите двойным кликом на последней точке. <br>
        Вы можете перетаскивать существующие точки в режиме «Редактировать».</p>
    </div>
</div>
