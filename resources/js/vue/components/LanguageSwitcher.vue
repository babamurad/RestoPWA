<template>
  <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800" role="group" :aria-label="t('common.language')">
    <button
      v-for="code in SUPPORTED_LOCALES"
      :key="code"
      type="button"
      @click="choose(code)"
      :aria-pressed="locale === code"
      class="px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wide transition-colors"
      :class="locale === code
        ? 'bg-orange-500 text-white shadow'
        : 'text-slate-600 dark:text-slate-300 hover:text-orange-500'"
    >
      {{ full ? LOCALE_NAMES[code] : code.toUpperCase() }}
    </button>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import { LOCALE_NAMES, SUPPORTED_LOCALES } from '../i18n';

defineProps({
  // Show "Русский / Türkmençe" instead of "RU / TK"
  full: { type: Boolean, default: false },
});

const { t, locale } = useI18n();
const authStore = useAuthStore();

const choose = async (code) => {
  if (code === locale.value) return;
  try {
    await authStore.changeLocale(code);
  } catch (e) {
    // The UI already switched; the profile will be updated on the next change
    console.error('Failed to save language', e);
  }
};
</script>
