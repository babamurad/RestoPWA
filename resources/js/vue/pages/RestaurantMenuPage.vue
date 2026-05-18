<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl pb-24 font-inter">
    <!-- Back Navigation Bar -->
    <div class="flex items-center gap-3 mb-6">
      <router-link to="/" class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700/50 flex items-center justify-center text-slate-300 hover:text-orange-400 hover:border-orange-500/30 transition-all active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </router-link>
      <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Назад к списку</span>
    </div>

    <!-- Loading Skeleton for Header -->
    <div v-if="store.loading && !store.currentRestaurant" class="bg-slate-800/30 border border-slate-800 rounded-3xl p-6 mb-8 animate-pulse">
      <div class="h-8 bg-slate-800 rounded-lg w-2/3 mb-4"></div>
      <div class="h-4 bg-slate-800 rounded-lg w-1/2 mb-6"></div>
      <div class="flex gap-4">
        <div class="h-4 bg-slate-800 rounded-lg w-16"></div>
        <div class="h-4 bg-slate-800 rounded-lg w-16"></div>
      </div>
    </div>

    <!-- Restaurant Cover & General Info -->
    <div v-else-if="store.currentRestaurant" class="relative overflow-hidden rounded-3xl mb-8 bg-slate-800/40 border border-slate-800/80 p-6 shadow-xl group select-none">
      <!-- Ambient backing glow -->
      <div class="absolute -right-16 -top-16 w-52 h-52 rounded-full bg-orange-500/5 blur-3xl group-hover:scale-110 transition-transform duration-700"></div>

      <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
        <!-- Thumbnail -->
        <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden bg-slate-900 border border-slate-850 flex-shrink-0 shadow-md">
          <img 
            :src="store.currentRestaurant.image ? (store.currentRestaurant.image.startsWith('http') ? store.currentRestaurant.image : '/storage/' + store.currentRestaurant.image) : 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80'" 
            :alt="store.currentRestaurant.name"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
        </div>

        <!-- Info details -->
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl md:text-2xl font-black text-slate-100 font-outfit tracking-wide leading-tight">{{ store.currentRestaurant.name }}</h2>
            <div class="px-2 py-0.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-black flex items-center gap-1">
              ★ {{ store.currentRestaurant.rating || '4.5' }}
            </div>
          </div>
          <p class="text-xs text-slate-400 mt-2 font-medium leading-relaxed max-w-xl">{{ store.currentRestaurant.description || 'Изысканные фирменные блюда из свежих ингредиентов с быстрой доставкой.' }}</p>
          
          <!-- Key Delivery params -->
          <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-4 pt-4 border-t border-slate-700/20 text-xs text-slate-400 font-semibold">
            <span class="flex items-center gap-1.5">
              ⏱ Время: <strong class="text-slate-200">{{ store.currentRestaurant.delivery_time || '20-30' }} мин</strong>
            </span>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-700 hidden sm:inline"></span>
            <span class="flex items-center gap-1.5">
              🚲 Доставка: <strong class="text-slate-200">{{ parseFloat(store.currentRestaurant.delivery_fee) === 0 ? 'Бесплатно' : store.currentRestaurant.delivery_fee + ' TMT' }}</strong>
            </span>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-700 hidden sm:inline"></span>
            <span class="flex items-center gap-1.5">
              💰 Мин. заказ: <strong class="text-slate-200">{{ store.currentRestaurant.min_order || '15' }} TMT</strong>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Menu Structure -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      <!-- Search & Filters Sidebar (Desktop) -->
      <div class="lg:col-span-1 space-y-6">
        <!-- Search bar -->
        <div class="bg-slate-800/40 border border-slate-800/80 p-4 rounded-2xl shadow-md space-y-3">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 font-outfit">Поиск блюд</h4>
          <div class="relative">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Поиск по меню..." 
              class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-9 pr-4 py-2.5 text-xs text-slate-200 font-bold focus:outline-none focus:border-orange-500/50 transition-colors placeholder:text-slate-650"
            />
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-slate-500 absolute left-3 top-3">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.602 10.602Z" />
            </svg>
          </div>
        </div>

        <!-- Price Range local filter -->
        <div class="bg-slate-800/40 border border-slate-800/80 p-4 rounded-2xl shadow-md space-y-3.5 select-none">
          <div class="flex justify-between items-baseline">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 font-outfit">Диапазон цены</h4>
            <span class="text-[10px] text-orange-400 font-black">{{ maxPriceLimit }} TMT</span>
          </div>
          <input 
            v-model="maxPriceLimit" 
            type="range" 
            :min="store.priceFilters.min || 0" 
            :max="store.priceFilters.max || 1000" 
            class="w-full h-1 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-orange-500"
          />
          <div class="flex justify-between text-[10px] text-slate-500 font-bold">
            <span>Мин: {{ store.priceFilters.min }} TMT</span>
            <span>Макс: {{ store.priceFilters.max }} TMT</span>
          </div>
        </div>
      </div>

      <!-- Categories & Products Area (Takes 3 columns) -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Categories Tabs Header -->
        <div class="bg-slate-800/20 p-1.5 rounded-2xl border border-slate-800/80 flex gap-2 overflow-x-auto scrollbar-hide select-none">
          <button 
            @click="selectCategory(null)"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap active:scale-95 duration-200',
              activeCategory === null 
                ? 'bg-orange-500 text-white shadow-md shadow-orange-500/10' 
                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
            ]"
          >
            🍽️ Все меню
          </button>
          
          <button 
            v-for="cat in store.categories" 
            :key="cat.id"
            @click="selectCategory(cat.id)"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap active:scale-95 duration-200',
              activeCategory === cat.id 
                ? 'bg-orange-500 text-white shadow-md shadow-orange-500/10' 
                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
            ]"
          >
            {{ cat.name }}
          </button>
        </div>

        <!-- Products Loading skeletons -->
        <div v-if="store.loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="n in 4" :key="n" class="bg-slate-800/30 border border-slate-800/60 rounded-3xl p-4 animate-pulse flex gap-4">
            <div class="w-24 h-24 bg-slate-800 rounded-2xl flex-shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-slate-800 rounded-lg w-2/3"></div>
              <div class="h-3 bg-slate-800 rounded-lg w-1/3"></div>
              <div class="h-4 bg-slate-800 rounded-lg w-1/4 mt-4"></div>
            </div>
          </div>
        </div>

        <!-- Empty Menu State -->
        <div v-else-if="filteredProducts.length === 0" class="text-center py-16 bg-slate-800/10 border border-slate-850/60 rounded-3xl select-none">
          <span class="text-4xl mb-3 block">🍽️</span>
          <h4 class="text-sm font-extrabold text-slate-300 mb-1 font-outfit">В этой категории пока пусто</h4>
          <p class="text-xs text-slate-500">Попробуйте заглянуть в другие вкладки или изменить фильтры.</p>
        </div>

        <!-- Products List -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="product in filteredProducts" 
            :key="product.id"
            @click="openProductModal(product)"
            class="bg-slate-800/40 backdrop-blur-sm border border-slate-800/80 rounded-2xl p-4 hover:border-slate-700/40 hover:bg-slate-800/65 transition-all duration-300 shadow-md flex gap-4 items-center group cursor-pointer"
          >
            <!-- Product image -->
            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-900 border border-slate-850 flex-shrink-0 shadow-sm relative">
              <img 
                :src="product.image_url || 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80'" 
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                @error="handleImageError"
              />
              <div v-if="!product.is_available" class="absolute inset-0 bg-slate-950/70 flex items-center justify-center text-[8px] font-black text-rose-400 uppercase tracking-widest text-center px-1">
                Нет в наличии
              </div>
            </div>

            <!-- Product info -->
            <div class="flex-1 min-w-0 flex flex-col justify-between h-24">
              <div>
                <h5 class="text-sm font-bold text-slate-100 group-hover:text-orange-400 transition-colors truncate">
                  {{ product.name }}
                </h5>
                <p v-if="product.weight_g" class="text-[10px] text-slate-455 mt-0.5 font-bold">Вес: {{ product.weight_g }} г</p>
                <p v-if="product.description" class="text-xs text-slate-400 mt-1.5 line-clamp-2 leading-relaxed font-medium">
                  {{ product.description }}
                </p>
              </div>

              <!-- Price & CTA bar -->
              <div class="flex justify-between items-center mt-2.5">
                <span class="text-sm font-black text-orange-400 font-outfit">{{ product.price }} TMT</span>
                <button 
                  :disabled="!product.is_available"
                  class="px-3.5 py-1.5 bg-slate-900 border border-slate-800/80 hover:bg-orange-500 hover:text-white disabled:hover:bg-slate-900 disabled:hover:text-slate-500 disabled:opacity-40 hover:border-transparent text-[11px] font-extrabold rounded-xl transition-all active:scale-90"
                >
                  Выбрать
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Modal component -->
    <ProductModal 
      v-if="selectedProduct"
      :product="selectedProduct"
      :show="showModal"
      @close="closeProductModal"
      @add-to-cart="handleAddToCart"
    />

    <!-- Dynamically Floating Glassmorphic Checkout Drawer Bar -->
    <transition name="drawer-fade">
      <div 
        v-if="showFloatingCartBar" 
        class="fixed bottom-20 left-4 right-4 md:bottom-6 md:left-1/2 md:-translate-x-1/2 md:right-auto z-40 max-w-xl w-[calc(100%-2rem)] md:w-[480px] bg-slate-900/80 backdrop-blur-xl border border-slate-700/50 p-4 rounded-2xl shadow-2xl flex items-center justify-between select-none"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center border border-orange-500/20">
            <span class="text-lg">🛒</span>
          </div>
          <div>
            <span class="text-[10px] text-slate-450 block font-black uppercase tracking-wider">Корзина</span>
            <span class="text-xs text-slate-200 font-extrabold">
              {{ cartStore.totalItemsCount }} блюда на сумму <strong class="text-orange-400 ml-0.5 font-black">{{ cartStore.subtotal }} TMT</strong>
            </span>
          </div>
        </div>
        
        <router-link 
          to="/cart" 
          class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all active:scale-95 flex items-center gap-1"
        >
          Оформить заказ
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-3.5 h-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </router-link>
      </div>
    </transition>

    <!-- Dynamic micro-toast notification -->
    <transition name="toast-fade">
      <div 
        v-if="toastVisible" 
        class="fixed bottom-36 left-1/2 -translate-x-1/2 md:bottom-24 z-50 px-4 py-2.5 rounded-2xl bg-emerald-500 border border-emerald-400/20 text-white font-extrabold text-xs shadow-lg flex items-center gap-2 select-none animate-bounce"
      >
        <span>✓</span> {{ toastMessage }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useRestaurantsStore } from '../stores/restaurants';
import { useCartStore } from '../stores/cart';
import ProductModal from '../components/ProductModal.vue';

const route = useRoute();
const store = useRestaurantsStore();
const cartStore = useCartStore();

const activeCategory = ref(null);
const searchQuery = ref('');
const maxPriceLimit = ref(1000);

// Modal state
const selectedProduct = ref(null);
const showModal = ref(false);

// Toast notification state
const toastVisible = ref(false);
const toastMessage = ref('');

// Dynamic Cart Bar visibility
const showFloatingCartBar = computed(() => {
  return !cartStore.isEmpty && store.currentRestaurant && cartStore.vendorId === store.currentRestaurant.id;
});

// Handle image load error fallback
const handleImageError = (e) => {
  e.target.src = 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80';
};

// Handle category changes and trigger Pinia fetch
const selectCategory = (catId) => {
  activeCategory.value = catId;
  store.fetchRestaurantMenu(route.params.slug, catId);
};

// Set maximum price limit once price filters are loaded from store
watch(() => store.priceFilters, (newVal) => {
  if (newVal && newVal.max) {
    maxPriceLimit.value = newVal.max;
  }
}, { immediate: true });

// Filter items locally based on search query and price range
const filteredProducts = computed(() => {
  if (!store.products) return [];
  let list = [...store.products];

  // Search query filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(p => p.name.toLowerCase().includes(q) || (p.description && p.description.toLowerCase().includes(q)));
  }

  // Price range limit filter
  list = list.filter(p => p.price <= maxPriceLimit.value);

  return list;
});

const openProductModal = (product) => {
  selectedProduct.value = product;
  showModal.value = true;
};

const closeProductModal = () => {
  showModal.value = false;
  setTimeout(() => {
    selectedProduct.value = null;
  }, 300); // Allow modal animation to complete
};

const handleAddToCart = (data) => {
  if (!store.currentRestaurant) return;

  const result = cartStore.addItem(data.product, data.quantity, store.currentRestaurant);

  if (result.status === 'mismatch') {
    if (window.Swal) {
      window.Swal.fire({
        title: 'Сменить ресторан?',
        text: `В вашей корзине уже находятся блюда из заведения "${result.currentVendorName}". Хотите очистить корзину и начать новый заказ в "${store.currentRestaurant.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Очистить и добавить',
        cancelButtonText: 'Отмена',
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#334155',
        background: '#0f172a',
        color: '#f8fafc',
        reverseButtons: true,
        customClass: { popup: 'rounded-3xl border border-slate-850 shadow-2xl' }
      }).then((swalResult) => {
        if (swalResult.isConfirmed) {
          cartStore.clearCart();
          cartStore.addItem(data.product, data.quantity, store.currentRestaurant);
          triggerToast(`${data.product.name} добавлено в корзину (${data.quantity} шт)!`);
        }
      });
    } else {
      const confirmClear = window.confirm(
        `В вашей корзине уже находятся блюда из ресторана "${result.currentVendorName}". Желаете очистить корзину и начать новый заказ в "${store.currentRestaurant.name}"?`
      );
      if (confirmClear) {
        cartStore.clearCart();
        cartStore.addItem(data.product, data.quantity, store.currentRestaurant);
        triggerToast(`${data.product.name} добавлено в корзину (${data.quantity} шт)!`);
      }
    }
  } else if (result.status === 'success') {
    triggerToast(`${data.product.name} добавлено в корзину (${data.quantity} шт)!`);
  }
};

const triggerToast = (msg) => {
  toastMessage.value = msg;
  toastVisible.value = true;
  setTimeout(() => {
    toastVisible.value = false;
  }, 2500);
};

onMounted(() => {
  store.fetchRestaurantMenu(route.params.slug);
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Toast Animation */
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translate(-50%, 15px);
}

/* Drawer Animation */
.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
  transform: translateY(40px) scale(0.95);
}
</style>
