import Dexie from 'dexie';

/**
 * @typedef {Object} CartItem
 * @property {number} [id]
 * @property {string} productId
 * @property {string} vendorId
 * @property {string} productName
 * @property {string} [image]
 * @property {string} modifiersHash
 * @property {Object} modifiers
 * @property {number} quantity
 * @property {number} price
 * @property {Date} addedAt
 */

/**
 * @typedef {Object} PendingOrder
 * @property {number} [id]
 * @property {Object} payload
 * @property {number} retries
 * @property {Date} createdAt
 */

/**
 * @typedef {Object} CartTotals
 * @property {number} totalItems
 * @property {number} totalPrice
 * @property {number} totalQuantity
 */

class RestoCartDatabase extends Dexie {
    constructor() {
        super('RestoCart');
        this.version(1).stores({
            cart: '++id, vendorId, productId, modifiersHash, [vendorId+productId+modifiersHash], addedAt',
            pendingOrders: '++id, createdAt'
        });
    }
}

const db = new RestoCartDatabase();

function hashModifiers(modifiers) {
    if (!modifiers || Object.keys(modifiers).length === 0) {
        return '';
    }
    const sorted = Object.entries(modifiers)
        .sort(([a], [b]) => a.localeCompare(b))
        .flat()
        .join('|');
    
    let hash = 0;
    for (let i = 0; i < sorted.length; i++) {
        const char = sorted.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    return hash.toString(16);
}

/** @type {CartService} */
const CartService = {
    /**
     * @param {string} productId
     * @param {string} vendorId
     * @param {string} productName
     * @param {string} [image]
     * @param {Object} [modifiers={}]
     * @param {number} price - price in cents
     * @param {number} [quantity=1]
     * @returns {Promise<number>}
     */
    async addItem(productId, vendorId, productName, image, modifiers = {}, price, quantity = 1) {
        const modifiersHash = hashModifiers(modifiers);
        
        // Defensive check to prevent IndexedDB DataError: "The parameter is not a valid key"
        if (!vendorId || !productId || typeof modifiersHash !== 'string') {
            console.error('Invalid keys for cart addition:', { vendorId, productId, modifiersHash });
            throw new Error('Invalid product data: missing required identifiers');
        }

        const existingItem = await db.cart
            .where('[vendorId+productId+modifiersHash]')
            .equals([vendorId, productId, modifiersHash])
            .first();

        if (existingItem) {
            await db.cart.update(existingItem.id, {
                quantity: existingItem.quantity + quantity
            });
            return existingItem.id;
        }

        console.log('CartService: Adding to IndexedDB', { productId, vendorId, quantity });
        const id = await db.cart.add({
            productId,
            vendorId,
            productName,
            image,
            modifiersHash,
            modifiers,
            quantity: quantity,
            price,
            addedAt: new Date()
        });
        console.log('CartService: Added with ID', id);
        return id;
    },

    /**
     * @param {number} id
     * @returns {Promise<void>}
     */
    async removeItem(id) {
        await db.cart.delete(id);
    },

    /**
     * @param {number} id
     * @param {number} quantity
     * @returns {Promise<void>}
     */
    async updateQuantity(id, quantity) {
        if (quantity <= 0) {
            await this.removeItem(id);
            return;
        }
        await db.cart.update(id, { quantity });
    },

    /**
     * @param {string} vendorId
     * @returns {Promise<CartItem[]>}
     */
    async getCartByVendor(vendorId) {
        return db.cart.where('vendorId').equals(vendorId).toArray();
    },

    /**
     * @param {string} vendorId
     * @returns {Promise<void>}
     */
    async clearVendorCart(vendorId) {
        await db.cart.where('vendorId').equals(vendorId).delete();
    },

    /**
     * @returns {Promise<CartTotals>}
     */
    async getTotals() {
        const items = await db.cart.toArray();
        const totals = items.reduce(
            (acc, item) => ({
                totalItems: acc.totalItems + 1,
                totalPrice: acc.totalPrice + (item.price * item.quantity),
                totalQuantity: acc.totalQuantity + item.quantity
            }),
            { totalItems: 0, totalPrice: 0, totalQuantity: 0 }
        );
        return totals;
    },

    /**
     * @param {Object} orderPayload
     * @returns {Promise<number>}
     */
    async queueOrder(orderPayload) {
        if (!orderPayload.idempotency_key) {
            orderPayload.idempotency_key = crypto.randomUUID();
        }
        return db.pendingOrders.add({
            payload: orderPayload,
            retries: 0,
            createdAt: new Date()
        });
    },

    /**
     * @returns {Promise<PendingOrder[]>}
     */
    async getPendingOrders() {
        return db.pendingOrders.toArray();
    },

    /**
     * @param {number} id
     * @returns {Promise<void>}
     */
    async removePendingOrder(id) {
        await db.pendingOrders.delete(id);
    },

    /**
     * @param {number} id
     * @returns {Promise<void>}
     */
    async incrementRetry(id) {
        const order = await db.pendingOrders.get(id);
        if (order) {
            await db.pendingOrders.update(id, { retries: order.retries + 1 });
        }
    },

    /**
     * @returns {Promise<CartItem[]>}
     */
    async getAllItems() {
        return db.cart.toArray();
    },

    /**
     * Bulk update items from server sync.
     * @param {string} vendorId
     * @param {Array} syncedItems
     * @returns {Promise<void>}
     */
    async bulkUpdateItems(vendorId, syncedItems) {
        for (const item of syncedItems) {
            const existing = await db.cart
                .where('[vendorId+productId+modifiersHash]')
                .equals([vendorId, item.product_id, hashModifiers(item.modifiers)])
                .first();

            if (existing) {
                await db.cart.update(existing.id, {
                    price: item.price,
                    productName: item.name,
                    image: item.image,
                });
            }
        }
    },
    /**
     * @param {string} vendorId
     * @param {CartItem[]} items
     * @returns {Promise<Object>}
     */
    async syncWithServer(vendorId, items) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        try {
            const response = await fetch('/api/v1/cart/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    vendor_id: vendorId,
                    items: items.map(item => ({
                        product_id: item.productId,
                        quantity: item.quantity,
                        price: item.price,
                        modifiers: Object.keys(item.modifiers || {})
                    }))
                })
            });

            if (!response.ok) {
                throw new Error('Sync failed');
            }

            const result = await response.json();
            
            if (result.success && result.data) {
                // Remove unavailable items from local cart
                if (result.data.unavailable_items && result.data.unavailable_items.length > 0) {
                    for (const item of result.data.unavailable_items) {
                        const existing = await db.cart
                            .where('productId')
                            .equals(item.product_id)
                            .and(r => r.vendorId === vendorId)
                            .toArray();
                        
                        for (const cartItem of existing) {
                            await db.cart.delete(cartItem.id);
                        }
                    }
                }

                // Automatically update local cart with server data
                await this.bulkUpdateItems(vendorId, result.data.validated_items);
                return result.data;
            }
            
            return null;
        } catch (error) {
            console.error('Cart sync error:', error);
            throw error;
        }
    },
};

window.CartService = CartService;
export default CartService;
