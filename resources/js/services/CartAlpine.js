document.addEventListener('alpine:init', () => {
    Alpine.data('cartManager', () => ({
        isInitialized: false,
        currentVendorId: null,
        traceId: null,

        generateTraceId() {
            if (!this.traceId) {
                this.traceId = crypto.randomUUID ? crypto.randomUUID() : 'trace-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            }
            return this.traceId;
        },

        async init() {
            this.generateTraceId();
            this.setupEventListeners();
            this.listenToLivewire();

            try {
                const allItems = await window.CartService.getAllItems();
                if (allItems.length > 0) {
                    const vendorIds = [...new Set(allItems.map(item => String(item.vendorId)))];
                    if (vendorIds.length === 1) {
                        this.currentVendorId = vendorIds[0];
                        console.log('[CartAlpine] Recovered single vendor ID', this.currentVendorId, 'trace:', this.traceId);
                    } else {
                        console.warn('[CartAlpine] Multiple vendors detected in cart during init', vendorIds, 'trace:', this.traceId);
                        this.currentVendorId = null;
                    }
                    await this.broadcastState();
                }
            } catch (error) {
                console.error('[CartAlpine] Failed to recover vendor state:', error, 'trace:', this.traceId);
            }
        },

        setupEventListeners() {
            window.addEventListener('cart-add-item', (e) => {
                console.log('[CartAlpine] cart-add-item received', e.detail, 'trace:', this.traceId);
                this.addItem(e.detail);
            });
        },

        listenToLivewire() {
            window.addEventListener('request-cart-state', () => {
                console.log('[CartAlpine] request-cart-state received', 'trace:', this.traceId);
                this.broadcastState();
            });

            window.addEventListener('cart-update-quantity', (e) => {
                console.log('[CartAlpine] cart-update-quantity received', e.detail, 'trace:', this.traceId);
                this.updateQuantity(e.detail.itemId, e.detail.quantity);
            });

            window.addEventListener('cart-remove-item', (e) => {
                console.log('[CartAlpine] cart-remove-item received', e.detail, 'trace:', this.traceId);
                this.removeItem(e.detail.itemId);
            });

            window.addEventListener('cart-clear', () => {
                console.log('[CartAlpine] cart-clear received', 'trace:', this.traceId);
                this.clearCart();
            });

            window.addEventListener('cart-checkout', () => {
                console.log('[CartAlpine] cart-checkout received', 'trace:', this.traceId);
                this.checkout();
            });

            window.addEventListener('sync-pending-orders', () => {
                console.log('[CartAlpine] sync-pending-orders received', 'trace:', this.traceId);
                this.syncPendingOrders();
            });

            window.addEventListener('set-vendor', (e) => {
                console.log('[CartAlpine] set-vendor received', e.detail, 'trace:', this.traceId);
                this.currentVendorId = e.detail.vendorId;
                this.broadcastState();
            });

            window.addEventListener('online', () => {
                console.log('[CartAlpine] online event, triggering sync', 'trace:', this.traceId);
                this.syncPendingOrders();
            });

            window.addEventListener('order-synced-from-sw', (e) => {
                console.log('[CartAlpine] Order synced from SW', e.detail, 'trace:', this.traceId);
                this.broadcastState();
            });

            window.addEventListener('auth-required-from-sw', (e) => {
                console.log('[CartAlpine] auth-required-from-sw', e.detail, 'trace:', this.traceId);
                this.handleAuthError('sync');
            });
        },

        async addItem({ productId, vendorId, productName, image, modifiers = {}, price, quantity = 1 }) {
            console.log('[CartAlpine] Adding item', { productId, vendorId, productName }, 'trace:', this.traceId);
            try {
                const normalizedVendorId = String(vendorId).toLowerCase().trim();
                const currentNormalizedId = this.currentVendorId ? String(this.currentVendorId).toLowerCase().trim() : null;

                if (currentNormalizedId && currentNormalizedId !== normalizedVendorId) {
                    console.log('[CartAlpine] Clearing different vendor cart', { current: currentNormalizedId, new: normalizedVendorId }, 'trace:', this.traceId);
                    await window.CartService.clearVendorCart(this.currentVendorId);
                }
                
                this.currentVendorId = String(vendorId);
                await window.CartService.addItem(productId, String(vendorId), productName, image, modifiers, price, quantity);
                console.log('[CartAlpine] Item added successfully', 'trace:', this.traceId);
                await this.broadcastState();
            } catch (error) {
                console.error('[CartAlpine] Failed to add item:', error, 'trace:', this.traceId);
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
            try {
                // If we have currentVendorId, clear only that, otherwise clear everything
                if (this.currentVendorId) {
                    await window.CartService.clearVendorCart(this.currentVendorId);
                } else {
                    const allItems = await window.CartService.getAllItems();
                    const vendorIds = [...new Set(allItems.map(item => String(item.vendorId)))];
                    for (const vid of vendorIds) {
                        await window.CartService.clearVendorCart(vid);
                    }
                }
                this.currentVendorId = null;
                await this.broadcastState();
            } catch (error) {
                console.error('Failed to clear cart:', error);
            }
        },

        async broadcastState() {
            let vendorId = this.currentVendorId;
            let hasMultiVendorConflict = false;
            
            const allItems = await window.CartService.getAllItems();
            const vendorIds = [...new Set(allItems.map(item => String(item.vendorId)))];

            if (vendorIds.length > 1) {
                hasMultiVendorConflict = true;
                vendorId = null;
            } else if (vendorIds.length === 1) {
                vendorId = vendorIds[0];
                this.currentVendorId = vendorId;
            }

            console.log('[CartAlpine] Broadcasting state', { vendorId, hasMultiVendorConflict }, 'trace:', this.traceId);

            let items = [];
            let totals = { totalItems: 0, totalPrice: 0, totalQuantity: 0 };

            try {
                if (hasMultiVendorConflict) {
                    items = allItems;
                } else if (vendorId) {
                    items = await window.CartService.getCartByVendor(String(vendorId));
                } else {
                    items = allItems;
                }

                totals = await window.CartService.getTotals(vendorId || undefined);

                window.dispatchEvent(new CustomEvent('cart-state', {
                    detail: {
                        items,
                        ...totals,
                        vendorId,
                        hasMultiVendorConflict,
                        traceId: this.traceId,
                    }
                }));
            } catch (error) {
                console.error('[CartAlpine] Error during broadcastState', error, 'trace:', this.traceId);
            }
        },

        async syncCartWithServer() {
            if (!this.currentVendorId || !navigator.onLine) return null;

            const items = await window.CartService.getCartByVendor(this.currentVendorId);
            if (items.length === 0) return null;

            const traceId = this.generateTraceId();

            try {
                const response = await fetch('/api/v1/cart/sync', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Vendor-ID': this.currentVendorId,
                        'X-Trace-Id': traceId,
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
                            console.warn('[CartAlpine] Sync returned errors', result.errors, 'trace:', traceId);
                            window.Swal.fire({
                                title: 'Внимание',
                                html: result.errors.join('<br>'),
                                icon: 'warning',
                                confirmButtonText: 'Понятно',
                                confirmButtonColor: '#f97316',
                                customClass: { popup: 'rounded-2xl' },
                            });
                        }
                        
                        await this.broadcastState();
                        return result.data;
                    }
                }
            } catch (error) {
                console.error('[CartAlpine] Cart sync failed:', error, 'trace:', traceId);
            }
            return null;
        },

        async checkout() {
            const traceId = this.generateTraceId();
            const allItems = await window.CartService.getAllItems();
            const vendorIds = [...new Set(allItems.map(item => String(item.vendorId)))];

            console.log('[CartAlpine] checkout initiated', { vendors: vendorIds }, 'trace:', traceId);
            
            if (vendorIds.length > 1) {
                console.error('[CartAlpine] checkout failed - multiple vendors', 'trace:', traceId);
                window.dispatchEvent(new CustomEvent('submit-order-failed', { 
                    detail: { reason: 'multi_vendor', traceId } 
                }));
                return;
            }

            if (vendorIds.length === 0) {
                console.error('[CartAlpine] checkout failed - cart empty', 'trace:', traceId);
                window.dispatchEvent(new CustomEvent('submit-order-failed', { 
                    detail: { reason: 'empty_cart', traceId } 
                }));
                return;
            }

            this.currentVendorId = vendorIds[0];

            console.log('[CartAlpine] redirecting to checkout', this.currentVendorId, 'trace:', traceId);
            window.location.href = `/checkout?vendor_id=${this.currentVendorId}&trace_id=${traceId}`;
        },


        async syncPendingOrders() {
            const traceId = this.generateTraceId();
            const pendingOrders = await window.CartService.getPendingOrders();
            
            if (pendingOrders.length === 0) return;

            let synced = 0;
            let failed = 0;
            let needsUserAction = 0;

            for (const order of pendingOrders) {
                if (order.retries >= 5) {
                    failed++;
                    continue;
                }

                if (order.status === 'needs_user_action') {
                    needsUserAction++;
                    console.log('[CartAlpine] Skipping order with needs_user_action status', order.id, 'trace:', traceId);
                    continue;
                }

                // P1-6: Validate contacts before sync
                const contactValidation = this.validateOrderContacts(order.payload);
                if (!contactValidation.valid) {
                    console.warn('[CartAlpine] Order has invalid contacts, marking needs_user_action', {
                        orderId: order.id,
                        reason: contactValidation.reason,
                        traceId,
                    });

                    await window.CartService.updatePendingOrderStatus(order.id, 'needs_user_action');
                    needsUserAction++;
                    failed++;
                    continue;
                }

                const orderTraceId = order.payload.trace_id || traceId;

                try {
                    const response = await fetch('/api/v1/orders', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Vendor-ID': order.payload.vendor_id,
                            'X-Idempotency-Key': order.payload.idempotency_key,
                            'X-Trace-Id': orderTraceId,
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
                            console.log('[CartAlpine] Duplicate order acknowledged', result.data.order_id, 'trace:', orderTraceId);
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
                    console.error('[CartAlpine] Failed to sync order:', error, 'trace:', orderTraceId);
                    await window.CartService.incrementRetry(order.id);
                    failed++;
                }
            }

            if (synced > 0) {
                window.dispatchEvent(new CustomEvent('orders-synced', {
                    detail: { synced, failed, traceId }
                }));
            }

            if (needsUserAction > 0) {
                window.dispatchEvent(new CustomEvent('orders-needs-action', {
                    detail: { count: needsUserAction }
                }));
            }

            await this.broadcastState();
        },

        validateOrderContacts(payload) {
            if (!payload.customer_name || payload.customer_name.trim() === '') {
                return { valid: false, reason: 'missing_name' };
            }

            if (!payload.customer_phone || payload.customer_phone.trim() === '') {
                return { valid: false, reason: 'missing_phone' };
            }

            const phone = payload.customer_phone.trim();
            if (!phone.startsWith('+')) {
                return { valid: false, reason: 'invalid_phone_format' };
            }

            const digits = phone.replace(/\D/g, '');
            if (digits.length < 8 || digits.length > 15) {
                return { valid: false, reason: 'invalid_phone_length' };
            }

            return { valid: true };
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
