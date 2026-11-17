<template>
  <div class="relative">
    <input
      v-bind="$attrs"
      :value="modelValue"
      :type="visible ? 'text' : 'password'"
      class="w-full dark:bg-slate-950/50 border dark:border-slate-700/50 rounded-xl pl-4 pr-12 py-3 text-sm dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-orange-500/80 focus:ring-1 focus:ring-orange-500/50 transition-all font-semibold bg-slate-50 border-slate-300 text-slate-900"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      tabindex="-1"
      :aria-pressed="visible"
      class="absolute inset-y-0 right-0 px-4 flex items-center text-slate-500 hover:text-orange-500 transition-colors"
      @click="visible = !visible"
    >
      <svg v-if="!visible" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
        <circle cx="12" cy="12" r="3" />
      </svg>
      <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
        <line x1="1" y1="1" x2="23" y2="23" />
      </svg>
    </button>
  </div>
</template>

<script>
export default { inheritAttrs: false };
</script>

<script setup>
import { ref } from 'vue';

defineProps({ modelValue: { type: String, default: '' } });
defineEmits(['update:modelValue']);

const visible = ref(false);
</script>
