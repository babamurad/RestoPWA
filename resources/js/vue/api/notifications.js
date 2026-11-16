import apiClient from './client';

// Notification settings of the signed-in customer
export default {
    async getSettings() {
        return (await apiClient.get('/profile/notifications')).data.data;
    },

    // marketing: { push?, email?, sms? }
    async updateMarketing(marketing) {
        return (await apiClient.put('/profile/notifications', { marketing })).data.data;
    },
};
