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
    },

    /**
     * Отзывы о ресторане (публично, по 10 на страницу)
     */
    async getReviews(slug, page = 1) {
        const response = await apiClient.get(`/restaurants/${slug}/reviews`, { params: { page } });
        return response.data;
    }
};
