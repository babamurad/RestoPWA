# Definition of Done (DoD)

To ensure consistent quality, each module and feature in RestoPWA must meet the following criteria before being considered "Done".

## 1. Code Quality
- [ ] Strictly typed (PHP 8.3+) and linted via Laravel Pint.
- [ ] No raw SQL; only Eloquent models and scopes.
- [ ] Blade components used for UI reuse; Tailwind for styling.
- [ ] Controller logic minimal; complex calculations in Domain Services.

## 2. PWA & Frontend
- [ ] Fully responsive on mobile (iOS/Android) and desktop.
- [ ] Service Worker updated if cache-breaking changes were made.
- [ ] Dexie state correctly synchronized with the backend.
- [ ] Offline indicator/fallback implemented if network-dependent.

## 3. Security & Multitenancy
- [ ] `BelongsToVendor` trait applied to vendor-specific models.
- [ ] `SetTenantContext` middleware used on all vendor/API routes.
- [ ] All data input validated via `Request::validate()`.
- [ ] Auth guard enforced on user-specific pages (/profile, /orders, /checkout).

## 4. Verification & Testing
- [ ] **PHP Code Quality**: Laravel Pint passes with no violations (`./vendor/bin/pint --test`).
- [ ] **Backend Tests**: PHPUnit feature tests cover core happy-path and error-path scenarios (`php artisan test`).
- [ ] **Frontend Tests**: Vitest unit tests cover service layer (CartService, offline sync) (`npm run test`).
- [ ] **Manual Verification**: Full end-to-end flow tested (Catalogue → Cart → Checkout → Order Success).
- [ ] **Changelog**: Updated with details of the enhancement/fix (`docs/CHANGELOG.md`).
