<?php

declare(strict_types=1);

namespace App\Filament\Resources\LocalPlaceResource\Pages;

use App\Filament\Resources\LocalPlaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLocalPlaces extends ListRecords
{
    protected static string $resource = LocalPlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
