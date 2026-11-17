<?php

namespace App\Filament\Vendor\Resources\PromoCodeResource\Pages;

use App\Filament\Vendor\Resources\PromoCodeResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePromoCode extends CreateRecord
{
    protected static string $resource = PromoCodeResource::class;

    /** A restaurant's code always belongs to that restaurant, whatever the form says */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['restaurant_id'] = Filament::getTenant()->id;

        return $data;
    }
}
