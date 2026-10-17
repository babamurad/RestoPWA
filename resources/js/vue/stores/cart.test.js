import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('../api/client', () => ({
    default: { post: vi.fn() },
}));

import apiClient from '../api/client';
import { useCartStore } from './cart';

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
