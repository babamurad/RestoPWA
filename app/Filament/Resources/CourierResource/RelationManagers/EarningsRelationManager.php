<?php

namespace App\Filament\Resources\CourierResource\RelationManagers;

use App\Domains\Logistics\Models\CourierEarning;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class EarningsRelationManager extends RelationManager
{
    protected static string $relationship = 'earnings';

    protected static ?string $title = 'Заработок';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает выплаты',
                        'paid' => 'Выплачено',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Заказ (ID)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('TMT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ожидает',
                        'paid' => 'Выплачено',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markPaid')
                        ->label('Отметить выплаченными')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records
                            ->where('status', 'pending')
                            ->each(fn (CourierEarning $e) => $e->update(['status' => 'paid']))),
                ]),
            ]);
    }
}
