<?php

namespace App\Filament\Resources\VendorSettlementResource\Pages;

use App\Filament\Resources\VendorSettlementResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageVendorSettlements extends ManageRecords
{
    protected static string $resource = VendorSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
