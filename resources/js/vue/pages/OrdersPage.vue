<template>
  <div class="px-4 py-6 max-w-xl mx-auto pb-24 font-inter animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-bold dark:text-slate-100 font-outfit tracking-wide text-slate-900">Мои заказы</h3>
      <span class="text-xs dark:text-slate-400 font-semibold dark:bg-slate-800/40 px-3 py-1.5 rounded-full border dark:border-slate-800 text-slate-600 bg-white border-slate-200">
        Всего: {{ ordersStore.orders.length }}
      </span>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="ordersStore.isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="dark:bg-slate-800/20 border dark:border-slate-800/80 rounded-2xl p-5 animate-pulse flex flex-col gap-3 bg-white border-slate-200">
        <div class="h-4 dark:bg-slate-700/50 rounded w-1/3 bg-slate-100"></div>
        <div class="h-8 dark:bg-slate-700/30 rounded bg-slate-100"></div>
        <div class="h-4 dark:bg-slate-700/40 rounded w-1/4 self-end bg-slate-100"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="ordersStore.orders.length === 0" class="dark:bg-slate-800/20 border dark:border-slate-800/60 rounded-3xl p-8 text-center shadow-lg backdrop-blur-sm bg-white border-slate-200">
      <div class="w-16 h-16 dark:bg-slate-800/50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl border dark:border-slate-700/30 bg-white border-slate-300">
        📋
      </div>
      <p class="text-sm dark:text-slate-400 font-medium leading-relaxed text-slate-600">У вас пока нет оформленных заказов</p>
      <router-link to="/" class="inline-block mt-5 px-6 py-2.5 bg-orange-500 text-white text-xs font-bold rounded-xl hover:bg-orange-600 active:scale-95 transition-all shadow-lg shadow-orange-500/10">
        Перейти к покупкам
      </router-link>
    </div>

    <!-- Orders List -->
    <div v-else class="space-y-4">
      <div 
        v-for="order in ordersStore.orders" 
        :key="order.id"
        class="dark:bg-slate-800/40 border dark:border-slate-800/80 rounded-2xl p-5 dark:hover:border-slate-700 transition-all duration-305 shadow-md hover:shadow-lg relative overflow-hidden group bg-white border-slate-200 hover:border-slate-300"
      >
        <!-- Card background subtle decoration -->
        <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-orange-500/2 blur-xl group-hover:bg-orange-500/4 transition-colors"></div>

        <!-- Order status & details header -->
        <div class="flex justify-between items-start mb-4">
          <div>
            <span class="text-xs dark:text-slate-400 font-semibold uppercase tracking-wider block text-slate-600">Заказ #{{ order.id }}</span>
            <span class="text-xs dark:text-slate-500 font-bold block mt-0.5 text-slate-500">{{ formatDate(order.created_at) }}</span>
          </div>
          <span 
            :class="[
              'text-[10px] px-2.5 py-1 rounded-full font-black border tracking-wide uppercase',
              order.status === 'delivered' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : '',
              ['pending', 'accepted', 'preparing'].includes(order.status) ? 'bg-orange-500/10 border-orange-500/20 text-orange-400' : '',
              ['cancelled', 'canceled'].includes(order.status) ? 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400' : ''
            ]"
          >
            {{ getStatusLabel(order.status) }}
          </span>
        </div>

        <!-- Restaurant name & list of items description -->
        <div class="flex items-center gap-3 py-3 border-y dark:border-slate-700/20 border-slate-300">
          <!-- Restaurant Image or Icon fallback -->
          <div class="w-10 h-10 rounded-xl overflow-hidden dark:bg-slate-900 flex items-center justify-center border dark:border-slate-800/80 bg-slate-50 border-slate-200">
            <img 
              v-if="getRestaurantImage(order.vendor_id)" 
              :src="getRestaurantImage(order.vendor_id)" 
              class="w-full h-full object-cover"
            />
            <span v-else class="text-lg">🍽️</span>
          </div>
          <div class="min-w-0 flex-1">
            <h5 class="text-sm font-bold dark:text-slate-200 truncate text-slate-800">{{ getRestaurantName(order.vendor_id) }}</h5>
            <p class="text-xs dark:text-slate-400 truncate mt-0.5 text-slate-600">{{ getOrderItemsSummary(order) }}</p>
          </div>
        </div>

        <!-- Price & action -->
        <div class="flex justify-between items-center mt-4">
          <span class="text-xs dark:text-slate-400 font-semibold text-slate-600">
            Сумма: <strong class="dark:text-slate-100 text-sm font-bold ml-1 text-slate-900">{{ order.total }} TMT</strong>
          </span>
          <button 
            @click="repeatOrder(order)"
            class="px-3.5 py-1.5 dark:bg-slate-800 dark:hover:bg-slate-700 hover:text-orange-400 text-xs font-bold rounded-lg transition-colors border dark:border-slate-700/30 active:scale-95 bg-white hover:bg-slate-100 border-slate-300"
          >
            Повторить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useOrdersStore } from '../stores/orders';
import { useRestaurantsStore } from '../stores/restaurants';
import { useCartStore } from '../stores/cart';

const router = useRouter();
const ordersStore = useOrdersStore();
const restaurantsStore = useRestaurantsStore();
const cartStore = useCartStore();

onMounted(async () => {
  // Ensure restaurants list is loaded for mapping IDs to names/images
  if (restaurantsStore.restaurants.length === 0) {
    await restaurantsStore.fetchRestaurants();
  }
  // Load order history
  await ordersStore.fetchOrders();
});

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

// One-click Repeat Order Action
const repeatOrder = (order) => {
  if (!order.items || !Array.isArray(order.items)) return;
  
  if (window.Swal) {
    window.Swal.fire({
      title: 'Повторить заказ?',
      text: 'Текущая корзина будет очищена, и все блюда из этого заказа будут добавлены.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Повторить',
      cancelButtonText: 'Отмена',
      confirmButtonColor: '#f97316',
      cancelButtonColor: '#334155',
      background: '#0f172a',
      color: '#f8fafc',
      reverseButtons: true,
      customClass: { popup: 'rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl' }
    }).then((result) => {
      if (result.isConfirmed) {
        cartStore.clear();
        cartStore.setVendorId(order.vendor_id);
        
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
    });
  } else {
    if (confirm(`Добавить все блюда из этого заказа в корзину?`)) {
      cartStore.clear();
      cartStore.setVendorId(order.vendor_id);
      
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
  }
};
</script>
