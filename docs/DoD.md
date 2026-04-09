# Definition of Done (DoD)

To ensure consistent quality, each module and feature in RestoPWA must meet the following criteria before being considered "Done".

## 1. Code Quality
- [ ] Strictly typed (PHP 8.3+) and linted via Laravel Pint.
- [ ] No raw SQL; only Eloquent models and scopes.
- [ ] Blade components used for UI reuse; Tailwind for styling.
- [x] Controller logic minimal; complex calculations in Domain Services. *(Order domain uses services)*

## 2. PWA & Frontend
- [ ] Fully responsive on mobile (iOS/Android) and desktop.
- [x] Service Worker exists at `public/sw.js` with offline fallback.
- [x] Dexie CartService implemented with sync methods (`resources/js/services/CartService.js`).
- [x] Offline indicator/fallback page exists (`resources/views/offline.blade.php`).

## 3. Security & Multitenancy
- [x] `BelongsToVendor` trait applied to vendor-specific models.
- [x] `SetTenantContext` middleware on API routes; `ensure.tenant` on web routes.
- [x] All data input validated via `Request::validate()`.
- [x] Auth guard enforced on /profile, /orders, /checkout, /vendor/*, /order/{id}/track.

## 4. Verification & Testing
- [x] **PHP Code Quality**: Laravel Pint passes with no violations (`./vendor/bin/pint --test`).
- [x] **Backend Tests**: PHPUnit feature tests cover core happy-path and error-path scenarios (`php artisan test`).
- [x] **Frontend Tests**: Vitest unit tests for CartService (`tests/CartService.test.js`).
- [x] **Manual Verification**: Full end-to-end flow tested (Catalogue → Cart → Checkout → Order Success).
- [x] **Changelog**: Updated with details of the enhancement/fix (`docs/CHANGELOG.md`).
