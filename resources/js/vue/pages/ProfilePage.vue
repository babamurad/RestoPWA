<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl pb-24 font-inter">
    <!-- Profile Card Header -->
    <div class="relative overflow-hidden rounded-3xl mb-8 bg-gradient-to-tr from-slate-800 to-slate-900 border border-slate-700/40 p-6 shadow-xl flex flex-col sm:flex-row items-center gap-6 group">
      <!-- Decorative radial backing glow -->
      <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-orange-500/5 blur-2xl group-hover:bg-orange-500/10 transition-colors duration-500"></div>

      <!-- Avatar with badge -->
      <div class="relative">
        <div class="w-20 h-20 rounded-full p-1 bg-gradient-to-tr from-orange-500 to-amber-500 shadow-md">
          <div class="w-full h-full rounded-full bg-slate-900 overflow-hidden flex items-center justify-center border border-slate-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-400">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
          </div>
        </div>
        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 border border-slate-900 flex items-center justify-center text-white text-[10px] font-black shadow-md" title="Активный аккаунт">
          ✓
        </div>
      </div>

      <!-- Name & Contact details -->
      <div class="text-center sm:text-left flex-1">
        <h3 class="text-lg font-bold text-slate-100 font-outfit">{{ authStore.user?.name || 'Пользователь' }}</h3>
        <p class="text-xs text-slate-400 mt-1 font-semibold flex flex-wrap justify-center sm:justify-start gap-3">
          <span v-if="authStore.user?.phone">{{ formatPhone(authStore.user.phone) }}</span>
          <span v-if="authStore.user?.phone && authStore.user?.email" class="w-1.5 h-1.5 rounded-full bg-slate-700 self-center hidden sm:inline"></span>
          <span>{{ authStore.user?.email }}</span>
        </p>
      </div>
    </div>

    <!-- Active Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Order History list (takes 2 columns) -->
      <div class="lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-base font-bold text-slate-100 font-outfit tracking-wide">История заказов</h4>
          <span class="text-xs text-slate-400 font-semibold">Всего: {{ ordersStore.orders.length }}</span>
        </div>

        <div v-if="ordersStore.isLoading" class="space-y-4">
          <div v-for="i in 3" :key="i" class="bg-slate-800/20 border border-slate-800/80 rounded-2xl p-5 animate-pulse flex flex-col gap-3">
            <div class="h-4 bg-slate-700/50 rounded w-1/3"></div>
            <div class="h-8 bg-slate-700/30 rounded"></div>
            <div class="h-4 bg-slate-700/40 rounded w-1/4 self-end"></div>
          </div>
        </div>

        <div v-else-if="ordersStore.orders.length === 0" class="bg-slate-800/20 border border-slate-800/60 rounded-2xl p-8 text-center">
          <p class="text-sm text-slate-400 font-medium">У вас пока нет оформленных заказов</p>
          <router-link to="/" class="inline-block mt-4 px-6 py-2 bg-orange-500 text-white text-xs font-bold rounded-xl hover:bg-orange-600 transition-colors">
            Перейти к покупкам
          </router-link>
        </div>

        <div v-else class="space-y-4">
          <div 
            v-for="order in ordersStore.orders" 
            :key="order.id"
            class="bg-slate-800/40 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700/40 transition-all duration-300 shadow-md"
          >
            <!-- Order status & details header -->
            <div class="flex justify-between items-start mb-4">
              <div>
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block">Заказ #{{ order.id }}</span>
                <span class="text-xs text-slate-500 font-bold block mt-0.5">{{ formatDate(order.created_at) }}</span>
              </div>
              <span 
                :class="[
                  'text-[10px] px-2.5 py-1 rounded-full font-black border tracking-wide uppercase',
                  order.status === 'delivered' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : '',
                  ['pending', 'accepted', 'preparing'].includes(order.status) ? 'bg-orange-500/10 border-orange-500/20 text-orange-400' : '',
                  ['cancelled', 'canceled'].includes(order.status) ? 'bg-slate-800 border-slate-700 text-slate-400' : ''
                ]"
              >
                {{ getStatusLabel(order.status) }}
              </span>
            </div>

            <!-- Restaurant name & list of items description -->
            <div class="flex items-center gap-3 py-3 border-y border-slate-700/20">
              <!-- Restaurant Image or Icon fallback -->
              <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-900 flex items-center justify-center">
                <img 
                  v-if="getRestaurantImage(order.vendor_id)" 
                  :src="getRestaurantImage(order.vendor_id)" 
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-lg">🍽️</span>
              </div>
              <div class="min-w-0 flex-1">
                <h5 class="text-sm font-bold text-slate-200 truncate">{{ getRestaurantName(order.vendor_id) }}</h5>
                <p class="text-xs text-slate-400 truncate mt-0.5">{{ getOrderItemsSummary(order) }}</p>
              </div>
            </div>

            <!-- Price & action -->
            <div class="flex justify-between items-center mt-4">
              <span class="text-xs text-slate-400 font-semibold">
                Сумма: <strong class="text-slate-100 text-sm font-bold ml-1">{{ order.total }} TMT</strong>
              </span>
              <button 
                @click="repeatOrder(order)"
                class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 hover:text-orange-400 text-xs font-bold rounded-lg transition-colors border border-slate-700/30"
              >
                Повторить
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Settings Links list -->
      <div class="lg:col-span-1">
        <h4 class="text-base font-bold text-slate-100 mb-4 font-outfit tracking-wide">Параметры и настройки</h4>
        <div class="bg-slate-800/40 border border-slate-800/80 rounded-2xl overflow-hidden divide-y divide-slate-800/50 shadow-md">
          <!-- Addresses link -->
          <div class="p-4 hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-lg">📍</span>
              <span class="text-xs font-bold text-slate-200">Адреса доставки</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-slate-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </div>

          <!-- Payments link -->
          <div class="p-4 hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-lg">💳</span>
              <span class="text-xs font-bold text-slate-200">Способы оплаты</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-slate-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </div>

          <!-- Push settings selector -->
          <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-lg">🔔</span>
              <span class="text-xs font-bold text-slate-200">Push-уведомления</span>
            </div>
            <!-- Interactive Switch -->
            <button 
              @click="togglePush"
              :class="['w-9 h-5 rounded-full p-0.5 transition-all shadow-inner relative flex items-center', pushEnabled ? 'bg-orange-500' : 'bg-slate-700']"
            >
              <div :class="['w-4 h-4 rounded-full bg-white shadow-md transition-all absolute', pushEnabled ? 'right-0.5' : 'left-0.5']"></div>
            </button>
          </div>

          <!-- Support link -->
          <div class="p-4 hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-lg">💬</span>
              <span class="text-xs font-bold text-slate-200">Служба поддержки</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-slate-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </div>

          <!-- Logout option -->
          <div @click="handleLogout" class="p-4 hover:bg-rose-500/5 transition-colors cursor-pointer flex items-center justify-between text-rose-400 group">
            <div class="flex items-center gap-3">
              <span class="text-lg">🚪</span>
              <span class="text-xs font-black text-rose-500 group-hover:text-rose-400 transition-colors">Выйти из профиля</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-rose-500/70 group-hover:text-rose-400 transition-colors">
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useOrdersStore } from '../stores/orders';
import { useRestaurantsStore } from '../stores/restaurants';
import { useCartStore } from '../stores/cart';

const router = useRouter();
const authStore = useAuthStore();
const ordersStore = useOrdersStore();
const restaurantsStore = useRestaurantsStore();
const cartStore = useCartStore();

const pushEnabled = ref(true);

onMounted(async () => {
  // Ensure restaurants list is loaded for mapping IDs to names/images
  if (restaurantsStore.restaurants.length === 0) {
    await restaurantsStore.fetchRestaurants();
  }
  // Load order history
  await ordersStore.fetchOrders();
});

// Format phone number to clean localized string
const formatPhone = (phone) => {
  if (!phone) return '';
  const cleaned = phone.replace(/\D/g, '');
  if (cleaned.length === 11 && cleaned.startsWith('993')) {
    return `+993 (${cleaned.substring(3, 5)}) ${cleaned.substring(5, 7)}-${cleaned.substring(7, 9)}-${cleaned.substring(9)}`;
  }
  return phone;
};

// Date formatter
const formatDate = (isoStr) => {
  if (!isoStr) return '';
  const date = new Date(isoStr);
  return date.toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Status mapper
const getStatusLabel = (status) => {
  const labels = {
    pending: 'Новый',
    accepted: 'Принят',
    preparing: 'Готовится',
    delivered: 'Доставлен',
    cancelled: 'Отменен',
    canceled: 'Отменен'
  };
  return labels[status] || status;
};

// Helper to match restaurant details
const getRestaurantName = (vendorId) => {
  const r = restaurantsStore.restaurants.find(x => x.id === vendorId);
  return r ? r.name : 'Ресторан';
};

const getRestaurantImage = (vendorId) => {
  const r = restaurantsStore.restaurants.find(x => x.id === vendorId);
  return r ? r.image : '';
};

// Generates items string description
const getOrderItemsSummary = (order) => {
  if (!order.items || !Array.isArray(order.items)) return '';
  return order.items.map(item => `${item.name} x${item.quantity}`).join(', ');
};

// Interactive Switch for push notifications
const togglePush = () => {
  pushEnabled.value = !pushEnabled.value;
  if (pushEnabled.value && window.askPushPermission) {
    window.askPushPermission();
  }
};

// One-click Repeat Order Action
const repeatOrder = (order) => {
  if (!order.items || !Array.isArray(order.items)) return;
  
  if (confirm(`Добавить все блюда из этого заказа в корзину?`)) {
    // Clear and set vendor ID for the cart
    cartStore.clear();
    cartStore.setVendorId(order.vendor_id);
    
    // Add all products to the cart store
    order.items.forEach(item => {
      cartStore.addItem({
        id: item.product_id || item.id,
        name: item.name,
        price: Number(item.price),
        weight_g: item.weight_g || item.weight || null,
        image_url: item.image_url || null
      }, item.quantity);
    });
    
    router.push('/cart');
  }
};

// Handle account logout
const handleLogout = async () => {
  if (confirm('Вы уверены, что хотите выйти из профиля?')) {
    await authStore.logout();
    router.push('/login');
  }
};
</script>
