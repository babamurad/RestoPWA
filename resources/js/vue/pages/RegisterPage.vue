<template>
  <div class="px-4 py-12 max-w-md mx-auto min-h-[80vh] flex flex-col justify-center font-inter">
    <!-- Language choice for guests -->
    <div class="flex justify-end mb-4">
      <LanguageSwitcher />
    </div>

    <div class="bg-gradient-to-tr from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700/40 rounded-3xl p-8 shadow-2xl relative overflow-hidden group">
      <!-- Decorative radial gradient background glow -->
      <div class="absolute -right-24 -top-24 w-64 h-64 rounded-full bg-orange-500/10 blur-3xl group-hover:bg-orange-500/15 transition-colors duration-500"></div>
      
      <div class="text-center mb-8 relative z-10">
        <h2 class="text-2xl font-black dark:text-slate-100 font-outfit tracking-wide mb-2 text-slate-900">{{ t('auth.register.title') }}</h2>
        <p class="text-xs dark:text-slate-400 font-medium text-slate-600">{{ t('auth.register.subtitle') }}</p>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4 relative z-10">
        <!-- Error Alert -->
        <div v-if="errorMsg" class="p-3.5 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-400 text-xs font-semibold flex items-center gap-2">
          <span>⚠️</span>
          <span>{{ errorMsg }}</span>
        </div>

        <!-- Name Field -->
        <div class="space-y-1">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.register.name') }}</label>
          <input 
            v-model="form.name" 
            type="text" 
            required
            :placeholder="t('auth.register.name_placeholder')"
            class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl px-4 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
          />
        </div>

        <!-- Email Field -->
        <div class="space-y-1">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.email') }}</label>
          <input 
            v-model="form.email" 
            type="email" 
            required
            placeholder="example@mail.com"
            class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl px-4 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
          />
        </div>

        <!-- Phone Field -->
        <div class="space-y-1">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.register.phone') }}</label>
          <div class="relative flex items-center">
            <span class="absolute left-4 dark:text-slate-400 font-bold text-sm text-slate-600">+993</span>
            <input 
              v-model="phoneInput" 
              type="tel" 
              required
              maxlength="8"
              placeholder="65123456"
              class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl pl-[60px] pr-4 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
            />
          </div>
          <p class="text-[10px] dark:text-slate-500 font-semibold pl-1 text-slate-500">{{ t('auth.register.phone_hint') }}</p>
        </div>

        <!-- Password Field -->
        <div class="space-y-1">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.password') }}</label>
          <input 
            v-model="form.password" 
            type="password" 
            required
            :placeholder="t('auth.register.password_placeholder')"
            class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl px-4 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
          />
        </div>

        <!-- Password Confirmation Field -->
        <div class="space-y-1">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.register.password_confirmation') }}</label>
          <input 
            v-model="form.password_confirmation" 
            type="password" 
            required
            :placeholder="t('auth.register.password_confirmation_placeholder')"
            class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl px-4 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
          />
        </div>

        <!-- Legal consent -->
        <label class="flex items-start gap-2.5 text-xs dark:text-slate-400 text-slate-600 font-semibold cursor-pointer select-none">
          <input
            v-model="form.terms_accepted"
            type="checkbox"
            required
            class="mt-0.5 w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-orange-500 focus:ring-orange-500/50"
          />
          <i18n-t keypath="auth.register.consent" tag="span" scope="global">
            <template #terms><a href="/legal/terms" target="_blank" rel="noopener" class="text-orange-400 hover:underline">{{ t('auth.links.terms') }}</a></template>
            <template #offer><a href="/legal/offer" target="_blank" rel="noopener" class="text-orange-400 hover:underline">{{ t('auth.links.offer') }}</a></template>
            <template #privacy><a href="/legal/privacy" target="_blank" rel="noopener" class="text-orange-400 hover:underline">{{ t('auth.links.privacy') }}</a></template>
          </i18n-t>
        </label>

        <!-- Submit Button -->
        <button 
          type="submit" 
          :disabled="isLoading"
          class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-orange-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2 mt-4"
        >
          <span v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ isLoading ? t('auth.register.submitting') : t('auth.register.submit') }}</span>
        </button>
      </form>

      <!-- Google Register -->
      <div class="mt-6 relative z-10">
        <div class="relative flex items-center mb-4">
          <div class="flex-grow border-t dark:border-slate-700 border-slate-300"></div>
          <span class="flex-shrink mx-3 text-[10px] dark:text-slate-500 font-semibold uppercase text-slate-400">{{ t('auth.or') }}</span>
          <div class="flex-grow border-t dark:border-slate-700 border-slate-300"></div>
        </div>
        <a
          :href="`/auth/google/redirect?redirect=${$route.query.redirect || '/profile'}`"
          class="w-full py-3 flex items-center justify-center gap-3 border dark:border-slate-700 border-slate-300 rounded-xl text-sm font-bold dark:text-slate-200 text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800/50 active:scale-[0.98] transition-all"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          {{ t('auth.register.google') }}
        </a>
        <i18n-t keypath="auth.google_consent" tag="p" scope="global" class="mt-2 text-[11px] text-center dark:text-slate-500 text-slate-500 font-medium">
          <template #terms><a href="/legal/terms" target="_blank" rel="noopener" class="text-orange-400 hover:underline">{{ t('auth.links.terms_short') }}</a></template>
          <template #offer><a href="/legal/offer" target="_blank" rel="noopener" class="text-orange-400 hover:underline">{{ t('auth.links.offer_short') }}</a></template>
          <template #privacy><a href="/legal/privacy" target="_blank" rel="noopener" class="text-orange-400 hover:underline">{{ t('auth.links.privacy') }}</a></template>
        </i18n-t>
      </div>

      <!-- Footer navigation -->
      <div class="mt-6 text-center relative z-10 border-t dark:border-slate-800 pt-6 border-slate-200">
        <p class="text-xs dark:text-slate-400 font-semibold text-slate-600">
          {{ t('auth.register.has_account') }}
          <router-link :to="{ name: 'login', query: { redirect: $route.query.redirect } }" class="text-orange-400 hover:text-orange-350 transition-colors ml-1 font-bold">
            {{ t('auth.register.login_link') }}
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import apiClient from '../api/client';
import { currentLocale } from '../i18n';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();

const phoneInput = ref('');
const isLoading = ref(false);
const errorMsg = ref('');

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms_accepted: false
});

const handleRegister = async () => {
  errorMsg.value = '';
  
  if (phoneInput.value.length !== 8 || isNaN(phoneInput.value)) {
    errorMsg.value = t('auth.register.errors.phone_digits');
    return;
  }

  if (form.password !== form.password_confirmation) {
    errorMsg.value = t('auth.register.errors.password_mismatch');
    return;
  }

  if (!form.terms_accepted) {
    errorMsg.value = t('auth.register.errors.terms_required');
    return;
  }

  isLoading.value = true;
  form.phone = phoneInput.value; // backend AuthController will prepend '+993'

  try {
    const response = await apiClient.post('/register', { ...form, locale: currentLocale() });
    if (response.data && response.data.success) {
      // Re-fetch user in store
      await authStore.fetchUser();
      const redirectPath = route.query.redirect || '/profile';
      router.push(redirectPath);
    } else {
      errorMsg.value = response.data.message || t('auth.register.errors.failed');
    }
  } catch (err) {
    const errors = err.response?.data?.errors;
    if (errors) {
      errorMsg.value = Object.values(errors).flat().join(' ');
    } else {
      errorMsg.value = err.response?.data?.message || t('auth.register.errors.generic');
    }
  } finally {
    isLoading.value = false;
  }
};
</script>
