import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '../pages/HomePage.vue';
import CartPage from '../pages/CartPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import RestaurantMenuPage from '../pages/RestaurantMenuPage.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomePage,
        meta: { title: 'Каталог ресторанов' }
    },
    {
        path: '/restaurants/:slug',
        name: 'restaurant-menu',
        component: RestaurantMenuPage,
        meta: { title: 'Меню ресторана' }
    },
    {
        path: '/cart',
        name: 'cart',
        component: CartPage,
        meta: { title: 'Корзина' }
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: () => import('../pages/CheckoutPage.vue'),
        meta: { title: 'Оформление заказа', requiresAuth: true }
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: { title: 'Вход | RestoPWA', guestOnly: true }
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../pages/RegisterPage.vue'),
        meta: { title: 'Регистрация | RestoPWA', guestOnly: true }
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        meta: { title: 'Профиль', requiresAuth: true }
    },
    {
        path: '/orders',
        name: 'orders',
        component: () => import('../pages/OrdersPage.vue'),
        meta: { title: 'Заказы', requiresAuth: true }
    },
    // Redirect all other unmatched routes to home
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory('/'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0, behavior: 'smooth' };
        }
    }
});

// Auth and Title Guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // Set dynamic page title
    const title = to.meta.title ? `${to.meta.title} | RestoPWA` : 'RestoPWA';
    document.title = title;

    // Check if user is already loaded/authenticated
    if (!authStore.hasChecked) {
        await authStore.fetchUser();
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (to.meta.guestOnly && authStore.isAuthenticated) {
        next({ name: 'profile' });
    } else {
        next();
    }
});

export default router;
