<?php

declare(strict_types=1);

namespace App\Domains\Vendor\Scopes;

use App\Domains\Vendor\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class BelongsToVendorScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = App::make(TenantContext::class);
        $vendorId = $tenantContext->getCurrentVendor();

        if ($vendorId) {
            $builder->where('vendor_id', $vendorId);
        } else {
            $builder->whereNull($model->getTable().'.id');
        }
    }
}
