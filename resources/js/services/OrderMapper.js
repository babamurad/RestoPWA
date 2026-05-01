/**
 * OrderMapper: transforms CartService items into the strict DTO format
 * expected by the Order API (POST /api/v1/orders).
 *
 * Client model: { productId, vendorId, productName, price, quantity, modifiers, image }
 * Server expects: { product_id, product_name, quantity, unit_price, total_price, modifiers, image }
 *
 * All prices are in cents (integer) on both sides.
 */

/**
 * @param {Array} cartItems - from CartService.getCartByVendor()
 * @returns {Array} items ready for API submission
 */
export function mapCartItemsToApiFormat(cartItems) {
    return cartItems.map(item => {
        const unitPrice = typeof item.price === 'number' ? item.price : parseInt(item.price, 10);
        const quantity = typeof item.quantity === 'number' ? item.quantity : parseInt(item.quantity, 10);
        const totalPrice = unitPrice * quantity;

        return {
            product_id: String(item.productId),
            product_name: String(item.productName || 'Product'),
            quantity: quantity,
            unit_price: unitPrice,
            total_price: totalPrice,
            modifiers: item.modifiers || {},
            image: item.image || null,
        };
    });
}

/**
 * Calculate total from mapped items + delivery fee.
 * @param {Array} mappedItems
 * @param {number} deliveryFee - in cents
 * @returns {number} total in cents
 */
export function calculateOrderTotal(mappedItems, deliveryFee = 0) {
    const itemsTotal = mappedItems.reduce((sum, item) => sum + (item.total_price || 0), 0);
    return itemsTotal + deliveryFee;
}

/**
 * Build the complete order payload for API submission.
 * @param {Object} params
 * @param {Array} params.cartItems - from CartService
 * @param {string} params.vendorId
 * @param {Object} params.address - { lat, lon, address, name, phone, house, apartment, comment }
 * @param {number} params.deliveryFee - in cents
 * @param {string} params.deliveryTime - 'asap' or ISO string
 * @param {string} params.paymentMethod
 * @param {string} [params.comment] - order-level comment
 * @param {string} [params.idempotencyKey]
 * @param {string} [params.traceId]
 * @returns {Object} complete payload
 */
export function buildOrderPayload({
    cartItems,
    vendorId,
    address,
    deliveryFee = 0,
    deliveryTime = 'asap',
    paymentMethod = 'card',
    comment = '',
    idempotencyKey = null,
    traceId = null,
}) {
    const mappedItems = mapCartItemsToApiFormat(cartItems);
    const total = calculateOrderTotal(mappedItems, deliveryFee);

    return {
        vendor_id: String(vendorId),
        items: mappedItems,
        total: total,
        delivery_fee: deliveryFee,
        delivery_time: deliveryTime,
        payment_method: paymentMethod,
        comment: comment || null,
        address: {
            lat: address.lat,
            lon: address.lon,
            address: address.address || '',
            name: address.name || '',
            phone: address.phone || '',
            house: address.house || null,
            apartment: address.apartment || null,
        },
        idempotency_key: idempotencyKey,
        trace_id: traceId,
    };
}
