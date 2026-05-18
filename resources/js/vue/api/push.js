import apiClient from './client';

export default {
    /**
     * Подписаться на push-уведомления
     */
    async subscribe(subscription) {
        const response = await apiClient.post('/push/subscribe', subscription);
        return response.data;
    },

    /**
     * Отписаться от push-уведомлений
     */
    async unsubscribe(endpoint) {
        const response = await apiClient.post('/push/unsubscribe', { endpoint });
        return response.data;
    }
};
