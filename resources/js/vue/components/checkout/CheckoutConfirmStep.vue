<template>
  <div class="space-y-6 select-none">
    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 font-outfit">Проверьте данные заказа</h3>

    <!-- Summary Details -->
    <div class="space-y-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
      <div class="flex justify-between items-start border-b border-slate-200 dark:border-slate-800/80 pb-3">
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400">Тип доставки</div>
        <div class="text-sm font-black text-slate-900 dark:text-slate-100 text-right">
          {{ orderData.delivery_type === 'pickup' ? 'Самовывоз' : 'Курьерская доставка' }}
        </div>
      </div>
      
      <div v-if="orderData.delivery_type === 'delivery'" class="flex justify-between items-start border-b border-slate-200 dark:border-slate-800/80 pb-3">
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400">Адрес</div>
        <div class="text-sm font-bold text-slate-800 dark:text-slate-200 text-right max-w-[60%]">
          {{ orderData.address }}
          <span class="text-slate-500 dark:text-slate-500 block text-[10px]">
            {{ [
                orderData.entrance ? 'подъезд ' + orderData.entrance : '',
                orderData.floor ? 'этаж ' + orderData.floor : '',
                orderData.apartment ? 'кв. ' + orderData.apartment : ''
               ].filter(Boolean).join(', ') 
            }}
          </span>
        </div>
      </div>

      <div class="flex justify-between items-start border-b border-slate-200 dark:border-slate-800/80 pb-3">
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400">Телефон</div>
        <div class="text-sm font-black text-slate-800 dark:text-slate-200 text-right">
          {{ orderData.phone }}
        </div>
      </div>

      <div class="flex justify-between items-start border-b border-slate-200 dark:border-slate-800/80 pb-3">
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400">Оплата</div>
        <div class="text-sm font-bold text-slate-800 dark:text-slate-200 text-right flex items-center gap-1.5 justify-end">
          <span v-if="orderData.payment_method === 'cash'">💵 Наличными</span>
          <span v-else-if="orderData.payment_method === 'terminal'">💳 Картой при получении</span>
          <span v-else>📱 Онлайн оплата</span>
        </div>
      </div>

      <div v-if="orderData.comment" class="pt-1">
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Комментарий</div>
        <div class="text-[11px] font-medium text-slate-700 dark:text-slate-300 italic bg-slate-50 dark:bg-slate-950 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
          {{ orderData.comment }}
        </div>
      </div>
    </div>

    <!-- Final Price & CTA -->
    <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
      <div class="flex justify-between items-baseline mb-6">
        <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Итого к оплате</span>
        <span class="text-3xl font-black text-orange-500 font-outfit">{{ finalTotal }} TMT</span>
      </div>

      <button 
        @click="$emit('submit')"
        class="w-full py-4 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-extrabold text-base rounded-2xl shadow-lg shadow-orange-500/20 active:scale-[0.98] transition-all flex items-center justify-center gap-2"
      >
        Подтвердить заказ
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useCartStore } from '../../stores/cart';

const cartStore = useCartStore();

const props = defineProps({
  orderData: {
    type: Object,
    required: true
  }
});

defineEmits(['submit']);

import { computed } from 'vue';

const finalTotal = computed(() => {
  const subtotal = cartStore.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  if (props.orderData.delivery_type === 'delivery') {
    return subtotal + cartStore.deliveryFee;
  }
  return subtotal;
});
</script>
