import { createI18n } from 'vue-i18n';
import apiClient from '../api/client';

export const SUPPORTED_LOCALES = ['ru', 'tk'];
export const DEFAULT_LOCALE = 'ru';
export const LOCALE_NAMES = { ru: 'Русский', tk: 'Türkmençe' };

const STORAGE_KEY = 'locale';

// Every file in locales/<lang>/<namespace>.js becomes messages[lang][namespace]
function loadMessages() {
    const files = import.meta.glob('./locales/*/*.js', { eager: true });
    const messages = {};
    for (const [path, module] of Object.entries(files)) {
        const [, lang, namespace] = path.match(/\.\/locales\/([^/]+)\/([^/]+)\.js$/);
        messages[lang] ??= {};
        messages[lang][namespace] = module.default;
    }
    return messages;
}

// Russian: 1 блюдо / 2 блюда / 5 блюд — messages hold "one | few | many"
function russianPlural(choice, choicesLength) {
    if (choicesLength < 3) {
        return choice === 1 ? 0 : 1;
    }
    const mod10 = choice % 10;
    const mod100 = choice % 100;
    if (mod10 === 1 && mod100 !== 11) return 0;
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 1;
    return 2;
}

// Turkmen nouns do not change after a numeral: "1 nahar", "5 nahar"
function turkmenPlural() {
    return 0;
}

export function normalizeLocale(locale) {
    return SUPPORTED_LOCALES.includes(locale) ? locale : null;
}

function storedLocale() {
    try {
        return normalizeLocale(localStorage.getItem(STORAGE_KEY));
    } catch {
        return null;
    }
}

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: storedLocale() ?? DEFAULT_LOCALE,
    fallbackLocale: DEFAULT_LOCALE,
    messages: loadMessages(),
    pluralRules: { ru: russianPlural, tk: turkmenPlural },
    missingWarn: false,
    fallbackWarn: false,
});

/** Translate outside components (stores, services, alerts). */
export const t = (...args) => i18n.global.t(...args);

export function currentLocale() {
    return i18n.global.locale.value;
}

function applyLocale(locale) {
    i18n.global.locale.value = locale;
    apiClient.defaults.headers.common['X-Locale'] = locale;
    if (typeof document !== 'undefined') {
        document.documentElement.lang = locale;
        // Server-rendered pages (order tracking, legal) read the same choice
        document.cookie = `locale=${locale}; path=/; max-age=31536000; SameSite=Lax`;
    }
}

/** Switch the UI language and remember it on this device. */
export function setLocale(locale) {
    const next = normalizeLocale(locale) ?? DEFAULT_LOCALE;
    applyLocale(next);
    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch {
        // private mode: the choice lasts until reload
    }
    return next;
}

applyLocale(i18n.global.locale.value);
