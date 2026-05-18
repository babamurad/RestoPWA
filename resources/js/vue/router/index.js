import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '../pages/HomePage.vue';
import CartPage from '../pages/CartPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import RestaurantMenuPage from '../pages/RestaurantMenuPage.vue';

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
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        meta: { title: 'Профиль' }
    },
    // Redirect all other unmatched routes to home
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory('/app'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0, behavior: 'smooth' };
        }
    }
});

// Update page title dynamically
router.beforeEach((to, from, next) => {
    const title = to.meta.title ? `${to.meta.title} | RestoPWA` : 'RestoPWA';
    document.title = title;
    next();
});

export default router;
