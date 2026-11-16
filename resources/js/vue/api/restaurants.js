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
    },

    /**
     * Избранные рестораны текущего пользователя (список id)
     */
    async getFavorites() {
        const response = await apiClient.get('/profile/favorites');
        return response.data.data || [];
    },

    async addFavorite(id) {
        return (await apiClient.put(`/profile/favorites/${id}`)).data;
    },

    async removeFavorite(id) {
        return (await apiClient.delete(`/profile/favorites/${id}`)).data;
    }
};
