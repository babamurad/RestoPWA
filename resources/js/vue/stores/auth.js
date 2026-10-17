import { defineStore } from 'pinia';
import apiClient from '../api/client';
import { currentLocale, setLocale, t } from '../i18n';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        hasChecked: false,
    }),
    actions: {
        async fetchUser() {
            this.isLoading = true;
            try {
                const response = await apiClient.get('/user');
                if (response.data.success && response.data.user) {
                    this.user = response.data.user;
                    this.isAuthenticated = true;
                    this.syncLocaleFromUser();
                } else {
                    this.user = null;
                    this.isAuthenticated = false;
                }
            } catch (error) {
                this.user = null;
                this.isAuthenticated = false;
            } finally {
                this.isLoading = false;
                this.hasChecked = true;
            }
        },
        async login(credentials) {
            this.isLoading = true;
            try {
                const response = await apiClient.post('/login', credentials);
                if (response.data && response.data.success) {
                    this.user = response.data.user;
                    this.isAuthenticated = true;
                    this.hasChecked = true;
                    this.syncLocaleFromUser();
                } else {
                    throw new Error(response.data?.message || t('auth.invalid_credentials'));
                }
            } finally {
                this.isLoading = false;
            }
        },
        // A signed-in user's saved language wins over this device's choice
        syncLocaleFromUser() {
            if (this.user?.locale && this.user.locale !== currentLocale()) {
                setLocale(this.user.locale);
            }
        },
        async changeLocale(locale) {
            const next = setLocale(locale);
            if (this.isAuthenticated) {
                await apiClient.put('/profile/locale', { locale: next });
                if (this.user) {
                    this.user.locale = next;
                }
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
                this.hasChecked = true;
            }
        }
    }
});
