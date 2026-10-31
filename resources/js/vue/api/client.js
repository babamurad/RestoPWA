import axios from 'axios';
import { Capacitor } from '@capacitor/core';

// Inside the Capacitor app the API lives on another origin, so auth can't ride
// on cookies/CSRF — it uses a Sanctum Bearer token instead (see vue/stores/auth.js).
export const isNativeApp = Capacitor.isNativePlatform();

// Replace with the production API domain before building the mobile app.
const MOBILE_API_BASE = 'https://restopwa.loc';

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
