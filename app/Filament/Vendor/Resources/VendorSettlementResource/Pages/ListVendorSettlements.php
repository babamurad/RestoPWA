<?php

namespace App\Filament\Vendor\Resources\VendorSettlementResource\Pages;

use App\Filament\Vendor\Resources\VendorSettlementResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorSettlements extends ListRecords
{
    protected static string $resource = VendorSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
