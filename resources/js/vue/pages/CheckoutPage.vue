<template>
  <div class="px-4 py-6 max-w-lg mx-auto md:max-w-4xl pb-24 font-inter">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
      <button @click="goBack" class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700/50 flex items-center justify-center text-slate-300 hover:text-orange-400 hover:border-orange-500/30 transition-all active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </button>
      <h2 class="text-xl font-bold text-slate-100 font-outfit tracking-wide">Оформление заказа</h2>
    </div>

    <!-- Steps Indicator -->
    <div class="flex items-center justify-between mb-8 relative">
      <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-slate-800 -z-10"></div>
      
      <div v-for="(step, index) in steps" :key="index" class="flex flex-col items-center gap-2 bg-slate-950 px-2">
        <div 
          class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs border-2 transition-all duration-300"
          :class="[
            currentStep > index ? 'bg-orange-500 border-orange-500 text-white' : 
            currentStep === index ? 'bg-slate-900 border-orange-500 text-orange-400 shadow-lg shadow-orange-500/20' : 
            'bg-slate-900 border-slate-800 text-slate-500'
          ]"
        >
          <span v-if="currentStep > index">✓</span>
          <span v-else>{{ index + 1 }}</span>
        </div>
        <span class="text-[10px] font-black uppercase tracking-wider" :class="currentStep >= index ? 'text-slate-300' : 'text-slate-500'">{{ step.title }}</span>
      </div>
    </div>

    <!-- Step Components -->
    <div class="bg-slate-850/50 border border-slate-800/80 rounded-3xl p-6 shadow-xl relative overflow-hidden">
      <transition name="step-fade" mode="out-in">
        <component 
          :is="currentStepComponent" 
          :order-data="orderData"
          @update-data="updateOrderData"
          @next-step="nextStep"
          @submit="submitOrder"
        />
      </transition>

      <!-- Loading overlay during submission -->
      <div v-if="ordersStore.isLoading" class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex flex-col items-center justify-center">
        <div class="w-12 h-12 border-4 border-orange-500/30 border-t-orange-500 rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-bold text-slate-200 animate-pulse">Отправка заказа...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCartStore } from '../stores/cart';
import { useOrdersStore } from '../stores/orders';

import CheckoutAddressStep from '../components/checkout/CheckoutAddressStep.vue';
import CheckoutPaymentStep from '../components/checkout/CheckoutPaymentStep.vue';
import CheckoutConfirmStep from '../components/checkout/CheckoutConfirmStep.vue';

const router = useRouter();
const cartStore = useCartStore();
const ordersStore = useOrdersStore();

const currentStep = ref(0);

const steps = [
  { title: 'Доставка', component: CheckoutAddressStep },
  { title: 'Оплата', component: CheckoutPaymentStep },
  { title: 'Подтверждение', component: CheckoutConfirmStep }
];

const currentStepComponent = computed(() => steps[currentStep.value].component);

// Global order state passed to children
const orderData = ref({
  delivery_type: 'delivery',
  address: '',
  entrance: '',
  floor: '',
  apartment: '',
  phone: '',
  comment: '',
  payment_method: 'cash',
});

onMounted(() => {
  if (cartStore.isEmpty) {
    router.replace('/cart');
  }
});

const goBack = () => {
  if (currentStep.value > 0) {
    currentStep.value--;
  } else {
    router.back();
  }
};

const updateOrderData = (data) => {
  orderData.value = { ...orderData.value, ...data };
};

const nextStep = () => {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++;
  }
};

const submitOrder = async () => {
  if (cartStore.isEmpty) return;

  const payload = {
    vendor_id: cartStore.vendorId,
    delivery_type: orderData.value.delivery_type,
    address: orderData.value.address,
    entrance: orderData.value.entrance,
    floor: orderData.value.floor,
    apartment: orderData.value.apartment,
    phone: orderData.value.phone,
    comment: orderData.value.comment,
    payment_method: orderData.value.payment_method,
    items: cartStore.items.map(item => ({
      product_id: item.id,
      quantity: item.quantity,
      price: Math.round(item.price * 100),
      modifiers: item.modifiers || []
    })),
    idempotency_key: self.crypto.randomUUID()
  };

  try {
    const result = await ordersStore.submitOrder(payload);
    
    // Clear cart upon successful order creation
    cartStore.clearCart();

    if (result && result.data && result.data.redirect_url) {
      window.location.href = result.data.redirect_url;
    } else if (result && result.data && result.data.order_id) {
      router.push(`/order/success/${result.data.order_id}`);
    } else {
      router.push('/profile');
    }
  } catch (error) {
    // Detect network / offline error
    const isNetworkError = !navigator.onLine || error.message === 'Network Error' || !error.response;
    
    if (isNetworkError && window.CartService) {
      try {
        // Save to IndexedDB queue
        await window.CartService.queueOrder(payload);
        
        // Clear local cart
        cartStore.clearCart();
        
        // Register SW background sync if possible
        if ('serviceWorker' in navigator) {
          try {
            const registration = await navigator.serviceWorker.ready;
            if (registration.sync) {
              await registration.sync.register('order-sync');
            }
          } catch (syncError) {
            console.error('Service Worker sync registration failed:', syncError);
          }
        }

        if (window.Swal) {
          window.Swal.fire({
            title: 'Заказ сохранён!',
            text: 'Соединение с интернетом отсутствует. Заказ будет отправлен автоматически, как только вы будете онлайн!',
            icon: 'success',
            confirmButtonText: 'Отлично',
            confirmButtonColor: '#f97316',
            customClass: { popup: 'rounded-2xl bg-slate-900 text-slate-100' },
          }).then(() => {
            router.push('/profile');
          });
        } else {
          alert('Соединение потеряно. Заказ сохранён и будет отправлен при появлении сети!');
          router.push('/profile');
        }
        return;
      } catch (dbError) {
        console.error('Failed to queue offline order:', dbError);
      }
    }

    // Fallback: normal error handling
    if (window.Swal) {
      window.Swal.fire({
        title: 'Ошибка',
        text: ordersStore.error || 'Не удалось оформить заказ. Попробуйте позже.',
        icon: 'error',
        confirmButtonColor: '#f97316',
        customClass: { popup: 'rounded-2xl bg-slate-900 text-slate-100' },
      });
    } else {
      alert(ordersStore.error || 'Ошибка оформления заказа');
    }
  }
};
</script>

<style scoped>
.step-fade-enter-active,
.step-fade-leave-active {
  transition: all 0.3s ease;
}
.step-fade-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.step-fade-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
