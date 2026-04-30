<?php

declare(strict_types=1);

namespace App\Domains\Order\Validators;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderRejectReason;

final readonly class OrderPreconditionValidator
{
    public function __construct(
        private GeoService $geoService,
    ) {
    }

    /**
     * Validate all preconditions before order creation.
     * Returns null on success, or OrderRejectReason on failure.
     */
    public function validate(array $data): ?OrderRejectReason
    {
        $reason = $this->validateVendor($data);
        if ($reason !== null) {
            return $reason;
        }

        $reason = $this->validateItems($data);
        if ($reason !== null) {
            return $reason;
        }

        $reason = $this->validateAddress($data);
        if ($reason !== null) {
            return $reason;
        }

        $reason = $this->validateTotal($data);
        if ($reason !== null) {
            return $reason;
        }

        $reason = $this->validateContacts($data);
        if ($reason !== null) {
            return $reason;
        }

        $reason = $this->validatePaymentMethod($data);
        if ($reason !== null) {
            return $reason;
        }

        $reason = $this->validateMinOrder($data);
        if ($reason !== null) {
            return $reason;
        }

        return null;
    }

    private function validateVendor(array $data): ?OrderRejectReason
    {
        if (empty($data['vendor_id'])) {
            return OrderRejectReason::NO_VENDOR;
        }

        $restaurant = Restaurant::find($data['vendor_id']);
        if (! $restaurant) {
            return OrderRejectReason::VENDOR_NOT_FOUND;
        }

        if (! $restaurant->is_active) {
            return OrderRejectReason::INVALID_VENDOR;
        }

        return null;
    }

    private function validateItems(array $data): ?OrderRejectReason
    {
        if (empty($data['items']) || ! is_array($data['items'])) {
            return OrderRejectReason::EMPTY_CART;
        }

        foreach ($data['items'] as $item) {
            if (empty($item['product_id'])) {
                return OrderRejectReason::INVALID_ITEMS;
            }

            if (! isset($item['quantity']) || (int) $item['quantity'] < 1) {
                return OrderRejectReason::INVALID_ITEMS;
            }

            if (! isset($item['unit_price']) || (int) $item['unit_price'] <= 0) {
                return OrderRejectReason::INVALID_PRICE;
            }

            if (! isset($item['total_price']) || (int) $item['total_price'] <= 0) {
                return OrderRejectReason::INVALID_PRICE;
            }
        }

        return null;
    }

    private function validateAddress(array $data): ?OrderRejectReason
    {
        $address = $data['address'] ?? [];

        if (empty($address)) {
            return OrderRejectReason::MISSING_ADDRESS;
        }

        if (empty($address['lat']) || empty($address['lon'])) {
            return OrderRejectReason::INVALID_COORDINATES;
        }

        $restaurant = Restaurant::find($data['vendor_id']);
        if ($restaurant) {
            $isInZone = $this->geoService->isPointInDeliveryZone(
                (float) $address['lat'],
                (float) $address['lon'],
                $restaurant->id
            );

            if (! $isInZone) {
                return OrderRejectReason::OUTSIDE_DELIVERY_ZONE;
            }
        }

        return null;
    }

    private function validateTotal(array $data): ?OrderRejectReason
    {
        $items = $data['items'] ?? [];
        $expectedTotal = 0;

        foreach ($items as $item) {
            $expectedTotal += (int) $item['total_price'];
        }

        $deliveryFee = (int) ($data['delivery_fee'] ?? 0);
        $expectedTotal += $deliveryFee;

        $submittedTotal = (int) $data['total'];

        if ($submittedTotal !== $expectedTotal) {
            return OrderRejectReason::INVALID_TOTAL;
        }

        return null;
    }

    private function validateContacts(array $data): ?OrderRejectReason
    {
        $address = $data['address'] ?? [];

        if (empty($address['name'])) {
            return OrderRejectReason::MISSING_NAME;
        }

        if (empty($address['phone'])) {
            return OrderRejectReason::INVALID_PHONE;
        }

        $phone = (string) $address['phone'];
        if (! preg_match('/^\+\d{8,15}$/', $phone)) {
            return OrderRejectReason::INVALID_PHONE;
        }

        return null;
    }

    private function validatePaymentMethod(array $data): ?OrderRejectReason
    {
        $method = $data['payment_method'] ?? null;
        $allowed = ['card', 'cash', 'sbp'];

        if (! in_array($method, $allowed, true)) {
            return OrderRejectReason::INVALID_PAYMENT_METHOD;
        }

        return null;
    }

    private function validateMinOrder(array $data): ?OrderRejectReason
    {
        $restaurant = Restaurant::find($data['vendor_id']);
        if (! $restaurant || empty($restaurant->min_order)) {
            return null;
        }

        $itemsTotal = 0;
        foreach ($data['items'] as $item) {
            $itemsTotal += (int) $item['total_price'];
        }

        // min_order is stored as float (e.g. 10.00 for 10.00 currency units)
        // Items total is in cents, so convert min_order to cents
        $minOrderCents = (int) round((float) $restaurant->min_order * 100);

        if ($itemsTotal < $minOrderCents) {
            return OrderRejectReason::BELOW_MIN_ORDER;
        }

        return null;
    }
}
