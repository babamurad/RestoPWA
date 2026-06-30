@vite('resources/js/filament-vue.js')

<div wire:ignore>
    <div 
        x-data="{
            state: @entangle($getStatePath()),
            init() {
                let checkInterval = setInterval(() => {
                    if (window.mountDeliveryZoneMap) {
                        clearInterval(checkInterval);
                        window.mountDeliveryZoneMap(
                            this.$refs.mapContainer,
                            this.state,
                            (val) => { this.state = val; }
                        );
                    }
                }, 100);
            }
        }"
    >
        <!-- Vue component will be mounted here -->
        <div x-ref="mapContainer"></div>
    </div>
</div>

<script>
    window.YANDEX_MAPS_KEY = '{{ config('services.yandex_maps.js_key') }}';
</script>