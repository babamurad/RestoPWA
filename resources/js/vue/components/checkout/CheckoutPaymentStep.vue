<template>
  <div class="space-y-6 select-none">
    <h3 class="text-lg font-bold dark:text-slate-100 font-outfit text-slate-900">Способ оплаты</h3>

    <div class="space-y-3">
      <!-- Cash Option -->
      <label class="flex items-center gap-4 p-4 rounded-2xl border cursor-pointer transition-all duration-200"
             :class="localData.payment_method === 'cash' ? 'bg-orange-500/10 border-orange-500 shadow-md shadow-orange-500/5' : 'dark:bg-slate-900 dark:border-slate-800 dark:hover:border-slate-700 bg-slate-50 border-slate-200 hover:border-slate-300'">
        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
             :class="localData.payment_method === 'cash' ? 'border-orange-500' : 'dark:border-slate-600 border-slate-400'">
          <div v-if="localData.payment_method === 'cash'" class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>
        </div>
        <div class="flex-1">
          <div class="text-sm font-bold dark:text-slate-100 text-slate-900">Наличными</div>
          <div class="text-[10px] font-semibold dark:text-slate-500 mt-0.5 text-slate-500">Оплата курьеру при получении</div>
        </div>
        <span class="text-xl">💵</span>
      </label>

      <!-- Terminal Option -->
      <label class="flex items-center gap-4 p-4 rounded-2xl border cursor-pointer transition-all duration-200"
             :class="localData.payment_method === 'terminal' ? 'bg-orange-500/10 border-orange-500 shadow-md shadow-orange-500/5' : 'dark:bg-slate-900 dark:border-slate-800 dark:hover:border-slate-700 bg-slate-50 border-slate-200 hover:border-slate-300'">
        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
             :class="localData.payment_method === 'terminal' ? 'border-orange-500' : 'dark:border-slate-600 border-slate-400'">
          <div v-if="localData.payment_method === 'terminal'" class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>
        </div>
        <div class="flex-1">
          <div class="text-sm font-bold dark:text-slate-100 text-slate-900">Картой при получении</div>
          <div class="text-[10px] font-semibold dark:text-slate-500 mt-0.5 text-slate-500">Курьер приедет с терминалом</div>
        </div>
        <span class="text-xl">💳</span>
      </label>

      <!-- Online Option -->
      <label class="flex items-center gap-4 p-4 rounded-2xl border cursor-pointer transition-all duration-200"
             :class="localData.payment_method === 'online' ? 'bg-orange-500/10 border-orange-500 shadow-md shadow-orange-500/5' : 'dark:bg-slate-900 dark:border-slate-800 dark:hover:border-slate-700 bg-slate-50 border-slate-200 hover:border-slate-300'">
        <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
             :class="localData.payment_method === 'online' ? 'border-orange-500' : 'dark:border-slate-600 border-slate-400'">
          <div v-if="localData.payment_method === 'online'" class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>
        </div>
        <div class="flex-1">
          <div class="text-sm font-bold dark:text-slate-100 text-slate-900">Онлайн оплата</div>
          <div class="text-[10px] font-semibold dark:text-slate-500 mt-0.5 text-slate-500">Перенаправление на шлюз оплаты</div>
        </div>
        <span class="text-xl">📱</span>
      </label>
    </div>

    <!-- Optional Comment -->
    <div class="pt-2">
      <label class="block text-[10px] font-black uppercase tracking-wider dark:text-slate-400 mb-1.5 ml-1 text-slate-600">Комментарий к заказу (необязательно)</label>
      <textarea 
        v-model="localData.comment" 
        rows="2"
        placeholder="Сдача с 1000, не звонить в дверь..."
        class="w-full dark:bg-slate-900 border dark:border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm dark:text-slate-200 outline-none transition-all placeholder:text-slate-400 dark:text-slate-600 resize-none bg-slate-50 border-slate-200 text-slate-800 text-slate-400"
      ></textarea>
    </div>

    <!-- Next Button -->
    <button 
      @click="handleNext"
      class="w-full mt-4 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-extrabold text-sm rounded-xl shadow-lg transition-all active:scale-[0.98]"
    >
      Перейти к подтверждению
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  orderData: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['update-data', 'next-step']);

const localData = ref({ ...props.orderData });

watch(localData, (newVal) => {
  emit('update-data', newVal);
}, { deep: true });

const handleNext = () => {
  emit('next-step');
};
</script>
