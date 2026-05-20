<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl pb-24 font-inter">
    <!-- Back Navigation Link -->
    <div class="flex items-center gap-3 mb-8">
      <router-link :to="cartStore.vendorSlug ? `/restaurants/${cartStore.vendorSlug}` : '/'" class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700/50 flex items-center justify-center text-slate-300 hover:text-orange-400 hover:border-orange-500/30 transition-all active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </router-link>
      <h2 class="text-xl font-bold text-slate-100 font-outfit tracking-wide">Моя корзина</h2>
    </div>

    <!-- Empty State -->
    <div v-if="cartStore.isEmpty" class="flex flex-col items-center justify-center py-16 text-center select-none animate-pulse">
      <div class="w-24 h-24 rounded-full bg-slate-800/50 border border-slate-700/30 flex items-center justify-center mb-6 shadow-xl relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-tr from-orange-500/10 to-amber-500/10 blur-xl opacity-100"></div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-slate-200 mb-2 font-outfit">Корзина пуста</h3>
      <p class="text-sm text-slate-400 max-w-xs mb-8 font-medium">
        Кажется, вы еще не добавили ни одного блюда в корзину. Выберите ресторан, чтобы начать заказ!
      </p>
      <router-link to="/" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold rounded-2xl shadow-lg shadow-orange-500/20 active:scale-95 transition-all text-xs">
        Перейти в каталог
      </router-link>
    </div>

    <!-- Active Cart Items -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Items list -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Server Sync status loading/warning -->
        <transition name="fade">
          <div v-if="cartStore.syncLoading" class="text-xs text-orange-400 font-bold bg-orange-500/5 border border-orange-500/10 p-3 rounded-2xl flex items-center gap-2 select-none animate-pulse">
            <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-ping"></span>
            Синхронизация цен и наличия с сервером...
          </div>
        </transition>

        <!-- Price Change Warning block -->
        <div v-if="cartStore.priceChanges.length > 0" class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-2xl text-xs text-amber-400 font-semibold space-y-2 select-none">
          <div class="flex items-center gap-2 font-black uppercase tracking-wider">
            <span>⚠️</span> Внимание! Цены на некоторые позиции изменились:
          </div>
          <ul class="list-disc pl-5 space-y-1">
            <li v-for="change in cartStore.priceChanges" :key="change.product_id">
              «{{ change.name }}»: старая цена {{ change.old_price }} TMT → новая цена {{ change.new_price }} TMT.
            </li>
          </ul>
        </div>

        <!-- Unavailable Items warning block -->
        <div v-if="cartStore.unavailableItems.length > 0" class="bg-rose-500/10 border border-rose-500/20 p-4 rounded-2xl text-xs text-rose-400 font-semibold space-y-2 select-none">
          <div class="flex items-center gap-2 font-black uppercase tracking-wider">
            <span>🚫</span> Следующие товары были удалены, так как они временно недоступны:
          </div>
          <ul class="list-disc pl-5 space-y-1">
            <li v-for="item in cartStore.unavailableItems" :key="item.product_id">
              «{{ item.name }}»: {{ item.reason || 'Временно недоступно' }}
            </li>
          </ul>
          <button @click="cartStore.unavailableItems = []" class="mt-2 text-[10px] underline hover:text-white font-black">
            Скрыть предупреждение
          </button>
        </div>

        <!-- Restaurant title header -->
        <div class="bg-slate-800/30 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between select-none">
          <div class="flex items-center gap-3">
            <span class="text-2xl">🍔</span>
            <div>
              <span class="text-xs text-slate-455 block font-black uppercase tracking-wider">Заказ из ресторана</span>
              <h4 class="text-sm font-black text-slate-100 group-hover:text-orange-400 hover:underline cursor-pointer" @click="$router.push(`/restaurants/${cartStore.vendorSlug}`)">
                {{ cartStore.vendorName }}
              </h4>
            </div>
          </div>
          <button @click="clearCart" class="text-xs text-rose-500 font-bold hover:text-rose-400 flex items-center gap-1 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
            Очистить
          </button>
        </div>

        <!-- Items cards -->
        <div class="space-y-3 select-none">
          <div 
            v-for="item in cartStore.items" 
            :key="item.id" 
            class="bg-slate-800/40 border border-slate-800/50 rounded-2xl p-4 flex gap-4 items-center justify-between shadow-md hover:border-slate-700/40 transition-all duration-300 animate-fade-in"
          >
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <img 
                :src="item.image" 
                :alt="item.name" 
                class="w-16 h-16 rounded-xl object-cover border border-slate-850 shadow-inner"
                @error="handleImageError"
              />
              <div class="min-w-0 flex-1">
                <h5 class="text-sm font-bold text-slate-100 truncate">{{ item.name }}</h5>
                <p v-if="item.weight" class="text-[10px] text-slate-455 mt-0.5">Вес: {{ item.weight }} г</p>
                <p class="text-xs text-slate-400 mt-1 font-semibold">{{ item.price }} TMT x {{ item.quantity }}</p>
                <div class="text-xs font-black text-orange-400 mt-1.5">{{ (item.price * item.quantity).toFixed(0) }} TMT</div>
              </div>
            </div>

            <!-- Item Quantity Buttons -->
            <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl p-1 shadow-inner flex-shrink-0">
              <button 
                @click="decreaseQty(item)" 
                class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-300 font-bold active:scale-90 transition-all"
              >
                -
              </button>
              <span class="w-8 text-center text-xs font-black text-slate-200">{{ item.quantity }}</span>
              <button 
                @click="increaseQty(item)" 
                class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-300 font-bold active:scale-90 transition-all"
              >
                +
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div class="lg:col-span-1 select-none">
        <div class="bg-slate-850 border border-slate-800/80 rounded-3xl p-6 shadow-xl sticky top-6 space-y-6">
          <h4 class="text-base font-extrabold text-slate-100 font-outfit tracking-wide">Детали заказа</h4>
          
          <div class="space-y-3.5 text-sm text-slate-400 font-semibold border-b border-slate-700/30 pb-5">
            <div class="flex justify-between">
              <span>Стоимость блюд</span>
              <span class="text-slate-200">
                {{ cartStore.syncLoading ? cartStore.localSubtotal.toFixed(0) : cartStore.subtotal }} TMT
              </span>
            </div>
            <div class="flex justify-between">
              <span>Доставка</span>
              <span class="text-slate-200">{{ cartStore.deliveryFee === 0 ? 'Бесплатно' : cartStore.deliveryFee + ' TMT' }}</span>
            </div>
          </div>

          <!-- Minimum Order warning block -->
          <div v-if="!cartStore.isMinOrderMet" class="bg-rose-500/10 border border-rose-500/20 p-3 rounded-2xl text-[11px] text-rose-400 font-semibold leading-relaxed flex gap-2">
            <span>🚨</span>
            <div>
              Минимальная сумма заказа составляет {{ cartStore.minOrder }} TMT. Пожалуйста, добавьте еще блюд на 
              <strong class="underline font-black">{{ (cartStore.minOrder - cartStore.subtotal).toFixed(0) }} TMT</strong>.
            </div>
          </div>

          <!-- Total price -->
          <div class="flex justify-between items-baseline">
            <span class="text-sm font-bold text-slate-400 font-outfit">Итого</span>
            <span class="text-2xl font-black text-orange-500 font-outfit">
              {{ cartStore.syncLoading ? cartStore.localTotal.toFixed(0) : cartStore.total }} TMT
            </span>
          </div>

          <!-- Checkout CTA -->
          <button 
            @click="proceedToCheckout" 
            :disabled="!cartStore.isMinOrderMet"
            class="w-full py-4 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 disabled:from-slate-800 disabled:to-slate-850 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-extrabold text-sm rounded-2xl shadow-lg shadow-orange-500/10 hover:shadow-orange-500/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2"
          >
            Оформить заказ
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useCartStore } from '../stores/cart';

import { useRouter } from 'vue-router';

const router = useRouter();
const cartStore = useCartStore();

const increaseQty = (item) => {
  cartStore.updateQuantity(item.id, item.quantity + 1);
};

const decreaseQty = (item) => {
  cartStore.updateQuantity(item.id, item.quantity - 1);
};

const clearCart = () => {
  if (window.Swal) {
    window.Swal.fire({
      title: 'Очистить корзину?',
      text: 'Все добавленные блюда будут безвозвратно удалены.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Да, очистить',
      cancelButtonText: 'Отмена',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#334155',
      background: '#0f172a',
      color: '#f8fafc',
      reverseButtons: true,
      customClass: { popup: 'rounded-3xl border border-slate-850 shadow-2xl' }
    }).then((result) => {
      if (result.isConfirmed) {
        cartStore.clearCart();
        window.Swal.fire({
          title: 'Очищено',
          text: 'Корзина успешно очищена',
          icon: 'success',
          timer: 1500,
          showConfirmButton: false,
          background: '#0f172a',
          color: '#f8fafc',
          customClass: { popup: 'rounded-3xl border border-slate-850 shadow-2xl' }
        });
      }
    });
  } else {
    if (confirm('Вы уверены, что хотите полностью очистить корзину?')) {
      cartStore.clearCart();
    }
  }
};

const proceedToCheckout = () => {
  if (!cartStore.isMinOrderMet) return;
  router.push('/checkout');
};

const handleImageError = (e) => {
  e.target.src = 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80';
};

onMounted(() => {
  // If items already in store (navigated within SPA), sync immediately.
  // If items not yet loaded (hard reload), App.vue.onMounted will call
  // loadFromLocalStorage() which pre-computes local totals then syncs.
  if (!cartStore.isEmpty) {
    cartStore.syncCart();
  }
});
</script>

<style scoped>
/* Sync Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-in-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
