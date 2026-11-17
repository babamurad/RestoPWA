import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('../api/client', () => ({
    // i18n (imported by the store) sets the X-Locale header on the client
    default: { post: vi.fn(), defaults: { headers: { common: {} } } },
}));

import apiClient from '../api/client';
import { useCartStore, cartLineKey, modifiersPrice } from './cart';

const syncResponse = (overrides = {}) => ({
    data: {
        success: true,
        data: {
            validated_items: [],
            price_changes: [],
            unavailable_items: [],
            subtotal: 100,
            delivery_fee: 10,
            delivery_fee_is_estimate: true,
            total: 110,
            min_order: 0,
            is_min_order_met: true,
            ...overrides,
        },
    },
});

describe('cart store: district delivery pricing', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        apiClient.post.mockReset();
    });

    const cartWithItem = () => {
        const cart = useCartStore();
        cart.items = [{ id: 'p1', name: 'Плов', price: 100, quantity: 1 }];
        cart.vendorId = 'v1';
        return cart;
    };

    it('marks the fee as an estimate ("от") until the address is known', async () => {
        apiClient.post.mockResolvedValue(syncResponse());
        const cart = cartWithItem();

        await cart.syncCart();

        expect(cart.deliveryFee).toBe(10);
        expect(cart.deliveryFeeIsEstimate).toBe(true);
    });

    it('uses the exact district price once checkout knows the address', async () => {
        apiClient.post.mockResolvedValue(syncResponse());
        const cart = cartWithItem();
        await cart.syncCart();

        cart.setDistrictFee(18);

        expect(cart.deliveryFee).toBe(18);
        expect(cart.deliveryFeeIsEstimate).toBe(false);
        expect(cart.total).toBe(118);
    });

    it('keeps the district price when the cart syncs again', async () => {
        apiClient.post.mockResolvedValue(syncResponse());
        const cart = cartWithItem();
        cart.setDistrictFee(18);

        await cart.syncCart();

        expect(cart.deliveryFee).toBe(18);
        expect(cart.deliveryFeeIsEstimate).toBe(false);
        expect(cart.total).toBe(118);
    });

    it('forgets the district price when the cart is cleared', () => {
        const cart = cartWithItem();
        cart.setDistrictFee(18);

        cart.clearCart();

        expect(cart.districtFee).toBeNull();
        expect(cart.deliveryFee).toBe(0);
    });
});

describe('cart store: modifiers', () => {
    const vendor = { id: 'v1', slug: 'pizza', name: 'Pizza' };
    const pizza = { id: 'p1', name: 'Пицца', price: 100, image_url: 'http://x/y.jpg' };
    const big = { group_id: 'g1', option_id: 'o-big', group: 'Размер', name: 'Большая', price: 50, quantity: 1 };
    const cheese = { group_id: 'g2', option_id: 'o-cheese', group: 'Топпинг', name: 'Сыр', price: 30, quantity: 1 };
    const ketchup = { group_id: 'g3', option_id: 'o-ketchup', group: 'Соус', name: 'Кетчуп', price: 5, quantity: 2 };

    beforeEach(() => {
        setActivePinia(createPinia());
        apiClient.post.mockReset();
        apiClient.post.mockResolvedValue(syncResponse());
        localStorage.clear();
    });

    it('prices one portion with its modifiers', () => {
        const cart = useCartStore();

        cart.addItem(pizza, 1, vendor, [big, cheese, ketchup]);

        // 100 + 50 + 30 + 2 × 5
        expect(cart.items[0].price).toBe(190);
        expect(modifiersPrice([big, ketchup])).toBe(60);
    });

    it('keeps the same dish with a different choice as a separate line', () => {
        const cart = useCartStore();

        cart.addItem(pizza, 1, vendor, [big]);
        cart.addItem(pizza, 1, vendor, [cheese]);
        cart.addItem(pizza, 2, vendor, [big]);

        expect(cart.items).toHaveLength(2);
        expect(cart.items.find((i) => i.modifiers[0].option_id === 'o-big').quantity).toBe(3);
        expect(cart.totalItemsCount).toBe(4);
    });

    it('does not tell choices apart by the order they were made in', () => {
        expect(cartLineKey('p1', [big, cheese])).toBe(cartLineKey('p1', [cheese, big]));
        expect(cartLineKey('p1', [])).not.toBe(cartLineKey('p1', [big]));
        expect(cartLineKey('p1', [ketchup])).not.toBe(cartLineKey('p1', [{ ...ketchup, quantity: 1 }]));
    });

    it('changes and removes one line at a time', () => {
        const cart = useCartStore();
        cart.addItem(pizza, 1, vendor, [big]);
        cart.addItem(pizza, 1, vendor, [cheese]);
        const [first, second] = cart.items.map((i) => i.key);

        cart.updateQuantity(first, 3);
        expect(cart.items.find((i) => i.key === first).quantity).toBe(3);
        expect(cart.items.find((i) => i.key === second).quantity).toBe(1);

        cart.updateQuantity(second, 0);
        expect(cart.items.map((i) => i.key)).toEqual([first]);

        cart.removeItem(first);
        expect(cart.isEmpty).toBe(true);
        expect(cart.vendorId).toBeNull();
    });

    it('sends the line key and only option ids and portions to the server', async () => {
        const cart = useCartStore();
        cart.addItem(pizza, 2, vendor, [big, ketchup]);
        await Promise.resolve();

        const payload = apiClient.post.mock.calls.at(-1)[1];
        expect(payload.items).toEqual([{
            key: cart.items[0].key,
            product_id: 'p1',
            quantity: 2,
            price: 16000, // 100 + 50 + 2 × 5
            modifiers: [{ option_id: 'o-big', quantity: 1 }, { option_id: 'o-ketchup', quantity: 2 }],
        }]);
    });

    it('applies the server price to the right line and drops only the line that no longer fits', async () => {
        const cart = useCartStore();
        cart.items = [
            { key: 'p1|a', id: 'p1', name: 'A', price: 100, quantity: 1, modifiers: [] },
            { key: 'p1|b', id: 'p1', name: 'B', price: 100, quantity: 1, modifiers: [] },
        ];
        cart.vendorId = 'v1';
        apiClient.post.mockResolvedValue(syncResponse({
            validated_items: [{ key: 'p1|a', product_id: 'p1', price: 14000 }],
            unavailable_items: [{ key: 'p1|b', product_id: 'p1', reason: 'changed' }],
        }));

        await cart.syncCart();

        expect(cart.items.map((i) => i.key)).toEqual(['p1|a']);
        expect(cart.items[0].price).toBe(140);
        expect(cart.unavailableItems).toHaveLength(1);
    });

    it('gives lines saved before modifiers existed a key when the cart is loaded', () => {
        localStorage.setItem('resto_pwa_cart', JSON.stringify({
            items: [{ id: 'p9', name: 'Плов', price: 80, quantity: 2 }],
            vendorId: 'v1',
        }));
        const cart = useCartStore();

        cart.loadFromLocalStorage();

        expect(cart.items[0].key).toBe('p9|');
        expect(cart.items[0].modifiers).toEqual([]);
    });

    it('puts a dish of an earlier order back with its choice', () => {
        const cart = useCartStore();

        cart.addOrderItem({
            product_id: 'p1',
            name: 'Пицца',
            quantity: 2,
            unit_price: 18000, // 180 TMT: 100 + 50 + 30
            modifiers: [
                { id: 'o-big', group_id: 'g1', group: 'Размер', name: 'Большая', price: 50, quantity: 1 },
                { id: 'o-cheese', group_id: 'g2', group: 'Топпинг', name: 'Сыр', price: 30, quantity: 1 },
            ],
        }, vendor);

        expect(cart.vendorId).toBe('v1');
        expect(cart.items[0].quantity).toBe(2);
        expect(cart.items[0].price).toBe(180);
        expect(cart.items[0].modifiers.map((m) => m.option_id)).toEqual(['o-big', 'o-cheese']);
        expect(cart.items[0].key).toBe(cartLineKey('p1', cart.items[0].modifiers));
    });
});

describe('cart store: promo code', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        localStorage.clear();
    });

    const cartWithItem = () => {
        const cart = useCartStore();
        cart.items = [{ id: 'p1', name: 'Плов', price: 100, quantity: 1, key: 'p1|' }];
        cart.vendorId = 'v1';
        return cart;
    };

    const promoResponse = (discountMinor = 2000) => ({
        data: { success: true, data: { valid: true, code: 'PLOV20', discount: discountMinor, subtotal: 10000 } },
    });

    it('applies the code and takes the server discount off the total', async () => {
        apiClient.post.mockResolvedValueOnce(syncResponse());
        const cart = cartWithItem();
        await cart.syncCart();
        apiClient.post.mockResolvedValueOnce(promoResponse());

        expect(await cart.applyPromo(' plov20 ')).toBe(true);

        expect(apiClient.post).toHaveBeenLastCalledWith('/cart/apply-promo', expect.objectContaining({ code: 'plov20' }));
        expect(cart.discount).toBe(20);
        expect(cart.payable).toBe(90);
    });

    it('keeps the reason and drops the code when the server refuses it', async () => {
        const cart = cartWithItem();
        cart.promo = { code: 'OLD', discount: 5 };
        apiClient.post.mockRejectedValueOnce({ response: { data: { reason: 'expired', message: 'Срок действия промокода истёк.' } } });

        expect(await cart.applyPromo('OLD')).toBe(false);

        expect(cart.promo).toBeNull();
        expect(cart.promoError.reason).toBe('expired');
        expect(cart.payable).toBe(cart.total);
    });

    it('re-checks the code after the cart syncs, and forgets it when the cart is cleared', async () => {
        const cart = cartWithItem();
        cart.promo = { code: 'PLOV20', discount: 20 };
        apiClient.post.mockResolvedValueOnce(syncResponse()).mockResolvedValueOnce(promoResponse(1000));

        await cart.syncCart();

        expect(cart.discount).toBe(10);

        cart.clearCart();
        expect(cart.promo).toBeNull();
    });
});
