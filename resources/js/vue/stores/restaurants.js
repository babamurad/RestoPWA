import { defineStore } from 'pinia';
import { t } from '../i18n';
import restaurantsApi from '../api/restaurants';

export const useRestaurantsStore = defineStore('restaurants', {
  state: () => ({
    restaurants: [],
    currentRestaurant: null,
    categories: [],
    products: [],
    productsMeta: {
      current_page: 1,
      last_page: 1,
      per_page: 50,
      total: 0
    },
    priceFilters: {
      min: 0,
      max: 1000
    },
    // Ids of the signed-in customer's favourite restaurants
    favoriteIds: [],
    loading: false,
    error: null
  }),

  getters: {
    isFavorite: (state) => (id) => state.favoriteIds.includes(id),
  },

  actions: {
    // Fetch active restaurants for the catalog; params: { search, tag }
    async fetchRestaurants(params = {}) {
      this.loading = true;
      this.error = null;
      // Only the answer to the latest request counts (the user keeps typing)
      const requestId = (this.lastRequestId = (this.lastRequestId || 0) + 1);
      try {
        const query = Object.fromEntries(Object.entries(params).filter(([, value]) => value));
        const data = await restaurantsApi.getRestaurants(query);
        if (requestId === this.lastRequestId) {
          this.restaurants = Array.isArray(data) ? data : [];
        }
      } catch (err) {
        if (requestId === this.lastRequestId) {
          console.error('Error fetching restaurants:', err);
          this.error = t('home.errors.load_restaurants');
        }
      } finally {
        if (requestId === this.lastRequestId) {
          this.loading = false;
        }
      }
    },

    async fetchFavorites() {
      try {
        this.favoriteIds = await restaurantsApi.getFavorites();
      } catch (err) {
        this.favoriteIds = [];
      }
    },

    clearFavorites() {
      this.favoriteIds = [];
    },

    // Switches at once and rolls back if the server refuses
    async toggleFavorite(id) {
      const wasFavorite = this.favoriteIds.includes(id);
      this.favoriteIds = wasFavorite ? this.favoriteIds.filter((x) => x !== id) : [...this.favoriteIds, id];
      try {
        await (wasFavorite ? restaurantsApi.removeFavorite(id) : restaurantsApi.addFavorite(id));
        return true;
      } catch (err) {
        this.favoriteIds = wasFavorite ? [...this.favoriteIds, id] : this.favoriteIds.filter((x) => x !== id);
        return false;
      }
    },

    // Fetch restaurant details and its menu categories/products
    async fetchRestaurantMenu(slug, categoryId = null) {
      this.loading = true;
      this.error = null;
      try {
        // 1. Fetch restaurant general details
        const detailsRes = await fetch(`/api/v1/restaurants/${slug}`);
        if (!detailsRes.ok) {
          throw new Error(t('menu.errors.not_found'));
        }
        this.currentRestaurant = await detailsRes.json();

        // 2. Fetch menu (categories, products list, and price filters)
        let menuUrl = `/api/v1/menu/${slug}`;
        if (categoryId) {
          menuUrl += `?category_id=${categoryId}`;
        }

        const menuRes = await fetch(menuUrl);
        if (!menuRes.ok) {
          throw new Error(t('menu.errors.load_menu'));
        }

        const menuData = await menuRes.json();
        this.categories = menuData.categories || [];
        
        // Handle paginated products returned from MenuController
        if (menuData.products) {
          this.products = menuData.products.data || [];
          this.productsMeta = menuData.products.meta || {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0
          };
        } else {
          this.products = [];
        }

        this.priceFilters = menuData.filters || { min: 0, max: 1000 };
      } catch (err) {
        console.error('Error fetching menu details:', err);
        this.error = err.message || t('menu.errors.load_menu_generic');
        this.currentRestaurant = null;
        this.categories = [];
        this.products = [];
      } finally {
        this.loading = false;
      }
    }
  }
});
