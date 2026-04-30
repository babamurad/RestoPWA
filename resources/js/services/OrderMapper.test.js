import { describe, it, expect } from 'vitest';
import { mapCartItemsToApiFormat, calculateOrderTotal, buildOrderPayload } from './OrderMapper';

describe('OrderMapper', () => {
    describe('mapCartItemsToApiFormat', () => {
        it('transforms cart items to API format', () => {
            const cartItems = [
                {
                    productId: 'prod-1',
                    vendorId: 'v1',
                    productName: 'Pizza',
                    price: 1000,
                    quantity: 2,
                    modifiers: { extra_cheese: true },
                    image: '/img.jpg',
                },
            ];

            const result = mapCartItemsToApiFormat(cartItems);

            expect(result).toEqual([
                {
                    product_id: 'prod-1',
                    product_name: 'Pizza',
                    quantity: 2,
                    unit_price: 1000,
                    total_price: 2000,
                    modifiers: { extra_cheese: true },
                    image: '/img.jpg',
                },
            ]);
        });

        it('calculates total_price as unit_price * quantity', () => {
            const cartItems = [
                { productId: 'p1', productName: 'A', price: 500, quantity: 3 },
            ];

            const result = mapCartItemsToApiFormat(cartItems);
            expect(result[0].total_price).toBe(1500);
        });

        it('handles missing optional fields', () => {
            const cartItems = [
                { productId: 'p1', productName: 'A', price: 100, quantity: 1 },
            ];

            const result = mapCartItemsToApiFormat(cartItems);
            expect(result[0].modifiers).toEqual({});
            expect(result[0].image).toBe(null);
        });

        it('handles string prices by parsing to integer', () => {
            const cartItems = [
                { productId: 'p1', productName: 'A', price: '1000', quantity: 1 },
            ];

            const result = mapCartItemsToApiFormat(cartItems);
            expect(result[0].unit_price).toBe(1000);
        });
    });

    describe('calculateOrderTotal', () => {
        it('sums item totals and delivery fee', () => {
            const items = [
                { total_price: 1000 },
                { total_price: 2000 },
            ];

            expect(calculateOrderTotal(items, 500)).toBe(3500);
        });

        it('works with zero delivery fee', () => {
            const items = [{ total_price: 1000 }];
            expect(calculateOrderTotal(items, 0)).toBe(1000);
        });

        it('handles empty items array', () => {
            expect(calculateOrderTotal([], 500)).toBe(500);
        });
    });

    describe('buildOrderPayload', () => {
        it('builds complete payload with all fields', () => {
            const payload = buildOrderPayload({
                cartItems: [
                    { productId: 'p1', productName: 'Pizza', price: 1000, quantity: 2 },
                ],
                vendorId: 'v1',
                address: {
                    lat: 55.7558,
                    lon: 37.6173,
                    address: 'Test Street 1',
                    name: 'John Doe',
                    phone: '+99312345678',
                },
                deliveryFee: 500,
                deliveryTime: 'asap',
                paymentMethod: 'card',
                comment: 'No spicy',
                idempotencyKey: 'key-123',
                traceId: 'trace-abc',
            });

            expect(payload).toMatchObject({
                vendor_id: 'v1',
                total: 2500,
                delivery_fee: 500,
                delivery_time: 'asap',
                payment_method: 'card',
                comment: 'No spicy',
                idempotency_key: 'key-123',
                trace_id: 'trace-abc',
            });

            expect(payload.items).toHaveLength(1);
            expect(payload.items[0].unit_price).toBe(1000);
            expect(payload.items[0].total_price).toBe(2000);
            expect(payload.address).toMatchObject({
                lat: 55.7558,
                lon: 37.6173,
                name: 'John Doe',
                phone: '+99312345678',
            });
        });

        it('uses defaults for optional fields', () => {
            const payload = buildOrderPayload({
                cartItems: [{ productId: 'p1', productName: 'A', price: 100, quantity: 1 }],
                vendorId: 'v1',
                address: { lat: 1, lon: 1, address: 'Test', name: 'Test', phone: '+123' },
            });

            expect(payload.delivery_fee).toBe(0);
            expect(payload.delivery_time).toBe('asap');
            expect(payload.payment_method).toBe('card');
            expect(payload.comment).toBe(null);
        });
    });
});
