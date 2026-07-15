<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\VendorSettlementResource\Pages;
use App\Models\VendorSettlement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VendorSettlementResource extends Resource
{
    protected static ?string $model = VendorSettlement::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?string $modelLabel = 'Выплата';
    protected static ?string $pluralModelLabel = 'История выплат';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_from')
                    ->label('Период С')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_to')
                    ->label('Период ПО')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Сумма заказов')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Комиссия')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_payable')
                    ->label('К выплате')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved' => 'primary',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Формируется',
                        'approved' => 'Готов к выплате',
                        'paid' => 'Выплачено',
                        default => $state,
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Read-only, no actions
            ])
            ->bulkActions([
                // No bulk actions for vendor
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorSettlements::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (Auth::user()->isRestaurateur()) {
            return $query->where('restaurant_id', Auth::user()->restaurant->id);
        }

        return $query;
    }
}
