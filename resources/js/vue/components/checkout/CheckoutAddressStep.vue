<template>
  <div class="space-y-6 select-none">
    <h3 class="text-lg font-bold text-slate-100 font-outfit">Куда доставить?</h3>
    
    <!-- Delivery Type Toggle -->
    <div class="flex p-1 bg-slate-900 border border-slate-800 rounded-xl">
      <button 
        @click="updateType('delivery')"
        class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
        :class="localData.delivery_type === 'delivery' ? 'bg-slate-800 text-orange-400 shadow-sm' : 'text-slate-500 hover:text-slate-300'"
      >
        Доставка
      </button>
      <button 
        @click="updateType('pickup')"
        class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
        :class="localData.delivery_type === 'pickup' ? 'bg-slate-800 text-orange-400 shadow-sm' : 'text-slate-500 hover:text-slate-300'"
      >
        Самовывоз
      </button>
    </div>

    <!-- Address Fields (only for delivery) -->
    <div v-if="localData.delivery_type === 'delivery'" class="space-y-4 animate-fade-in">
      
      <!-- MAP CONTAINER -->
      <div class="h-48 w-full rounded-xl overflow-hidden relative border border-slate-800">
        <div id="checkout-map" class="w-full h-full"></div>
        
        <!-- Pin icon in center (fixed, map moves) -->
        <div
          class="absolute inset-0 flex items-center justify-center pointer-events-none pb-8"
          style="z-index: 1000"
          v-show="mapLoaded && geo.status.value !== 'loading'"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-orange-600 drop-shadow-xl" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>

        <!-- Map init spinner -->
        <div v-if="!mapLoaded" class="absolute inset-0 flex items-center justify-center bg-slate-900 z-10">
          <div class="w-6 h-6 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin"></div>
        </div>

        <!-- Geolocation loading overlay -->
        <div
          v-if="mapLoaded && geo.status.value === 'loading'"
          class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/80 backdrop-blur-sm z-20"
        >
          <div class="w-6 h-6 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin mb-2"></div>
          <span class="text-xs text-slate-300 font-semibold">Определяем ваше местоположение…</span>
        </div>

        <!-- Zone check badge (top-left) -->
        <div
          v-if="mapLoaded && localData.delivery_type === 'delivery' && zoneStatus !== 'idle' && zoneStatus !== 'loading'"
          class="absolute top-2 left-2 z-20 flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold shadow-lg backdrop-blur-sm"
          :class="{
            'bg-green-500/20 border border-green-500/40 text-green-400': zoneStatus === 'inside',
            'bg-red-500/20 border border-red-500/40 text-red-400': zoneStatus === 'outside',
            'bg-slate-700/80 border border-slate-600/50 text-slate-300': zoneStatus === 'zone_missing' || zoneStatus === 'error',
          }"
        >
          <span v-if="zoneStatus === 'inside'">✓ Доставляем</span>
          <span v-else-if="zoneStatus === 'outside'">✗ Вне зоны</span>
          <span v-else>Зона не задана</span>
        </div>

        <!-- Zone check loading spinner (top-left) -->
        <div
          v-if="mapLoaded && zoneStatus === 'loading'"
          class="absolute top-2 left-2 z-20 flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-700/80 border border-slate-600/50"
        >
          <div class="w-3 h-3 border border-slate-400/30 border-t-slate-300 rounded-full animate-spin"></div>
        </div>

        <!-- Fullscreen button (top-right of map) -->
        <button
          v-if="mapLoaded"
          @click="enterFullscreen"
          title="Открыть карту на весь экран"
          class="absolute top-2 right-2 z-20 w-8 h-8 rounded-lg bg-slate-900/90 border border-slate-700 flex items-center justify-center text-orange-400 hover:text-orange-300 hover:border-orange-500/40 transition-all active:scale-95 shadow-lg"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 3H5a2 2 0 0 0-2 2v3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8V5a2 2 0 0 0-2-2h-3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21h3a2 2 0 0 0 2-2v-3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16v3a2 2 0 0 0 2 2h3" />
          </svg>
        </button>

        <!-- Retry geo button (bottom-right of map) -->
        <button
          v-if="mapLoaded && GEO_ENABLED && geo.status.value !== 'loading'"
          @click="handleRetryGeo"
          title="Определить моё местоположение"
          class="absolute bottom-2 right-2 z-20 w-8 h-8 rounded-lg bg-slate-900/90 border border-slate-700 flex items-center justify-center text-orange-400 hover:text-orange-300 hover:border-orange-500/40 transition-all active:scale-95 shadow-lg"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3" />
            <path stroke-linecap="round" d="M12 2v2m0 16v2M2 12h2m16 0h2" />
            <path stroke-linecap="round" d="M12 5a7 7 0 100 14A7 7 0 0012 5z" />
          </svg>
        </button>
      </div>

      <!-- Zone outside warning banner -->
      <div
        v-if="zoneStatus === 'outside'"
        class="flex items-start gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-xs text-red-400"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <div>
          <p class="font-bold mb-0.5">Адрес вне зоны доставки</p>
          <p class="text-red-400/80">Переместите точку на карте или выберите <button @click="updateType('pickup')" class="underline font-bold">самовывоз</button>.</p>
        </div>
      </div>

      <!-- Geo error / denied banner -->
      <div
        v-if="geoError"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-800/80 border border-slate-700/60 text-xs text-slate-300"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-orange-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <span>{{ geoError }}</span>
        <button
          v-if="geo.status.value !== 'denied'"
          @click="handleRetryGeo"
          class="ml-auto shrink-0 text-orange-400 font-bold hover:text-orange-300 transition-colors"
        >
          Повторить
        </button>
      </div>

      <!-- Low GPS accuracy warning banner -->
      <div
        v-if="isLowGpsAccuracy"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-400"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <span>Низкая точность геолокации. Рекомендуется уточнить адрес на карте вручную.</span>
      </div>

      <!-- Reverse-geocoded address hint -->
      <div
        v-if="reverseAddr && confidence"
        class="flex items-start gap-2 px-3 py-2.5 rounded-xl border text-xs"
        :class="{
          'bg-green-500/5 border-green-500/20 text-slate-300': confidence === 'high',
          'bg-slate-800/60 border-slate-700/50 text-slate-300': confidence === 'medium',
          'bg-amber-500/5 border-amber-500/20 text-amber-400/80': confidence === 'low',
        }"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        </svg>
        <div class="min-w-0">
          <span class="break-words">{{ reverseAddr }}</span>
          <span v-if="confidence === 'low'" class="block mt-0.5 text-amber-400/70">Низкая точность — уточните адрес вручную</span>
        </div>
      </div>

      <div>
        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Улица, дом (необязательно)</label>
        <input 
          v-model="localData.address" 
          type="text" 
          placeholder="ул. Азади, д. 45"
          class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600"
        />
      </div>

      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Подъезд</label>
          <input 
            v-model="localData.entrance" 
            type="text" 
            placeholder="1"
            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 text-center"
          />
        </div>
        <div>
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Этаж</label>
          <input 
            v-model="localData.floor" 
            type="text" 
            placeholder="5"
            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 text-center"
          />
        </div>
        <div>
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Квартира</label>
          <input 
            v-model="localData.apartment" 
            type="text" 
            placeholder="25"
            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 text-center"
          />
        </div>
      </div>
    </div>

    <!-- Pickup text -->
    <div v-else class="p-4 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-semibold leading-relaxed animate-fade-in">
      Вы сможете забрать заказ в выбранном ресторане. Мы пришлем уведомление, когда блюда будут готовы.
    </div>

    <!-- Phone Number (Required) -->
    <div>
      <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Контактный телефон</label>
      <input 
        v-model="localData.phone" 
        type="tel" 
        placeholder="+993 6X XXXXXX"
        class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 font-medium tracking-wide"
      />
    </div>

    <button 
      @click="handleNext"
      :disabled="!isValid"
      class="w-full mt-6 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 disabled:from-slate-800 disabled:to-slate-850 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-extrabold text-sm rounded-xl shadow-lg transition-all active:scale-[0.98]"
    >
      Продолжить
    </button>

    <!-- Fullscreen Map Overlay -->
    <div 
      v-if="isFullscreen"
      class="fixed inset-0 z-[60] bg-slate-950 flex flex-col select-none"
    >
      <!-- Header with safe-area top padding for premium mobile UX -->
      <div class="flex items-center justify-between px-4 border-b border-slate-800 shrink-0 bg-slate-900 relative z-[2000]" style="height: calc(4rem + env(safe-area-inset-top)); padding-top: env(safe-area-inset-top);">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 bg-orange-500/10 rounded-xl flex items-center justify-center border border-orange-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h2 class="text-sm font-bold text-slate-100 font-outfit tracking-wide">Укажите точку на карте</h2>
        </div>
        <button @click="exitFullscreen" class="p-2 hover:bg-slate-800 rounded-full transition-colors text-slate-400 hover:text-slate-200 active:scale-95">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Map Container -->
      <div class="flex-1 relative overflow-hidden bg-slate-900">
        <div id="checkout-map-fullscreen" class="w-full h-full"></div>
        
        <!-- Floating controls on top of the fullscreen map -->
        <div v-show="fullscreenMapLoaded" class="absolute right-4 bottom-20 z-[2000] flex flex-col gap-2.5">
          <!-- Zoom In -->
          <button
            @click="zoomInFullscreen"
            type="button"
            title="Приблизить"
            class="w-10 h-10 rounded-xl bg-slate-900/95 border border-slate-800 flex items-center justify-center text-orange-400 hover:text-orange-300 hover:border-orange-500/40 transition-all active:scale-95 shadow-lg"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
          </button>
          
          <!-- Zoom Out -->
          <button
            @click="zoomOutFullscreen"
            type="button"
            title="Отдалить"
            class="w-10 h-10 rounded-xl bg-slate-900/95 border border-slate-800 flex items-center justify-center text-orange-400 hover:text-orange-300 hover:border-orange-500/40 transition-all active:scale-95 shadow-lg"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
            </svg>
          </button>

          <!-- Geolocate Button -->
          <button
            v-if="GEO_ENABLED"
            @click="handleRetryGeo"
            type="button"
            :disabled="geo.status.value === 'loading'"
            title="Определить моё местоположение"
            class="w-10 h-10 rounded-xl bg-slate-900/95 border border-slate-800 flex items-center justify-center text-orange-400 hover:text-orange-300 hover:border-orange-500/40 transition-all active:scale-95 shadow-lg mt-2 disabled:opacity-50"
          >
            <div v-if="geo.status.value === 'loading'" class="w-4 h-4 border border-orange-400/30 border-t-orange-400 rounded-full animate-spin"></div>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3" />
              <path stroke-linecap="round" d="M12 2v2m0 16v2M2 12h2m16 0h2" />
              <path stroke-linecap="round" d="M12 5a7 7 0 100 14A7 7 0 0012 5z" />
            </svg>
          </button>
        </div>
        
        <!-- Fixed Pin in Center -->
        <div
          class="absolute inset-0 flex items-center justify-center pointer-events-none pb-8"
          style="z-index: 2000"
          v-show="fullscreenMapLoaded"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-orange-500 drop-shadow-2xl" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>

        <div v-show="!fullscreenMapLoaded" class="absolute inset-0 bg-slate-900 flex flex-col items-center justify-center gap-3 z-[2000]">
          <div class="w-8 h-8 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin"></div>
          <p class="text-xs text-slate-400 font-medium">Загрузка...</p>
        </div>
        
        <div v-show="fullscreenMapLoaded" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-slate-900/90 border border-slate-800 backdrop-blur-sm px-4 py-2 rounded-full shadow-lg text-[10px] uppercase font-black tracking-wider text-slate-300 whitespace-nowrap pointer-events-none z-[2000]">
          Перетащите карту под маркер
        </div>
      </div>

      <!-- Actions with safe-area bottom padding -->
      <div class="px-4 border-t border-slate-800 shrink-0 bg-slate-900 relative z-[2000]" style="padding-top: 1rem; padding-left: 1rem; padding-right: 1rem; padding-bottom: max(1rem, env(safe-area-inset-bottom));">
        <div class="flex gap-3">
          <button 
            @click="exitFullscreen"
            class="flex-1 py-3.5 bg-slate-800 hover:bg-slate-700 active:bg-slate-750 text-slate-300 font-bold rounded-xl transition-all active:scale-[0.98] text-sm font-inter"
          >
            Отмена
          </button>
          <button 
            @click="confirmFullscreenSelection"
            class="flex-1 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold rounded-xl shadow-lg shadow-orange-500/20 transition-all active:scale-[0.98] text-sm font-inter"
          >
            Подтвердить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useCartStore } from '../../stores/cart';
import { useGeolocate } from '../../composables/useGeolocate';
import { useZoneCheck } from '../../composables/useZoneCheck';
import { useTelemetry } from '../../composables/useTelemetry';

// Feature flag: VITE_CHECKOUT_GEOLOCATE=false в .env или backend config отключает автогеолокацию
const GEO_ENABLED = window.checkoutDefaultGeolocateEnabled !== false && import.meta.env.VITE_CHECKOUT_GEOLOCATE !== 'false';

const props = defineProps({
  orderData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update-data', 'next-step']);

const cartStore = useCartStore();

const localData = ref({
  ...props.orderData,
  lat: props.orderData.lat || 39.0886,
  lon: props.orderData.lon || 63.5593,
  address_source: props.orderData.address_source || null,
  geolocate_attempted: props.orderData.geolocate_attempted || false,
  geolocate_status: props.orderData.geolocate_status || null,
  geolocate_accuracy_m: props.orderData.geolocate_accuracy_m || null,
});

const isFullscreen = ref(false);
const tempLat = ref(39.0886);
const tempLon = ref(63.5593);
let fullscreenMapInstance = null;
const fullscreenMapLoaded = ref(false);

const mapLoaded = ref(false);
let mapInstance = null;

// Защита от перезаписи ручного выбора (FR-4)
const userHasMovedMap = ref(false);
let skipNextMoveEnd = false;

// Низкая точность геолокации (GPS accuracy > 200м)
const isLowGpsAccuracy = ref(false);

// Флаг: пользователь явно выбрал точку (GPS / drag / клик)
// До этого zone check не запускается, чтобы дефолтные координаты не давали ложный «вне зоны»
const hasSelectedPoint = ref(!!props.orderData.lat && props.orderData.address_source != null);

const geo           = useGeolocate({ enabled: GEO_ENABLED });
const zoneChecker   = useZoneCheck();
const { track }     = useTelemetry();

// Время открытия шага
const stepOpenedAt = Date.now();

// Прокси для удобного доступа в шаблоне
// Zone check показывается только если точка явно выбрана
const zoneStatus  = computed(() => hasSelectedPoint.value ? zoneChecker.zoneStatus.value : 'idle');
const reverseAddr = computed(() => hasSelectedPoint.value ? zoneChecker.reverseAddr.value : null);
const confidence  = computed(() => hasSelectedPoint.value ? zoneChecker.confidence.value : null);

// Счётчик перемещений карты для аналитики P0.4
let pinMoveCount = 0;

// Geo error messages
const geoError = computed(() => {
  switch (geo.status.value) {
    case 'denied':      return 'Доступ к геолокации запрещён. Выберите точку на карте вручную.';
    case 'timeout':     return 'Не удалось получить геопозицию (таймаут). Попробуйте снова или выберите точку вручную.';
    case 'unavailable': return 'Геолокация недоступна на этом устройстве. Выберите точку на карте вручную.';
    case 'error':       return 'Не удалось получить геопозицию. Выберите точку на карте вручную.';
    default:            return null;
  }
});

onMounted(() => {
  // P0.4: map_opened event
  track('map_opened', {
    lat: localData.value.lat,
    lon: localData.value.lon,
    time_from_step_open_ms: 0,
  });

  if (localData.value.delivery_type === 'delivery') {
    initMap();
  }
});

onUnmounted(() => {
  zoneChecker.reset();
  if (mapInstance && typeof mapInstance.remove === 'function') {
    try { mapInstance.remove(); } catch(e) {}
  }
});

watch(() => localData.value.delivery_type, (newType) => {
  if (newType === 'delivery' && !mapInstance) {
    nextTick(() => initMap());
  }
  if (newType !== 'delivery') {
    zoneChecker.reset();
  }
});

watch(localData, (newVal) => {
  emit('update-data', newVal);
}, { deep: true });

const initMap = async () => {
  try {
    if (!window.L) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(link);

      await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.type = 'text/javascript';
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
      });
    }

    if (!window.L) {
      console.error('Leaflet script failed to load');
      mapLoaded.value = true;
      return;
    }

    const container = document.getElementById('checkout-map');
    if (!container) return;

    const map = window.L.map('checkout-map', {
      center: [localData.value.lat, localData.value.lon],
      zoom: 15,
      zoomControl: false,
      attributionControl: false
    });

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
    }).addTo(map);

    map.on('moveend', () => {
      if (skipNextMoveEnd) {
        skipNextMoveEnd = false;
        return;
      }

      // Сбрасываем предупреждение о низкой точности, так как пользователь двигает маркер вручную
      isLowGpsAccuracy.value = false;

      // Пользователь двинул карту вручную — это явный выбор точки
      userHasMovedMap.value = true;
      hasSelectedPoint.value = true;
      pinMoveCount++;

      const center = map.getCenter();
      const lat = center.lat;
      const lon = center.lng;

      localData.value.lat = lat;
      localData.value.lon = lon;

      if (localData.value.address_source === 'geolocate') {
        localData.value.address_source = 'map_pin';
      } else if (!localData.value.address_source) {
        localData.value.address_source = 'map_pin';
      }

      // P0.4: pin_moved event
      track('pin_moved', {
        lat_rounded: Math.round(lat * 100) / 100,
        lon_rounded: Math.round(lon * 100) / 100,
        move_count: pinMoveCount,
        time_from_step_open_ms: Date.now() - stepOpenedAt,
      });

      // P0.1: zone check (только после явного выбора точки)
      if (cartStore.vendorId) {
        zoneChecker.checkZone(cartStore.vendorId, lat, lon);
      }

      // P0.2: reverse geocode
      zoneChecker.reverseGeocode(lat, lon);
    });

    map.on('click', (e) => {
      map.panTo(e.latlng);
    });

    mapInstance = map;
    mapLoaded.value = true;

    // Первичный zone check только если точка уже была явно выбрана ранее (повторный вход в шаг)
    if (hasSelectedPoint.value && cartStore.vendorId) {
      zoneChecker.checkZone(cartStore.vendorId, localData.value.lat, localData.value.lon, 1200);
      zoneChecker.reverseGeocode(localData.value.lat, localData.value.lon, 1200);
    }

    // Запускаем геолокацию
    if (GEO_ENABLED) {
      tryGeolocate();
    }
  } catch (e) {
    console.error('Map init error', e);
    mapLoaded.value = true;
  }
};

const initFullscreenMap = async () => {
  try {
    tempLat.value = localData.value.lat;
    tempLon.value = localData.value.lon;

    const el = document.getElementById('checkout-map-fullscreen');
    if (!el) return;

    const map = window.L.map('checkout-map-fullscreen', {
      center: [tempLat.value, tempLon.value],
      zoom: 16,
      zoomControl: false,
      attributionControl: false
    });

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
    }).addTo(map);

    map.on('moveend', () => {
      const center = map.getCenter();
      tempLat.value = center.lat;
      tempLon.value = center.lng;
    });

    map.on('click', (e) => {
      map.panTo(e.latlng);
    });

    fullscreenMapInstance = map;
    fullscreenMapLoaded.value = true;
  } catch (e) {
    console.error('Fullscreen map init error', e);
    fullscreenMapLoaded.value = true;
  }
};

const zoomInFullscreen = () => {
  if (fullscreenMapInstance) {
    fullscreenMapInstance.zoomIn();
  }
};

const zoomOutFullscreen = () => {
  if (fullscreenMapInstance) {
    fullscreenMapInstance.zoomOut();
  }
};

const enterFullscreen = () => {
  track('map_fullscreen_open', {
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });

  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
    mapLoaded.value = false;
  }

  isFullscreen.value = true;
  nextTick(() => {
    initFullscreenMap();
  });
};

const exitFullscreen = () => {
  track('map_fullscreen_close', {
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });

  if (fullscreenMapInstance) {
    fullscreenMapInstance.remove();
    fullscreenMapInstance = null;
    fullscreenMapLoaded.value = false;
  }

  isFullscreen.value = false;
  nextTick(() => {
    initMap();
  });
};

const confirmFullscreenSelection = () => {
  track('map_fullscreen_confirm', {
    lat_rounded: Math.round(tempLat.value * 100) / 100,
    lon_rounded: Math.round(tempLon.value * 100) / 100,
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });

  localData.value.lat = tempLat.value;
  localData.value.lon = tempLon.value;
  localData.value.address_source = 'fullscreen_map';
  hasSelectedPoint.value = true;

  if (fullscreenMapInstance) {
    fullscreenMapInstance.remove();
    fullscreenMapInstance = null;
    fullscreenMapLoaded.value = false;
  }

  isFullscreen.value = false;
  nextTick(() => {
    initMap();
    if (cartStore.vendorId) {
      zoneChecker.checkZone(cartStore.vendorId, localData.value.lat, localData.value.lon);
    }
    zoneChecker.reverseGeocode(localData.value.lat, localData.value.lon);
  });
};

// --- Zone check watchers for telemetry (P0.4) ---
watch(zoneChecker.zoneStatus, (status) => {
  if (status === 'inside') {
    track('zone_check_pass', {
      lat_rounded: Math.round(localData.value.lat * 100) / 100,
      lon_rounded: Math.round(localData.value.lon * 100) / 100,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });
  } else if (status === 'outside') {
    track('zone_check_failed', {
      lat_rounded: Math.round(localData.value.lat * 100) / 100,
      lon_rounded: Math.round(localData.value.lon * 100) / 100,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });
  }
});

// --- Geolocation ---
const tryGeolocate = async () => {
  track('geolocate_requested', {
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });

  localData.value.geolocate_attempted = true;
  localData.value.geolocate_status = 'loading';

  try {
    const { lat, lon, accuracy } = await geo.request();

    track('geolocate_success', {
      accuracy_m: accuracy,
      lat_rounded: Math.round(lat * 100) / 100,
      lon_rounded: Math.round(lon * 100) / 100,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });

    localData.value.geolocate_status = 'success';
    localData.value.geolocate_accuracy_m = accuracy;
    isLowGpsAccuracy.value = accuracy > 200;

    // Защита от перезаписи ручного выбора (при автоматическом запуске)
    if (userHasMovedMap.value || (localData.value.address_source && localData.value.address_source !== 'geolocate')) return;

    localData.value.lat = lat;
    localData.value.lon = lon;
    localData.value.address_source = 'geolocate';
    hasSelectedPoint.value = true; // геолокация = явный выбор точки

    if (fullscreenMapInstance) {
      fullscreenMapInstance.setView([lat, lon], 16);
      tempLat.value = lat;
      tempLon.value = lon;
    } else if (mapInstance) {
      skipNextMoveEnd = true;
      mapInstance.setView([lat, lon], 15);

      track('geolocate_applied_to_map', {
        accuracy_m: accuracy,
        lat_rounded: Math.round(lat * 100) / 100,
        lon_rounded: Math.round(lon * 100) / 100,
        time_from_step_open_ms: Date.now() - stepOpenedAt,
      });
    }

    // Zone check + reverse after geolocate
    if (cartStore.vendorId) {
      zoneChecker.checkZone(cartStore.vendorId, lat, lon, 200);
    }
    zoneChecker.reverseGeocode(lat, lon, 200);

  } catch ({ code }) {
    const eventMap = {
      denied: 'geolocate_denied',
      timeout: 'geolocate_timeout',
      unavailable: 'geolocate_error',
      error: 'geolocate_error',
    };
    const statusCode = code || 'error';
    localData.value.geolocate_status = statusCode;
    localData.value.geolocate_accuracy_m = null;
    isLowGpsAccuracy.value = false;

    track(eventMap[statusCode] ?? 'geolocate_error', {
      code: statusCode,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });
  }
};

const handleRetryGeo = () => {
  track('geolocate_retry_clicked', {
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });
  userHasMovedMap.value = false;
  isLowGpsAccuracy.value = false;

  localData.value.geolocate_attempted = true;
  localData.value.geolocate_status = 'loading';

  geo.retry()
    .then(({ lat, lon, accuracy }) => {
      track('geolocate_success', {
        accuracy_m: accuracy,
        lat_rounded: Math.round(lat * 100) / 100,
        lon_rounded: Math.round(lon * 100) / 100,
        time_from_step_open_ms: Date.now() - stepOpenedAt,
      });
      localData.value.geolocate_status = 'success';
      localData.value.geolocate_accuracy_m = accuracy;
      isLowGpsAccuracy.value = accuracy > 200;

      localData.value.lat = lat;
      localData.value.lon = lon;
      localData.value.address_source = 'geolocate';
      hasSelectedPoint.value = true;

      if (fullscreenMapInstance) {
        fullscreenMapInstance.setView([lat, lon], 16);
        tempLat.value = lat;
        tempLon.value = lon;
      } else if (mapInstance) {
        skipNextMoveEnd = true;
        mapInstance.setView([lat, lon], 15);
        track('geolocate_applied_to_map', {
          accuracy_m: accuracy,
          lat_rounded: Math.round(lat * 100) / 100,
          lon_rounded: Math.round(lon * 100) / 100,
          time_from_step_open_ms: Date.now() - stepOpenedAt,
        });
      }
      if (cartStore.vendorId) {
        zoneChecker.checkZone(cartStore.vendorId, lat, lon, 200);
      }
      zoneChecker.reverseGeocode(lat, lon, 200);
    })
    .catch(({ code }) => {
      const eventMap = {
        denied: 'geolocate_denied',
        timeout: 'geolocate_timeout',
        unavailable: 'geolocate_error',
        error: 'geolocate_error',
      };
      const statusCode = code || 'error';
      localData.value.geolocate_status = statusCode;
      localData.value.geolocate_accuracy_m = null;
      isLowGpsAccuracy.value = false;

      track(eventMap[statusCode] ?? 'geolocate_error', {
        code: statusCode,
        time_from_step_open_ms: Date.now() - stepOpenedAt,
      });
    });
};

const updateType = (type) => {
  localData.value.delivery_type = type;
};

// Валидация: телефон + не вне зоны (если pickup — зона не проверяется)
// Блокируем «Далее» только если пользователь явно выбрал точку и она вне зоны
const isValid = computed(() => {
  if (!localData.value.phone || localData.value.phone.length < 8) return false;
  if (localData.value.delivery_type === 'delivery' && hasSelectedPoint.value && zoneStatus.value === 'outside') return false;
  return true;
});

const handleNext = () => {
  if (isValid.value) {
    emit('next-step');
  }
};
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
