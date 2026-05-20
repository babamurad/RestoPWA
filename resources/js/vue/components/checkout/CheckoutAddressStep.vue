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
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none pb-8" style="z-index: 1000" v-show="mapLoaded && geo.status.value !== 'loading'">
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

    <!-- Next Button -->
    <button 
      @click="handleNext"
      :disabled="!isValid"
      class="w-full mt-6 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 disabled:from-slate-800 disabled:to-slate-850 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-extrabold text-sm rounded-xl shadow-lg transition-all active:scale-[0.98]"
    >
      Продолжить
    </button>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useGeolocate } from '../../composables/useGeolocate';
import { useTelemetry } from '../../composables/useTelemetry';

// Feature flag: VITE_CHECKOUT_GEOLOCATE=false в .env отключает фичу
const GEO_ENABLED = import.meta.env.VITE_CHECKOUT_GEOLOCATE !== 'false';

const props = defineProps({
  orderData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update-data', 'next-step']);

const localData = ref({ 
  ...props.orderData,
  lat: props.orderData.lat || 39.0886,
  lon: props.orderData.lon || 63.5593,
  address_source: props.orderData.address_source || null,
});

const mapLoaded = ref(false);
let mapInstance = null;

// Защита от перезаписи ручного выбора (FR-4)
// true = пользователь уже вручную двигал карту
const userHasMovedMap = ref(false);
// Флаг: первое программное перемещение (автогеолокация) не считается «ручным»
let skipNextMoveEnd = false;

const geo = useGeolocate({ enabled: GEO_ENABLED });
const { track } = useTelemetry();

// Время открытия шага для телеметрии
const stepOpenedAt = Date.now();

// Человеко-читаемые сообщения об ошибках гео
const geoError = computed(() => {
  switch (geo.status.value) {
    case 'denied':
      return 'Доступ к геолокации запрещён. Выберите точку на карте вручную.';
    case 'timeout':
      return 'Не удалось получить геопозицию (таймаут). Попробуйте снова или выберите точку вручную.';
    case 'unavailable':
      return 'Геолокация недоступна на этом устройстве. Выберите точку на карте вручную.';
    case 'error':
      return 'Не удалось получить геопозицию. Выберите точку на карте вручную.';
    default:
      return null;
  }
});

onMounted(() => {
  if (localData.value.delivery_type === 'delivery') {
    initMap();
  }
});

onUnmounted(() => {
  if (mapInstance && typeof mapInstance.remove === 'function') {
    try {
      mapInstance.remove();
    } catch(e) {}
  }
});

watch(() => localData.value.delivery_type, (newType) => {
  if (newType === 'delivery' && !mapInstance) {
    nextTick(() => {
      initMap();
    });
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
      // Пользователь двинул карту вручную
      userHasMovedMap.value = true;
      const center = map.getCenter();
      localData.value.lat = center.lat;
      localData.value.lon = center.lng;
      // Ручное перемещение меняет источник адреса
      if (localData.value.address_source === 'geolocate') {
        localData.value.address_source = 'map_pin';
      }
    });

    map.on('click', (e) => {
      map.panTo(e.latlng);
    });
    
    mapInstance = map;
    mapLoaded.value = true;

    // Запускаем геолокацию после инициализации карты
    if (GEO_ENABLED) {
      tryGeolocate();
    }
  } catch (e) {
    console.error('Map init error', e);
    mapLoaded.value = true;
  }
};

const tryGeolocate = async () => {
  track('geolocate_requested', {
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });

  try {
    const { lat, lon, accuracy } = await geo.request();

    track('geolocate_success', {
      accuracy_m: accuracy,
      lat_rounded: Math.round(lat * 100) / 100,
      lon_rounded: Math.round(lon * 100) / 100,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });

    // Не перезаписываем, если пользователь уже двигал карту вручную (FR-4)
    if (userHasMovedMap.value) return;

    localData.value.lat = lat;
    localData.value.lon = lon;
    localData.value.address_source = 'geolocate';

    if (mapInstance) {
      skipNextMoveEnd = true; // программное перемещение не считается ручным
      mapInstance.setView([lat, lon], 15);

      track('geolocate_applied_to_map', {
        accuracy_m: accuracy,
        lat_rounded: Math.round(lat * 100) / 100,
        lon_rounded: Math.round(lon * 100) / 100,
        time_from_step_open_ms: Date.now() - stepOpenedAt,
      });
    }
  } catch ({ code }) {
    const eventMap = {
      denied: 'geolocate_denied',
      timeout: 'geolocate_timeout',
      unavailable: 'geolocate_error',
      error: 'geolocate_error',
    };
    track(eventMap[code] ?? 'geolocate_error', {
      code,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });
  }
};

const handleRetryGeo = () => {
  track('geolocate_retry_clicked', {
    time_from_step_open_ms: Date.now() - stepOpenedAt,
  });
  // Сброс флага ручного перемещения — пользователь явно хочет гео
  userHasMovedMap.value = false;
  geo.retry().then(({ lat, lon, accuracy }) => {
    track('geolocate_success', {
      accuracy_m: accuracy,
      lat_rounded: Math.round(lat * 100) / 100,
      lon_rounded: Math.round(lon * 100) / 100,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });

    localData.value.lat = lat;
    localData.value.lon = lon;
    localData.value.address_source = 'geolocate';

    if (mapInstance) {
      skipNextMoveEnd = true;
      mapInstance.setView([lat, lon], 15);
      track('geolocate_applied_to_map', {
        accuracy_m: accuracy,
        lat_rounded: Math.round(lat * 100) / 100,
        lon_rounded: Math.round(lon * 100) / 100,
        time_from_step_open_ms: Date.now() - stepOpenedAt,
      });
    }
  }).catch(({ code }) => {
    const eventMap = {
      denied: 'geolocate_denied',
      timeout: 'geolocate_timeout',
      unavailable: 'geolocate_error',
      error: 'geolocate_error',
    };
    track(eventMap[code] ?? 'geolocate_error', {
      code,
      time_from_step_open_ms: Date.now() - stepOpenedAt,
    });
  });
};

const updateType = (type) => {
  localData.value.delivery_type = type;
};

const isValid = computed(() => {
  if (!localData.value.phone || localData.value.phone.length < 8) return false;
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
