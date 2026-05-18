<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl pb-24 font-inter">
    <!-- Profile Card Header -->
    <div class="relative overflow-hidden rounded-3xl mb-8 bg-gradient-to-tr from-slate-800 to-slate-900 border border-slate-700/40 p-6 shadow-xl flex flex-col sm:flex-row items-center gap-6 group">
      <!-- Gradient backing design -->
      <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-orange-500/5 blur-2xl group-hover:bg-orange-500/10 transition-colors duration-500"></div>

      <!-- Avatar with gorgeous badge -->
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
        <h3 class="text-lg font-bold text-slate-100 font-outfit">Алексей Иванов</h3>
        <p class="text-xs text-slate-400 mt-1 font-semibold flex flex-wrap justify-center sm:justify-start gap-3">
          <span>+993 (65) 12-34-56</span>
          <span class="w-1.5 h-1.5 rounded-full bg-slate-700 self-center hidden sm:inline"></span>
          <span>alex.ivanov@mail.com</span>
        </p>
      </div>

      <!-- Edit profile button -->
      <button class="px-4 py-2 bg-slate-800 hover:bg-slate-750 hover:text-orange-400 border border-slate-700/60 rounded-xl text-xs font-bold transition-all active:scale-95 duration-200">
        Изменить
      </button>
    </div>

    <!-- Active Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Order History list (takes 2 columns) -->
      <div class="lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-base font-bold text-slate-100 font-outfit tracking-wide">История заказов</h4>
          <span class="text-xs text-slate-400 font-semibold">Всего: 3</span>
        </div>

        <div class="space-y-4">
          <div 
            v-for="order in pastOrders" 
            :key="order.id"
            class="bg-slate-800/40 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700/40 transition-all duration-300 shadow-md"
          >
            <!-- Order status & details header -->
            <div class="flex justify-between items-start mb-4">
              <div>
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block">Заказ #{{ order.id }}</span>
                <span class="text-xs text-slate-500 font-bold block mt-0.5">{{ order.date }}</span>
              </div>
              <span 
                :class="[
                  'text-[10px] px-2.5 py-1 rounded-full font-black border tracking-wide uppercase',
                  order.status === 'delivered' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : '',
                  order.status === 'processing' ? 'bg-orange-500/10 border-orange-500/20 text-orange-400' : '',
                  order.status === 'canceled' ? 'bg-slate-800 border-slate-700 text-slate-400' : ''
                ]"
              >
                {{ order.statusLabel }}
              </span>
            </div>

            <!-- Restaurant name & list of items description -->
            <div class="flex items-center gap-3 py-3 border-y border-slate-700/20">
              <span class="text-2xl">{{ order.restaurantIcon }}</span>
              <div class="min-w-0 flex-1">
                <h5 class="text-sm font-bold text-slate-200 truncate">{{ order.restaurant }}</h5>
                <p class="text-xs text-slate-400 truncate mt-0.5">{{ order.itemsSummary }}</p>
              </div>
            </div>

            <!-- Price & action -->
            <div class="flex justify-between items-center mt-4">
              <span class="text-xs text-slate-400 font-semibold">
                Сумма: <strong class="text-slate-100 text-sm font-bold ml-1">{{ order.total }} TMT</strong>
              </span>
              <button class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 hover:text-orange-400 text-xs font-bold rounded-lg transition-colors border border-slate-700/30">
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
          <div @click="logout" class="p-4 hover:bg-rose-500/5 transition-colors cursor-pointer flex items-center justify-between text-rose-400 group">
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
import { ref } from 'vue';

const pushEnabled = ref(true);

const pastOrders = ref([
  {
    id: 4892,
    date: '16 мая 2026, 18:24',
    status: 'delivered',
    statusLabel: 'Доставлен',
    restaurant: 'Burger & Co',
    restaurantIcon: '🍔',
    itemsSummary: 'Чикен Бургер XL x2, Картофель Фри малый x1',
    total: 90
  },
  {
    id: 4761,
    date: '12 мая 2026, 14:15',
    status: 'delivered',
    statusLabel: 'Доставлен',
    restaurant: 'Sushi master',
    restaurantIcon: '🍣',
    itemsSummary: 'Сет Филадельфия x1, Сет Самурай x1',
    total: 185
  },
  {
    id: 4321,
    date: '3 мая 2026, 21:05',
    status: 'canceled',
    statusLabel: 'Отменен',
    restaurant: 'Bella Italia',
    restaurantIcon: '🍕',
    itemsSummary: 'Пицца Маргарита x1, Пицца Четыре сыра x1',
    total: 110
  }
]);

const togglePush = () => {
  pushEnabled.value = !pushEnabled.value;
  if (pushEnabled.value && window.askPushPermission) {
    window.askPushPermission();
  }
};

const logout = () => {
  if (confirm('Вы уверены, что хотите выйти из профиля?')) {
    // Standard logout form submit can be performed here later
    alert('Имитация логаута! В дальнейшем мы подключим Sanctum Auth или стандартный logout web-endpoint.');
  }
};
</script>
