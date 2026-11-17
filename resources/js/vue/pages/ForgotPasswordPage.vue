<template>
  <div class="px-4 py-12 max-w-md mx-auto min-h-[80vh] flex flex-col justify-center font-inter">
    <div class="flex justify-end mb-4">
      <LanguageSwitcher />
    </div>

    <div class="bg-gradient-to-tr from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700/40 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
      <div class="text-center mb-8 relative z-10">
        <h2 class="text-2xl font-black dark:text-slate-100 font-outfit tracking-wide mb-2 text-slate-900">{{ t('auth.forgot.title') }}</h2>
        <p class="text-xs dark:text-slate-400 font-medium text-slate-600">{{ t('auth.forgot.subtitle') }}</p>
      </div>

      <div v-if="sentMsg" class="relative z-10 space-y-4" dusk="forgot-sent">
        <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/25 rounded-xl text-emerald-500 text-xs font-semibold">
          {{ sentMsg }}
        </div>
        <p class="text-[11px] text-center dark:text-slate-500 text-slate-500 font-medium">{{ t('auth.forgot.sent_hint') }}</p>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-6 relative z-10">
        <div v-if="errorMsg" class="p-3.5 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-400 text-xs font-semibold flex items-center gap-2">
          <span>⚠️</span>
          <span>{{ errorMsg }}</span>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.email') }}</label>
          <input
            v-model="email"
            dusk="forgot-email"
            type="email"
            required
            placeholder="example@mail.com"
            class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl px-4 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
          />
        </div>

        <button
          type="submit"
          dusk="forgot-submit"
          :disabled="isLoading"
          class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-orange-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2"
        >
          <span v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ isLoading ? t('auth.forgot.submitting') : t('auth.forgot.submit') }}</span>
        </button>
      </form>

      <div class="mt-6 text-center relative z-10 border-t dark:border-slate-800 pt-6 border-slate-200">
        <router-link :to="{ name: 'login' }" class="text-xs text-orange-400 hover:text-orange-300 transition-colors font-bold">
          {{ t('auth.forgot.back') }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import apiClient from '../api/client';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';

const route = useRoute();
const { t } = useI18n();

const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
const isLoading = ref(false);
const errorMsg = ref('');
const sentMsg = ref('');

const handleSubmit = async () => {
  errorMsg.value = '';
  isLoading.value = true;
  try {
    const response = await apiClient.post('/password/forgot', { email: email.value });
    sentMsg.value = response.data?.message || t('auth.forgot.sent_hint');
  } catch (err) {
    const data = err.response?.data;
    errorMsg.value = (data?.errors ? Object.values(data.errors).flat().join(' ') : data?.message)
      || (err.response ? t('auth.forgot.failed') : t('auth.network_error'));
  } finally {
    isLoading.value = false;
  }
};
</script>
