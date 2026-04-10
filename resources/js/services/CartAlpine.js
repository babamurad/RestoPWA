document.addEventListener('alpine:init', () => {
    Alpine.data('cartManager', () => ({
        isInitialized: false,
        currentVendorId: null,

        init() {
            this.setupEventListeners();
            this.listenToLivewire();
        },

        setupEventListeners() {
            window.addEventListener('cart-add-item', (e) => {
                this.addItem(e.detail);
            });
        },

        listenToLivewire() {
            window.addEventListener('request-cart-state', () => {
                this.broadcastState();
            });

            window.addEventListener('cart-update-quantity', (e) => {
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
                if (this.currentVendorId && this.currentVendorId !== vendorId) {
                    console.log('CartAlpine: Clearing different vendor cart');
                    await window.CartService.clearVendorCart(this.currentVendorId);
                }
                
                this.currentVendorId = vendorId;
                await window.CartService.addItem(productId, vendorId, productName, image, modifiers, price, quantity);
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
            const vendorId = this.currentVendorId || '';
            console.log('CartAlpine: Broadcasting state for vendor', vendorId);
            let items = [];
            let totals = { totalItems: 0, totalPrice: 0, totalQuantity: 0 };

            if (vendorId) {
                items = await window.CartService.getCartByVendor(vendorId);
            }

            totals = await window.CartService.getTotals();
            console.log('CartAlpine: State totals', totals);

            window.dispatchEvent(new CustomEvent('cart-state', {
                detail: {
                    items,
                    ...totals,
                    vendorId
                }
            }));
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
                            alert(result.errors.join('\n'));
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
            if (!this.currentVendorId) return;

            let syncData = null;
            if (navigator.onLine) {
                syncData = await this.syncCartWithServer();
                
                if (syncData && !syncData.is_min_order_met) {
                    alert(`Минимальная сумма заказа: ${syncData.min_order} ₽`);
                    return;
                }
            }

            const items = await window.CartService.getCartByVendor(this.currentVendorId);
            if (items.length === 0) return;

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
                idempotency_key: crypto.randomUUID(),
                created_at: new Date().toISOString()
            };

            if (navigator.onLine) {
                window.dispatchEvent(new CustomEvent('submit-order', { detail: orderPayload }));
            } else {
                await window.CartService.queueOrder(orderPayload);
                window.dispatchEvent(new CustomEvent('cart-clear'));
                await window.CartService.clearVendorCart(this.currentVendorId);
                await this.broadcastState();
                alert('Заказ сохранён и будет отправлен когда появится интернет');
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
            this.updateBadge();
            window.addEventListener('cart-state', () => this.updateBadge());
        },

        async updateBadge() {
            const totals = await window.CartService.getTotals();
            this.badgeCount = totals.totalQuantity;
        },

        openCart() {
            window.dispatchEvent(new CustomEvent('open-cart'));
        }
    }));
});
