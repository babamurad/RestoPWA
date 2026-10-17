<template>
  <div class="px-4 py-6 max-w-xl mx-auto pb-24 font-inter">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
      <button @click="$router.back()" class="p-2 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 text-slate-800 dark:text-slate-200">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
      </button>
      <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 font-outfit">{{ t('profile.support.title') }}</h1>
    </div>

    <!-- Contact Form -->
    <div v-if="!success" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl p-5 shadow-lg">
      <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">{{ t('profile.support.intro') }}</p>
      
      <form @submit.prevent="submitSupport" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">{{ t('profile.support.subject_label') }}</label>
          <input v-model="form.subject" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100">
        </div>
        
        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">{{ t('profile.support.message_label') }}</label>
          <textarea v-model="form.message" required rows="5" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100"></textarea>
        </div>

        <button type="submit" :disabled="saving" class="w-full py-4 rounded-xl font-bold text-white bg-orange-500 hover:bg-orange-600 transition-colors disabled:opacity-70 flex justify-center mt-4">
          {{ saving ? t('profile.support.sending') : t('profile.support.send') }}
        </button>
      </form>
    </div>

    <!-- Success Screen -->
    <div v-else class="text-center py-12 bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 px-6">
      <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-10 h-10">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 font-outfit mb-2">{{ t('profile.support.sent_title') }}</h2>
      <p class="text-slate-600 dark:text-slate-400 mb-8">{{ t('profile.support.sent_text') }}</p>
      
      <button @click="$router.push('/profile')" class="px-8 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl font-bold text-slate-700 dark:text-slate-200 transition-colors">
        {{ t('profile.support.back_to_profile') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import apiClient from '../../api/client';

const { t } = useI18n();

const saving = ref(false);
const success = ref(false);
const form = ref({
  subject: '',
  message: ''
});

const submitSupport = async () => {
  saving.value = true;
  try {
    await apiClient.post('/profile/support', form.value);
    success.value = true;
  } catch (error) {
    console.error(error);
    alert(t('profile.support.send_error'));
  } finally {
    saving.value = false;
  }
};
</script>
