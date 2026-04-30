/**
 * Detects multi-vendor conflicts in cart items.
 * Returns { isConflict: boolean, vendorIds: string[], message: string }
 */
export function detectMultiVendorConflict(items) {
    if (!items || items.length === 0) {
        return {
            isConflict: false,
            vendorIds: [],
            message: '',
        };
    }

    const vendorIds = [...new Set(items.map(item => String(item.vendorId)))];

    if (vendorIds.length > 1) {
        return {
            isConflict: true,
            vendorIds,
            message: `В корзине товары из ${vendorIds.length} разных ресторанов`,
        };
    }

    return {
        isConflict: false,
        vendorIds,
        message: '',
    };
}

/**
 * Validates that cart items are ready for submission.
 * Returns array of error reasons (empty = valid).
 */
export function validateCartForSubmit(items, vendorId, address) {
    const errors = [];

    if (!items || items.length === 0) {
        errors.push('empty_cart');
        return errors;
    }

    const conflict = detectMultiVendorConflict(items);
    if (conflict.isConflict) {
        errors.push('multi_vendor');
        return errors;
    }

    if (!vendorId) {
        errors.push('no_vendor');
    }

    if (!address || !address.lat || !address.lon) {
        errors.push('missing_address');
    }

    return errors;
}
