import apiClient from './client';

export default {
    /**
     * Создать заказ
     */
    async createOrder(payload, idempotencyKey = null) {
        const headers = {
            'X-Vendor-ID': payload.vendor_id,
        };
        if (idempotencyKey) {
            headers['X-Idempotency-Key'] = idempotencyKey;
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
    }
};
