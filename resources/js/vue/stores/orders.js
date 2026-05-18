import { defineStore } from 'pinia';
import ordersApi from '../api/orders';

export const useOrdersStore = defineStore('orders', {
    state: () => ({
        orders: [],
        currentOrder: null,
        isLoading: false,
        error: null,
    }),
    actions: {
        async submitOrder(payload) {
            this.isLoading = true;
            this.error = null;
            try {
                // Генерация idempotencyKey, если нужно
                const idempotencyKey = payload.idempotency_key || self.crypto.randomUUID();
                const result = await ordersApi.createOrder(payload, idempotencyKey);
                return result;
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка оформления заказа';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },
        async fetchOrders(params = {}) {
            this.isLoading = true;
            try {
                const response = await ordersApi.getOrders(params);
                this.orders = response.data || [];
            } catch (error) {
                this.error = 'Ошибка загрузки заказов';
            } finally {
                this.isLoading = false;
            }
        },
        async fetchOrderById(id) {
            this.isLoading = true;
            try {
                const response = await ordersApi.getOrderById(id);
                this.currentOrder = response.data;
            } catch (error) {
                this.error = 'Ошибка загрузки заказа';
            } finally {
                this.isLoading = false;
            }
        }
    }
});
