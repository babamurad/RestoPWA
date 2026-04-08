import { describe, it, expect, beforeEach, vi } from 'vitest';

const mockCartData = new Map();
const mockPendingData = [];
let cartIdCounter = 0;
let pendingIdCounter = 0;

function matchesCompositeKey(item, key) {
    if (Array.isArray(key)) {
        const [vendorId, productId, modifiersHash] = key;
        return item.vendorId === vendorId && 
               item.productId === productId && 
               item.modifiersHash === modifiersHash;
    }
    return item.vendorId === key;
}

function createMockDB() {
    return {
        cart: {
            add: vi.fn(async (item) => {
                cartIdCounter++;
                const newItem = { ...item, id: cartIdCounter };
                mockCartData.set(cartIdCounter, newItem);
                return cartIdCounter;
            }),
            where: vi.fn((field) => ({
                equals: vi.fn((val) => ({
                    toArray: vi.fn(async () => {
                        if (field.startsWith('[')) {
                            return Array.from(mockCartData.values()).filter(item => 
                                matchesCompositeKey(item, val)
                            );
                        }
                        return Array.from(mockCartData.values()).filter(item => item[field] === val);
                    }),
                    delete: vi.fn(async () => {
                        for (const [id, item] of mockCartData.entries()) {
                            if (field.startsWith('[')) {
                                if (matchesCompositeKey(item, val)) {
                                    mockCartData.delete(id);
                                }
                            } else if (item[field] === val) {
                                mockCartData.delete(id);
                            }
                        }
                    }),
                    first: vi.fn(async () => {
                        if (field.startsWith('[')) {
                            return Array.from(mockCartData.values()).find(item => 
                                matchesCompositeKey(item, val)
                            );
                        }
                        return Array.from(mockCartData.values()).find(item => item[field] === val);
                    }),
                })),
            })),
            toArray: vi.fn(async () => Array.from(mockCartData.values())),
            delete: vi.fn(async (id) => mockCartData.delete(id)),
            update: vi.fn(async (id, changes) => {
                const item = mockCartData.get(id);
                if (item) {
                    mockCartData.set(id, { ...item, ...changes });
                }
            }),
        },
        pendingOrders: {
            add: vi.fn(async (item) => {
                pendingIdCounter++;
                mockPendingData.push({ ...item, id: pendingIdCounter });
                return pendingIdCounter;
            }),
            get: vi.fn(async (id) => {
                return mockPendingData.find(o => o.id === id);
            }),
            toArray: vi.fn(async () => [...mockPendingData]),
            delete: vi.fn(async (id) => {
                const idx = mockPendingData.findIndex(o => o.id === id);
                if (idx !== -1) mockPendingData.splice(idx, 1);
            }),
            update: vi.fn(async (id, changes) => {
                const order = mockPendingData.find(o => o.id === id);
                if (order) {
                    Object.assign(order, changes);
                }
            }),
        },
        open: vi.fn(),
        delete: vi.fn(async () => {
            mockCartData.clear();
            mockPendingData.length = 0;
            cartIdCounter = 0;
            pendingIdCounter = 0;
        }),
        version: vi.fn(() => ({
            stores: vi.fn(),
        })),
    };
}

let mockDb;

vi.mock('dexie', () => ({
    default: vi.fn(() => {
        mockDb = createMockDB();
        return mockDb;
    }),
}));

describe('CartService', () => {
    beforeEach(async () => {
        mockCartData.clear();
        mockPendingData.length = 0;
        cartIdCounter = 0;
        pendingIdCounter = 0;
        vi.clearAllMocks();
        await import('./CartService');
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
