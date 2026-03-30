import Dexie from 'dexie';

/**
 * @typedef {Object} CartItem
 * @property {number} [id]
 * @property {string} productId
 * @property {string} vendorId
 * @property {string} modifiersHash
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
     * @param {Object} [modifiers={}]
     * @param {number} price - price in cents
     * @returns {Promise<number>}
     */
    async addItem(productId, vendorId, modifiers = {}, price) {
        const modifiersHash = hashModifiers(modifiers);
        
        const existingItem = await db.cart
            .where('[vendorId+productId+modifiersHash]')
            .equals([vendorId, productId, modifiersHash])
            .first();

        if (existingItem) {
            await db.cart.update(existingItem.id, {
                quantity: existingItem.quantity + 1
            });
            return existingItem.id;
        }

        const id = await db.cart.add({
            productId,
            vendorId,
            modifiersHash,
            quantity: 1,
            price,
            addedAt: new Date()
        });
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
    }
};

window.CartService = CartService;
export default CartService;
