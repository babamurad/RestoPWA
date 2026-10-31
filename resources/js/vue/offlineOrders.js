import Swal from 'sweetalert2';
import { t } from './i18n';

/**
 * Shared "please log in" prompt for a queued offline order that the server rejected
 * as unauthenticated. Fired either by public/sw.js (web, via a postMessage relay in
 * app.js) or by CartService.flushPendingOrders() (mobile, no Service Worker).
 */
export function setupOfflineAuthPrompt() {
    window.addEventListener('auth-required-from-offline', () => {
        Swal.fire({
            title: t('app.auth_required.title'),
            text: t('app.auth_required.text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: t('common.login'),
            cancelButtonText: t('common.later'),
            confirmButtonColor: '#f97316',
            reverseButtons: true,
            customClass: { popup: 'rounded-2xl' },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
            }
        });
    });
}
