import { defineStore } from 'pinia';
import apiClient from '../api/client';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
        isLoading: false,
    }),
    actions: {
        async fetchUser() {
            this.isLoading = true;
            try {
                // Предполагается, что на бэкенде есть маршрут /api/user для получения текущего пользователя
                const response = await apiClient.get('/user');
                this.user = response.data;
                this.isAuthenticated = !!this.user;
            } catch (error) {
                this.user = null;
                this.isAuthenticated = false;
            } finally {
                this.isLoading = false;
            }
        },
        async login(credentials) {
            await apiClient.get('/sanctum/csrf-cookie');
            await apiClient.post('/login', credentials);
            await this.fetchUser();
        },
        async logout() {
            await apiClient.post('/logout');
            this.user = null;
            this.isAuthenticated = false;
        }
    }
});
