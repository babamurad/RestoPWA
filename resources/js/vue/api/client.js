import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
});

// Добавляем CSRF токен для POST/PUT/DELETE запросов
apiClient.interceptors.request.use(config => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token.content;
        }
    }
    return config;
});

export default apiClient;
