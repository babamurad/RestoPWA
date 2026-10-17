import { describe, it, expect, beforeEach } from 'vitest';
import apiClient from '../api/client';
import { i18n, setLocale, t, currentLocale } from './index';

function flatKeys(obj, prefix = '') {
    return Object.entries(obj).flatMap(([key, value]) =>
        value && typeof value === 'object' ? flatKeys(value, `${prefix}${key}.`) : [`${prefix}${key}`]);
}

describe('i18n', () => {
    beforeEach(() => setLocale('ru'));

    it('defaults to Russian and switches to Turkmen', () => {
        expect(t('common.nav.cart')).toBe('Корзина');

        setLocale('tk');

        expect(currentLocale()).toBe('tk');
        expect(t('common.nav.cart')).toBe('Sebet');
        expect(localStorage.getItem('locale')).toBe('tk');
        expect(document.documentElement.lang).toBe('tk');
        expect(apiClient.defaults.headers.common['X-Locale']).toBe('tk');
        expect(document.cookie).toContain('locale=tk');
    });

    it('ignores unsupported languages', () => {
        expect(setLocale('en')).toBe('ru');
        expect(currentLocale()).toBe('ru');
    });

    it('uses Russian plural forms', () => {
        expect(t('common.dishes', 1)).toBe('1 блюдо');
        expect(t('common.dishes', 3)).toBe('3 блюда');
        expect(t('common.dishes', 5)).toBe('5 блюд');
        expect(t('common.dishes', 11)).toBe('11 блюд');
        expect(t('common.dishes', 22)).toBe('22 блюда');

        setLocale('tk');
        expect(t('common.dishes', 5)).toBe('5 nahar');
    });

    it('has every Russian key translated to Turkmen', () => {
        const { ru, tk } = i18n.global.messages.value;
        const missing = flatKeys(ru).filter((key) => !flatKeys(tk).includes(key));

        expect(missing).toEqual([]);
        expect(Object.keys(tk).sort()).toEqual(Object.keys(ru).sort());
    });
});
