<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-inter antialiased selection:bg-orange-500/30 selection:text-orange-300">
    <!-- Desktop Top Navigation Header with Premium Glassmorphism -->
    <header class="sticky top-0 z-50 w-full bg-slate-900/60 backdrop-blur-xl border-b border-slate-800/40 shadow-sm transition-all hidden md:block">
      <div class="max-w-6xl mx-auto px-6 h-18 flex items-center justify-between">
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-2 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-orange-500 to-amber-500 flex items-center justify-center shadow-lg shadow-orange-500/20 group-hover:rotate-6 transition-transform duration-300">
            <span class="text-xl font-black text-white">R</span>
          </div>
          <span class="text-lg font-black tracking-wider text-slate-100 font-outfit">
            Resto<span class="text-orange-500">PWA</span>
          </span>
        </router-link>

        <!-- Nav Links -->
        <nav class="flex items-center gap-1.5 text-sm font-bold text-slate-300">
          <router-link 
            to="/" 
            active-class="bg-slate-800/50 text-orange-400 border-slate-700/40"
            class="px-4 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/40 hover:text-slate-100 transition-all flex items-center gap-2"
          >
            <span>🍔</span> Каталог
          </router-link>
          
          <router-link 
            to="/cart" 
            active-class="bg-slate-800/50 text-orange-400 border-slate-700/40"
            class="px-4 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/40 hover:text-slate-100 transition-all flex items-center gap-2 relative"
          >
            <span>🛒</span> Корзина
            <span v-if="cartStore.totalItemsCount > 0" class="w-5 h-5 rounded-full bg-orange-500 text-white text-[10px] font-black flex items-center justify-center border border-slate-950 shadow-md">
              {{ cartStore.totalItemsCount }}
            </span>
          </router-link>

          <router-link 
            to="/orders" 
            active-class="bg-slate-800/50 text-orange-400 border-slate-700/40"
            class="px-4 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/40 hover:text-slate-100 transition-all flex items-center gap-2"
          >
            <span>📋</span> Заказы
          </router-link>

          <router-link 
            to="/profile" 
            active-class="bg-slate-800/50 text-orange-400 border-slate-700/40"
            class="px-4 py-2.5 rounded-xl border border-transparent hover:bg-slate-800/40 hover:text-slate-100 transition-all flex items-center gap-2"
          >
            <span>👤</span> Профиль
          </router-link>
        </nav>
      </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 w-full max-w-6xl mx-auto relative">
      <router-view v-slot="{ Component }">
        <transition name="fade-up" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Mobile Bottom Tab Bar with glassmorphism -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-slate-900/75 backdrop-blur-xl border-t border-slate-800/40 py-2.5 px-6 flex items-center justify-around md:hidden shadow-2xl">
      <!-- Home tab -->
      <router-link 
        to="/" 
        active-class="text-orange-500 scale-105"
        class="flex flex-col items-center gap-1 text-slate-400 transition-all duration-200"
      >
        <span class="text-xl">🍔</span>
        <span class="text-[9px] font-black uppercase tracking-wider">Каталог</span>
      </router-link>

      <!-- Cart tab with items count badge -->
      <router-link 
        to="/cart" 
        active-class="text-orange-500 scale-105"
        class="flex flex-col items-center gap-1 text-slate-400 transition-all duration-200 relative"
      >
        <span class="text-xl">🛒</span>
        <span class="text-[9px] font-black uppercase tracking-wider">Корзина</span>
        <span v-if="cartStore.totalItemsCount > 0" class="absolute -top-1.5 -right-2.5 w-4 h-4 rounded-full bg-orange-500 text-white text-[9px] font-black flex items-center justify-center border border-slate-950 shadow-md">
          {{ cartStore.totalItemsCount }}
        </span>
      </router-link>

      <!-- Orders tab -->
      <router-link 
        to="/orders" 
        active-class="text-orange-500 scale-105"
        class="flex flex-col items-center gap-1 text-slate-400 transition-all duration-200"
      >
        <span class="text-xl">📋</span>
        <span class="text-[9px] font-black uppercase tracking-wider">Заказы</span>
      </router-link>

      <!-- Profile tab -->
      <router-link 
        to="/profile" 
        active-class="text-orange-500 scale-105"
        class="flex flex-col items-center gap-1 text-slate-400 transition-all duration-200"
      >
        <span class="text-xl">👤</span>
        <span class="text-[9px] font-black uppercase tracking-wider">Профиль</span>
      </router-link>
    </nav>

    <!-- Floating Glassmorphic Offline indicator -->
    <transition name="slide-up">
      <div 
        v-if="isOffline" 
        class="fixed bottom-20 left-4 right-4 md:bottom-6 md:right-6 md:left-auto z-50 max-w-sm mx-auto bg-rose-500/90 backdrop-blur-md border border-rose-400/30 text-white p-4 rounded-2xl shadow-xl flex items-center gap-3.5"
      >
        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 text-white">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
          </svg>
        </div>
        <div>
          <h4 class="text-xs font-black uppercase tracking-wide">Режим оффлайн</h4>
          <p class="text-[11px] text-rose-100 mt-0.5 leading-relaxed font-medium">Отсутствует интернет-подключение. Вы можете продолжать просмотр, заказы будут синхронизированы при появлении сети.</p>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useCartStore } from './stores/cart';

const cartStore = useCartStore();

const isOffline = ref(!navigator.onLine);

const updateOnlineStatus = () => {
  isOffline.value = !navigator.onLine;
};

onMounted(() => {
  cartStore.loadFromLocalStorage();
  window.addEventListener('online', updateOnlineStatus);
  window.addEventListener('offline', updateOnlineStatus);
  // Support connectivity-changed custom event from our app.js
  window.addEventListener('connectivity-changed', (e) => {
    isOffline.value = e.detail?.isOffline;
  });
});

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus);
  window.removeEventListener('offline', updateOnlineStatus);
});
</script>

<style>
/* Transition Animations */
.fade-up-enter-active,
.fade-up-leave-active {
  transition: all 0.25s ease-out;
}

.fade-up-enter-from {
  opacity: 0;
  transform: translateY(12px);
}

.fade-up-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(30px);
}
</style>
