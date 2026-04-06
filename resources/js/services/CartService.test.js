import { describe, it, expect, beforeEach, vi } from 'vitest';
import 'fake-indexeddb/auto';
import CartService from './CartService';
import Dexie from 'dexie';

describe('CartService', () => {
    beforeEach(async () => {
        // Clear IndexedDB before each test
        const db = new Dexie('RestoCart');
        await db.delete();
        await db.open();
    });

    it('should add an item to the cart', async () => {
        const id = await CartService.addItem(
            'prod-1',
            'vendor-1',
            'Pizza',
            '/img.jpg',
            {},
            1000
        );

        expect(id).toBeDefined();
        const items = await CartService.getCartByVendor('vendor-1');
        expect(items).toHaveLength(1);
        expect(items[0].productName).toBe('Pizza');
        expect(items[0].quantity).toBe(1);
    });

    it('should increment quantity if item already exists', async () => {
        await CartService.addItem('prod-1', 'vendor-1', 'Pizza', null, {}, 1000);
        await CartService.addItem('prod-1', 'vendor-1', 'Pizza', null, {}, 1000);

        const items = await CartService.getCartByVendor('vendor-1');
        expect(items).toHaveLength(1);
        expect(items[0].quantity).toBe(2);
    });

    it('should calculate totals correctly', async () => {
        await CartService.addItem('p1', 'v1', 'A', null, {}, 100); // 100 * 1 = 100
        await CartService.addItem('p2', 'v1', 'B', null, {}, 200); // 200 * 1 = 200
        await CartService.addItem('p1', 'v1', 'A', null, {}, 100); // 100 * 1 = 100 (quantity 2)

        const totals = await CartService.getTotals();
        // A (2) * 100 + B (1) * 200 = 400
        expect(totals.totalPrice).toBe(400);
        expect(totals.totalQuantity).toBe(3);
        expect(totals.totalItems).toBe(2); // Two unique product+modifier entries
    });

    it('should handle offline queue operations', async () => {
        const payload = { items: [{ id: 'p1' }], total: 500 };
        const orderId = await CartService.queueOrder(payload);
        
        expect(orderId).toBeDefined();
        let pending = await CartService.getPendingOrders();
        expect(pending).toHaveLength(1);
        expect(pending[0].payload.total).toBe(500);

        await CartService.incrementRetry(orderId);
        pending = await CartService.getPendingOrders();
        expect(pending[0].retries).toBe(1);

        await CartService.removePendingOrder(orderId);
        pending = await CartService.getPendingOrders();
        expect(pending).toHaveLength(0);
    });

    it('should perform bulk updates during server sync', async () => {
        const localId = await CartService.addItem('p1', 'v1', 'Old Name', null, {}, 100);
        
        const syncedItems = [
            {
                product_id: 'p1',
                name: 'New Name',
                price: 150,
                modifiers: {}
            }
        ];

        await CartService.bulkUpdateItems('v1', syncedItems);
        
        const items = await CartService.getCartByVendor('v1');
        expect(items[0].productName).toBe('New Name');
        expect(items[0].price).toBe(150);
    });
});
