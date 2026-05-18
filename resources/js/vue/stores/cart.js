import { defineStore } from 'pinia';
import apiClient from '../api/client';

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    vendorId: null,
    vendorSlug: null,
    vendorName: null,
    
    // Server synchronized values
    subtotal: 0,
    deliveryFee: 0,
    total: 0,
    minOrder: 0,
    isMinOrderMet: true,
    
    syncLoading: false,
    syncError: null,
    priceChanges: [],
    unavailableItems: []
  }),

  getters: {
    totalItemsCount: (state) => {
      return state.items.reduce((sum, item) => sum + item.quantity, 0);
    },
    isEmpty: (state) => {
      return state.items.length === 0;
    }
  },

  actions: {
    // Add item to cart. Supports same-restaurant verification!
    addItem(product, quantity, vendor) {
      const activeVendorId = vendor.id || vendor.vendor_id;
      
      // Mismatch protection
      if (this.items.length > 0 && this.vendorId !== activeVendorId) {
        return {
          status: 'mismatch',
          currentVendorName: this.vendorName
        };
      }

      // If cart is empty, register vendor info
      if (this.items.length === 0) {
        this.vendorId = activeVendorId;
        this.vendorSlug = vendor.slug;
        this.vendorName = vendor.name;
      }

      // Add or increment item quantity
      const existingItem = this.items.find(item => item.id === product.id);
      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        this.items.push({
          id: product.id,
          name: product.name,
          price: product.price,
          quantity: quantity,
          image: product.image_url || (product.image && product.image.startsWith('http') ? product.image : '/storage/' + product.image),
          weight: product.weight_g || null,
          modifiers: [] // Can support chosen modifier IDs
        });
      }

      this.saveToLocalStorage();
      this.syncCart();
      return { status: 'success' };
    },

    // Update item quantity
    updateQuantity(productId, quantity) {
      const item = this.items.find(i => i.id === productId);
      if (!item) return;

      if (quantity <= 0) {
        this.removeItem(productId);
      } else {
        item.quantity = quantity;
        this.saveToLocalStorage();
        this.syncCart();
      }
    },

    // Remove item
    removeItem(productId) {
      this.items = this.items.filter(item => item.id !== productId);
      
      // If cart becomes empty, clear vendor info
      if (this.items.length === 0) {
        this.vendorId = null;
        this.vendorSlug = null;
        this.vendorName = null;
        this.subtotal = 0;
        this.deliveryFee = 0;
        this.total = 0;
        this.minOrder = 0;
        this.isMinOrderMet = true;
      }
      
      this.saveToLocalStorage();
      this.syncCart();
    },

    // Clear cart entirely
    clearCart() {
      this.items = [];
      this.vendorId = null;
      this.vendorSlug = null;
      this.vendorName = null;
      this.subtotal = 0;
      this.deliveryFee = 0;
      this.total = 0;
      this.minOrder = 0;
      this.isMinOrderMet = true;
      
      this.saveToLocalStorage();
    },

    // Sync cart with backend API for actual prices & availability
    async syncCart() {
      if (this.items.length === 0) return;

      this.syncLoading = true;
      this.syncError = null;

      try {
        // Format payload to match backend expectations in CartController.php
        const payload = {
          vendor_id: this.vendorId,
          items: this.items.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: Math.round(item.price * 100), // convert unit price to cents
            modifiers: item.modifiers || []
          }))
        };

        const response = await apiClient.post('/cart/sync', payload);
        const data = response.data;

        if (data && data.status === 'success' && data.data) {
          const syncData = data.data;

          // Merge any price changes in local items state
          if (syncData.validated_items) {
            syncData.validated_items.forEach(validated => {
              const localItem = this.items.find(i => i.id === validated.product_id);
              if (localItem) {
                localItem.price = validated.price / 100; // convert cents back to standard TMT
              }
            });
          }

          // Handle unavailable items filtered out by backend
          if (syncData.unavailable_items && syncData.unavailable_items.length > 0) {
            this.unavailableItems = syncData.unavailable_items;
            
            // Remove unavailable items from cart
            syncData.unavailable_items.forEach(unavailable => {
              this.items = this.items.filter(i => i.id !== unavailable.product_id);
            });
            this.saveToLocalStorage();
          } else {
            this.unavailableItems = [];
          }

          // Handle price warning list
          this.priceChanges = syncData.price_changes || [];

          // Map summary metrics from backend
          this.subtotal = syncData.subtotal || 0;
          this.deliveryFee = syncData.delivery_fee || 0;
          this.total = syncData.total || 0;
          this.minOrder = syncData.min_order || 0;
          this.isMinOrderMet = syncData.is_min_order_met !== undefined ? syncData.is_min_order_met : true;
        }
      } catch (err) {
        console.error('Error syncing cart:', err);
        this.syncError = err.message || 'Ошибка синхронизации';
        
        // Fallback local calculations in case server sync is offline
        const localSubtotal = this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        this.subtotal = localSubtotal;
        this.total = localSubtotal + this.deliveryFee;
      } finally {
        this.syncLoading = false;
      }
    },

    // Save cart state locally
    saveToLocalStorage() {
      const cartData = {
        items: this.items,
        vendorId: this.vendorId,
        vendorSlug: this.vendorSlug,
        vendorName: this.vendorName
      };
      localStorage.setItem('resto_pwa_cart', JSON.stringify(cartData));
    },

    // Load cart state on initialization
    loadFromLocalStorage() {
      try {
        const stored = localStorage.getItem('resto_pwa_cart');
        if (stored) {
          const cartData = JSON.parse(stored);
          this.items = cartData.items || [];
          this.vendorId = cartData.vendorId || null;
          this.vendorSlug = cartData.vendorSlug || null;
          this.vendorName = cartData.vendorName || null;
          
          if (this.items.length > 0) {
            this.syncCart(); // Sync immediately once loaded to verify latest prices/avail
          }
        }
      } catch (err) {
        console.error('Failed loading cart from localstorage:', err);
      }
    }
  }
});
