<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Promo\Models\PromoCode;
use App\Filament\Resources\PromoCodeResource as AdminPromoCodeResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The restaurant's own promo codes. The panel's tenancy limits the list to the current
 * restaurant and attaches new codes to it: a restaurant cannot see or create codes for others.
 * The discount comes out of the restaurant's own takings.
 */
class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'Финансы';

    protected static ?string $modelLabel = 'Промокод';

    protected static ?string $pluralModelLabel = 'Промокоды';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(AdminPromoCodeResource::sections(withRestaurant: false));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(AdminPromoCodeResource::columns(withRestaurant: false))
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** Only the current restaurant's codes: no other restaurant's and no platform-wide ones */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('restaurant_id', Filament::getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => PromoCodeResource\Pages\ListPromoCodes::route('/'),
            'create' => PromoCodeResource\Pages\CreatePromoCode::route('/create'),
            'edit' => PromoCodeResource\Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
