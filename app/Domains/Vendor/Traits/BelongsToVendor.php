<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Traits;

use App\Domains\Vendor\Scopes\BelongsToVendorScope;
use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToVendor
{
    /**
     * The "boot" method of the model.
     */
    public static function bootBelongsToVendor(): void
    {
        static::addGlobalScope(new BelongsToVendorScope);

        static::creating(function (Model $model) {
            if (empty($model->vendor_id)) {
                /** @var TenantContext $tenantContext */
                $tenantContext = app(TenantContext::class);
                $vendorId = $tenantContext->getCurrentVendor();

                if ($vendorId) {
                    $model->vendor_id = $vendorId;
                }
            }
        });
    }
}
