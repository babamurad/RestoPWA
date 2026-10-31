import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { i18n } from './i18n';
import { initSentry } from './sentry';

/** Mounts the Vue SPA onto the given selector. Shared by the web (Blade) and mobile (Capacitor) entry points. */
export function mountApp(selector = '#app') {
    const appElement = document.querySelector(selector);
    if (!appElement) {
        return null;
    }

    const app = createApp(App);
    const pinia = createPinia();
    app.use(pinia);
    app.use(router);
    app.use(i18n);
    initSentry(app, router);
    app.mount(selector);

    return app;
}
