<template>
  <div class="space-y-6 select-none">
    <h3 class="text-lg font-bold text-slate-100 font-outfit">Куда доставить?</h3>
    
    <!-- Delivery Type Toggle -->
    <div class="flex p-1 bg-slate-900 border border-slate-800 rounded-xl">
      <button 
        @click="updateType('delivery')"
        class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
        :class="localData.delivery_type === 'delivery' ? 'bg-slate-800 text-orange-400 shadow-sm' : 'text-slate-500 hover:text-slate-300'"
      >
        Доставка
      </button>
      <button 
        @click="updateType('pickup')"
        class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
        :class="localData.delivery_type === 'pickup' ? 'bg-slate-800 text-orange-400 shadow-sm' : 'text-slate-500 hover:text-slate-300'"
      >
        Самовывоз
      </button>
    </div>

    <!-- Address Fields (only for delivery) -->
    <div v-if="localData.delivery_type === 'delivery'" class="space-y-4 animate-fade-in">
      <div>
        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Улица, дом</label>
        <input 
          v-model="localData.address" 
          type="text" 
          placeholder="ул. Азади, д. 45"
          class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600"
        />
      </div>

      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Подъезд</label>
          <input 
            v-model="localData.entrance" 
            type="text" 
            placeholder="1"
            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 text-center"
          />
        </div>
        <div>
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Этаж</label>
          <input 
            v-model="localData.floor" 
            type="text" 
            placeholder="5"
            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 text-center"
          />
        </div>
        <div>
          <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Квартира</label>
          <input 
            v-model="localData.apartment" 
            type="text" 
            placeholder="25"
            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 text-center"
          />
        </div>
      </div>
    </div>

    <!-- Pickup text -->
    <div v-else class="p-4 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-semibold leading-relaxed animate-fade-in">
      Вы сможете забрать заказ в выбранном ресторане. Мы пришлем уведомление, когда блюда будут готовы.
    </div>

    <!-- Phone Number (Required) -->
    <div>
      <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5 ml-1">Контактный телефон</label>
      <input 
        v-model="localData.phone" 
        type="tel" 
        placeholder="+993 6X XXXXXX"
        class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500/50 rounded-xl px-4 py-3 text-sm text-slate-200 outline-none transition-all placeholder:text-slate-600 font-medium tracking-wide"
      />
    </div>

    <!-- Next Button -->
    <button 
      @click="handleNext"
      :disabled="!isValid"
      class="w-full mt-6 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 disabled:from-slate-800 disabled:to-slate-850 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-extrabold text-sm rounded-xl shadow-lg transition-all active:scale-[0.98]"
    >
      Продолжить
    </button>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

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

const updateType = (type) => {
  localData.value.delivery_type = type;
};

const isValid = computed(() => {
  if (!localData.value.phone || localData.value.phone.length < 8) return false;
  if (localData.value.delivery_type === 'delivery') {
    return localData.value.address && localData.value.address.length > 3;
  }
  return true;
});

const handleNext = () => {
  if (isValid.value) {
    emit('next-step');
  }
};
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
