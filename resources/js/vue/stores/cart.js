import { defineStore } from 'pinia';
import apiClient from '../api/client';
import { t } from '../i18n';

// One cart line is a dish with one particular choice of modifiers: the same pizza with
// different toppings is two lines, so a line is told apart by this key.
// modifiers: [{ option_id, quantity, ... }]
export function cartLineKey(productId, modifiers = []) {
  const choice = [...modifiers]
    .map((m) => `${m.option_id}x${m.quantity || 1}`)
    .sort()
    .join(',');
  return `${productId}|${choice}`;
}

// Price of the chosen modifiers for one portion of the dish
export function modifiersPrice(modifiers = []) {
  return modifiers.reduce((sum, m) => sum + (Number(m.price) || 0) * (m.quantity || 1), 0);
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    vendorId: null,
    vendorSlug: null,
    vendorName: null,
    
    // Server synchronized values
    subtotal: 0,
    deliveryFee: 0,
    // District pricing: cart shows the cheapest district ("от") until checkout
    // knows the address; then districtFee holds the exact price for it.
    deliveryFeeIsEstimate: false,
    districtFee: null,
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
    },
    // Instant local subtotal (used before server sync completes)
    localSubtotal: (state) => {
      return state.items.reduce((sum, item) => sum + (item.price || 0) * item.quantity, 0);
    },
    localTotal: (state) => {
      const sub = state.items.reduce((sum, item) => sum + (item.price || 0) * item.quantity, 0);
      return sub + state.deliveryFee;
    }
  },

  actions: {
    // Add item to cart. Supports same-restaurant verification!
    // modifiers: the customer's choice, [{ group_id, option_id, group, name, price, quantity }]
    addItem(product, quantity, vendor, modifiers = []) {
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

      // Add or increment the line with the same dish and the same choice
      const key = cartLineKey(product.id, modifiers);
      const existingItem = this.items.find(item => item.key === key);
      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        this.items.push({
          key,
          id: product.id,
          name: product.name,
          // One portion with the modifiers; the server recalculates it on sync
          price: Number(product.price) + modifiersPrice(modifiers),
          quantity: quantity,
          image: product.image_url || (product.image && product.image.startsWith('http') ? product.image : '/storage/' + product.image),
          weight: product.weight_g || null,
          modifiers
        });
      }

      this.saveToLocalStorage();
      this.syncCart();
      return { status: 'success' };
    },

    // Put a dish of an earlier order back into the cart ("repeat order").
    // item is an order line as the API returns it: unit_price in minor units, modifiers with option ids.
    addOrderItem(item, vendor) {
      const modifiers = (item.modifiers || [])
        .filter((m) => m && m.id)
        .map((m) => ({
          group_id: m.group_id,
          option_id: m.id,
          group: m.group,
          name: m.name,
          price: Number(m.price) || 0,
          quantity: m.quantity || 1,
        }));

      const basePrice = Math.max(0, (Number(item.unit_price) || 0) / 100 - modifiersPrice(modifiers));

      return this.addItem({
        id: item.product_id || item.id,
        name: item.name || item.product_name,
        price: basePrice,
        image_url: item.image || item.image_url || null,
        weight_g: item.weight_g || null,
      }, item.quantity, vendor, modifiers);
    },

    // Update the quantity of a cart line
    updateQuantity(key, quantity) {
      const item = this.items.find(i => i.key === key);
      if (!item) return;

      if (quantity <= 0) {
        this.removeItem(key);
      } else {
        item.quantity = quantity;
        this.saveToLocalStorage();
        this.syncCart();
      }
    },

    // Remove a cart line
    removeItem(key) {
      this.items = this.items.filter(item => item.key !== key);

      // If cart becomes empty, clear vendor info
      if (this.items.length === 0) {
        this.vendorId = null;
        this.vendorSlug = null;
        this.vendorName = null;
        this.subtotal = 0;
        this.deliveryFee = 0;
        this.deliveryFeeIsEstimate = false;
        this.districtFee = null;
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
      this.deliveryFeeIsEstimate = false;
      this.districtFee = null;
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
            key: item.key,
            product_id: item.id,
            quantity: item.quantity,
            price: Math.round(item.price * 100), // convert unit price to cents
            modifiers: (item.modifiers || []).map(m => ({ option_id: m.option_id, quantity: m.quantity || 1 }))
          }))
        };

        const response = await apiClient.post('/cart/sync', payload);
        const data = response.data;

        if (data && data.success && data.data) {
          const syncData = data.data;

          // Merge any price changes in local items state
          if (syncData.validated_items) {
            syncData.validated_items.forEach(validated => {
              const localItem = this.items.find(i => (validated.key ? i.key === validated.key : i.id === validated.product_id));
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
              this.items = this.items.filter(i => (unavailable.key ? i.key !== unavailable.key : i.id !== unavailable.product_id));
            });
            this.saveToLocalStorage();
          } else {
            this.unavailableItems = [];
          }

          // Handle price warning list
          this.priceChanges = syncData.price_changes || [];

          // Map summary metrics from backend
          this.subtotal = syncData.subtotal || 0;
          if (this.districtFee !== null) {
            // Address already chosen in checkout: keep the exact district price
            this.deliveryFee = this.districtFee;
            this.deliveryFeeIsEstimate = false;
            this.total = (syncData.subtotal || 0) + this.districtFee;
          } else {
            this.deliveryFee = syncData.delivery_fee || 0;
            this.deliveryFeeIsEstimate = !!syncData.delivery_fee_is_estimate;
            this.total = syncData.total || 0;
          }
          this.minOrder = syncData.min_order || 0;
          this.isMinOrderMet = syncData.is_min_order_met !== undefined ? syncData.is_min_order_met : true;
        }
      } catch (err) {
        console.error('Error syncing cart:', err);
        this.syncError = err.message || t('cart.sync_error');
        
        // Fallback local calculations in case server sync is offline
        const localSubtotal = this.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        this.subtotal = localSubtotal;
        this.total = localSubtotal + this.deliveryFee;
      } finally {
        this.syncLoading = false;
      }
    },

    // Exact delivery price for the chosen address (district pricing)
    setDistrictFee(fee) {
      this.districtFee = fee;
      if (fee !== null) {
        this.deliveryFee = fee;
        this.deliveryFeeIsEstimate = false;
        this.total = (this.subtotal || 0) + fee;
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
          // Lines saved before the modifiers existed have no key
          this.items = (cartData.items || []).map(item => ({
            ...item,
            modifiers: item.modifiers || [],
            key: item.key || cartLineKey(item.id, item.modifiers || [])
          }));
          this.vendorId = cartData.vendorId || null;
          this.vendorSlug = cartData.vendorSlug || null;
          this.vendorName = cartData.vendorName || null;

          if (this.items.length > 0) {
            // Pre-compute totals immediately from local prices so the UI
            // never shows 0 while the async server sync is in flight.
            const localSub = this.items.reduce(
              (sum, item) => sum + (item.price || 0) * item.quantity, 0
            );
            this.subtotal = localSub;
            this.total = localSub + this.deliveryFee;

            // Then confirm with server (will override with authoritative values)
            this.syncCart();
          }
        }
      } catch (err) {
        console.error('Failed loading cart from localstorage:', err);
      }
    }
  }
});
