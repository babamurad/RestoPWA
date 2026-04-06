# Changelog

All notable changes to the RestoPWA project will be documented in this file.

## [1.1.0] - 2026-04-06

### Added
- **Cart Sync API**: `POST /api/v1/cart/sync` for server-side price and availability validation.
- **Tenant Isolation**: Strict enforcement of `X-Vendor-ID` header and subdomain for all API routes.
- **Improved Order Security**: Restricted `OrderController` to only allow orders from currently authenticated users.
- **Order Success Page**: New controller-based success page with security checks and vendor data.
- **PWA Meta**: Optimized `manifest.json` and meta tags for better standalone performance.

### Fixed
- **Order Submission**: Corrected API endpoint prefix from `/api/` to `/api/v1/` in `app.js` and `sw.js`.
- **User Model**: Added `phone` to fillable attributes to support the registration form.
- **Routing**: Refactored `web.php` from closures to `RestaurantController` for better performance and maintainability.

### Optimized
- **Service Worker**: Implemented cache versioning (`v2`) and improved background synchronization logic.

## [1.0.0] - 2026-03-30

### Initial Release
- Core DDD architecture.
- Livewire PWA layout.
- Basic menu and order functionality.
