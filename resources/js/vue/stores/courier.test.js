import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('../api/client', () => ({
    // i18n (imported by the store) sets the X-Locale header on the client
    default: { post: vi.fn(), get: vi.fn(), defaults: { headers: { common: {} } } },
}));

vi.mock('../api/courier', () => ({
    default: {
        getOrders: vi.fn(),
        getOffers: vi.fn(),
        acceptOffer: vi.fn(),
        declineOffer: vi.fn(),
        acceptOrder: vi.fn(),
        setOrderStatus: vi.fn(),
        setShiftStatus: vi.fn(),
        sendLocation: vi.fn(),
        getEarnings: vi.fn(),
    },
}));

import courierApi from '../api/courier';
import { useCourierStore } from './courier';

const httpError = (status, message) => Object.assign(new Error('http'), { response: { status, data: { message } } });

const orders = (overrides = {}) => ({
    courier: { id: 'c1', status: 'available' },
    available: [],
    active: [],
    ...overrides,
});

describe('courier store', () => {
    let store;

    beforeEach(() => {
        vi.clearAllMocks();
        setActivePinia(createPinia());
        store = useCourierStore();
        courierApi.getOrders.mockResolvedValue(orders());
        courierApi.getOffers.mockResolvedValue([]);
        courierApi.getEarnings.mockResolvedValue({ data: [], summary: { today: 5, week: 20, month: 60, unpaid: 15 } });
    });

    it('loads orders, offers and the shift status', async () => {
        courierApi.getOrders.mockResolvedValue(orders({
            available: [{ id: 'o1' }],
            active: [{ id: 'o2', status: 'delivering' }, { id: 'o3', status: 'ready_for_pickup' }],
        }));
        courierApi.getOffers.mockResolvedValue([{ offer_id: 'f1', expires_at: new Date(Date.now() + 30000).toISOString() }]);

        await store.refresh();

        expect(store.courierId).toBe('c1');
        expect(store.isOnShift).toBe(true);
        expect(store.available).toHaveLength(1);
        expect(store.offers).toHaveLength(1);
        expect(store.deliveringOrders.map((o) => o.id)).toEqual(['o2']);
        expect(store.hasDeliveryOnTheWay).toBe(true);
        expect(store.hasLoaded).toBe(true);
    });

    it('hides offers whose time has run out', () => {
        const now = Date.now();
        store.offers = [
            { offer_id: 'old', expires_at: new Date(now - 1000).toISOString() },
            { offer_id: 'open', expires_at: new Date(now + 20000).toISOString() },
        ];

        expect(store.openOffers(now).map((o) => o.offer_id)).toEqual(['open']);

        store.expireOffer('open');
        expect(store.offers.map((o) => o.offer_id)).toEqual(['old']);
    });

    it('accepts an offer and reloads the lists', async () => {
        courierApi.acceptOffer.mockResolvedValue({});

        expect(await store.acceptOffer('f1')).toBe(true);

        expect(courierApi.acceptOffer).toHaveBeenCalledWith('f1');
        expect(courierApi.getOrders).toHaveBeenCalledTimes(1);
        expect(store.error).toBeNull();
        expect(store.busyId).toBeNull();
    });

    it('explains that the order was taken by someone else (409) and reloads', async () => {
        courierApi.acceptOffer.mockRejectedValue(httpError(409, 'Order already assigned to another courier'));

        expect(await store.acceptOffer('f1')).toBe(false);

        expect(store.error).toBe('Заказ уже взял другой курьер.');
        expect(courierApi.getOrders).toHaveBeenCalledTimes(1);
    });

    it('removes a declined offer without waiting for a reload', async () => {
        store.offers = [{ offer_id: 'f1' }, { offer_id: 'f2' }];
        courierApi.declineOffer.mockResolvedValue({});

        await store.declineOffer('f1');

        expect(store.offers.map((o) => o.offer_id)).toEqual(['f2']);
    });

    it('goes on and off shift', async () => {
        courierApi.setShiftStatus.mockResolvedValue({});

        await store.setShift(true);
        expect(courierApi.setShiftStatus).toHaveBeenLastCalledWith('available');
        expect(store.isOnShift).toBe(true);

        await store.setShift(false);
        expect(courierApi.setShiftStatus).toHaveBeenLastCalledWith('offline');
        expect(store.isOnShift).toBe(false);
    });

    it('keeps the shift on and shows the server message when a delivery is on the way', async () => {
        store.status = 'available';
        courierApi.setShiftStatus.mockRejectedValue(httpError(422, 'Сначала доставьте заказ, который уже в пути.'));

        expect(await store.setShift(false)).toBe(false);

        expect(store.status).toBe('available');
        expect(store.error).toBe('Сначала доставьте заказ, который уже в пути.');
    });

    it('marks an order delivered and refreshes the earnings', async () => {
        courierApi.setOrderStatus.mockResolvedValue({});

        expect(await store.markDelivered('o2')).toBe(true);

        expect(courierApi.setOrderStatus).toHaveBeenCalledWith('o2', 'delivered');
        expect(store.summary).toEqual({ today: 5, week: 20, month: 60, unpaid: 15 });
    });

    it('reports a lost connection in plain words', async () => {
        courierApi.acceptOrder.mockRejectedValue(new Error('Network Error'));
        courierApi.getOrders.mockRejectedValue(new Error('Network Error'));

        expect(await store.acceptOrder('o1')).toBe(false);

        expect(store.error).toBe('Нет связи с сервером. Проверьте интернет и повторите.');
    });

    it('ignores a second tap while a request is running', async () => {
        let finish;
        courierApi.acceptOrder.mockReturnValue(new Promise((resolve) => { finish = resolve; }));

        const first = store.acceptOrder('o1');
        expect(await store.acceptOrder('o1')).toBe(false);
        finish({});
        expect(await first).toBe(true);

        expect(courierApi.acceptOrder).toHaveBeenCalledTimes(1);
    });
});
