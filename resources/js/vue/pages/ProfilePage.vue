<template>
  <div class="px-4 py-6 max-w-xl mx-auto pb-24 font-inter">
    <!-- Profile Card Header -->
    <div class="relative overflow-hidden rounded-3xl mb-8 bg-gradient-to-tr from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700/40 p-6 shadow-xl flex flex-col sm:flex-row items-center gap-6 group transition-colors duration-300">
      <!-- Decorative radial backing glow -->
      <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-orange-500/5 blur-2xl group-hover:bg-orange-500/10 transition-colors duration-500"></div>

      <!-- Avatar with badge -->
      <div class="relative">
        <div class="w-20 h-20 rounded-full p-1 bg-gradient-to-tr from-orange-500 to-amber-500 shadow-md">
          <div class="w-full h-full rounded-full bg-slate-50 dark:bg-slate-900 overflow-hidden flex items-center justify-center border border-slate-200 dark:border-slate-800 transition-colors duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-400">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
          </div>
        </div>
        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 border border-white dark:border-slate-900 flex items-center justify-center text-white text-[10px] font-black shadow-md transition-colors duration-300" title="Активный аккаунт">
          ✓
        </div>
      </div>

      <!-- Name & Contact details -->
      <div class="text-center sm:text-left flex-1">
        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 font-outfit transition-colors duration-300">{{ authStore.user?.name || 'Пользователь' }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-semibold flex flex-wrap justify-center sm:justify-start gap-3 transition-colors duration-300">
          <span v-if="authStore.user?.phone">{{ formatPhone(authStore.user.phone) }}</span>
          <span v-if="authStore.user?.phone && authStore.user?.email" class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700 self-center hidden sm:inline transition-colors duration-300"></span>
          <span>{{ authStore.user?.email }}</span>
        </p>
      </div>
    </div>

    <!-- Quick Settings Links list -->
    <div>
      <h4 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-4 font-outfit tracking-wide transition-colors duration-300">Параметры и настройки</h4>
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800/50 shadow-md transition-colors duration-300">
        
        <!-- Theme selector -->
        <div class="p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-lg">{{ isDark ? '🌙' : '☀️' }}</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors duration-300">Тёмная тема</span>
          </div>
          <!-- Interactive Switch -->
          <button 
            @click="toggleTheme"
            :class="['w-9 h-5 rounded-full p-0.5 transition-all shadow-inner relative flex items-center', isDark ? 'bg-orange-500' : 'bg-slate-300']"
          >
            <div :class="['w-4 h-4 rounded-full bg-white shadow-md transition-all absolute', isDark ? 'right-0.5' : 'left-0.5']"></div>
          </button>
        </div>

        <!-- Addresses link -->
        <div @click="showDevelopmentNotification" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-lg">📍</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors duration-300">Адреса доставки</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-colors duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </div>

        <!-- Payments link -->
        <div @click="showDevelopmentNotification" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-lg">💳</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors duration-300">Способы оплаты</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-colors duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </div>

        <!-- Push settings selector -->
        <div class="p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-lg">🔔</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors duration-300">Push-уведомления</span>
          </div>
          <!-- Interactive Switch -->
          <button 
            @click="togglePush"
            :class="['w-9 h-5 rounded-full p-0.5 transition-all shadow-inner relative flex items-center', pushEnabled ? 'bg-orange-500' : 'bg-slate-300 dark:bg-slate-700']"
          >
            <div :class="['w-4 h-4 rounded-full bg-white shadow-md transition-all absolute', pushEnabled ? 'right-0.5' : 'left-0.5']"></div>
          </button>
        </div>

        <!-- Support link -->
        <div @click="showDevelopmentNotification" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-lg">💬</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors duration-300">Служба поддержки</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-colors duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </div>

        <!-- Logout option -->
        <div @click="handleLogout" class="p-4 hover:bg-rose-50 dark:hover:bg-rose-500/5 transition-colors cursor-pointer flex items-center justify-between text-rose-500 dark:text-rose-400 group">
          <div class="flex items-center gap-3">
            <span class="text-lg">🚪</span>
            <span class="text-xs font-black text-rose-500 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">Выйти из профиля</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5 text-rose-400 dark:text-rose-500/70 group-hover:text-rose-500 dark:group-hover:text-rose-400 transition-colors">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const pushEnabled = ref(true);
const isDark = ref(false);

// Initialize theme
onMounted(() => {
  const theme = localStorage.getItem('theme');
  if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  } else {
    isDark.value = false;
    document.documentElement.classList.remove('dark');
  }
});

// Watch and apply theme changes
watch(isDark, (newValue) => {
  if (newValue) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
});

// Toggle theme
const toggleTheme = () => {
  isDark.value = !isDark.value;
};

// Format phone number to clean localized string
const formatPhone = (phone) => {
  if (!phone) return '';
  const cleaned = phone.replace(/\D/g, '');
  if (cleaned.length === 11 && cleaned.startsWith('993')) {
    return `+993 (${cleaned.substring(3, 5)}) ${cleaned.substring(5, 7)}-${cleaned.substring(7, 9)}-${cleaned.substring(9)}`;
  }
  return phone;
};

// Interactive Switch for push notifications
const togglePush = () => {
  pushEnabled.value = !pushEnabled.value;
  if (pushEnabled.value && window.askPushPermission) {
    window.askPushPermission();
  }
};

// Handle account logout
const handleLogout = async () => {
  const isDarkMode = document.documentElement.classList.contains('dark');
  if (window.Swal) {
    window.Swal.fire({
      title: 'Выйти из профиля?',
      text: 'Вам потребуется повторный вход для оформления заказов.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Выйти',
      cancelButtonText: 'Отмена',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: isDarkMode ? '#334155' : '#e2e8f0',
      background: isDarkMode ? '#0f172a' : '#ffffff',
      color: isDarkMode ? '#f8fafc' : '#0f172a',
      reverseButtons: true,
      customClass: { popup: isDarkMode ? 'rounded-3xl border border-slate-850 shadow-2xl' : 'rounded-3xl border border-slate-200 shadow-xl' }
    }).then(async (result) => {
      if (result.isConfirmed) {
        await authStore.logout();
        router.push('/login');
      }
    });
  } else {
    if (confirm('Вы уверены, что хотите выйти из профиля?')) {
      await authStore.logout();
      router.push('/login');
    }
  }
};

// Show notification for features in development
const showDevelopmentNotification = () => {
  const isDarkMode = document.documentElement.classList.contains('dark');
  if (window.Swal) {
    window.Swal.fire({
      title: 'В разработке',
      text: 'Эта функция появится в ближайших обновлениях!',
      icon: 'info',
      confirmButtonText: 'Понятно',
      confirmButtonColor: '#f97316',
      background: isDarkMode ? '#0f172a' : '#ffffff',
      color: isDarkMode ? '#f8fafc' : '#0f172a',
      customClass: { popup: isDarkMode ? 'rounded-3xl border border-slate-800 shadow-2xl' : 'rounded-3xl border border-slate-200 shadow-xl' }
    });
  } else {
    alert('Эта функция появится в ближайших обновлениях!');
  }
};
</script>
