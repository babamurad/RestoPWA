// Browser error reporting for the customer app. The SDK is loaded as a separate
// chunk and only when VITE_SENTRY_DSN is set, so it costs nothing otherwise.
export function initSentry(app, router) {
    const dsn = import.meta.env.VITE_SENTRY_DSN;
    if (!dsn) {
        return;
    }

    import('@sentry/vue')
        .then((Sentry) => {
            Sentry.init({
                app,
                dsn,
                environment: import.meta.env.MODE,
                // No names, phones or addresses: customers' data stays on our server
                sendDefaultPii: false,
                integrations: [Sentry.browserTracingIntegration({ router })],
                tracesSampleRate: 0,
            });
        })
        .catch((error) => console.warn('Sentry failed to load', error));
}
