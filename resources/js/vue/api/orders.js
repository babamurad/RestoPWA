import apiClient from './client';

export default {
    /**
     * Создать заказ
     */
    async createOrder(payload, idempotencyKey = null) {
        const headers = {
            'X-Vendor-ID': payload.vendor_id,
        };
        if (idempotencyKey || payload.idempotency_key) {
            headers['X-Idempotency-Key'] = idempotencyKey || payload.idempotency_key;
        }
        if (payload.trace_id) {
            headers['X-Trace-Id'] = payload.trace_id;
        }

        const response = await apiClient.post('/orders', payload, { headers });
        return response.data;
    },

    /**
     * Получить список заказов текущего пользователя
     */
    async getOrders(params = {}) {
        const response = await apiClient.get('/orders', { params });
        return response.data;
    },

    /**
     * Получить детали заказа по ID
     */
    async getOrderById(orderId) {
        const response = await apiClient.get(`/orders/${orderId}`);
        return response.data;
    },

    /**
     * Оставить отзыв о доставленном заказе (оценка 1-5 и комментарий)
     */
    async reviewOrder(orderId, payload) {
        const response = await apiClient.post(`/orders/${orderId}/review`, payload);
        return response.data;
    }
};
