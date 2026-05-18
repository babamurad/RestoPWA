import apiClient from './client';

export default {
    /**
     * Получить меню ресторана
     */
    async getMenu(vendorId, params = {}) {
        const response = await apiClient.get(`/menu/${vendorId}`, { params });
        return response.data;
    },

    /**
     * Получить информацию о продукте
     */
    async getProduct(productId) {
        const response = await apiClient.get(`/menu/product/${productId}`);
        return response.data;
    }
};
