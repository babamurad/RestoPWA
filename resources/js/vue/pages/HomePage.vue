<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl pb-24">
    <!-- Header address bar mockup with glassmorphism -->
    <div class="flex items-center justify-between mb-8 bg-white dark:bg-slate-800/60 backdrop-blur-md border border-slate-300 dark:border-slate-700/50 p-4 rounded-2xl shadow-lg">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center border border-orange-500/20">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
          </svg>
        </div>
        <div>
          <span class="text-xs text-slate-600 dark:text-slate-400 block font-medium uppercase tracking-wider">Доставка по адресу</span>
          <span class="text-sm text-slate-900 dark:text-slate-100 font-bold hover:text-orange-400 cursor-pointer transition-colors flex items-center gap-1">
            ул. Туркменбаши, д. 45
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3 text-orange-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
          </span>
        </div>
      </div>
      
      <!-- Search toggle or filter button -->
      <button class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-orange-400 transition-all flex items-center justify-center text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700/50 active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.24 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
        </svg>
      </button>
    </div>

    <!-- Stunning Hero Promo Banner with smooth gradients -->
    <div class="relative overflow-hidden rounded-3xl mb-10 bg-gradient-to-tr from-orange-600 to-amber-500 p-6 md:p-8 shadow-xl shadow-orange-950/20 group">
      <!-- Decorative circles -->
      <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-white/10 blur-xl group-hover:scale-110 transition-transform duration-700"></div>
      <div class="absolute -left-10 -top-10 w-32 h-32 rounded-full bg-white/10 blur-lg group-hover:scale-110 transition-transform duration-700"></div>
      
      <div class="relative z-10 max-w-xs md:max-w-md">
        <span class="inline-block px-3 py-1 rounded-full bg-white/20 text-xs font-bold text-white uppercase tracking-wider mb-3 backdrop-blur-sm">
          Скидка 20%
        </span>
        <h2 class="text-2xl md:text-3xl font-extrabold text-white leading-tight mb-2 font-outfit">
          Первый заказ вкуснее!
        </h2>
        <p class="text-sm text-orange-100 mb-6 font-medium">
          Заказывайте в любимых ресторанах со скидкой на доставку прямо сейчас.
        </p>
        <button class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 dark:hover:bg-slate-100 text-orange-600 font-bold text-sm shadow-md transition-all active:scale-95 duration-200">
          Выбрать ресторан
        </button>
      </div>
    </div>

    <!-- Horizontal Categories Carousel -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 tracking-wide font-outfit">Популярные категории</h3>
        <span class="text-xs text-orange-500 font-bold hover:underline cursor-pointer">Все</span>
      </div>
      <div class="flex gap-4 overflow-x-auto pb-3 scrollbar-hide -mx-4 px-4 mask-right">
        <div 
          v-for="cat in categories" 
          :key="cat.id" 
          @click="activeCategory = cat.id"
          :class="[
            'flex flex-col items-center justify-center p-3 rounded-2xl min-w-[76px] cursor-pointer transition-all border shadow-sm active:scale-95 duration-200',
            activeCategory === cat.id 
              ? 'bg-gradient-to-b from-orange-500 to-orange-600 border-orange-400 text-white shadow-orange-500/20 scale-105' 
              : 'bg-white dark:bg-slate-800/40 border-slate-300 dark:border-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 hover:border-slate-300 dark:hover:border-slate-600'
          ]"
        >
          <span class="text-2xl mb-1.5">{{ cat.icon }}</span>
          <span class="text-xs font-bold whitespace-nowrap">{{ cat.name }}</span>
        </div>
      </div>
    </div>

    <!-- Restaurants Grid -->
    <div>
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 tracking-wide font-outfit">Выбирайте из лучших мест</h3>
        <div class="flex gap-2">
          <button 
            @click="sortBy = 'rating'"
            :class="['text-xs px-3 py-1.5 rounded-lg border font-bold transition-all', sortBy === 'rating' ? 'bg-orange-500/10 border-orange-500/40 text-orange-400' : 'bg-white dark:bg-slate-800/40 border-slate-300 dark:border-slate-700/50 text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800']"
          >
            По рейтингу
          </button>
          <button 
            @click="sortBy = 'delivery'"
            :class="['text-xs px-3 py-1.5 rounded-lg border font-bold transition-all', sortBy === 'delivery' ? 'bg-orange-500/10 border-orange-500/40 text-orange-400' : 'bg-white dark:bg-slate-800/40 border-slate-300 dark:border-slate-700/50 text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800']"
          >
            Быстрая доставка
          </button>
        </div>
      </div>

      <!-- Skeleton Loaders if fetching data -->
      <div v-if="store.loading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="n in 4" :key="n" class="bg-white dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/60 rounded-3xl p-4 animate-pulse">
          <div class="w-full h-44 bg-white dark:bg-slate-800/60 rounded-2xl mb-4"></div>
          <div class="h-5 bg-white dark:bg-slate-800/60 rounded-lg w-2/3 mb-2"></div>
          <div class="h-4 bg-white dark:bg-slate-800/60 rounded-lg w-1/3 mb-4"></div>
          <div class="flex justify-between mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/40">
            <div class="h-4 bg-white dark:bg-slate-800/60 rounded-lg w-1/4"></div>
            <div class="h-4 bg-white dark:bg-slate-800/60 rounded-lg w-1/4"></div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredRestaurants.length === 0" class="text-center py-16">
        <span class="text-4xl mb-4 block">🔍</span>
        <h4 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-1">Ничего не найдено</h4>
        <p class="text-xs text-slate-600 dark:text-slate-400">Попробуйте изменить параметры фильтрации.</p>
      </div>

      <!-- Restaurants list -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div 
          v-for="restaurant in filteredRestaurants" 
          :key="restaurant.id"
          @click="openMenu(restaurant.slug)"
          class="bg-white dark:bg-slate-800/40 backdrop-blur-sm border border-slate-200 dark:border-slate-800/80 rounded-3xl p-4 hover:border-slate-300 dark:hover:border-slate-700/50 hover:bg-white dark:hover:bg-slate-800/60 transition-all duration-300 shadow-lg hover:shadow-orange-950/5 group cursor-pointer"
        >
          <!-- Thumbnail Image container -->
          <div class="relative w-full h-44 rounded-2xl overflow-hidden mb-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/60 shadow-inner">
            <img 
              :src="restaurant.image ? (restaurant.image.startsWith('http') ? restaurant.image : '/storage/' + restaurant.image) : 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80'" 
              :alt="restaurant.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              @error="handleImageError"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-100/70 dark:from-slate-950/70 via-transparent to-transparent"></div>
            
            <!-- Rating Badge -->
            <div class="absolute top-3 right-3 px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900/90 backdrop-blur-md text-amber-400 text-xs font-black flex items-center gap-1 border border-slate-200 dark:border-slate-800/80 shadow-md">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5">
                <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
              </svg>
              {{ restaurant.rating || '4.5' }}
            </div>

            <!-- Delivery time badge -->
            <div class="absolute bottom-3 left-3 px-2.5 py-1 rounded-xl bg-orange-500 text-white text-xs font-extrabold flex items-center gap-1 shadow-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
              {{ restaurant.delivery_time || '25-35' }} мин
            </div>
          </div>

          <!-- Restaurant Metadata -->
          <div>
            <h4 class="text-base font-bold text-slate-900 dark:text-slate-100 group-hover:text-orange-400 transition-colors flex items-center justify-between">
              {{ restaurant.name }}
            </h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 line-clamp-1 font-medium">{{ restaurant.description || 'Изысканные блюда от шеф-повара с доставкой' }}</p>
            
            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-300 dark:border-slate-700/30 text-xs text-slate-600 dark:text-slate-400 font-semibold">
              <span class="flex items-center gap-1">
                Доставка: <strong class="text-slate-800 dark:text-slate-200">{{ parseFloat(restaurant.delivery_fee) === 0 ? 'Бесплатно' : restaurant.delivery_fee + ' TMT' }}</strong>
              </span>
              <span class="w-1.5 h-1.5 rounded-full bg-slate-100 dark:bg-slate-700"></span>
              <span class="flex items-center gap-1">
                Мин. заказ: <strong class="text-slate-800 dark:text-slate-200">{{ restaurant.min_order || '15' }} TMT</strong>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useRestaurantsStore } from '../stores/restaurants';

const router = useRouter();
const store = useRestaurantsStore();

const activeCategory = ref('all');
const sortBy = ref('rating');

const categories = ref([
  { id: 'all', name: 'Все', icon: '🍽️' },
  { id: 'burgers', name: 'Бургеры', icon: '🍔' },
  { id: 'pizza', name: 'Пицца', icon: '🍕' },
  { id: 'sushi', name: 'Суши', icon: '🍣' },
  { id: 'desserts', name: 'Сладости', icon: '🍰' },
  { id: 'drinks', name: 'Напитки', icon: '🥤' }
]);

// Fallback mock data in case API returns nothing
const mockRestaurantsFallback = [
  {
    id: 1,
    name: 'Burger & Co',
    slug: 'burger-co',
    rating: '4.8',
    delivery_time: '20-30',
    delivery_fee: '5',
    min_order: '30',
    description: 'Легендарные бургеры из мраморной говядины с авторскими соусами',
    category: 'burgers'
  },
  {
    id: 2,
    name: 'Bella Italia',
    slug: 'bella-italia',
    rating: '4.7',
    delivery_time: '30-40',
    delivery_fee: '0',
    min_order: '50',
    description: 'Настоящая неаполитанская пицца на тонком тесте из дровяной печи',
    category: 'pizza'
  },
  {
    id: 3,
    name: 'Sushi master',
    slug: 'sushi-master',
    rating: '4.9',
    delivery_time: '40-50',
    delivery_fee: '8',
    min_order: '40',
    description: 'Свежайшие роллы, суши и гунканы по традиционным рецептам',
    category: 'sushi'
  }
];

const handleImageError = (e) => {
  e.target.src = 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80'; // Fallback food pattern
};

const openMenu = (slug) => {
  router.push({ name: 'restaurant-menu', params: { slug } });
};

const filteredRestaurants = computed(() => {
  let list = store.restaurants && store.restaurants.length > 0 
    ? [...store.restaurants] 
    : [...mockRestaurantsFallback];
  
  // Filter by category
  if (activeCategory.value !== 'all') {
    list = list.filter(r => r.category === activeCategory.value || (r.tags && r.tags.includes(activeCategory.value)));
  }
  
  // Sort
  if (sortBy.value === 'rating') {
    list.sort((a, b) => parseFloat(b.rating || 0) - parseFloat(a.rating || 0));
  } else if (sortBy.value === 'delivery') {
    list.sort((a, b) => {
      const timeA = parseInt(a.delivery_time?.split('-')[0] || 999);
      const timeB = parseInt(b.delivery_time?.split('-')[0] || 999);
      return timeA - timeB;
    });
  }
  
  return list;
});

onMounted(() => {
  store.fetchRestaurants();
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
.mask-right {
  mask-image: linear-gradient(to right, rgba(0,0,0,1) 85%, rgba(0,0,0,0) 100%);
  -webkit-mask-image: linear-gradient(to right, rgba(0,0,0,1) 85%, rgba(0,0,0,0) 100%);
}
</style>
