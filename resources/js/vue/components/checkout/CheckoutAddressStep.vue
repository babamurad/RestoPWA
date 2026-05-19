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
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none pb-8" v-show="mapLoaded">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-orange-500 drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>

        <!-- Spinner overlay -->
        <div v-if="!mapLoaded" class="absolute inset-0 flex items-center justify-center bg-slate-900 z-10">
          <div class="w-6 h-6 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin"></div>
        </div>
      </div>

      <div>
        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Улица, дом</label>
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
  lon: props.orderData.lon || 63.5593
});

const mapLoaded = ref(false);
let mapInstance = null;

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
      // Dynamically load Leaflet CSS
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(link);

      // Dynamically load Leaflet JS
      await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
        script.type = "text/javascript";
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
      const center = map.getCenter();
      localData.value.lat = center.lat;
      localData.value.lon = center.lng;
    });
    
    mapInstance = map;
    mapLoaded.value = true;
  } catch (e) {
    console.error('Map init error', e);
    mapLoaded.value = true; // prevent infinite loader
  }
};

const updateType = (type) => {
  localData.value.delivery_type = type;
};

const isValid = computed(() => {
  if (!localData.value.phone || localData.value.phone.length < 8) return false;
  if (localData.value.delivery_type === 'delivery') {
    return localData.value.address && localData.value.address.length > 3;
  }
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
