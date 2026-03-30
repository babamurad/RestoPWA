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
        },

        async addItem({ productId, vendorId, modifiers = {}, price, productName }) {
            try {
                await window.CartService.addItem(productId, vendorId, modifiers, price);
                await this.broadcastState();
            } catch (error) {
                console.error('Failed to add item:', error);
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
            let items = [];
            let totals = { totalItems: 0, totalPrice: 0, totalQuantity: 0 };

            if (vendorId) {
                items = await window.CartService.getCartByVendor(vendorId);
            }

            totals = await window.CartService.getTotals();

            window.dispatchEvent(new CustomEvent('cart-state', {
                detail: {
                    items,
                    ...totals,
                    vendorId
                }
            }));
        },

        async checkout() {
            if (!this.currentVendorId) return;

            const items = await window.CartService.getCartByVendor(this.currentVendorId);
            
            if (items.length === 0) return;

            const orderPayload = {
                vendorId: this.currentVendorId,
                items: items.map(item => ({
                    productId: item.productId,
                    quantity: item.quantity,
                    price: item.price,
                    modifiersHash: item.modifiersHash
                })),
                createdAt: new Date().toISOString()
            };

            if (navigator.onLine) {
                window.dispatchEvent(new CustomEvent('submit-order', { detail: orderPayload }));
            } else {
                await window.CartService.queueOrder(orderPayload);
                alert('Заказ сохранён и будет отправлен когда появится интернет');
                await window.CartService.clearVendorCart(this.currentVendorId);
                await this.broadcastState();
            }
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
