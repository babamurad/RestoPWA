<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

final readonly class DeliveryZoneCheckResult
{
    /**
     * @param string $status inside|outside|zone_missing|invalid_geometry|postgis_error
     * @param bool $allowed
     * @param string $message
     * @param array<string, mixed> $debugContext
     */
    public function __construct(
        public string $status,
        public bool $allowed,
        public string $message,
        public array $debugContext = []
    ) {
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function messageForUser(): string
    {
        return $this->message;
    }

    public function debugContext(): array
    {
        return $this->debugContext;
    }
}
