<template>
  <div class="px-4 py-6 max-w-xl mx-auto pb-24 font-inter">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
      <button @click="$router.back()" class="p-2 -ml-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 text-slate-800 dark:text-slate-200">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
      </button>
      <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 font-outfit">Мои адреса</h1>
    </div>

    <!-- Address List -->
    <div v-if="!showForm" class="space-y-4">
      <div v-if="loading" class="text-center py-10 text-slate-500">Загрузка...</div>
      
      <div v-else-if="addresses.length === 0" class="text-center py-10">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-2xl">📍</span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 font-medium">У вас пока нет сохраненных адресов</p>
      </div>

      <div v-else class="space-y-3">
        <div 
          v-for="address in addresses" 
          :key="address.id"
          class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm relative group"
        >
          <div class="absolute right-4 top-4 flex gap-2">
            <button @click="editAddress(address)" class="text-slate-400 hover:text-orange-500 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
              </svg>
            </button>
            <button @click="deleteAddress(address.id)" class="text-slate-400 hover:text-rose-500 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
          </div>

          <div class="pr-16">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
              {{ address.title || 'Адрес доставки' }}
              <span v-if="address.is_default" class="text-[10px] uppercase font-black bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full">Основной</span>
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ address.full_address }}</p>
            <p v-if="address.comment" class="text-xs text-slate-500 mt-2 italic">{{ address.comment }}</p>
          </div>
        </div>
      </div>

      <button @click="showForm = true; form = {}" class="w-full mt-6 bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20 font-bold py-3.5 rounded-2xl flex items-center justify-center gap-2 hover:bg-orange-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Добавить новый адрес
      </button>
    </div>

    <!-- Address Form -->
    <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-3xl p-5 shadow-lg">
      <h3 class="font-bold text-lg mb-4 text-slate-800 dark:text-slate-100">{{ form.id ? 'Редактировать адрес' : 'Новый адрес' }}</h3>
      
      <form @submit.prevent="saveAddress" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">Название (Например: Дом)</label>
          <input v-model="form.title" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100">
        </div>
        
        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">Полный адрес *</label>
          <input v-model="form.full_address" type="text" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Квартира</label>
            <input v-model="form.apartment" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 mb-1">Подъезд</label>
            <input v-model="form.entrance" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-500 mb-1">Комментарий курьеру</label>
          <textarea v-model="form.comment" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-orange-500 transition-colors text-slate-800 dark:text-slate-100"></textarea>
        </div>

        <label class="flex items-center gap-3 py-2 cursor-pointer">
          <input v-model="form.is_default" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Сделать основным</span>
        </label>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="showForm = false" class="flex-1 py-3.5 rounded-xl font-bold text-slate-500 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
            Отмена
          </button>
          <button type="submit" :disabled="saving" class="flex-1 py-3.5 rounded-xl font-bold text-white bg-orange-500 hover:bg-orange-600 transition-colors disabled:opacity-70">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '../../api/client';

const addresses = ref([]);
const loading = ref(true);
const showForm = ref(false);
const saving = ref(false);
const form = ref({});

const fetchAddresses = async () => {
  loading.value = true;
  try {
    const { data } = await apiClient.get('/profile/addresses');
    addresses.value = data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const saveAddress = async () => {
  saving.value = true;
  try {
    if (form.value.id) {
      await apiClient.put(`/profile/addresses/${form.value.id}`, form.value);
    } else {
      await apiClient.post('/profile/addresses', form.value);
    }
    await fetchAddresses();
    showForm.value = false;
  } catch (error) {
    console.error(error);
    alert('Произошла ошибка при сохранении адреса');
  } finally {
    saving.value = false;
  }
};

const editAddress = (address) => {
  form.value = { ...address };
  showForm.value = true;
};

const deleteAddress = async (id) => {
  if (!confirm('Удалить этот адрес?')) return;
  try {
    await apiClient.delete(`/profile/addresses/${id}`);
    await fetchAddresses();
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  fetchAddresses();
});
</script>
