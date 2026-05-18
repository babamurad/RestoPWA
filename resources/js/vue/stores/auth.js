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
                const response = await apiClient.get('/user');
                if (response.data.success && response.data.user) {
                    this.user = response.data.user;
                    this.isAuthenticated = true;
                } else {
                    this.user = null;
                    this.isAuthenticated = false;
                }
            } catch (error) {
                this.user = null;
                this.isAuthenticated = false;
            } finally {
                this.isLoading = false;
            }
        },
        async login(credentials) {
            this.isLoading = true;
            try {
                const response = await apiClient.post('/login', credentials);
                if (response.data && response.data.success) {
                    this.user = response.data.user;
                    this.isAuthenticated = true;
                } else {
                    throw new Error(response.data?.message || 'Неверные учетные данные');
                }
            } finally {
                this.isLoading = false;
            }
        },
        async logout() {
            this.isLoading = true;
            try {
                await apiClient.post('/logout');
            } finally {
                this.user = null;
                this.isAuthenticated = false;
                this.isLoading = false;
            }
        }
    }
});
