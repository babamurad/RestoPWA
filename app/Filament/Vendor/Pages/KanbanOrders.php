<?php

namespace App\Filament\Vendor\Pages;

use Filament\Pages\Page;

class KanbanOrders extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = null;

    protected string $view = 'filament.vendor.pages.kanban-orders';

    protected static ?string $navigationLabel = 'Канбан доска';
    protected static ?string $title = 'Канбан заказов';
    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    protected static ?int $navigationSort = 1;

    public function mount(): void
    {
        // Enforce tenancy check if needed, but Filament handles this
    }
}
