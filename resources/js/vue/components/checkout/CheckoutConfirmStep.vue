<template>
  <div class="space-y-6 select-none">
    <h3 class="text-lg font-bold dark:text-slate-100 font-outfit text-slate-900">Проверьте данные заказа</h3>

    <!-- Summary Details -->
    <div class="space-y-4 dark:bg-slate-900 border dark:border-slate-800 rounded-2xl p-5 bg-slate-50 border-slate-200">
      <div class="flex justify-between items-start border-b dark:border-slate-800/80 pb-3 border-slate-200">
        <div class="text-xs font-bold dark:text-slate-400 text-slate-600">Тип доставки</div>
        <div class="text-sm font-black dark:text-slate-100 text-right text-slate-900">
          {{ orderData.delivery_type === 'pickup' ? 'Самовывоз' : 'Курьерская доставка' }}
        </div>
      </div>
      
      <div v-if="orderData.delivery_type === 'delivery'" class="flex justify-between items-start border-b dark:border-slate-800/80 pb-3 border-slate-200">
        <div class="text-xs font-bold dark:text-slate-400 text-slate-600">Адрес</div>
        <div class="text-sm font-bold dark:text-slate-200 text-right max-w-[60%] text-slate-800">
          {{ orderData.address }}
          <span class="dark:text-slate-500 block text-[10px] text-slate-500">
            {{ [
                orderData.entrance ? 'подъезд ' + orderData.entrance : '',
                orderData.floor ? 'этаж ' + orderData.floor : '',
                orderData.apartment ? 'кв. ' + orderData.apartment : ''
               ].filter(Boolean).join(', ') 
            }}
          </span>
        </div>
      </div>

      <div class="flex justify-between items-start border-b dark:border-slate-800/80 pb-3 border-slate-200">
        <div class="text-xs font-bold dark:text-slate-400 text-slate-600">Телефон</div>
        <div class="text-sm font-black dark:text-slate-200 text-right text-slate-800">
          {{ orderData.phone }}
        </div>
      </div>

      <div class="flex justify-between items-start border-b dark:border-slate-800/80 pb-3 border-slate-200">
        <div class="text-xs font-bold dark:text-slate-400 text-slate-600">Оплата</div>
        <div class="text-sm font-bold dark:text-slate-200 text-right flex items-center gap-1.5 justify-end text-slate-800">
          <span v-if="orderData.payment_method === 'cash'">💵 Наличными</span>
          <span v-else-if="orderData.payment_method === 'terminal'">💳 Картой при получении</span>
          <span v-else>📱 Онлайн оплата</span>
        </div>
      </div>

      <div v-if="orderData.comment" class="pt-1">
        <div class="text-xs font-bold dark:text-slate-400 mb-1 text-slate-600">Комментарий</div>
        <div class="text-[11px] font-medium dark:text-slate-300 italic dark:bg-slate-950 p-2.5 rounded-lg border dark:border-slate-800 text-slate-700 bg-slate-50 border-slate-200">
          {{ orderData.comment }}
        </div>
      </div>
    </div>

    <!-- Final Price & CTA -->
    <div class="pt-4 border-t dark:border-slate-800 border-slate-200">
      <div class="flex justify-between items-baseline mb-6">
        <span class="text-sm font-bold dark:text-slate-400 text-slate-600">Итого к оплате</span>
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
