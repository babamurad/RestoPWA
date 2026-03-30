<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Services;

class TenantContext
{
    private ?string $currentVendorId = null;

    /**
     * Set the current vendor ID.
     */
    public function setCurrentVendor(?string $vendorId): void
    {
        $this->currentVendorId = $vendorId;
    }

    /**
     * Get the current vendor ID.
     */
    public function getCurrentVendor(): ?string
    {
        return $this->currentVendorId;
    }
}
