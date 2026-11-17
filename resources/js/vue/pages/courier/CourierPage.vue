<template>
  <div class="px-4 py-6 max-w-xl mx-auto pb-28 font-inter animate-fade-in">
    <!-- Shift -->
    <section class="rounded-3xl p-5 border dark:bg-slate-900/60 dark:border-slate-800/40 bg-white border-slate-200 shadow-sm">
      <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
          <h1 class="text-xl font-bold font-outfit dark:text-slate-100 text-slate-900">{{ t('courier.title') }}</h1>
          <!-- Shown once loaded: the shift must not flash "off" for a courier who is on it -->
          <p v-if="store.hasLoaded" class="mt-1 flex items-center gap-2 text-sm font-bold" :class="store.isOnShift ? 'text-emerald-500' : 'dark:text-slate-400 text-slate-500'">
            <span class="w-2.5 h-2.5 rounded-full" :class="store.isOnShift ? 'bg-emerald-500' : 'bg-slate-400'" aria-hidden="true"></span>
            {{ store.isOnShift ? t('courier.shift.on') : t('courier.shift.off') }}
          </p>
        </div>
        <button
          v-if="store.hasLoaded"
          type="button"
          dusk="courier-shift"
          @click="toggleShift"
          :disabled="store.busyId === 'shift'"
          class="px-4 py-2.5 rounded-xl text-sm font-bold transition-colors active:scale-95 disabled:opacity-50"
          :class="store.isOnShift
            ? 'border dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 border-slate-300 text-slate-700 hover:bg-slate-100'
            : 'bg-orange-500 text-white hover:bg-orange-600 shadow-lg shadow-orange-500/20'"
        >
          {{ store.isOnShift ? t('courier.shift.go_off') : t('courier.shift.go_on') }}
        </button>
      </div>
      <p v-if="store.hasLoaded" class="mt-3 text-xs dark:text-slate-400 text-slate-600">
        {{ store.isOnShift ? t('courier.shift.hint_on') : t('courier.shift.hint_off') }}
      </p>
    </section>

    <!-- Errors -->
    <div
      v-if="store.error"
      role="alert"
      class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 flex items-start justify-between gap-3"
    >
      <p class="text-sm font-semibold text-rose-600 dark:text-rose-300">{{ store.error }}</p>
      <button type="button" @click="store.refresh()" class="text-xs font-bold text-rose-600 dark:text-rose-300 underline flex-shrink-0">
        {{ t('courier.retry') }}
      </button>
    </div>

    <!-- Location sharing state, only while an order is on the way -->
    <p
      v-if="store.hasDeliveryOnTheWay"
      class="mt-4 rounded-2xl p-3 text-xs font-semibold border"
      :class="locationProblem
        ? 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300'
        : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'"
      :role="locationProblem ? 'alert' : 'status'"
    >
      {{ locationProblem ? t(`courier.location.${locationProblem}`) : t('courier.location.sharing') }}
    </p>

    <!-- Offers: answer within the countdown -->
    <section v-if="openOffers.length" class="mt-6 space-y-3" aria-live="polite">
      <div
        v-for="offer in openOffers"
        :key="offer.offer_id"
        class="rounded-3xl p-5 border-2 border-orange-500 dark:bg-slate-900 bg-white shadow-xl shadow-orange-500/10"
      >
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-black dark:text-slate-100 text-slate-900">{{ t('courier.offer.title') }}</h2>
          <span class="text-xs font-black px-2.5 py-1 rounded-full bg-orange-500/10 text-orange-500 tabular-nums">
            {{ t('courier.offer.seconds_left', { n: secondsLeft(offer) }) }}
          </span>
        </div>
        <OrderSummary v-if="offer.order" :order="offer.order" class="mt-3" />
        <div class="mt-4 grid grid-cols-2 gap-3">
          <button
            type="button"
            @click="store.declineOffer(offer.offer_id)"
            :disabled="!!store.busyId"
            class="py-3 rounded-xl text-sm font-bold border dark:border-slate-700 dark:text-slate-200 border-slate-300 text-slate-700 active:scale-95 disabled:opacity-50"
          >
            {{ t('courier.offer.decline') }}
          </button>
          <button
            type="button"
            @click="store.acceptOffer(offer.offer_id)"
            :disabled="!!store.busyId"
            class="py-3 rounded-xl text-sm font-bold bg-orange-500 text-white hover:bg-orange-600 active:scale-95 disabled:opacity-50"
          >
            {{ t('courier.offer.accept') }}
          </button>
        </div>
      </div>
    </section>

    <!-- My deliveries -->
    <section v-if="store.active.length" class="mt-6">
      <h2 class="text-sm font-black uppercase tracking-wider dark:text-slate-400 text-slate-600 mb-3">{{ t('courier.active.title') }}</h2>
      <div class="space-y-3">
        <article
          v-for="order in store.active"
          :key="order.id"
          class="rounded-3xl p-5 border dark:bg-slate-900/60 dark:border-slate-800/40 bg-white border-slate-200"
        >
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-black dark:text-slate-100 text-slate-900">{{ t('courier.order.number', { n: order.number }) }}</h3>
            <span class="text-[10px] px-2.5 py-1 rounded-full font-black border tracking-wide uppercase"
                  :class="order.status === 'delivering' ? 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400' : 'bg-orange-500/10 border-orange-500/20 text-orange-400'">
              {{ order.status === 'delivering' ? t('courier.active.on_the_way') : t('courier.active.waiting_pickup') }}
            </span>
          </div>

          <dl class="mt-3 space-y-2 text-sm">
            <div>
              <dt class="text-xs font-semibold dark:text-slate-500 text-slate-500">{{ t('courier.active.pick_up_at') }}</dt>
              <dd class="font-bold dark:text-slate-200 text-slate-800">{{ order.restaurant?.name }}</dd>
            </div>
            <div>
              <dt class="text-xs font-semibold dark:text-slate-500 text-slate-500">{{ t('courier.active.deliver_to') }}</dt>
              <dd class="font-bold dark:text-slate-200 text-slate-800">{{ addressLine(order) }}</dd>
              <dd v-if="addressDetails(order)" class="text-xs dark:text-slate-400 text-slate-600">{{ addressDetails(order) }}</dd>
            </div>
            <div v-if="orderComment(order)">
              <dt class="text-xs font-semibold dark:text-slate-500 text-slate-500">{{ t('courier.active.comment') }}</dt>
              <dd class="dark:text-slate-300 text-slate-700">{{ orderComment(order) }}</dd>
            </div>
          </dl>

          <p class="mt-3 rounded-xl p-3 text-sm font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            {{ t('courier.active.take_cash', { amount: money(order.total) }) }}
          </p>

          <div class="mt-4 grid gap-3" :class="order.customer_phone ? 'grid-cols-2' : 'grid-cols-1'">
            <a
              v-if="order.customer_phone"
              :href="`tel:${order.customer_phone}`"
              class="py-3 rounded-xl text-sm font-bold text-center border dark:border-slate-700 dark:text-slate-200 border-slate-300 text-slate-700 active:scale-95"
            >
              {{ t('courier.active.call') }}
            </a>
            <button
              v-if="order.status === 'delivering'"
              type="button"
              dusk="courier-delivered"
              @click="deliver(order)"
              :disabled="!!store.busyId"
              class="py-3 rounded-xl text-sm font-bold bg-emerald-500 text-white hover:bg-emerald-600 active:scale-95 disabled:opacity-50"
            >
              {{ t('courier.active.delivered') }}
            </button>
            <button
              v-else
              type="button"
              @click="store.markPickedUp(order.id)"
              :disabled="!!store.busyId"
              class="py-3 rounded-xl text-sm font-bold bg-orange-500 text-white hover:bg-orange-600 active:scale-95 disabled:opacity-50"
            >
              {{ t('courier.active.picked_up') }}
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- Free orders to take by hand -->
    <section v-if="store.isOnShift" class="mt-6">
      <h2 class="text-sm font-black uppercase tracking-wider dark:text-slate-400 text-slate-600 mb-3">{{ t('courier.available.title') }}</h2>
      <p v-if="!store.available.length" class="text-sm dark:text-slate-500 text-slate-500">{{ t('courier.available.empty') }}</p>
      <div v-else class="space-y-3">
        <article
          v-for="order in store.available"
          :key="order.id"
          class="rounded-3xl p-5 border dark:bg-slate-900/60 dark:border-slate-800/40 bg-white border-slate-200"
        >
          <OrderSummary :order="order" />
          <button
            type="button"
            dusk="courier-take-order"
            @click="store.acceptOrder(order.id)"
            :disabled="!!store.busyId"
            class="mt-4 w-full py-3 rounded-xl text-sm font-bold bg-orange-500 text-white hover:bg-orange-600 active:scale-95 disabled:opacity-50"
          >
            {{ t('courier.available.take') }}
          </button>
        </article>
      </div>
    </section>

    <!-- Earnings -->
    <section class="mt-8">
      <h2 class="text-sm font-black uppercase tracking-wider dark:text-slate-400 text-slate-600 mb-3">{{ t('courier.earnings.title') }}</h2>
      <div class="grid grid-cols-3 gap-3">
        <div v-for="period in ['today', 'week', 'month']" :key="period"
             class="rounded-2xl p-3 border dark:bg-slate-900/60 dark:border-slate-800/40 bg-white border-slate-200">
          <p class="text-[11px] font-semibold dark:text-slate-500 text-slate-500">{{ t(`courier.earnings.${period}`) }}</p>
          <p class="mt-1 text-base font-black dark:text-slate-100 text-slate-900 tabular-nums">{{ money(store.summary[period]) }}</p>
        </div>
      </div>
      <p v-if="store.summary.unpaid > 0" class="mt-3 text-xs font-semibold dark:text-slate-400 text-slate-600">
        {{ t('courier.earnings.unpaid', { amount: money(store.summary.unpaid) }) }}
      </p>

      <details class="mt-4">
        <summary class="cursor-pointer text-sm font-bold text-orange-500">{{ t('courier.earnings.history') }}</summary>
        <p v-if="!store.earnings.length" class="mt-3 text-sm dark:text-slate-500 text-slate-500">{{ t('courier.earnings.empty') }}</p>
        <ul v-else class="mt-3 divide-y dark:divide-slate-800 divide-slate-200">
          <li v-for="earning in store.earnings" :key="earning.id" class="py-2.5 flex items-center justify-between gap-3 text-sm">
            <div class="min-w-0">
              <p class="font-bold dark:text-slate-200 text-slate-800">{{ t('courier.order.number', { n: earning.order_number }) }}</p>
              <p class="text-xs dark:text-slate-500 text-slate-500">
                {{ formatDate(earning.created_at) }} · {{ earning.status === 'paid' ? t('courier.earnings.paid') : t('courier.earnings.pending') }}
              </p>
            </div>
            <span class="font-black dark:text-slate-100 text-slate-900 tabular-nums">{{ money(earning.amount) }}</span>
          </li>
        </ul>
      </details>
    </section>
  </div>
</template>

<script setup>
import { computed, h, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import courierApi from '../../api/courier';
import { useCourierStore } from '../../stores/courier';

const store = useCourierStore();
const { t, locale } = useI18n();

// Lists reload this often when no websocket event arrives (slow or no realtime)
const POLL_MS = 15000;
// The customer's map does not need the courier's position more often than this
const LOCATION_MS = 12000;

const now = ref(Date.now());
const openOffers = computed(() => store.openOffers(now.value));
const secondsLeft = (offer) => Math.max(0, Math.ceil((new Date(offer.expires_at).getTime() - now.value) / 1000));

const money = (amount) => t('common.price', { amount: Number(amount ?? 0).toFixed(2).replace(/\.00$/, '') });

const formatDate = (iso) => iso
  ? new Date(iso).toLocaleString(locale.value === 'tk' ? 'tk-TM' : 'ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
  : '';

const addressLine = (order) => order.address?.address || order.address?.manual_address || order.address?.landmark || '';
const addressDetails = (order) => {
  const a = order.address ?? {};
  return [
    a.landmark && a.landmark !== addressLine(order) ? a.landmark : null,
    a.entrance ? t('courier.active.entrance', { v: a.entrance }) : null,
    a.floor ? t('courier.active.floor', { v: a.floor }) : null,
    a.apartment ? t('courier.active.apartment', { v: a.apartment }) : null,
  ].filter(Boolean).join(', ');
};
const orderComment = (order) => [order.address?.courier_comment, order.comment].filter(Boolean).join(' · ');

// What a courier sees about an order that is not his yet
const OrderSummary = (props) => h('div', { class: 'space-y-1 text-sm' }, [
  h('p', { class: 'font-black dark:text-slate-100 text-slate-900' },
    [t('courier.order.number', { n: props.order.number }), props.order.restaurant?.name].filter(Boolean).join(' · ')),
  h('p', { class: 'dark:text-slate-300 text-slate-700' }, addressLine(props.order)),
  h('p', { class: 'font-bold text-emerald-600 dark:text-emerald-400' }, t('courier.order.your_fee', { amount: money(props.order.delivery_fee) })),
  h('p', { class: 'text-xs dark:text-slate-500 text-slate-500' },
    [t('courier.order.order_sum', { amount: money(props.order.total) }), props.order.items_count ? t('courier.order.items', props.order.items_count) : null]
      .filter(Boolean).join(' · ')),
]);
OrderSummary.props = ['order'];

const toggleShift = () => store.setShift(!store.isOnShift);

const deliver = async (order) => {
  if (!window.confirm(t('courier.active.confirm_delivered'))) return;
  await store.markDelivered(order.id);
};

// --- Live updates: websocket when available, polling always ---
let tickTimer = null;
let pollTimer = null;
let channelName = null;

const subscribe = () => {
  if (!window.Echo || !store.courierId || channelName) return;
  channelName = `courier.${store.courierId}`;
  window.Echo.private(channelName).listen('.courier.offer.created', () => store.refresh());
};

watch(() => store.courierId, subscribe);

// --- Location: shared only while an order is on the way to the customer ---
const locationProblem = ref(null);
let watchId = null;
let lastSentAt = 0;

const stopLocation = () => {
  if (watchId !== null) navigator.geolocation.clearWatch(watchId);
  watchId = null;
  locationProblem.value = null;
};

const startLocation = () => {
  if (watchId !== null) return;
  if (!navigator.geolocation) {
    locationProblem.value = 'unavailable';
    return;
  }
  watchId = navigator.geolocation.watchPosition(
    (position) => {
      locationProblem.value = null;
      if (Date.now() - lastSentAt < LOCATION_MS) return;
      lastSentAt = Date.now();
      // A lost point is harmless: the next one follows in seconds
      courierApi.sendLocation(position.coords.latitude, position.coords.longitude).catch(() => {});
    },
    (error) => {
      locationProblem.value = error.code === error.PERMISSION_DENIED ? 'denied' : 'unavailable';
    },
    { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 },
  );
};

watch(() => store.hasDeliveryOnTheWay, (onTheWay) => (onTheWay ? startLocation() : stopLocation()), { immediate: true });

onMounted(async () => {
  tickTimer = setInterval(() => { now.value = Date.now(); }, 1000);
  pollTimer = setInterval(() => store.refresh(), POLL_MS);
  await Promise.all([store.refresh(), store.fetchEarnings()]);
  subscribe();
});

onUnmounted(() => {
  clearInterval(tickTimer);
  clearInterval(pollTimer);
  stopLocation();
  if (channelName && window.Echo) window.Echo.leave(channelName);
});
</script>
