<template>
  <div class="px-4 py-12 max-w-md mx-auto min-h-[80vh] flex flex-col justify-center font-inter">
    <div class="bg-gradient-to-tr from-slate-800 to-slate-900 border border-slate-700/40 rounded-3xl p-8 shadow-2xl relative overflow-hidden group">
      <!-- Decorative radial gradient background glow -->
      <div class="absolute -right-24 -top-24 w-64 h-64 rounded-full bg-orange-500/10 blur-3xl group-hover:bg-orange-500/15 transition-colors duration-500"></div>
      
      <div class="text-center mb-8 relative z-10">
        <h2 class="text-2xl font-black text-slate-100 font-outfit tracking-wide mb-2">Вход в аккаунт</h2>
        <p class="text-xs text-slate-400 font-medium">Войдите, чтобы отслеживать заказы и сохранять адреса</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6 relative z-10">
        <!-- Error Alert -->
        <div v-if="errorMsg" class="p-3.5 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-400 text-xs font-semibold flex items-center gap-2">
          <span>⚠️</span>
          <span>{{ errorMsg }}</span>
        </div>

        <!-- Email Field -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Email</label>
          <div class="relative">
            <input 
              v-model="form.email" 
              type="email" 
              required
              placeholder="example@mail.com"
              class="w-full bg-slate-950/50 border border-slate-700/50 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold"
            />
          </div>
        </div>

        <!-- Password Field -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Пароль</label>
          <div class="relative">
            <input 
              v-model="form.password" 
              type="password" 
              required
              placeholder="••••••••"
              class="w-full bg-slate-950/50 border border-slate-700/50 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold"
            />
          </div>
        </div>

        <!-- Submit Button -->
        <button 
          type="submit" 
          :disabled="authStore.isLoading"
          class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-orange-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2"
        >
          <span v-if="authStore.isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ authStore.isLoading ? 'Выполняется вход...' : 'Войти' }}</span>
        </button>
      </form>

      <!-- Footer navigation -->
      <div class="mt-8 text-center relative z-10 border-t border-slate-800 pt-6">
        <p class="text-xs text-slate-400 font-semibold">
          Еще нет аккаунта? 
          <router-link :to="{ name: 'register', query: { redirect: $route.query.redirect } }" class="text-orange-400 hover:text-orange-350 transition-colors ml-1 font-bold">
            Зарегистрироваться
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = reactive({
  email: '',
  password: ''
});

const errorMsg = ref('');

const handleLogin = async () => {
  errorMsg.value = '';
  try {
    await authStore.login({ ...form });
    const redirectPath = route.query.redirect || '/profile';
    router.push(redirectPath);
  } catch (err) {
    errorMsg.value = err.message || 'Ошибка входа. Проверьте правильность email и пароля.';
  }
};
</script>
