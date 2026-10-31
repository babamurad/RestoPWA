import '../css/app.css';
import { mountApp } from '../js/vue/bootstrapApp';
import { startConnectivityMonitor } from '../js/vue/connectivity';
import { setupOfflineAuthPrompt } from '../js/vue/offlineOrders';
import apiClient from '../js/vue/api/client';
import CartService from '../js/services/CartService';
import '../js/bootstrap';
import Swal from 'sweetalert2';

window.Swal = Swal;
setupOfflineAuthPrompt();
startConnectivityMonitor();

// Blade injects this on web (see app.blade.php); the static mobile shell fetches it instead.
apiClient.get('/config').then(({ data }) => {
    window.checkoutDefaultGeolocateEnabled = data.geolocate_enabled;
}).catch(() => {});

// No Service Worker inside the Capacitor WebView, so offline orders queued by
// CartService.queueOrder() (see CheckoutPage.vue) are retried from here instead.
CartService.flushPendingOrders();
window.addEventListener('online', () => CartService.flushPendingOrders());

mountApp();
