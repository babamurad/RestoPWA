<template>
  <div class="px-4 py-12 max-w-md mx-auto min-h-[80vh] flex flex-col justify-center font-inter">
    <div class="flex justify-end mb-4">
      <LanguageSwitcher />
    </div>

    <div class="bg-gradient-to-tr from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700/40 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
      <div class="text-center mb-8 relative z-10">
        <h2 class="text-2xl font-black dark:text-slate-100 font-outfit tracking-wide mb-2 text-slate-900">{{ t('auth.reset.title') }}</h2>
        <p class="text-xs dark:text-slate-400 font-medium text-slate-600">{{ t('auth.reset.subtitle') }}</p>
      </div>

      <div v-if="!token || !email" class="relative z-10 space-y-4 text-center">
        <div class="p-3.5 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-400 text-xs font-semibold">
          {{ t('auth.reset.invalid_link') }}
        </div>
        <router-link :to="{ name: 'forgot-password' }" class="inline-block text-xs text-orange-400 hover:text-orange-300 font-bold">
          {{ t('auth.reset.request_new') }}
        </router-link>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-5 relative z-10">
        <div v-if="errorMsg" class="p-3.5 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-400 text-xs font-semibold flex items-center gap-2" dusk="reset-error">
          <span>⚠️</span>
          <span>{{ errorMsg }}</span>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.reset.password') }}</label>
          <PasswordInput
            v-model="form.password"
            dusk="reset-password"
            required
            :placeholder="t('auth.register.password_placeholder')"
          />
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-bold dark:text-slate-300 uppercase tracking-wider text-slate-700">{{ t('auth.register.password_confirmation') }}</label>
          <PasswordInput
            v-model="form.password_confirmation"
            dusk="reset-password-confirmation"
            required
            :placeholder="t('auth.register.password_confirmation_placeholder')"
          />
        </div>

        <button
          type="submit"
          dusk="reset-submit"
          :disabled="isLoading"
          class="w-full py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm rounded-xl shadow-lg shadow-orange-500/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2"
        >
          <span v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span>{{ isLoading ? t('auth.reset.submitting') : t('auth.reset.submit') }}</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import apiClient from '../api/client';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';
import PasswordInput from '../components/PasswordInput.vue';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const token = typeof route.query.token === 'string' ? route.query.token : '';
const email = typeof route.query.email === 'string' ? route.query.email : '';

const form = reactive({ password: '', password_confirmation: '' });
const isLoading = ref(false);
const errorMsg = ref('');

const handleSubmit = async () => {
  errorMsg.value = '';

  if (form.password !== form.password_confirmation) {
    errorMsg.value = t('auth.register.errors.password_mismatch');
    return;
  }

  isLoading.value = true;
  try {
    await apiClient.post('/password/reset', { token, email, ...form });
    // The login page shows the success note; the user signs in with the new password
    router.push({ name: 'login', query: { reset: '1' } });
  } catch (err) {
    const data = err.response?.data;
    errorMsg.value = (data?.errors ? Object.values(data.errors).flat().join(' ') : data?.message)
      || (err.response ? t('auth.reset.failed') : t('auth.network_error'));
  } finally {
    isLoading.value = false;
  }
};
</script>
