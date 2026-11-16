<template>
  <transition name="modal-fade">
    <div 
      v-if="show" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 dark:bg-slate-950/80 backdrop-blur-md bg-slate-50"
      @click.self="close"
    >
      <!-- Modal Box -->
      <div class="relative w-full max-w-md dark:bg-slate-900 border dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-2xl transition-all duration-300 max-h-[90vh] flex flex-col bg-slate-50 border-slate-200">
        <!-- Close Button floating -->
        <button 
          @click="close"
          class="absolute top-4 right-4 z-10 w-9 h-9 rounded-xl dark:bg-slate-950/85 dark:hover:bg-slate-950 hover:text-orange-400 dark:text-slate-400 transition-colors flex items-center justify-center shadow-md active:scale-90 bg-slate-50 hover:bg-slate-50 text-slate-600"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Product Image (Vibrant & Large) -->
        <div class="relative w-full h-56 dark:bg-slate-950 overflow-hidden border-b dark:border-slate-800 flex-shrink-0 bg-slate-50 border-slate-200">
          <img 
            :src="product.image_url || 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80'" 
            :alt="product.name"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-900 dark:from-slate-900 via-transparent to-transparent"></div>
          
          <!-- Availability Badge -->
          <div 
            v-if="!product.is_available"
            class="absolute top-4 left-4 px-3 py-1 rounded-lg bg-rose-500 text-white text-[10px] font-black uppercase tracking-wider shadow-md"
          >
            {{ t('menu.out_of_stock') }}
          </div>
        </div>

        <!-- Details Area (scrollable if content overflows) -->
        <div class="p-6 overflow-y-auto flex-1 space-y-4 select-none">
          <div>
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-lg font-black dark:text-slate-100 font-outfit tracking-wide leading-snug text-slate-900">
                {{ product.name }}
              </h3>
              <!-- Weight detail -->
              <span v-if="product.weight_g" class="text-xs dark:text-slate-400 font-bold whitespace-nowrap dark:bg-slate-800/60 border dark:border-slate-800 px-2.5 py-1 rounded-xl text-slate-600 bg-white border-slate-200">
                {{ t('menu.weight_short', { weight: product.weight_g }) }}
              </span>
            </div>
            
            <p v-if="product.description" class="text-xs dark:text-slate-400 mt-3 leading-relaxed font-semibold text-slate-600">
              {{ product.description }}
            </p>
            <p v-else class="text-xs dark:text-slate-500 italic mt-3 text-slate-500">{{ t('menu.modal.no_description') }}</p>
          </div>

          <!-- Modifier groups: choose what goes into the dish -->
          <div v-for="group in groups" :key="group.id" class="space-y-2 pt-3 border-t dark:border-slate-800/40 border-slate-200">
            <div class="flex items-baseline justify-between gap-3">
              <h4 class="text-xs font-black uppercase tracking-wider dark:text-slate-300 font-outfit text-slate-700">{{ group.name }}</h4>
              <span
                class="text-[10px] font-bold whitespace-nowrap"
                :class="groupError(group) ? 'text-rose-500' : (group.min > 0 ? 'text-orange-500' : 'dark:text-slate-500 text-slate-500')"
              >{{ groupHint(group) }}</span>
            </div>

            <div class="space-y-1.5">
              <template v-for="option in group.options" :key="option.id">
                <!-- Counter: portions with - / + -->
                <div v-if="group.type === 'counter'" class="flex items-center justify-between gap-3 text-xs">
                  <span class="dark:text-slate-300 font-bold text-slate-700 min-w-0 truncate">
                    {{ option.name }}
                    <span v-if="option.price > 0" class="text-orange-400 font-black ml-1">+{{ option.price }} TMT</span>
                  </span>
                  <div class="flex items-center gap-1 flex-shrink-0">
                    <button type="button" @click="setPortions(group, option, portions(group, option) - 1)" :disabled="portions(group, option) === 0"
                            :aria-label="t('menu.modal.fewer', { name: option.name })"
                            class="w-7 h-7 rounded-lg dark:bg-slate-800 bg-white border dark:border-slate-700 border-slate-300 font-black disabled:opacity-40 active:scale-90">-</button>
                    <span class="w-6 text-center font-black dark:text-slate-200 text-slate-800">{{ portions(group, option) }}</span>
                    <button type="button" @click="setPortions(group, option, portions(group, option) + 1)" :disabled="!canAddPortion(group)"
                            :aria-label="t('menu.modal.more', { name: option.name })"
                            class="w-7 h-7 rounded-lg dark:bg-slate-800 bg-white border dark:border-slate-700 border-slate-300 font-black disabled:opacity-40 active:scale-90">+</button>
                  </div>
                </div>

                <!-- Radio / checkbox -->
                <label v-else class="flex items-center justify-between gap-3 text-xs cursor-pointer"
                       :class="isDisabled(group, option) ? 'opacity-40 cursor-not-allowed' : ''">
                  <span class="flex items-center gap-2.5 min-w-0">
                    <input
                      :type="group.type === 'radio' ? 'radio' : 'checkbox'"
                      :name="'mod-' + group.id"
                      :checked="portions(group, option) > 0"
                      :disabled="isDisabled(group, option)"
                      @change="toggle(group, option)"
                      class="w-4 h-4 accent-orange-500 flex-shrink-0"
                    />
                    <span class="dark:text-slate-300 font-bold text-slate-700 truncate">{{ option.name }}</span>
                  </span>
                  <span v-if="option.price > 0" class="text-orange-400 font-black flex-shrink-0">+{{ option.price }} TMT</span>
                </label>
              </template>
            </div>
          </div>
        </div>

        <!-- Footer Control Area (Fixed bottom inside modal) -->
        <div class="p-6 border-t dark:border-slate-800 dark:bg-slate-900 flex-shrink-0 flex items-center justify-between gap-4 border-slate-200 bg-slate-50">
          <!-- Quantity counter buttons -->
          <div class="flex items-center dark:bg-slate-950 border dark:border-slate-800 rounded-2xl p-1.5 shadow-inner bg-slate-50 border-slate-200">
            <button 
              @click="decreaseQty" 
              class="w-9 h-9 rounded-xl dark:bg-slate-900 dark:hover:bg-slate-800 hover:text-orange-400 dark:text-slate-300 font-black text-sm active:scale-90 transition-all bg-slate-50 hover:bg-white text-slate-700"
            >
              -
            </button>
            <span class="w-10 text-center text-sm font-black dark:text-slate-200 text-slate-800">{{ quantity }}</span>
            <button 
              @click="increaseQty" 
              class="w-9 h-9 rounded-xl dark:bg-slate-900 dark:hover:bg-slate-800 hover:text-orange-400 dark:text-slate-300 font-black text-sm active:scale-90 transition-all bg-slate-50 hover:bg-white text-slate-700"
            >
              +
            </button>
          </div>

          <!-- Add to Cart CTA -->
          <button 
            @click="addToCart"
            :disabled="!product.is_available || !isValid"
            class="flex-1 py-3.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 dark:disabled:from-slate-800 dark:disabled:to-slate-800 dark:disabled:text-slate-500 disabled:cursor-not-allowed text-white font-extrabold text-xs rounded-2xl shadow-lg shadow-orange-500/10 hover:shadow-orange-500/20 active:scale-[0.97] transition-all flex items-center justify-center gap-1.5 disabled:from-slate-200 disabled:to-slate-200 disabled:text-slate-500"
          >
            <span>{{ t('menu.modal.add_to_cart_for') }}</span>
            <span class="font-black underline decoration-2 decoration-orange-300">{{ t('common.price', { amount: totalPrice }) }}</span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useModifierSelection } from '../composables/useModifierSelection';

const { t } = useI18n();

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'add-to-cart']);

const quantity = ref(1);

const groups = computed(() => (Array.isArray(props.product?.modifiers) ? props.product.modifiers : []).filter(
  (group) => group && Array.isArray(group.options) && group.options.length > 0
));

const {
  portions, reset: resetSelection, canAddPortion, isDisabled, toggle, setPortions,
  groupError, isValid, chosenModifiers, modifiersTotal,
} = useModifierSelection(groups);

// Reset quantity and choice when the modal opens. The page may mount the modal already
// open (v-if on the product), so this also runs once at the start, not only on a change.
watch(() => [props.show, props.product], ([isOpen]) => {
  if (isOpen) {
    quantity.value = 1;
    resetSelection();
  }
}, { immediate: true });

const groupHint = (group) => {
  if (group.type === 'radio') {
    return group.min > 0 ? t('menu.modal.choose_one') : t('menu.modal.optional');
  }
  if (group.min > 0 && group.max && group.min === group.max) {
    return t('menu.modal.choose_exactly', { n: group.min });
  }
  if (group.min > 0) {
    return group.max ? t('menu.modal.choose_between', { min: group.min, max: group.max }) : t('menu.modal.choose_at_least', { n: group.min });
  }
  return group.max ? t('menu.modal.up_to', { n: group.max }) : t('menu.modal.optional');
};

const totalPrice = computed(() => {
  if (!props.product) return 0;
  return Math.round((Number(props.product.price) + modifiersTotal.value) * quantity.value * 100) / 100;
});

const increaseQty = () => {
  quantity.value++;
};

const decreaseQty = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

const close = () => {
  emit('close');
};

const addToCart = () => {
  if (!props.product.is_available) return;
  if (!isValid.value) return;
  emit('add-to-cart', {
    product: props.product,
    quantity: quantity.value,
    modifiers: chosenModifiers.value
  });
  close();
};

const handleImageError = (e) => {
  e.target.src = 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?w=600&auto=format&fit=crop&q=80';
};
</script>

<style scoped>
/* Modal Animations */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-from .relative,
.modal-fade-leave-to .relative {
  transform: scale(0.9) translateY(20px);
  opacity: 0;
}

.modal-fade-enter-to .relative,
.modal-fade-leave-from .relative {
  transform: scale(1) translateY(0);
  opacity: 1;
}
</style>
