import { t } from '../vue/i18n';

export const OrderRejectReason = Object.freeze({
    EMPTY_CART: 'empty_cart',
    NO_VENDOR: 'no_vendor',
    MULTI_VENDOR: 'multi_vendor',
    INVALID_VENDOR: 'invalid_vendor',
    VENDOR_NOT_FOUND: 'vendor_not_found',
    INVALID_ITEMS: 'invalid_items',
    ITEM_NOT_FOUND: 'item_not_found',
    ITEM_UNAVAILABLE: 'item_unavailable',
    INVALID_PRICE: 'invalid_price',
    INVALID_TOTAL: 'invalid_total',
    MISSING_ADDRESS: 'missing_address',
    INVALID_COORDINATES: 'invalid_coordinates',
    OUTSIDE_DELIVERY_ZONE: 'outside_delivery_zone',
    INVALID_PHONE: 'invalid_phone',
    MISSING_NAME: 'missing_name',
    INVALID_PAYMENT_METHOD: 'invalid_payment_method',
    UNAUTHORIZED: 'unauthorized',
    NETWORK: 'network',
    VALIDATION: 'validation',
    SERVER_ERROR: 'server_error',
    BELOW_MIN_ORDER: 'below_min_order',
});

// Messages are resolved on access so they follow the current UI language
export const UserFacingMessages = Object.freeze(
    Object.values(OrderRejectReason).reduce((messages, reason) => {
        Object.defineProperty(messages, reason, {
            enumerable: true,
            get: () => t(`errors.order.${reason}`),
        });
        return messages;
    }, {}),
);

export function getErrorMessage(reason) {
    return UserFacingMessages[reason] || t('common.unknown_error');
}
