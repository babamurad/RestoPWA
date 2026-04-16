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
            console.log('CartAlpine: Broadcasting state for vendor', vendorId || 'unknown');

            let items = [];
            let totals = { totalItems: 0, totalPrice: 0, totalQuantity: 0 };

            try {
                if (vendorId) {
                    items = await window.CartService.getCartByVendor(String(vendorId));
                }

                // Final safety check: if we have NO items for this vendor, but DB is NOT empty,
                // recover vendor from existing items
                if (items.length === 0) {
                    const allItems = await window.CartService.getAllItems();
                    if (allItems.length > 0) {
                        console.warn('CartAlpine: Filtered items empty but DB has items. Recovering vendor.');
                        this.currentVendorId = String(allItems[0].vendorId);
                        vendorId = this.currentVendorId;
                        items = await window.CartService.getCartByVendor(vendorId);
                    }
                }

                // Always compute totals from vendor-specific items only
                totals = await window.CartService.getTotals(vendorId);

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
            if (!this.currentVendorId) {
                window.dispatchEvent(new CustomEvent('submit-order-failed'));
                return;
            }

            try {
                let syncData = null;
                if (navigator.onLine) {
                    syncData = await this.syncCartWithServer();
                    
                    if (syncData && !syncData.is_min_order_met) {
                        window.Swal.fire({
                            title: 'Минимальная сумма заказа',
                            text: `${syncData.min_order} ₽`,
                            icon: 'info',
                            confirmButtonText: 'Понятно',
                            confirmButtonColor: '#f97316',
                            customClass: { popup: 'rounded-2xl' },
                        });
                        window.dispatchEvent(new CustomEvent('submit-order-failed'));
                        return;
                    }
                }

                const items = await window.CartService.getCartByVendor(this.currentVendorId);
                if (items.length === 0) {
                    window.dispatchEvent(new CustomEvent('submit-order-failed'));
                    return;
                }

                const total = syncData ? syncData.total : items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const subtotal = syncData ? syncData.subtotal : items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const deliveryFee = syncData ? syncData.delivery_fee : 0;

                const orderPayload = {
                    vendor_id: this.currentVendorId,
                    items: items.map(item => ({
                        product_id: item.productId,
                        product_name: item.productName,
                        image: item.image,
                        quantity: item.quantity,
                        unit_price: item.price,
                        total_price: item.price * item.quantity,
                        modifiers: item.modifiers || {},
                    })),
                    subtotal: subtotal,
                    total: total,
                    delivery_fee: deliveryFee,
                    is_offline: !navigator.onLine,
                    idempotency_key: window.generateUUID ? window.generateUUID() : Math.random().toString(36).substring(2),
                    created_at: new Date().toISOString()
                };

                if (navigator.onLine) {
                    window.dispatchEvent(new CustomEvent('submit-order', { detail: orderPayload }));
                } else {
                    await window.CartService.queueOrder(orderPayload);
                    window.dispatchEvent(new CustomEvent('cart-clear'));
                    await window.CartService.clearVendorCart(this.currentVendorId);
                    await this.broadcastState();
                    window.Swal.fire({
                        title: 'Заказ сохранён!',
                        text: 'Будет отправлен когда появится интернет',
                        icon: 'success',
                        timer: 3500,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        customClass: { popup: 'rounded-2xl' },
                    });
                }
            } catch (error) {
                console.error('Checkout error:', error);
                window.Swal.fire({
                    title: 'Ошибка',
                    text: 'Не удалось обработать заказ. Пожалуйста, попробуйте еще раз.',
                    icon: 'error',
                    confirmButtonText: 'ОК',
                    confirmButtonColor: '#f97316',
                    customClass: { popup: 'rounded-2xl' },
                });
            }
        },

        async syncPendingOrders() {
            const pendingOrders = await window.CartService.getPendingOrders();
            
            if (pendingOrders.length === 0) return;

            let synced = 0;
            let failed = 0;

            for (const order of pendingOrders) {
                if (order.retries >= 5) {
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
