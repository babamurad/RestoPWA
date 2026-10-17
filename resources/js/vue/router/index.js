import { watch } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '../pages/HomePage.vue';
import CartPage from '../pages/CartPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import RestaurantMenuPage from '../pages/RestaurantMenuPage.vue';
import { useAuthStore } from '../stores/auth';
import { i18n, t } from '../i18n';

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomePage,
        meta: { titleKey: 'app.titles.home' }
    },
    {
        path: '/restaurants/:slug',
        name: 'restaurant-menu',
        component: RestaurantMenuPage,
        meta: { titleKey: 'app.titles.restaurant_menu' }
    },
    {
        path: '/cart',
        name: 'cart',
        component: CartPage,
        meta: { titleKey: 'app.titles.cart' }
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: () => import('../pages/CheckoutPage.vue'),
        meta: { titleKey: 'app.titles.checkout', requiresAuth: true }
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: { titleKey: 'app.titles.login', guestOnly: true }
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../pages/RegisterPage.vue'),
        meta: { titleKey: 'app.titles.register', guestOnly: true }
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        meta: { titleKey: 'app.titles.profile', requiresAuth: true }
    },
    {
        path: '/profile/addresses',
        name: 'profile-addresses',
        component: () => import('../pages/profile/AddressesPage.vue'),
        meta: { titleKey: 'app.titles.addresses', requiresAuth: true }
    },
    {
        path: '/profile/payments',
        name: 'profile-payments',
        component: () => import('../pages/profile/PaymentsPage.vue'),
        meta: { titleKey: 'app.titles.payments', requiresAuth: true }
    },
    {
        path: '/profile/support',
        name: 'profile-support',
        component: () => import('../pages/profile/SupportPage.vue'),
        meta: { titleKey: 'app.titles.support', requiresAuth: true }
    },
    {
        path: '/orders',
        name: 'orders',
        component: () => import('../pages/OrdersPage.vue'),
        meta: { titleKey: 'app.titles.orders', requiresAuth: true }
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
    applyTitle(to);

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

// Page title in the current language: "<page> | RestoPWA"
function applyTitle(route) {
    document.title = route.meta.titleKey ? `${t(route.meta.titleKey)} | RestoPWA` : 'RestoPWA';
}

// Re-translate the title when the user switches the language
watch(i18n.global.locale, () => applyTitle(router.currentRoute.value));

export default router;
