# Tenant Security & Isolation Audit Matrix

This document tracks the status of tenant isolation and security enforcement across all system endpoints.

## 1. Public API (v1)
All routes under `/api/v1/` are protected by the `SetTenantContext` middleware.

| Endpoint | Method | Required Header | Isolation Pattern | Status |
| :--- | :--- | :--- | :--- | :--- |
| `api/v1/menu/{vendor}` | GET | `X-Vendor-ID` | Vendor slug must match tenant context. | ✅ OK |
| `api/v1/menu/product/{product}` | GET | `X-Vendor-ID` | Product must belong to the active vendor. | ✅ OK |
| `api/v1/restaurants` | GET | `X-Vendor-ID` | Global list, but scoped to active status. | ✅ OK |
| `api/v1/cart/sync` | POST | `X-Vendor-ID` | Item validation against active vendor. | ✅ OK |
| `api/v1/orders` | POST | `X-Vendor-ID` | **Hardened**: Order created for vendor and auth user. | ✅ OK |

## 2. Vendor Admin Panel
All routes under `/vendor/` are protected by the `ensure.tenant` and `auth` middleware.

| Endpoint | Method | Middleware | Isolation Pattern | Status |
| :--- | :--- | :--- | :--- | :--- |
| `/vendor/products/*` | Any | `ensure.tenant`, `auth` | Global scope `BelongsToVendor` ensures isolation. | ✅ OK |
| `/vendor/orders/*` | Any | `ensure.tenant`, `auth` | Global scope `BelongsToVendor` ensures isolation. | ✅ OK |
| `/vendor/settings/*` | Any | `ensure.tenant`, `auth` | Restricted to the active vendor instance. | ✅ OK |

## 3. Customer Web Routes
These routes do not use the `ensure.tenant` middleware as they rely on slug or ID resolution.

| Endpoint | Method | Security Check | Isolation Pattern | Status |
| :--- | :--- | :--- | :--- | :--- |
| `/restaurants/{vendor}` | GET | slug-based | Fetches specific restaurant by slug. | ✅ OK |
| `/order/success/{id}` | GET | session/owner | Prevents cross-order viewing by guessing IDs. | ✅ OK |
| `/order/{id}/track` | GET | `auth` + ownership check | Restricted to order owner via `user_id` match in controller. | ✅ OK |

---

## Technical Enforcement Details

- **Global Scopes**: All tenant-aware models (Product, Category, Order) use the `BelongsToVendor` trait, which applies a global filter based on `TenantContext::getCurrentVendor()`.
- **Middleware**: `SetTenantContext` (for API) and `ensure.tenant` (for Web) ensure that `TenantContext` is populated early in the request lifecycle.
- **API Response**: Requests failing the tenant context check return a `400 Bad Request`.
