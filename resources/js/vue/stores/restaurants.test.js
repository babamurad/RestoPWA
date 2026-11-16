import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

vi.mock('../api/client', () => ({
    // i18n (imported by the store) sets the X-Locale header on the client
    default: { get: vi.fn(), defaults: { headers: { common: {} } } },
}));

vi.mock('../api/restaurants', () => ({
    default: {
        getRestaurants: vi.fn(),
        getFavorites: vi.fn(),
        addFavorite: vi.fn(),
        removeFavorite: vi.fn(),
    },
}));

import restaurantsApi from '../api/restaurants';
import { useRestaurantsStore } from './restaurants';

describe('restaurants store: search and favourites', () => {
    let store;

    beforeEach(() => {
        vi.clearAllMocks();
        vi.spyOn(console, 'error').mockImplementation(() => {});
        setActivePinia(createPinia());
        store = useRestaurantsStore();
    });

    it('sends only the filled filters', async () => {
        restaurantsApi.getRestaurants.mockResolvedValue([{ id: 'r1' }]);

        await store.fetchRestaurants({ search: 'плов', tag: '' });

        expect(restaurantsApi.getRestaurants).toHaveBeenCalledWith({ search: 'плов' });
        expect(store.restaurants).toEqual([{ id: 'r1' }]);
        expect(store.loading).toBe(false);
    });

    it('keeps the answer to the latest search when an older one arrives late', async () => {
        let finishFirst;
        restaurantsApi.getRestaurants
            .mockReturnValueOnce(new Promise((resolve) => { finishFirst = resolve; }))
            .mockResolvedValueOnce([{ id: 'second' }]);

        const first = store.fetchRestaurants({ search: 'п' });
        await store.fetchRestaurants({ search: 'плов' });
        finishFirst([{ id: 'first' }]);
        await first;

        expect(store.restaurants).toEqual([{ id: 'second' }]);
    });

    it('shows an error and keeps the list when loading fails', async () => {
        store.restaurants = [{ id: 'r1' }];
        restaurantsApi.getRestaurants.mockRejectedValue(new Error('Network Error'));

        await store.fetchRestaurants();

        expect(store.error).toBe('Не удалось загрузить список ресторанов');
        expect(store.restaurants).toEqual([{ id: 'r1' }]);
    });

    it('adds and removes a favourite at once', async () => {
        restaurantsApi.addFavorite.mockResolvedValue({});
        restaurantsApi.removeFavorite.mockResolvedValue({});

        expect(await store.toggleFavorite('r1')).toBe(true);
        expect(store.isFavorite('r1')).toBe(true);
        expect(restaurantsApi.addFavorite).toHaveBeenCalledWith('r1');

        expect(await store.toggleFavorite('r1')).toBe(true);
        expect(store.isFavorite('r1')).toBe(false);
        expect(restaurantsApi.removeFavorite).toHaveBeenCalledWith('r1');
    });

    it('rolls the heart back when the server refuses', async () => {
        restaurantsApi.addFavorite.mockRejectedValue(new Error('500'));

        expect(await store.toggleFavorite('r1')).toBe(false);
        expect(store.isFavorite('r1')).toBe(false);

        store.favoriteIds = ['r1'];
        restaurantsApi.removeFavorite.mockRejectedValue(new Error('500'));

        expect(await store.toggleFavorite('r1')).toBe(false);
        expect(store.isFavorite('r1')).toBe(true);
    });
});
