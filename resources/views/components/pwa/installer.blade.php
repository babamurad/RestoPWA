<div x-data="pwaInstaller()" 
     x-show="showInstallBanner" 
     x-transition
     class="fixed bottom-0 inset-x-0 pb-4 sm:pb-6 z-[100]"
     style="display: none;">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="/icons/icon-192x192.svg" alt="App Icon" class="w-12 h-12 rounded-xl">
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ config('app.name', 'RestoPWA') }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Установить на домашний экран</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="installPwa()" class="px-4 py-2 text-sm font-semibold text-white bg-orange-500 rounded-xl shadow-md hover:bg-orange-600 active:scale-95 transition-all">
                    Установить
                </button>
                <button @click="dismissBanner()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pwaInstaller', () => ({
            deferredPrompt: null,
            showInstallBanner: false,

            init() {
                if (localStorage.getItem('pwa_install_dismissed')) {
                    return;
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    this.showInstallBanner = true;
                });

                window.addEventListener('appinstalled', () => {
                    this.showInstallBanner = false;
                    this.deferredPrompt = null;
                });
            },

            async installPwa() {
                if (!this.deferredPrompt) {
                    return;
                }
                
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                
                this.deferredPrompt = null;
                this.showInstallBanner = false;
            },

            dismissBanner() {
                this.showInstallBanner = false;
                localStorage.setItem('pwa_install_dismissed', 'true');
            }
        }));
    });
</script>
