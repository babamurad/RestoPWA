import { defineStore } from 'pinia';
import courierApi from '../api/courier';
import { t } from '../i18n';

const emptySummary = () => ({ today: 0, week: 0, month: 0, unpaid: 0 });

export const useCourierStore = defineStore('courier', {
    state: () => ({
        courierId: null,
        status: 'offline',
        offers: [],
        available: [],
        active: [],
        earnings: [],
        summary: emptySummary(),
        isLoading: false,
        hasLoaded: false,
        // Id of the order or offer a request is running for, to disable its buttons
        busyId: null,
        error: null,
    }),
    getters: {
        isOnShift: (state) => state.status !== 'offline',
        // Orders already picked up: the courier's location is shared while there is one
        deliveringOrders: (state) => state.active.filter((order) => order.status === 'delivering'),
        hasDeliveryOnTheWay() {
            return this.deliveringOrders.length > 0;
        },
        // Offers whose time has not run out yet (the server drops them anyway)
        openOffers: (state) => (now = Date.now()) => state.offers.filter((offer) => new Date(offer.expires_at).getTime() > now),
    },
    actions: {
        // Orders and offers; called on open, on a websocket event and by the poll timer
        async refresh() {
            if (!this.hasLoaded) this.isLoading = true;
            try {
                const [orders, offers] = await Promise.all([courierApi.getOrders(), courierApi.getOffers()]);
                this.courierId = orders.courier?.id ?? null;
                this.status = orders.courier?.status ?? this.status;
                this.available = orders.available ?? [];
                this.active = orders.active ?? [];
                this.offers = offers ?? [];
                this.error = null;
            } catch (error) {
                this.error = this.messageOf(error, 'courier.errors.load');
            } finally {
                this.isLoading = false;
                this.hasLoaded = true;
            }
        },
        async fetchEarnings() {
            try {
                const response = await courierApi.getEarnings();
                this.earnings = response.data ?? [];
                this.summary = { ...emptySummary(), ...(response.summary ?? {}) };
            } catch (error) {
                this.error = this.messageOf(error, 'courier.errors.load');
            }
        },
        async setShift(onShift) {
            const next = onShift ? 'available' : 'offline';
            return this.run('shift', async () => {
                await courierApi.setShiftStatus(next);
                this.status = next;
            }, 'courier.errors.shift');
        },
        async acceptOffer(offerId) {
            return this.run(offerId, async () => {
                await courierApi.acceptOffer(offerId);
                await this.refresh();
            }, 'courier.errors.accept', { 409: 'courier.errors.taken' });
        },
        async declineOffer(offerId) {
            return this.run(offerId, async () => {
                await courierApi.declineOffer(offerId);
                this.offers = this.offers.filter((offer) => offer.offer_id !== offerId);
            }, 'courier.errors.decline');
        },
        async acceptOrder(orderId) {
            return this.run(orderId, async () => {
                await courierApi.acceptOrder(orderId);
                await this.refresh();
            }, 'courier.errors.accept', { 409: 'courier.errors.taken' });
        },
        // An order assigned by the restaurant or an admin: picked up, now on the way
        async markPickedUp(orderId) {
            return this.run(orderId, async () => {
                await courierApi.setOrderStatus(orderId, 'delivering');
                await this.refresh();
            }, 'courier.errors.status');
        },
        async markDelivered(orderId) {
            return this.run(orderId, async () => {
                await courierApi.setOrderStatus(orderId, 'delivered');
                await Promise.all([this.refresh(), this.fetchEarnings()]);
            }, 'courier.errors.status');
        },
        // Drop an offer whose countdown reached zero
        expireOffer(offerId) {
            this.offers = this.offers.filter((offer) => offer.offer_id !== offerId);
        },
        // Runs one request: true on success, false with this.error set otherwise.
        // On a failure the lists are reloaded: the order may already be someone else's.
        async run(id, request, fallbackKey, byStatus = {}) {
            if (this.busyId) return false;
            this.busyId = id;
            this.error = null;
            try {
                await request();
                return true;
            } catch (error) {
                const key = byStatus[error.response?.status];
                await this.refresh();
                this.error = key ? t(key) : this.messageOf(error, fallbackKey);
                return false;
            } finally {
                this.busyId = null;
            }
        },
        messageOf(error, fallbackKey) {
            if (!error.response) return t('courier.errors.network');
            return error.response.data?.message || t(fallbackKey);
        },
    },
});
