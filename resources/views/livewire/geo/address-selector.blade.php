<div    x-data="{
        isLocalModalOpen: @entangle('isAddressModalOpen'),
        searchQuery: @entangle('address'),
        showSuggestions: false,
        debounceTimer: null,
        mapInstance: null,
        markerInstance: null,
        mapInitialized: false,
        isMapLoading: true,
        mapFailed: false,
        isDragging: false,
        initRetryCount: 0,
        showRefinement: @entangle('showRefinement'),

        // Fullscreen map state
        isFullscreen: false,
        fullscreenLat: 0,
        fullscreenLon: 0,
        fullscreenMapInstance: null,
        fullscreenMarkerInstance: null,
        fsMapInitialized: false,
        fsInitRetryCount: 0,

        debugLog(...args) {
            if (@js(config('app.debug'))) {
                console.log(...args);
            }
        },

        init() {
            this.debugLog('[AddressSelector] Component initialized');
            this.$watch('isLocalModalOpen', value => {
                this.debugLog('[AddressSelector] Modal open state changed:', value);
                if (value === true) {
                    this.isMapLoading = true;
                    this.mapFailed = false;
                    this.initRetryCount = 0;
                    setTimeout(() => this.initMap(), 400);
                } else {
                    this.cleanupFullscreenMap();
                    this.isFullscreen = false;
                    this.cleanupMap();
                }
            });

            this.$watch('showRefinement', value => {
                if (value === false && this.mapInitialized && this.mapInstance) {
                    this.debugLog('[AddressSelector] Returning to map');
                    setTimeout(() => {
                        try {
                            if (this.mapInstance) {
                                const lat = parseFloat($wire.lat) || 39.0886;
                                const lon = parseFloat($wire.lon) || 63.5593;
                                this.updateMap(lat, lon);
                            }
                        } catch (e) {
                            console.error('[AddressSelector] Refinement switch update failed:', e);
                        }
                    }, 350);
                }
            });
        },

        cleanupMap() {
            this.debugLog('[AddressSelector] Cleaning up map');
            if (this.mapInstance) {
                try { this.mapInstance.destroy(); } catch(e) {}
            }
            this.mapInstance = null;
            this.markerInstance = null;
            this.mapInitialized = false;
            this.isMapLoading = true;
            this.mapFailed = false;
            this.initRetryCount = 0;
        },

        updateMap(lat, lon) {
            if (!this.mapInitialized || !this.mapInstance || !this.markerInstance) return;
            try {
                const la = parseFloat(lat), lo = parseFloat(lon);
                if (isNaN(la) || isNaN(lo) || la === 0) return;
                this.markerInstance.update({ coordinates: [lo, la] });
                this.mapInstance.setLocation({ center: [lo, la], zoom: 16, duration: 300 });
            } catch (e) {
                console.warn('[AddressSelector] updateMap error:', e);
            }
        },
        
        async initMap() {
            if (typeof ymaps3 === 'undefined') {
                console.error('[AddressSelector] ymaps3 is undefined!');
                this.isMapLoading = false;
                this.mapFailed = true;
                return;
            }

            this.debugLog('[AddressSelector] initMap v3 started...');
            try {
                await ymaps3.ready;
            } catch(e) {
                console.error('[AddressSelector] ymaps3.ready failed:', e);
                this.isMapLoading = false;
                this.mapFailed = true;
                return;
            }

            if (!this.isLocalModalOpen) return;

            const mapEl = this.$refs.yandexMap;
            if (!mapEl || mapEl.offsetWidth === 0) {
                this.initRetryCount++;
                if (this.initRetryCount >= 12) {
                    this.isMapLoading = false;
                    this.mapFailed = true;
                    return;
                }
                if (this.isLocalModalOpen) setTimeout(() => this.initMap(), 400);
                return;
            }

            // Cleanup existing
            if (this.mapInstance) {
                try { this.mapInstance.destroy(); } catch(e) {}
                this.mapInstance = null;
                this.markerInstance = null;
                this.mapInitialized = false;
            }

            const serverLat = parseFloat(@js($lat));
            const serverLon = parseFloat(@js($lon));
            const startLat = (serverLat && !isNaN(serverLat) && serverLat !== 0) ? serverLat : 39.0886;
            const startLon = (serverLon && !isNaN(serverLon) && serverLon !== 0) ? serverLon : 63.5593;

            this.debugLog('[AddressSelector] Creating ymaps3.YMap', { startLat, startLon });
            try {
                const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapControls, YMapZoomControl, YMapDefaultMarker, YMapListener } = ymaps3;

                const map = new YMap(mapEl, {
                    location: { center: [startLon, startLat], zoom: 16 },
                    behaviors: ['drag', 'pinchZoom', 'dblClick'],
                });

                map.addChild(new YMapDefaultSchemeLayer());
                map.addChild(new YMapDefaultFeaturesLayer());

                // Zoom controls
                const controls = new YMapControls({ position: 'right' });
                controls.addChild(new YMapZoomControl());
                map.addChild(controls);

                // Draggable marker
                const marker = new YMapDefaultMarker({
                    coordinates: [startLon, startLat],
                    title: 'Перетащите метку',
                    draggable: true,
                    color: '#FF6B35',
                    onDragStart: () => { this.isDragging = true; },
                    onDragEnd: (coords) => {
                        this.isDragging = false;
                        if (coords && coords.length >= 2) {
                            // ymaps3 coords: [lon, lat]
                            $wire.setLocation(coords[1], coords[0], 'map_pin');
                        }
                    },
                    onDrag: () => {},
                });
                map.addChild(marker);
                this.markerInstance = marker;

                // Click on map
                const listener = new YMapListener({
                    layer: 'any',
                    onClick: (obj, event) => {
                        if (this.isDragging) return;
                        const coords = event.coordinates; // [lon, lat]
                        if (coords) {
                            marker.update({ coordinates: coords });
                            $wire.setLocation(coords[1], coords[0], 'map_pin');
                        }
                    }
                });
                map.addChild(listener);

                this.mapInstance = map;
                this.mapInitialized = true;
                this.isMapLoading = false;
                this.debugLog('[AddressSelector] YMap v3 initialized successfully');

            } catch (e) {
                console.error('[AddressSelector] YMap v3 creation error:', e);
                this.isMapLoading = false;
                this.mapFailed = true;
            }
        },

        search() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                $wire.searchAddress(this.searchQuery);
                this.showSuggestions = true;
            }, 300);
        },
        
        select(index) {
            this.showSuggestions = false;
            $wire.selectAddress(index);
        },

        resetToMap() {
            this.showRefinement = false;
            $wire.backToMap();
        },

        // ── Fullscreen Map ──

        enterFullscreen() {
            this.cleanupMap();
            this.fullscreenLat = parseFloat($wire.lat) || 0;
            this.fullscreenLon = parseFloat($wire.lon) || 0;
            this.isFullscreen = true;
            this.fsInitRetryCount = 0;
            this.fsMapInitialized = false;
            window.dispatchEvent(new CustomEvent('map-fullscreen-open'));
            setTimeout(() => this.initFullscreenMap(), 400);
        },

        exitFullscreen() {
            this.cleanupFullscreenMap();
            this.isFullscreen = false;
            this.isMapLoading = true;
            this.mapFailed = false;
            this.initRetryCount = 0;
            window.dispatchEvent(new CustomEvent('map-fullscreen-close'));
            setTimeout(() => this.initMap(), 300);
        },

        async confirmFullscreenSelection() {
            if (!this.fullscreenLat || !this.fullscreenLon) return;
            this.cleanupFullscreenMap();
            this.isFullscreen = false;
            window.dispatchEvent(new CustomEvent('map-fullscreen-confirm'));
            try {
                await $wire.confirmFullscreenPoint(this.fullscreenLat, this.fullscreenLon);
            } catch (e) {
                console.error('[AddressSelector] Fullscreen confirm error:', e);
            }
        },

        async initFullscreenMap() {
            if (typeof ymaps3 === 'undefined') return;
            try { await ymaps3.ready; } catch(e) { return; }
            if (!this.isFullscreen) return;

            const el = this.$refs.fullscreenMapContainer;
            if (!el || el.offsetWidth === 0) {
                this.fsInitRetryCount++;
                if (this.fsInitRetryCount >= 12) return;
                if (this.isFullscreen) setTimeout(() => this.initFullscreenMap(), 400);
                return;
            }

            this.cleanupFullscreenMap();

            const startLat = parseFloat(this.fullscreenLat) || parseFloat($wire.lat) || 39.0886;
            const startLon = parseFloat(this.fullscreenLon) || parseFloat($wire.lon) || 63.5593;
            this.fullscreenLat = startLat;
            this.fullscreenLon = startLon;

            try {
                const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapControls, YMapZoomControl, YMapDefaultMarker, YMapListener } = ymaps3;

                const map = new YMap(el, {
                    location: { center: [startLon, startLat], zoom: 16 },
                    behaviors: ['drag', 'pinchZoom', 'dblClick'],
                });

                map.addChild(new YMapDefaultSchemeLayer());
                map.addChild(new YMapDefaultFeaturesLayer());

                const controls = new YMapControls({ position: 'right' });
                controls.addChild(new YMapZoomControl());
                map.addChild(controls);

                const marker = new YMapDefaultMarker({
                    coordinates: [startLon, startLat],
                    title: 'Перетащите метку',
                    draggable: true,
                    color: '#FF6B35',
                    onDragEnd: (coords) => {
                        if (coords && coords.length >= 2) {
                            this.fullscreenLat = coords[1];
                            this.fullscreenLon = coords[0];
                        }
                    },
                });
                map.addChild(marker);
                this.fullscreenMarkerInstance = marker;

                const listener = new YMapListener({
                    layer: 'any',
                    onClick: (obj, event) => {
                        const coords = event.coordinates;
                        if (coords) {
                            marker.update({ coordinates: coords });
                            this.fullscreenLat = coords[1];
                            this.fullscreenLon = coords[0];
                        }
                    }
                });
                map.addChild(listener);

                this.fullscreenMapInstance = map;
                this.fsMapInitialized = true;
            } catch (e) {
                console.error('[AddressSelector] Fullscreen map creation error:', e);
            }
        },

        cleanupFullscreenMap() {
            if (this.fullscreenMapInstance) {
                try { this.fullscreenMapInstance.destroy(); } catch(e) {}
            }
            this.fullscreenMapInstance = null;
            this.fullscreenMarkerInstance = null;
            this.fsMapInitialized = false;
        },

        geolocateInFullscreen() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        this.fullscreenLat = lat;
                        this.fullscreenLon = lon;
                        if (this.fullscreenMapInstance && this.fullscreenMarkerInstance) {
                            this.fullscreenMarkerInstance.update({ coordinates: [lon, lat] });
                            this.fullscreenMapInstance.setLocation({ center: [lon, lat], zoom: 16, duration: 300 });
                        }
                    },
                    (error) => {
                        console.error('[AddressSelector] Fullscreen geolocate error:', error);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            }
        }
    }"
    wire:key="address-selector-v2"
    x-show="isLocalModalOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @open-address-selector.window="
        $wire.openModal($event.detail?.fullscreen ?? false);
        if ($event.detail?.fullscreen) {
            isLocalModalOpen = true;
            showRefinement = false;
            setTimeout(() => enterFullscreen(), 300);
        }
    "
    @close-address-selector.window="$wire.closeModal()"
    @map-update.window="updateMap($event.detail.lat, $event.detail.lon)"
    @enter-fullscreen-mode.window="isLocalModalOpen = true; showRefinement = false; setTimeout(() => enterFullscreen(), 300);"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$wire.closeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] overflow-hidden flex flex-col"
         x-show="isLocalModalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">
        <div class="flex items-center justify-between px-4 py-3.5 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <h2 class="text-base font-bold text-gray-900">Адрес доставки</h2>
            </div>
            <button @click="$wire.closeModal()" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        {{-- Step 1: Map + Search --}}
        <div x-show="!showRefinement" style="display: none;">
            <div class="px-4 pt-3 pb-2 shrink-0">
                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <input type="text" x-model="searchQuery" @input="search()" @focus="showSuggestions = @js(!empty($suggestions))" @blur="setTimeout(() => showSuggestions = false, 200)" @keydown.enter.prevent="$wire.goToRefinement()" placeholder="Улица, дом..." class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
                        <div x-show="showSuggestions && @js(!empty($suggestions))" class="absolute z-20 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl max-h-48 overflow-y-auto" style="display:none">
                            @foreach($suggestions as $index => $suggestion)
                                <button type="button" @click="select({{ $index }})" class="w-full px-3 py-2.5 text-left hover:bg-orange-50 border-b border-gray-50 last:border-b-0 transition-colors flex items-start gap-2">
                                    <svg class="w-4 h-4 text-orange-400 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <div class="min-w-0"><p class="text-sm font-medium text-gray-900 truncate">{{ $suggestion['address'] }}</p>@if($suggestion['kind'])<p class="text-xs text-gray-400">{{ $suggestion['kind'] }}</p>@endif</div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" wire:click="detectLocation" wire:loading.attr="disabled" class="px-3.5 py-2.5 bg-orange-500 text-white rounded-xl hover:bg-orange-600 active:scale-95 transition-all disabled:opacity-50 flex items-center gap-1.5 shrink-0 shadow-sm shadow-orange-200">
                        <svg wire:loading wire:target="detectLocation" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <svg wire:loading.remove wire:target="detectLocation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-xs font-semibold hidden sm:block">Найти меня</span>
                    </button>
                </div>
            </div>
            <div class="relative mx-4 mb-3 rounded-xl overflow-hidden shrink-0 border border-gray-200 shadow-sm" style="height: 260px;" wire:ignore>
                <div x-show="isMapLoading && !mapFailed" class="absolute inset-0 bg-gray-100 flex flex-col items-center justify-center gap-3 z-10" style="display:none">
                    <div class="w-10 h-10 border-orange-200 border-t-orange-500 rounded-full animate-spin" style="border-width: 3px; border-style: solid;"></div>
                    <p class="text-xs text-gray-400 font-medium">Загрузка карты...</p>
                </div>
                <div x-show="mapFailed" class="absolute inset-0 bg-gray-50 flex flex-col items-center justify-center gap-3 z-10 p-4" style="display:none">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <p class="text-xs text-gray-500 font-medium text-center">Карта недоступна.<br>Введите адрес вручную или попробуйте снова.</p>
                    <button type="button" @click="mapFailed = false; isMapLoading = true; initRetryCount = 0; initMap()" class="text-xs text-orange-500 font-bold hover:text-orange-600 underline">Попробовать снова</button>
                </div>
                <div x-ref="yandexMap" id="yandex-map-v2" class="w-full h-full" style="width: 100%; height: 100%;"></div>
                <div x-show="!isMapLoading && !mapFailed" class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full shadow text-[11px] text-gray-500 font-medium whitespace-nowrap pointer-events-none z-10" style="display:none">Перетащите метку для уточнения адреса</div>
                <button @click="enterFullscreen()" x-show="!isMapLoading && !mapFailed"
                    class="absolute top-2 right-2 z-10 w-9 h-9 bg-white/90 backdrop-blur-sm rounded-lg shadow flex items-center justify-center text-gray-500 hover:text-orange-500 hover:bg-white transition-all"
                    title="Открыть карту на весь экран">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                </button>
            </div>
            <div class="px-4 pb-2 shrink-0 space-y-2">
                @if($error && !$showRefinement)<div class="flex items-center gap-2 p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>{{ $error }}</span></div>@endif
                @if($isInDeliveryZone && $address && $hasSelectedPoint && !$showRefinement)<div class="flex items-center gap-2 p-3 bg-green-50 border border-green-100 rounded-xl text-green-700 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Адрес <strong>входит</strong> в зону доставки</span></div>@endif
                @if($address && $hasSelectedPoint && !$isInDeliveryZone && !$error && !$showRefinement)<div class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl text-amber-700 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>Адрес за пределами зоны доставки</span></div>@endif
            </div>
            <div class="px-4 pb-4 shrink-0">
                <button type="button" wire:click="goToRefinement" wire:loading.attr="disabled" class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:from-orange-600 hover:to-orange-700 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading wire:target="goToRefinement" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                    <svg wire:loading.remove wire:target="goToRefinement" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span wire:loading.remove wire:target="goToRefinement">Продолжить</span>
                    <span wire:loading wire:target="goToRefinement">Обработка...</span>
                </button>
            </div>
        </div>

        {{-- Step 2: Manual Refinement --}}
        <div x-show="showRefinement" class="flex flex-col flex-1 overflow-hidden">
            <div class="px-4 pt-3 pb-2 shrink-0">
                <button type="button" @click="resetToMap()" class="flex items-center gap-1.5 text-sm font-bold text-orange-500 hover:text-orange-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Вернуться к карте</span>
                </button>
            </div>

            <div class="px-4 overflow-y-auto flex-1 space-y-3 pb-4">
                @if($address)
                    <div class="p-3 bg-orange-50 border border-orange-100 rounded-xl">
                        <p class="text-xs text-orange-500 font-bold uppercase tracking-wider mb-1">Найденный адрес</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $address }}</p>
                    </div>
                @endif

                @if(!$address)
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Адрес вручную</label>
                        <input type="text" wire:model.live="manualAddress" placeholder="Улица, дом (если карта не определила адрес)" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
                    </div>
                @endif

                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Ориентир для курьера</label>
                    <input type="text" wire:model="landmark" placeholder="Например: рядом с рынком, школа, 5-этажка" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Подъезд</label>
                        <input type="text" wire:model="entrance" placeholder="1" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Этаж</label>
                        <input type="text" wire:model="floor" placeholder="5" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Кв./офис</label>
                        <input type="text" wire:model="apartment" placeholder="42" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 block">Комментарий курьеру</label>
                    <textarea wire:model="courierComment" placeholder="Дополнительные указания" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
                </div>

                <div class="pt-1 space-y-2">
                    @if($error && $showRefinement)<div class="flex items-center gap-2 p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>{{ $error }}</span></div>@endif
                    @if($isInDeliveryZone && $hasSelectedPoint)<div class="flex items-center gap-2 p-3 bg-green-50 border border-green-100 rounded-xl text-green-700 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Точка <strong>входит</strong> в зону доставки</span></div>@endif
                    @if($hasSelectedPoint && !$isInDeliveryZone && !$error && $showRefinement)<div class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl text-amber-700 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>Точка за пределами зоны доставки</span></div>@endif
                </div>
            </div>

            <div class="px-4 pb-4 shrink-0">
                <button type="button" wire:click="confirmAddress" wire:loading.attr="disabled" class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:from-orange-600 hover:to-orange-700 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading wire:target="confirmAddress" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                    <svg wire:loading.remove wire:target="confirmAddress" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span wire:loading.remove wire:target="confirmAddress">Подтвердить адрес</span>
                    <span wire:loading wire:target="confirmAddress">Обработка...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Fullscreen Map Overlay --}}
    <div x-show="isFullscreen"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] bg-white flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-4 border-b border-gray-100 shrink-0 relative z-[2000]" style="height: calc(3.5rem + env(safe-area-inset-top)); padding-top: env(safe-area-inset-top);">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FF6B35" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <h2 class="text-base font-bold text-gray-900">Выберите точку на карте</h2>
            </div>
            <button @click="exitFullscreen()" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 min-h-0 h-0 relative overflow-hidden" wire:ignore>
            <div x-show="!fsMapInitialized && !mapFailed" class="absolute inset-0 bg-gray-100 flex flex-col items-center justify-center gap-3 z-[2000]">
                <div class="w-10 h-10 border-orange-200 border-t-orange-500 rounded-full animate-spin" style="border-width: 3px; border-style: solid;"></div>
                <p class="text-xs text-gray-400 font-medium">Загрузка карты...</p>
            </div>
            <div x-ref="fullscreenMapContainer" class="absolute inset-0"></div>
            
            <div x-show="fsMapInitialized" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full shadow text-xs text-gray-500 font-medium whitespace-nowrap pointer-events-none z-[2000]">
                Перетащите метку для выбора точки
            </div>
        </div>
        <div class="px-4 border-t border-gray-100 shrink-0 bg-white relative z-[2000]" style="padding-top: 1rem; padding-left: 1rem; padding-right: 1rem; padding-bottom: max(1rem, env(safe-area-inset-bottom));">
            <div class="flex gap-3">
                <button @click="exitFullscreen()"
                    class="flex-1 py-3.5 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all active:scale-[0.98]">
                    Отмена
                </button>
                <button @click="confirmFullscreenSelection()"
                    x-bind:disabled="!fullscreenLat || !fullscreenLon"
                    class="flex-1 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:from-orange-600 hover:to-orange-700 transition-all active:scale-[0.98] disabled:opacity-50">
                    Подтвердить
                </button>
            </div>
        </div>
        </div>
        
        <!-- Geolocate Button in Livewire Fullscreen Map (Moved outside map container) -->
        <button type="button" @click="geolocateInFullscreen()" x-show="fsMapInitialized"
            class="absolute right-4 top-24 w-9 h-9 bg-white/90 backdrop-blur-sm rounded-lg shadow flex items-center justify-center text-gray-500 hover:text-orange-500 hover:bg-white transition-all active:scale-95 pointer-events-auto"
            style="z-index: 99999;"
            title="Определить моё местоположение">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        </button>
    </div>
</div>
