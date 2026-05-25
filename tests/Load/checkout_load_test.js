import http from 'k6/http';
import { check, sleep } from 'k6';

// 1. Конфигурация сценария нагрузки
// Этот скрипт имитирует "спайк" нагрузки (например, после push-уведомления).
export const options = {
    stages: [
        { duration: '30s', target: 10 },  // Разгон до 10 пользователей за 30 сек
        { duration: '1m', target: 10 },   // Удержание 10 пользователей 1 мин
        { duration: '30s', target: 0 },   // Плавное снижение
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% запросов должны выполняться быстрее 2 секунд
        http_req_failed: ['rate<0.05'],   // Не более 5% ошибок (включает 422 валидацию, если данные кривые, но мы шлем валидные)
    },
};

// 2. Глобальные настройки (замените перед запуском)
const BASE_URL = __ENV.API_BASE_URL || 'http://localhost/api/v1';
const VENDOR_ID = __ENV.VENDOR_ID || '1';
const PRODUCT_ID = __ENV.PRODUCT_ID || '1';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || ''; // Если требуется авторизация, передать токен

export default function () {
    // 3. Формирование динамического payload
    // Используем уникальный idempotency_key для каждого заказа, чтобы избегать ошибки is_duplicate
    const idempotencyKey = `k6-test-${__VU}-${__ITER}-${Date.now()}`;
    const traceId = `k6-trace-${__VU}-${__ITER}`;

    const payload = JSON.stringify({
        vendor_id: VENDOR_ID,
        items: [
            {
                product_id: PRODUCT_ID,
                product_name: "Test Load Pizza",
                quantity: 1,
                unit_price: 10000,
                total_price: 10000
            }
        ],
        total: 10000,
        delivery_fee: 0,
        customer_name: "K6 Load Tester",
        customer_phone: "+99361112233",
        address: {
            lat: 39.0886,
            lon: 63.5593,
            address: "Test Load Address 1"
        },
        payment_method: "cash"
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Idempotency-Key': idempotencyKey,
            'X-Trace-Id': traceId,
        },
    };

    if (AUTH_TOKEN) {
        params.headers['Authorization'] = `Bearer ${AUTH_TOKEN}`;
    }

    // 4. Отправка POST запроса
    const res = http.post(`${BASE_URL}/orders`, payload, params);

    // 5. Проверка результатов
    check(res, {
        'status is 201 or 401': (r) => r.status === 201 || r.status === 401,
        'has trace_id': (r) => r.status === 201 ? JSON.parse(r.body).trace_id !== undefined : true,
    });

    // 6. Пауза между запросами пользователя (think time)
    sleep(1);
}
