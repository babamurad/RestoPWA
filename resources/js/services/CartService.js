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
            cart: '++id, vendorId, productId, modifiersHash, quantity, price, addedAt',
            pendingOrders: '++id, payload, retries, createdAt'
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
    async addItem(productId, vendorId, modifiers = {}, price) {
        const modifiersHash = hashModifiers(modifiers);
        
        const existingItem = await db.cart
            .where('[vendorId+modifiersHash]')
            .equals([vendorId, modifiersHash])
            .and(item => item.productId === productId)
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

    async removeItem(id) {
        await db.cart.delete(id);
    },

    async updateQuantity(id, quantity) {
        if (quantity <= 0) {
            await this.removeItem(id);
            return;
        }
        await db.cart.update(id, { quantity });
    },

    async getCartByVendor(vendorId) {
        return db.cart.where('vendorId').equals(vendorId).toArray();
    },

    async clearVendorCart(vendorId) {
        await db.cart.where('vendorId').equals(vendorId).delete();
    },

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

    async queueOrder(orderPayload) {
        await db.pendingOrders.add({
            payload: orderPayload,
            retries: 0,
            createdAt: new Date()
        });
    },

    async getPendingOrders() {
        return db.pendingOrders.toArray();
    },

    async removePendingOrder(id) {
        await db.pendingOrders.delete(id);
    },

    async incrementRetry(id) {
        const order = await db.pendingOrders.get(id);
        if (order) {
            await db.pendingOrders.update(id, { retries: order.retries + 1 });
        }
    }
};

window.CartService = CartService;
export default CartService;
