import apiClient from './client';

// Courier section of the app: shift, offers, deliveries, location, earnings
export default {
    async getOrders() {
        return (await apiClient.get('/courier/orders')).data.data;
    },
    async getOffers() {
        return (await apiClient.get('/courier/offers')).data.data;
    },
    async acceptOffer(offerId) {
        return (await apiClient.post(`/courier/offers/${offerId}/accept`)).data;
    },
    async declineOffer(offerId) {
        return (await apiClient.post(`/courier/offers/${offerId}/decline`)).data;
    },
    async acceptOrder(orderId) {
        return (await apiClient.post(`/courier/orders/${orderId}/accept`)).data;
    },
    async setOrderStatus(orderId, status) {
        return (await apiClient.post(`/courier/orders/${orderId}/status`, { status })).data;
    },
    async setShiftStatus(status) {
        return (await apiClient.post('/courier/status', { status })).data;
    },
    async sendLocation(lat, lon) {
        return (await apiClient.post('/courier/location', { lat, lon })).data;
    },
    async getEarnings() {
        return (await apiClient.get('/courier/earnings')).data;
    },
};
