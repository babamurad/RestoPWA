import axios from 'axios';
import { Capacitor } from '@capacitor/core';

// Inside the Capacitor app the API lives on another origin, so auth can't ride
// on cookies/CSRF — it uses a Sanctum Bearer token instead (see vue/stores/auth.js).
export const isNativeApp = Capacitor.isNativePlatform();

// Address of the site the mobile app talks to: VITE_MOBILE_API_BASE in .env
// (e.g. https://example.com), read when the mobile bundle is built.
const MOBILE_API_BASE = (import.meta.env?.VITE_MOBILE_API_BASE || '').replace(/\/+$/, '');

if (isNativeApp && !MOBILE_API_BASE) {
    console.error('VITE_MOBILE_API_BASE is not set: the mobile app does not know where the API is.');
}

export const MOBILE_AUTH_TOKEN_KEY = 'mobile_auth_token';

const apiClient = axios.create({
    baseURL: isNativeApp ? `${MOBILE_API_BASE}/api/v1` : '/api/v1',
    withCredentials: !isNativeApp,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
});

apiClient.interceptors.request.use(config => {
    if (isNativeApp) {
        const token = localStorage.getItem(MOBILE_AUTH_TOKEN_KEY);
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
    } else if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
        // CSRF token for the cookie-session web app
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token.content;
        }
    }
    return config;
});

export default apiClient;
