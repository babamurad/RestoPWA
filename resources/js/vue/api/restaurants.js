import apiClient from './client';

export default {
    /**
     * Получить список ресторанов
     */
    async getRestaurants(params = {}) {
        const response = await apiClient.get('/restaurants', { params });
        return response.data;
    },

    /**
     * Получить информацию о ресторане
     */
    async getRestaurantBySlug(slug) {
        const response = await apiClient.get(`/restaurants/${slug}`);
        return response.data;
    }
};
