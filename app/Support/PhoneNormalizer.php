<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * PhoneNormalizer: handles phone number normalization and validation
 * using a shared policy that both Livewire and API can use.
 */
final class PhoneNormalizer
{
    /**
     * Normalize a raw phone input string to E.164-like format.
     *
     * Rules:
     * - Strip all non-digit characters except leading '+'
     * - Replace leading '00' with '+'
     * - Ensure single leading '+'
     */
    public static function normalize(string $raw): string
    {
        $trimmed = trim($raw);

        // Replace leading '00' with '+'
        if (str_starts_with($trimmed, '00')) {
            $trimmed = '+' . substr($trimmed, 2);
        }

        // Remove all non-digit characters except leading '+'
        $clean = '';
        $starts = false;
        for ($i = 0; $i < strlen($trimmed); $i++) {
            $char = $trimmed[$i];
            if ($char === '+' && $i === 0) {
                $starts = true;
                $clean = '+';
                continue;
            }
            if (ctype_digit($char)) {
                $clean .= $char;
            }
        }

        // Remove duplicate leading '+' signs
        $clean = preg_replace('/^\++/', '+', $clean);

        return $clean;
    }

    /**
     * Validate phone number according to the configured policy.
     *
     * Returns ['valid' => true] or ['valid' => false, 'reason' => string, 'message' => string]
     */
    public static function validate(string $phone): array
    {
        $normalized = self::normalize($phone);

        if (empty($normalized)) {
            return [
                'valid' => false,
                'reason' => 'invalid_phone_format',
                'message' => __('checkout.validation.phone.required'),
            ];
        }

        if (! str_starts_with($normalized, '+')) {
            return [
                'valid' => false,
                'reason' => 'invalid_phone_format',
                'message' => __('checkout.validation.phone.missing_plus'),
            ];
        }

        $mode = config('checkout.phone.mode', 'strict_region');

        if ($mode === 'strict_region') {
            return self::validateStrictRegion($normalized);
        }

        if ($mode === 'e164') {
            return self::validateE164($normalized);
        }

        return [
            'valid' => false,
            'reason' => 'invalid_phone_format',
            'message' => __('checkout.validation.phone.unknown_mode'),
        ];
    }

    /**
     * Validate against the strict region pattern.
     */
    private static function validateStrictRegion(string $phone): array
    {
        $defaultCountry = config('checkout.phone.default_country', '993');
        $patterns = config('checkout.phone_patterns', []);
        $countryConfig = $patterns[$defaultCountry] ?? null;

        if (! $countryConfig) {
            return [
                'valid' => false,
                'reason' => 'invalid_phone_format',
                'message' => __('checkout.validation.phone.config_error'),
            ];
        }

        if (! preg_match($countryConfig['pattern'], $phone)) {
            return [
                'valid' => false,
                'reason' => 'invalid_phone_format',
                'message' => __('checkout.validation.phone.strict_format', [
                    'example' => $countryConfig['example'],
                    'label' => $countryConfig['label'],
                ]),
            ];
        }

        // Check whitelist if configured
        $allowed = config('checkout.phone.allowed_countries', []);
        if (! empty($allowed)) {
            $countryCode = self::extractCountryCode($phone);
            if (! in_array($countryCode, $allowed, true)) {
                return [
                    'valid' => false,
                    'reason' => 'invalid_phone_format',
                    'message' => __('checkout.validation.phone.not_allowed'),
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Validate against international E.164 format.
     */
    private static function validateE164(string $phone): array
    {
        // E.164: +[1-9]\d{7,14}
        if (! preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            return [
                'valid' => false,
                'reason' => 'invalid_phone_format',
                'message' => __('checkout.validation.phone.e164_format'),
            ];
        }

        // Check whitelist if configured
        $allowed = config('checkout.phone.allowed_countries', []);
        if (! empty($allowed)) {
            $countryCode = self::extractCountryCode($phone);
            if (! in_array($countryCode, $allowed, true)) {
                return [
                    'valid' => false,
                    'reason' => 'invalid_phone_format',
                    'message' => __('checkout.validation.phone.not_allowed'),
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Extract country code from E.164 phone (digits after + until pattern match).
     */
    private static function extractCountryCode(string $phone): string
    {
        $digits = ltrim($phone, '+');

        // Try 1-digit country codes first (1 for US/CA)
        if (in_array($digits[0], ['1', '7'], true)) {
            return $digits[0];
        }

        // Try 2-digit country codes
        $two = substr($digits, 0, 2);
        if (in_array($two, ['20', '27', '30', '31', '32', '33', '34', '36', '39', '40', '41', '43', '44', '45', '46', '47', '48', '49', '51', '52', '53', '54', '55', '56', '57', '58', '60', '61', '62', '63', '64', '65', '66', '76', '81', '82', '84', '86', '90', '91', '92', '93', '94', '95', '98'], true)) {
            return $two;
        }

        // Try 3-digit country codes
        $three = substr($digits, 0, 3);
        return $three;
    }

    /**
     * Get the phone mode for frontend rendering.
     */
    public static function getMode(): string
    {
        return config('checkout.phone.mode', 'strict_region');
    }

    /**
     * Get the example phone number for the current mode.
     */
    public static function getExample(): string
    {
        $mode = self::getMode();

        if ($mode === 'e164') {
            return '+99312345678';
        }

        $defaultCountry = config('checkout.phone.default_country', '993');
        $patterns = config('checkout.phone_patterns', []);

        return $patterns[$defaultCountry]['example'] ?? '+99312345678';
    }

    /**
     * Get the helper text for the phone input.
     */
    public static function getHelperText(): string
    {
        $mode = self::getMode();

        if ($mode === 'e164') {
            return __('checkout.validation.phone.e164_helper');
        }

        $defaultCountry = config('checkout.phone.default_country', '993');
        $patterns = config('checkout.phone_patterns', []);
        $label = $patterns[$defaultCountry]['label'] ?? '';

        return __('checkout.validation.phone.strict_helper', ['country' => $label]);
    }

    /**
     * Max allowed comment length.
     */
    public static function maxCommentLength(): int
    {
        return (int) config('checkout.max_comment_length', 500);
    }
}
