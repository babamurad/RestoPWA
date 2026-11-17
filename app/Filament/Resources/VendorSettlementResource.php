<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorSettlementResource\Pages;
use App\Models\VendorSettlement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class VendorSettlementResource extends Resource
{
    protected static ?string $model = VendorSettlement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = 'Финансы';

    protected static ?string $modelLabel = 'Счёт ресторану';

    protected static ?string $pluralModelLabel = 'Счета ресторанам (комиссия)';

    // Subscription mode: no commission, so no payouts to show in the menu (old ones stay reachable by URL)
    public static function shouldRegisterNavigation(): bool
    {
        return config('billing.mode') === 'commission';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->label('Ресторан')
                    ->required()
                    ->disabled(),
                Forms\Components\DatePicker::make('period_from')
                    ->label('Период С')
                    ->disabled(),
                Forms\Components\DatePicker::make('period_to')
                    ->label('Период ПО')
                    ->disabled(),
                Forms\Components\TextInput::make('gross_amount')
                    ->label('Выручка за блюда')
                    ->disabled()
                    ->suffix('TMT'),
                Forms\Components\TextInput::make('commission_amount')
                    ->label('К оплате платформе (комиссия)')
                    ->disabled()
                    ->suffix('TMT'),
                Forms\Components\TextInput::make('net_payable')
                    ->label('Остаётся ресторану')
                    ->disabled()
                    ->suffix('TMT'),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'approved' => 'Выставлен',
                        'paid' => 'Оплачен',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Дата оплаты'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_from')
                    ->label('Период С')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_to')
                    ->label('Период ПО')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('К оплате платформе')
                    ->money('TMT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_payable')
                    ->label('Остаётся ресторану')
                    ->money('TMT')
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
                        'draft' => 'Черновик',
                        'approved' => 'Выставлен',
                        'paid' => 'Оплачен',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->label('Ресторан'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'approved' => 'Выставлен',
                        'paid' => 'Оплачен',
                    ])
                    ->label('Статус'),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('Выставить счёт')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (VendorSettlement $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(fn (VendorSettlement $record) => $record->update(['status' => 'approved'])),
                Action::make('markPaid')
                    ->label('Отметить оплаченным')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VendorSettlement $record) => $record->status !== 'paid')
                    ->requiresConfirmation()
                    ->action(fn (VendorSettlement $record) => $record->update(['status' => 'paid', 'paid_at' => now()])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markPaid')
                        ->label('Отметить оплаченными')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records
                            ->where('status', '!=', 'paid')
                            ->each(fn (VendorSettlement $r) => $r->update(['status' => 'paid', 'paid_at' => now()]))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVendorSettlements::route('/'),
        ];
    }
}
