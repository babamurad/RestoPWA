<?php

declare(strict_types=1);

namespace App\Support;

final class PIIMasker
{
    /**
     * Mask a phone number, keeping country code and last 2 digits.
     * E.g. +99312345678 -> +993*****78
     */
    public static function maskPhone(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);
        if (! $clean || strlen($clean) < 4) {
            return '***';
        }

        $length = strlen($clean);
        $prefix = substr($clean, 0, 4);
        $suffix = substr($clean, -2);
        $masked = str_repeat('*', max(0, $length - 6));

        return $prefix . $masked . $suffix;
    }

    /**
     * Mask a name, keeping first character.
     * E.g. "Иван Иванов" -> "И*** И*****"
     */
    public static function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        $masked = [];

        foreach ($parts as $part) {
            if (strlen($part) === 0) {
                continue;
            }
            $masked[] = mb_substr($part, 0, 1) . str_repeat('*', max(0, mb_strlen($part) - 1));
        }

        return implode(' ', $masked) ?: '***';
    }

    /**
     * Mask an address, keeping only street name.
     * E.g. "ул. Ахалтекинская, д. 10, кв. 5" -> "ул. Ахалтекинская, ***"
     */
    public static function maskAddress(string $address): string
    {
        $parts = explode(',', $address);
        if (count($parts) <= 1) {
            return $address;
        }

        return trim($parts[0]) . ', ***';
    }

    /**
     * Mask a comment to first 20 characters.
     */
    public static function maskComment(string $comment, int $maxVisible = 20): string
    {
        if (mb_strlen($comment) <= $maxVisible) {
            return $comment;
        }

        return mb_substr($comment, 0, $maxVisible) . '...';
    }

    /**
     * Mask PII in an order payload for safe logging.
     */
    public static function maskOrderPayload(array $payload): array
    {
        $masked = $payload;

        if (isset($masked['address'])) {
            $addr = $masked['address'];
            if (isset($addr['name'])) {
                $addr['name'] = self::maskName($addr['name']);
            }
            if (isset($addr['phone'])) {
                $addr['phone'] = self::maskPhone($addr['phone']);
            }
            if (isset($addr['address'])) {
                $addr['address'] = self::maskAddress($addr['address']);
            }
            if (isset($addr['comment'])) {
                $addr['comment'] = self::maskComment($addr['comment']);
            }
            $masked['address'] = $addr;
        }

        if (isset($masked['comment'])) {
            $masked['comment'] = self::maskComment($masked['comment']);
        }

        if (isset($masked['phone'])) {
            $masked['phone'] = self::maskPhone($masked['phone']);
        }

        if (isset($masked['name'])) {
            $masked['name'] = self::maskName($masked['name']);
        }

        return $masked;
    }

    /**
     * Checklist: fields that MUST be masked before logging.
     *
     * - address.name
     * - address.phone
     * - address.address (full street + apt)
     * - address.comment
     * - comment (order-level comment)
     * - phone (if top-level)
     * - name (if top-level)
     * - email (if ever added)
     *
     * Fields that are SAFE to log:
     * - vendor_id
     * - product_id
     * - quantity
     * - prices (unit_price, total_price, total)
     * - trace_id
     * - timestamps
     * - status codes
     */
    public static function getMaskingChecklist(): array
    {
        return [
            'must_mask' => [
                'address.name',
                'address.phone',
                'address.address',
                'address.comment',
                'address.house',
                'address.apartment',
                'comment',
                'phone',
                'name',
                'email',
            ],
            'safe_to_log' => [
                'vendor_id',
                'product_id',
                'quantity',
                'unit_price',
                'total_price',
                'total',
                'delivery_fee',
                'trace_id',
                'created_at',
                'status',
                'payment_method',
            ],
        ];
    }
}
