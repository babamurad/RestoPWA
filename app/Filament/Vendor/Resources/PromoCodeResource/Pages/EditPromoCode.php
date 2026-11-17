<?php

namespace App\Filament\Vendor\Resources\PromoCodeResource\Pages;

use App\Filament\Vendor\Resources\PromoCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromoCode extends EditRecord
{
    protected static string $resource = PromoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
