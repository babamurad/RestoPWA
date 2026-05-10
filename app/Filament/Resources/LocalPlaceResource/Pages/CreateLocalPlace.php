<?php

declare(strict_types=1);

namespace App\Filament\Resources\LocalPlaceResource\Pages;

use App\Filament\Resources\LocalPlaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocalPlace extends CreateRecord
{
    protected static string $resource = LocalPlaceResource::class;
}
