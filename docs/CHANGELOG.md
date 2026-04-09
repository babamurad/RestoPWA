# Changelog

All notable changes to the RestoPWA project will be documented in this file.

## [1.2.0] - 2026-04-07

### Added
- **Quality Gate**: Инициализация quality gate (полный прогон php artisan test, pint, vitest).
- **Access Control Tests**: `AccessControlTest.php` с 5 тест-кейсами на защищённые маршруты.
- **Order Idempotency**: `idempotency_key` для order submit (backend) + генерация ключа на клиенте.
- **API Responses**: Единый формат API ошибок через трейт `ApiResponses`.
- **Checkout Conflict Resolution**: UX с подтверждением изменений корзины (изменение цен/наличия) перед оплатой.

## [1.1.0] - 2026-04-06

### Added
- **Cart Sync API**: `POST /api/v1/cart/sync` for server-side price and availability validation.
- **Tenant Isolation**: Strict enforcement of `X-Vendor-ID` header and subdomain for all API routes.
- **Improved Order Security**: Restricted `OrderController` to only allow orders from currently authenticated users.
- **Order Success Page**: New controller-based success page with security checks and vendor data.
- **PWA Meta**: Optimized `manifest.json` and meta tags for better standalone performance.
- **Quality Gate Infrastructure**: Reproducible test execution with `composer install`, `php artisan test`, `./vendor/bin/pint --test`, and `npm run build`.
- **Tenant Audit Matrix**: Formal documentation of API tenant isolation in `docs/tenant-audit.md`.
- **Lighthouse Baseline**: PWA compliance baseline report in `docs/lighthouse-baseline.md`.
- **Vitest Setup**: Frontend testing infrastructure for `CartService` with unit and integration tests.

### Fixed
- **Order Submission**: Corrected API endpoint prefix from `/api/` to `/api/v1/` in `app.js` and `sw.js`.
- **User Model**: Added `phone` to fillable attributes to support the registration form.
- **Routing**: Refactored `web.php` from closures to `RestaurantController` for better performance and maintainability.

### Optimized
- **Service Worker**: Implemented cache versioning (`v2`) and improved background synchronization logic.
- **Offline Cart Sync**: Enhanced `CartService` queue processing, retry logic, and total calculation.

## [1.0.0] - 2026-03-30

### Initial Release
- Core DDD architecture.
- Livewire PWA layout.
- Basic menu and order functionality.
