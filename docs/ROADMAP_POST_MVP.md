# Post-MVP Roadmap: RestoPWA

After completing the initial stabilization (Stage A & B) and quality audit (Stage C), the following features are prioritized for future development.

## 1. Real-time Order Tracking (Stage D)
- **Standard**: Periodic polling for order status changes.
- **Advanced**: Integration with **Laravel Reverb** or Pusher for real-time WebSocket updates.
- **UI**: Interactive order progress bar (Received → Cooking → Delivery → Delivered).

## 2. Push Notifications (Stage E)
- **VAPID Keys**: Integration of VAPID key management in the admin panel.
- **Service Worker Hooks**: Enhancing `sw.js` to handle background push payloads even when the tab is closed.
- **Notification Manager**: A dedicated opt-in UI for push subscription.

## 3. Payments & Checkout Polish (Stage F)
- **Gateway Integration**: Support for Yandex Pay, Stripe, or Tinkoff.
- **Auto-fill Address**: Better integration with Yandex Maps for address suggestions.
- **Coupons & Loyalty**: Referral system and discount codes.

## 4. Multi-Vendor / Marketplace Mode
- **Discovery**: Improved restaurant search and categorization.
- **Reviews**: User-generated ratings and menu item feedback.
- **Merchant App**: A dedicated simplified PWA for restaurant owners to manage orders.

---

> [!TIP]
> **Priority**: Our immediate next focus after Stage C should be **Real-time Order Status** to provide instant feedback to the user on their order progress.
