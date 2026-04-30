document.addEventListener('alpine:init', () => {
    Alpine.data('cartManager', () => ({
        isInitialized: false,
        currentVendorId: null,

        async init() {
            this.setupEventListeners();
            this.listenToLivewire();

            // Attempt to recover currentVendorId from existing items in IndexedDB
            try {
                const allItems = await window.CartService.getAllItems();
                if (allItems.length > 0) {
                    this.currentVendorId = String(allItems[0].vendorId);
                    console.log('CartAlpine: Recovered vendor ID', this.currentVendorId);
                    await this.broadcastState();
                }
            } catch (error) {
                console.error('CartAlpine: Failed to recover vendor state:', error);
            }
        },

        setupEventListeners() {
            window.addEventListener('cart-add-item', (e) => {
                this.addItem(e.detail);
            });
        },

        listenToLivewire() {
            window.addEventListener('request-cart-state', () => {
                console.log('CartAlpine: request-cart-state received');
                this.broadcastState();
            });

            window.addEventListener('cart-update-quantity', (e) => {
                console.log('CartAlpine: cart-update-quantity received', e.detail);
                this.updateQuantity(e.detail.itemId, e.detail.quantity);
            });

            window.addEventListener('cart-remove-item', (e) => {
                this.removeItem(e.detail.itemId);
            });

            window.addEventListener('cart-clear', () => {
                this.clearCart();
            });

            window.addEventListener('cart-checkout', () => {
                this.checkout();
            });

            window.addEventListener('sync-pending-orders', () => {
                this.syncPendingOrders();
            });

            window.addEventListener('set-vendor', (e) => {
                console.log('CartAlpine: set-vendor received', e.detail);
                this.currentVendorId = e.detail.vendorId;
                this.broadcastState();
            });

            window.addEventListener('online', () => {
                this.syncPendingOrders();
            });

            window.addEventListener('order-synced-from-sw', (e) => {
                console.log('CartAlpine: Order synced from SW', e.detail);
                this.broadcastState();
            });

            window.addEventListener('auth-required-from-sw', (e) => {
                this.handleAuthError('sync');
            });
        },

        async addItem({ productId, vendorId, productName, image, modifiers = {}, price, quantity = 1 }) {
            console.log('CartAlpine: Adding item', { productId, vendorId, productName });
            try {
                const normalizedVendorId = String(vendorId).toLowerCase().trim();
                const currentNormalizedId = this.currentVendorId ? String(this.currentVendorId).toLowerCase().trim() : null;

                if (currentNormalizedId && currentNormalizedId !== normalizedVendorId) {
                    console.log('CartAlpine: Clearing different vendor cart', { current: currentNormalizedId, new: normalizedVendorId });
                    await window.CartService.clearVendorCart(this.currentVendorId);
                }
                
                this.currentVendorId = String(vendorId);
                await window.CartService.addItem(productId, String(vendorId), productName, image, modifiers, price, quantity);
                console.log('CartAlpine: Item added successfully');
                await this.broadcastState();
            } catch (error) {
                console.error('CartAlpine: Failed to add item:', error);
            }
        },

        async updateQuantity(itemId, quantity) {
            try {
                await window.CartService.updateQuantity(itemId, quantity);
                await this.broadcastState();
            } catch (error) {
                console.error('Failed to update quantity:', error);
            }
        },

        async removeItem(itemId) {
            try {
                await window.CartService.removeItem(itemId);
                await this.broadcastState();
            } catch (error) {
                console.error('Failed to remove item:', error);
            }
        },

        async clearCart() {
            if (!this.currentVendorId) return;
            try {
                await window.CartService.clearVendorCart(this.currentVendorId);
                await this.broadcastState();
            } catch (error) {
                console.error('Failed to clear cart:', error);
            }
        },

        async broadcastState() {
            let vendorId = this.currentVendorId;
            
            // If vendorId is missing, try to recover it from the first item in the cart
            if (!vendorId) {
                const allItems = await window.CartService.getAllItems();
                if (allItems.length > 0) {
                    vendorId = String(allItems[0].vendorId);
                    this.currentVendorId = vendorId;
                    console.log('CartAlpine: Recovered vendorId from items', vendorId);
                }
            }

            console.log('CartAlpine: Broadcasting state for vendor', vendorId || 'unknown');

            let items = [];
            let totals = { totalItems: 0, totalPrice: 0, totalQuantity: 0 };

            try {
                if (vendorId) {
                    items = await window.CartService.getCartByVendor(String(vendorId));
                } else {
                    // Fallback to all items if no vendor identified yet
                    items = await window.CartService.getAllItems();
                }

                // If we still have no items but totals are computed, compute totals from all items
                // This ensures consistency on pages like /cart that might show everything
                totals = await window.CartService.getTotals(vendorId || undefined);

                console.log('CartAlpine: State totals', totals);

                window.dispatchEvent(new CustomEvent('cart-state', {
                    detail: {
                        items,
                        ...totals,
                        vendorId
                    }
                }));
            } catch (error) {
                console.error('CartAlpine: Error during broadcastState', error);
            }
        },

        async syncCartWithServer() {
            if (!this.currentVendorId || !navigator.onLine) return null;

            const items = await window.CartService.getCartByVendor(this.currentVendorId);
            if (items.length === 0) return null;

            try {
                const response = await fetch('/api/v1/cart/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Vendor-ID': this.currentVendorId,
                    },
                    body: JSON.stringify({
                        vendor_id: this.currentVendorId,
                        items: items.map(item => ({
                            product_id: item.productId,
                            quantity: item.quantity,
                            modifiers: Object.keys(item.modifiers || {}),
                        }))
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    
                    if (result.success) {
                        await window.CartService.bulkUpdateItems(this.currentVendorId, result.data.items);
                        
                        if (result.errors && result.errors.length > 0) {
                            window.Swal.fire({
                                title: 'Внимание',
                                html: result.errors.join('<br>'),
                                icon: 'warning',
                                confirmButtonText: 'Понятно',
                                confirmButtonColor: '#f97316',
                                customClass: { popup: 'rounded-2xl' },
                            });
                            // Optional: Refresh if items were removed due to availability
                        }
                        
                        await this.broadcastState();
                        return result.data;
                    }
                }
            } catch (error) {
                console.error('Cart sync failed:', error);
            }
            return null;
        },

        async checkout() {
            console.log('CartAlpine: checkout initiated', { currentVendorId: this.currentVendorId });
            
            if (!this.currentVendorId) {
                console.warn('CartAlpine: currentVendorId is missing, attempting recovery');
                const allItems = await window.CartService.getAllItems();
                if (allItems.length > 0) {
                    this.currentVendorId = String(allItems[0].vendorId);
                    console.log('CartAlpine: Recovered vendor ID during checkout:', this.currentVendorId);
                }
            }

            if (!this.currentVendorId) {
                console.error('CartAlpine: checkout failed - no vendor ID');
                window.dispatchEvent(new CustomEvent('submit-order-failed'));
                return;
            }

            // Redirect to checkout wizard
            console.log('CartAlpine: redirecting to checkout', this.currentVendorId);
            window.location.href = `/checkout?vendor_id=${this.currentVendorId}`;
        },

        async syncPendingOrders() {
            const pendingOrders = await window.CartService.getPendingOrders();
            
            if (pendingOrders.length === 0) return;

            let synced = 0;
            let failed = 0;

            for (const order of pendingOrders) {
                if (order.retries >= 5) {
                    failed++;
                    continue;
                }

                try {
                    const response = await fetch('/api/v1/orders', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Vendor-ID': order.payload.vendor_id,
                            'X-Idempotency-Key': order.payload.idempotency_key,
                        },
                        body: JSON.stringify({
                            ...order.payload,
                            is_offline: true,
                        })
                    });

                    if (response.ok) {
                        await window.CartService.removePendingOrder(order.id);
                        synced++;
                        
                        const result = await response.json();
                        if (result.data?.redirect_url) {
                            window.location.href = result.data.redirect_url;
                        }
                    } else if (response.status === 409) {
                        const result = await response.json();
                        if (result.data?.is_duplicate && result.data?.order_id) {
                            await window.CartService.removePendingOrder(order.id);
                            synced++;
                            console.log('syncPendingOrders: Duplicate order acknowledged', result.data.order_id);
                        } else {
                            failed++;
                        }
                    } else if (response.status === 401) {
                        await this.handleAuthError('sync');
                        failed++;
                        break;
                    } else if (response.status === 403) {
                        await window.CartService.removePendingOrder(order.id);
                        window.Swal.fire({
                            title: 'Ошибка доступа',
                            text: 'Ваша сессия истекла. Войдите в профиль для повторной отправки заказа.',
                            icon: 'error',
                            confirmButtonText: 'Понятно',
                        });
                        failed++;
                    } else {
                        await window.CartService.incrementRetry(order.id);
                        failed++;
                    }
                } catch (error) {
                    console.error('Failed to sync order:', error);
                    await window.CartService.incrementRetry(order.id);
                    failed++;
                }
            }

            if (synced > 0) {
                window.dispatchEvent(new CustomEvent('orders-synced', {
                    detail: { synced, failed }
                }));
            }

            await this.broadcastState();
        },

        async handleAuthError(context = 'order') {
            window.dispatchEvent(new CustomEvent('auth-error', {
                detail: { context, timestamp: Date.now() }
            }));
            
            window.Swal.fire({
                title: 'Требуется вход',
                text: context === 'sync' 
                    ? 'Для отправки офлайн-заказов необходимо войти в профиль.'
                    : 'Для оформления заказа необходимо войти в профиль.',
                icon: 'warning',
                confirmButtonText: 'Войти',
                cancelButtonText: 'Отмена',
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                }
            });
        }
    }));

    Alpine.data('cartButton', (vendorId) => ({
        vendorId,
        badgeCount: 0,

        init() {
            // Load initial badge from DB (vendor-filtered if vendorId provided)
            this.refreshBadge();
            // Keep in sync via cart-state event (already vendor-filtered by broadcastState)
            window.addEventListener('cart-state', (e) => {
                this.badgeCount = e.detail.totalQuantity ?? 0;
            });
        },

        async refreshBadge() {
            // Prefer vendor-filtered totals; fall back to all if no vendorId given
            const totals = await window.CartService.getTotals(this.vendorId || undefined);
            this.badgeCount = totals.totalQuantity;
        },

        openCart() {
            window.dispatchEvent(new CustomEvent('open-cart'));
        }
    }));
});
