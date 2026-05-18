import { defineStore } from 'pinia';

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
    loading: false,
    error: null
  }),

  actions: {
    // Fetch all active restaurants for the catalog list
    async fetchRestaurants() {
      this.loading = true;
      this.error = null;
      try {
        const response = await fetch('/api/v1/restaurants');
        if (!response.ok) {
          throw new Error('Не удалось загрузить список ресторанов');
        }
        const data = await response.json();
        this.restaurants = data;
      } catch (err) {
        console.error('Error fetching restaurants:', err);
        this.error = err.message || 'Ошибка загрузки данных';
      } finally {
        this.loading = false;
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
          throw new Error('Ресторан не найден');
        }
        this.currentRestaurant = await detailsRes.json();

        // 2. Fetch menu (categories, products list, and price filters)
        let menuUrl = `/api/v1/menu/${slug}`;
        if (categoryId) {
          menuUrl += `?category_id=${categoryId}`;
        }

        const menuRes = await fetch(menuUrl);
        if (!menuRes.ok) {
          throw new Error('Не удалось загрузить меню ресторана');
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
        this.error = err.message || 'Ошибка загрузки меню';
        this.currentRestaurant = null;
        this.categories = [];
        this.products = [];
      } finally {
        this.loading = false;
      }
    }
  }
});
