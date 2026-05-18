<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl pb-24 font-inter">
    <div class="flex items-center gap-3 mb-8">
      <router-link to="/" class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700/50 flex items-center justify-center text-slate-300 hover:text-orange-400 hover:border-orange-500/30 transition-all active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </router-link>
      <h2 class="text-xl font-bold text-slate-100 font-outfit tracking-wide">Моя корзина</h2>
    </div>

    <!-- Empty State -->
    <div v-if="cartItems.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
      <div class="w-24 h-24 rounded-full bg-slate-800/50 border border-slate-700/30 flex items-center justify-center mb-6 shadow-xl relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-tr from-orange-500/10 to-amber-500/10 blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-500 group-hover:text-orange-400 group-hover:scale-110 transition-all duration-300">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-slate-200 mb-2 font-outfit">Корзина пуста</h3>
      <p class="text-sm text-slate-400 max-w-xs mb-8">
        Кажется, вы еще не добавили ни одного блюда в корзину. Выберите ресторан, чтобы начать заказ!
      </p>
      <router-link to="/" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold rounded-2xl shadow-lg shadow-orange-500/20 active:scale-95 transition-all text-sm">
        Перейти в каталог
      </router-link>
    </div>

    <!-- Active Cart Items -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Items list -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Restaurant title header -->
        <div class="bg-slate-800/30 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">🍔</span>
            <div>
              <span class="text-xs text-slate-400 block font-medium">Заказ из ресторана</span>
              <h4 class="text-sm font-bold text-slate-100">Burger & Co</h4>
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
        <div class="space-y-3">
          <div 
            v-for="item in cartItems" 
            :key="item.id" 
            class="bg-slate-800/40 border border-slate-800/50 rounded-2xl p-4 flex gap-4 items-center justify-between shadow-md hover:border-slate-700/40 transition-all duration-300"
          >
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <img :src="item.image" :alt="item.name" class="w-16 h-16 rounded-xl object-cover border border-slate-850 shadow-inner" />
              <div class="min-w-0 flex-1">
                <h5 class="text-sm font-bold text-slate-100 truncate">{{ item.name }}</h5>
                <p class="text-xs text-slate-400 mt-0.5">{{ item.price }} TMT x {{ item.quantity }}</p>
                <div class="text-xs font-bold text-orange-400 mt-1">{{ item.price * item.quantity }} TMT</div>
              </div>
            </div>

            <!-- Item Quantity Buttons -->
            <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl p-1 shadow-inner">
              <button 
                @click="decreaseQty(item)" 
                class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-300 active:scale-90 transition-all"
              >
                -
              </button>
              <span class="w-8 text-center text-xs font-black text-slate-200">{{ item.quantity }}</span>
              <button 
                @click="increaseQty(item)" 
                class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-300 active:scale-90 transition-all"
              >
                +
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div class="lg:col-span-1">
        <div class="bg-slate-850 border border-slate-800/80 rounded-3xl p-6 shadow-xl sticky top-6">
          <h4 class="text-base font-extrabold text-slate-100 mb-6 font-outfit">Детали заказа</h4>
          
          <div class="space-y-3.5 mb-6 text-sm text-slate-400 font-semibold border-b border-slate-700/30 pb-5">
            <div class="flex justify-between">
              <span>Стоимость блюд</span>
              <span class="text-slate-200">{{ subtotal }} TMT</span>
            </div>
            <div class="flex justify-between">
              <span>Доставка</span>
              <span class="text-slate-200">{{ deliveryFee === 0 ? 'Бесплатно' : deliveryFee + ' TMT' }}</span>
            </div>
            <div class="flex justify-between text-emerald-400" v-if="promoApplied">
              <span>Промокод</span>
              <span>-{{ discount }} TMT</span>
            </div>
          </div>

          <!-- Promo Code entry -->
          <div class="mb-6">
            <div class="flex gap-2">
              <input 
                v-model="promoCode" 
                type="text" 
                placeholder="Промокод" 
                class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 font-bold focus:outline-none focus:border-orange-500/50 transition-colors placeholder:text-slate-600"
              />
              <button 
                @click="applyPromo"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-750 border border-slate-700/50 hover:text-orange-400 text-xs font-bold rounded-xl transition-all"
              >
                Применить
              </button>
            </div>
            <p v-if="promoApplied" class="text-[10px] text-emerald-400 font-bold mt-1.5 flex items-center gap-1">
              ✓ Промокод успешно применен!
            </p>
          </div>

          <!-- Total price -->
          <div class="flex justify-between items-baseline mb-6">
            <span class="text-sm font-bold text-slate-400 font-outfit">Итого</span>
            <span class="text-2xl font-black text-orange-500 font-outfit">{{ total }} TMT</span>
          </div>

          <!-- Checkout CTA -->
          <button @click="proceedToCheckout" class="w-full py-4 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-extrabold text-sm rounded-2xl shadow-lg shadow-orange-500/10 hover:shadow-orange-500/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
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
import { ref, computed } from 'vue';

const cartItems = ref([
  {
    id: 101,
    name: 'Чикен Бургер XL',
    price: 35,
    quantity: 2,
    image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&auto=format&fit=crop&q=80'
  },
  {
    id: 102,
    name: 'Картофель Фри малый',
    price: 15,
    quantity: 1,
    image: 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=200&auto=format&fit=crop&q=80'
  }
]);

const deliveryFee = ref(5);
const promoCode = ref('');
const promoApplied = ref(false);
const discount = ref(0);

const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

const total = computed(() => {
  return Math.max(0, subtotal.value + deliveryFee.value - discount.value);
});

const increaseQty = (item) => {
  item.quantity++;
};

const decreaseQty = (item) => {
  if (item.quantity > 1) {
    item.quantity--;
  } else {
    cartItems.value = cartItems.value.filter(i => i.id !== item.id);
  }
};

const clearCart = () => {
  cartItems.value = [];
};

const applyPromo = () => {
  if (promoCode.value.toUpperCase() === 'FIRST20') {
    discount.value = Math.round(subtotal.value * 0.2);
    promoApplied.value = true;
  } else {
    alert('Неверный промокод! Попробуйте FIRST20');
  }
};

const proceedToCheckout = () => {
  alert('Имитация перехода к оформлению заказа! На следующих этапах мы перенесем сюда CheckoutWizard.');
};
</script>
