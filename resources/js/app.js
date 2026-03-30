import './bootstrap';
import './services/CartService';
import './services/CartAlpine';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}

window.addEventListener('online', () => {
    document.body.classList.remove('is-offline');
    if (window.Livewire) {
        window.Livewire.dispatch('browser-online');
    }
});

window.addEventListener('offline', () => {
    document.body.classList.add('is-offline');
    if (window.Livewire) {
        window.Livewire.dispatch('browser-offline');
    }
});
