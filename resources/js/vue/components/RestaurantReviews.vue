<template>
  <section class="mt-8" aria-labelledby="reviews-title">
    <div class="flex items-baseline justify-between mb-4">
      <h3 id="reviews-title" class="text-lg font-black dark:text-slate-100 text-slate-900">{{ t('reviews.title') }}</h3>
      <span v-if="reviewCount > 0" class="text-xs font-bold text-amber-500">
        ★ {{ Number(rating).toFixed(1) }} · {{ t('reviews.count', reviewCount) }}
      </span>
    </div>

    <p v-if="!loading && reviews.length === 0" class="text-xs dark:text-slate-400 text-slate-600">
      {{ t('reviews.empty') }}
    </p>

    <ul class="space-y-3">
      <li
        v-for="review in reviews"
        :key="review.id"
        class="p-4 rounded-2xl border dark:border-slate-800/80 dark:bg-slate-800/30 bg-white border-slate-200"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold dark:text-slate-200 text-slate-800">{{ review.author }}</span>
          <span class="text-xs text-amber-500" :aria-label="t('reviews.rating_label', { rating: review.rating })">
            {{ '★'.repeat(review.rating) }}<span class="dark:text-slate-700 text-slate-300">{{ '★'.repeat(5 - review.rating) }}</span>
          </span>
        </div>
        <p v-if="review.comment" class="mt-2 text-xs leading-relaxed dark:text-slate-300 text-slate-700">{{ review.comment }}</p>
        <p class="mt-1 text-[10px] dark:text-slate-500 text-slate-400">{{ formatDate(review.created_at) }}</p>
        <div v-if="review.vendor_reply" class="mt-3 pl-3 border-l-2 border-orange-400">
          <p class="text-[10px] font-bold uppercase tracking-wider text-orange-500">{{ t('reviews.vendor_reply') }}</p>
          <p class="mt-1 text-xs leading-relaxed dark:text-slate-300 text-slate-700">{{ review.vendor_reply }}</p>
        </div>
      </li>
    </ul>

    <button
      v-if="page < lastPage"
      :disabled="loading"
      @click="load(page + 1)"
      class="mt-4 w-full py-2.5 text-xs font-bold rounded-xl border dark:border-slate-700 border-slate-300 dark:text-slate-300 text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50"
    >
      {{ t('reviews.show_more') }}
    </button>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import restaurantsApi from '../api/restaurants';

const props = defineProps({
  slug: { type: String, required: true },
});

const { t, locale } = useI18n();

const reviews = ref([]);
const rating = ref(null);
const reviewCount = ref(0);
const page = ref(0);
const lastPage = ref(1);
const loading = ref(false);

const load = async (nextPage = 1) => {
  loading.value = true;
  try {
    const response = await restaurantsApi.getReviews(props.slug, nextPage);
    reviews.value = nextPage === 1 ? response.data : [...reviews.value, ...response.data];
    rating.value = response.meta.rating;
    reviewCount.value = response.meta.review_count;
    page.value = response.meta.current_page;
    lastPage.value = response.meta.last_page;
  } catch (e) {
    // Reviews are secondary: the menu stays usable without them
    console.error('Failed to load reviews', e);
  } finally {
    loading.value = false;
  }
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString(locale.value === 'tk' ? 'tk-TM' : 'ru-RU') : '');

watch(() => props.slug, () => load(1), { immediate: true });
</script>
