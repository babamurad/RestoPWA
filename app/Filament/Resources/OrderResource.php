<?php

namespace App\Filament\Resources;

use App\Domains\Order\Models\Order;
use App\Filament\Resources\OrderResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Заказы';

    protected static ?string $modelLabel = 'Заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Информация о заказе')
                    ->schema([
                        Forms\Components\TextInput::make('status')
                            ->label('Статус')
                            ->disabled(),
                        Forms\Components\TextInput::make('payment_status')
                            ->label('Оплата')
                            ->disabled(),
                    ])->columns(2),
                Section::make('Клиент')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->label('Имя')
                            ->disabled(),
                        Forms\Components\TextInput::make('client_phone')
                            ->label('Телефон')
                            ->disabled(),
                        Forms\Components\TextInput::make('full_address')
                            ->label('Адрес')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->limit(8),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->sortable(),
                Tables\Columns\TextColumn::make('address.name')
                    ->label('Клиент')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Order::STATUSES[$state]['label'] ?? $state)
                    ->color(fn (string $state): string => Order::STATUSES[$state]['color'] ?? 'gray')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(Order::STATUSES)->map(fn($status) => $status['label'])->toArray()),
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Ресторан')
                    ->relationship('restaurant', 'name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn ($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
            ])
            ->actions([
                Actions\Action::make('changeStatus')
                    ->label('Сменить статус')
                    ->form([
                        Forms\Components\Select::make('new_status')
                            ->label('Новый статус')
                            ->options(collect(Order::STATUSES)->map(fn($status) => $status['label'])->toArray())
                            ->required(),
                    ])
                    ->action(function (Order $order, array $data) {
                        $order->update(['status' => $data['new_status']]);
                    })
                    ->icon('heroicon-m-pencil'),
                Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
