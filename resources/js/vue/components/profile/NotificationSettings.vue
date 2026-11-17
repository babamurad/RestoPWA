<template>
  <section class="rounded-3xl border dark:bg-slate-900/60 dark:border-slate-800/40 bg-white border-slate-200 shadow-sm overflow-hidden" :aria-label="t('profile.notifications.title')">
    <h3 class="px-4 pt-4 text-[11px] font-black uppercase tracking-wider dark:text-slate-400 text-slate-500">{{ t('profile.notifications.title') }}</h3>

    <!-- Order notifications: part of the service, cannot be switched off -->
    <div class="p-4 flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-bold dark:text-slate-200 text-slate-800">{{ t('profile.notifications.order_title') }}</p>
        <p class="mt-1 text-[11px] leading-snug dark:text-slate-400 text-slate-600">{{ t('profile.notifications.order_text') }}</p>
      </div>
      <span class="flex-shrink-0 inline-flex items-center gap-1 text-[10px] font-black px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
        🔒 {{ t('profile.notifications.always_on') }}
      </span>
    </div>

    <!-- Marketing: off until the customer agrees -->
    <div class="border-t dark:border-slate-800/40 border-slate-200 p-4">
      <p class="text-xs font-bold dark:text-slate-200 text-slate-800">{{ t('profile.notifications.marketing_title') }}</p>
      <p class="mt-1 text-[11px] leading-snug dark:text-slate-400 text-slate-600">{{ t('profile.notifications.marketing_text') }}</p>

      <ul class="mt-3 space-y-2.5">
        <li v-for="channel in MARKETING_CHANNELS" :key="channel" class="flex items-center justify-between gap-3">
          <span class="text-xs font-semibold dark:text-slate-300 text-slate-700">{{ t(`profile.notifications.channel_${channel}`) }}</span>
          <button
            type="button"
            :dusk="'marketing-' + channel"
            role="switch"
            :aria-checked="marketing[channel]"
            :aria-label="t(`profile.notifications.channel_${channel}`)"
            :disabled="!loaded || savingChannel !== null"
            @click="toggle(channel)"
            :class="['w-9 h-5 rounded-full p-0.5 transition-all shadow-inner relative flex items-center disabled:opacity-60', marketing[channel] ? 'bg-orange-500' : 'bg-slate-300 dark:bg-slate-700']"
          >
            <span :class="['w-4 h-4 rounded-full bg-white shadow-md transition-all absolute', marketing[channel] ? 'right-0.5' : 'left-0.5']"></span>
          </button>
        </li>
      </ul>

      <p v-if="error" role="alert" class="mt-3 text-[11px] font-semibold text-rose-500">{{ t('profile.notifications.error') }}</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { MARKETING_CHANNELS, useNotificationSettings } from '../../composables/useNotificationSettings';

const { t } = useI18n();
const { marketing, loaded, error, savingChannel, load, toggle } = useNotificationSettings();

onMounted(load);
</script>
