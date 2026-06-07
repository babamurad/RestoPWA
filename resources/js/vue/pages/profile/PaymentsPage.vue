<template>
  <div class="px-4 py-6 max-w-xl mx-auto pb-24 font-inter">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
      <button @click="$router.back()" class="p-2 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 text-slate-800 dark:text-slate-200">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
      </button>
      <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 font-outfit">Способы оплаты</h1>
    </div>

    <div v-if="loading" class="text-center py-10 text-slate-500">Загрузка...</div>

    <div v-else class="space-y-4">
      <div v-if="methods.length === 0" class="text-center py-10">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-2xl">💳</span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 font-medium">У вас пока нет привязанных карт</p>
      </div>

      <div v-else class="space-y-3">
        <div 
          v-for="method in methods" 
          :key="method.id"
          class="bg-gradient-to-r from-slate-800 to-slate-900 text-white rounded-2xl p-5 shadow-md relative overflow-hidden"
        >
          <!-- Card decoration -->
          <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
          <div class="absolute right-4 top-4">
            <button @click="deleteMethod(method.id)" class="text-slate-400 hover:text-white transition-colors bg-white/10 p-2 rounded-full">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
          </div>
          
          <div class="mb-6">
            <svg class="w-10 h-8 opacity-80" viewBox="0 0 36 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22 12C22 16.9706 17.9706 21 13 21C8.02944 21 4 16.9706 4 12C4 7.02944 8.02944 3 13 3C17.9706 3 22 7.02944 22 12Z" fill="#EB001B"/>
              <path d="M32 12C32 16.9706 27.9706 21 23 21C18.0294 21 14 16.9706 14 12C14 7.02944 18.0294 3 23 3C27.9706 3 32 7.02944 32 12Z" fill="#F79E1B"/>
            </svg>
          </div>
          <div class="text-lg tracking-widest font-mono">{{ method.card_mask }}</div>
          <div class="mt-2 text-xs text-slate-400 uppercase tracking-wider flex items-center justify-between">
            <span>{{ method.card_type || 'Банковская карта' }}</span>
            <span v-if="method.is_default" class="bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded-full font-bold">Основная</span>
          </div>
        </div>
      </div>

      <!-- Simple mock form for adding a card -->
      <div v-if="showForm" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl p-5 shadow-lg mt-6">
        <h3 class="font-bold text-lg mb-4 text-slate-800 dark:text-slate-100">Привязать карту</h3>
        <form @submit.prevent="saveMethod" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Номер карты (симуляция)</label>
            <input v-model="form.cardNumber" type="text" placeholder="0000 0000 0000 0000" maxlength="19" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100 font-mono">
          </div>
          
          <label class="flex items-center gap-3 py-2 cursor-pointer">
            <input v-model="form.is_default" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Сделать основной</span>
          </label>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showForm = false" class="flex-1 py-3.5 rounded-xl font-bold text-slate-500 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
              Отмена
            </button>
            <button type="submit" :disabled="saving" class="flex-1 py-3.5 rounded-xl font-bold text-white bg-orange-500 hover:bg-orange-600 transition-colors disabled:opacity-70">
              {{ saving ? 'Сохранение...' : 'Привязать' }}
            </button>
          </div>
        </form>
      </div>

      <button v-else @click="showForm = true; form = {cardNumber: '', is_default: true}" class="w-full mt-6 bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20 font-bold py-3.5 rounded-2xl flex items-center justify-center gap-2 hover:bg-orange-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Добавить карту
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';

const methods = ref([]);
const loading = ref(true);
const showForm = ref(false);
const saving = ref(false);
const form = ref({
  cardNumber: '',
  is_default: true
});

const fetchMethods = async () => {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/profile/payments');
    methods.value = data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const saveMethod = async () => {
  saving.value = true;
  
  // Format card to mask (e.g., **** **** **** 1234)
  const cleanNumber = form.value.cardNumber.replace(/\D/g, '');
  const last4 = cleanNumber.slice(-4);
  const mask = `**** **** **** ${last4 || '0000'}`;
  
  try {
    await apiClient.post('/profile/payments', {
      card_mask: mask,
      card_type: 'MasterCard',
      is_default: form.value.is_default
    });
    await fetchMethods();
    showForm.value = false;
  } catch (error) {
    console.error(error);
    alert('Произошла ошибка при добавлении карты');
  } finally {
    saving.value = false;
  }
};

const deleteMethod = async (id) => {
  if (!confirm('Удалить эту карту?')) return;
  try {
    await apiClient.delete(`/profile/payments/${id}`);
    await fetchMethods();
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  fetchMethods();
});
</script>
