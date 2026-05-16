<div    x-data="{
        isLocalModalOpen: @entangle('isAddressModalOpen'),
        searchQuery: @entangle('address'),
        showSuggestions: false,
        debounceTimer: null,
        mapInstance: null,
        markerInstance: null,
        mapInitialized: false,
        isMapLoading: true,
        isDragging: false,
        showRefinement: @entangle('showRefinement'),
        
        init() {
            this.$watch('isLocalModalOpen', value => {
                if (value === true) {
                    setTimeout(() => this.initMap(), 600);
                }
            });

            // Watch for coordinate changes from Livewire (e.g. from search)
            this.$watch(() => $wire.lat + ',' + $wire.lon, (val) => {
                if (!this.mapInitialized || !this.markerInstance || this.isDragging) return;
                
                const [nLat, nLon] = val.split(',').map(parseFloat);
                if (!nLat || !nLon) return;

                const currentCoords = this.markerInstance.geometry.getCoordinates();
                if (!currentCoords || Math.abs(currentCoords[0] - nLat) > 0.0001 || Math.abs(currentCoords[1] - nLon) > 0.0001) {
                    this.markerInstance.geometry.setCoordinates([nLat, nLon]);
                    this.mapInstance.setCenter([nLat, nLon], 16, { checkZoomRange: true });
                }
            });
        },
        
        initMap() {
            if (typeof ymaps === 'undefined') {
                this.isMapLoading = false;
                $wire.setError('Карта недоступна. Пожалуйста, введите адрес вручную или проверьте настройки API.');
                return;
            }

            ymaps.ready(() => {
                const mapEl = this.$refs.yandexMap;
                if (!mapEl || mapEl.offsetWidth === 0) {
                    setTimeout(() => this.initMap(), 300);
                    return;
                }

                if (this.mapInitialized && this.mapInstance) {
                    this.mapInstance.container.fitToViewport();
                    const lat = parseFloat($wire.lat) || 39.0886;
                    const lon = parseFloat($wire.lon) || 63.5593;
                    this.mapInstance.setCenter([lat, lon], 16);
                    if (this.markerInstance) {
                        this.markerInstance.geometry.setCoordinates([lat, lon]);
                    }
                    this.isMapLoading = false;
                    return;
                }

                try {
                    const lat = parseFloat($wire.lat) || 39.0886;
                    const lon = parseFloat($wire.lon) || 63.5593;

                    this.mapInstance = new ymaps.Map(mapEl, {
                        center: [lat, lon],
                        zoom: 16,
                        controls: [],
                        behaviors: ['drag', 'multiTouch']
                    }, {
                        suppressMapOpenBlock: true
                    });

                    this.mapInstance.events.once('boundschange', () => {
                        this.mapInitialized = true;
                        this.isMapLoading = false;

                        try {
                            this.mapInstance.controls.add('zoomControl');
                            this.mapInstance.controls.add('geolocationControl');
                        } catch (e) {
                            console.warn('Controls init error:', e);
                        }

                        try {
                            this.markerInstance = new ymaps.Placemark([lat, lon], {
                                hintContent: 'Перетащите метку'
                            }, {
                                preset: 'islands#orangeDotIcon',
                                draggable: true
                            });

                            this.markerInstance.events.add('dragstart', () => {
                                this.isDragging = true;
                            });

                            this.markerInstance.events.add('dragend', () => {
                                this.isDragging = false;
                                const coords = this.markerInstance.geometry.getCoordinates();
                                if (coords && coords.length >= 2) {
                                    $wire.setLocation(coords[0], coords[1], 'map_pin');
                                }
                            });

                            this.mapInstance.geoObjects.add(this.markerInstance);
                        } catch (e) {
                            console.warn('Placemark init error:', e);
                        }

                        // Add click listener for map
                        try {
                            this.mapInstance.events.add('click', (e) => {
                                if (this.isDragging) return;
                                const coords = e.get('coords');
                                if (coords) {
                                    this.markerInstance.geometry.setCoordinates(coords);
                                    $wire.setLocation(coords[0], coords[1], 'map_pin');
                                }
                            });
                        } catch (e) {
                            console.warn('Map click listener error:', e);
                        }
                    });

                } catch (e) {
                    console.error('Map init error:', e);
                    this.isMapLoading = false;
                }
            });
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
    @open-address-selector.window="isLocalModalOpen = true; $wire.openModal()"
    @close-address-selector.window="isLocalModalOpen = false; $wire.closeModal()"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isLocalModalOpen = false; $wire.closeModal()"></div>
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
            <button @click="isLocalModalOpen = false; $wire.closeModal()" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-400 hover:text-gray-600">
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
                        <input type="text" x-model="searchQuery" @input="search()" @focus="showSuggestions = @js(!empty($suggestions))" @blur="setTimeout(() => showSuggestions = false, 200)" @keydown.enter.prevent="$wire.confirmAddress()" placeholder="Улица, дом..." class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none text-sm bg-gray-50 focus:bg-white transition-all">
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
                <div x-show="isMapLoading" class="absolute inset-0 bg-gray-100 flex flex-col items-center justify-center gap-3 z-10">
                    <div class="w-10 h-10 border-3 border-orange-200 border-t-orange-500 rounded-full animate-spin" style="border-width: 3px;"></div>
                    <p class="text-xs text-gray-400 font-medium">Загрузка карты...</p>
                </div>
                <div x-ref="yandexMap" id="yandex-map-v2" class="w-full h-full" style="width: 100%; height: 100%;"></div>
                <div x-show="!isMapLoading" class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full shadow text-[11px] text-gray-500 font-medium whitespace-nowrap pointer-events-none z-10" style="display:none">Перетащите метку для уточнения адреса</div>
            </div>
            <div class="px-4 pb-2 shrink-0 space-y-2">
                @if($error && !$showRefinement)<div class="flex items-center gap-2 p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>{{ $error }}</span></div>@endif
                @if($isInDeliveryZone && $address && $hasSelectedPoint && !$showRefinement)<div class="flex items-center gap-2 p-3 bg-green-50 border border-green-100 rounded-xl text-green-700 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>Адрес <strong>входит</strong> в зону доставки</span></div>@endif
                @if($address && $hasSelectedPoint && !$isInDeliveryZone && !$error && !$showRefinement)<div class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl text-amber-700 text-sm"><svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>Адрес за пределами зоны доставки</span></div>@endif
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
</div>
