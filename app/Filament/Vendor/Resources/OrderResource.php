<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Order\Models\Order;
use App\Enums\PaymentStatus;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Статус заказа')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Новый',
                                'confirmed' => 'Подтвержден',
                                'cooking' => 'Готовится',
                                'ready_for_pickup' => 'Готов',
                                'delivering' => 'Доставляется',
                                'delivered' => 'Доставлен',
                                'completed' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Статус оплаты')
                            ->options(PaymentStatus::options())
                            ->required(),
                    ])->columns(2),

                Section::make('Детали заказа')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('ID заказа')
                            ->disabled(),
                        Forms\Components\TextInput::make('total')
                            ->label('Сумма')
                            ->numeric()
                            ->prefix('₽')
                            ->disabled(),
                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID заказа')
                            ->formatStateUsing(fn (string $state): string => '#'.substr($state, 0, 8)),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed', 'cooking' => 'info',
                                'ready_for_pickup' => 'primary',
                                'delivering' => 'warning',
                                'delivered', 'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('total')
                            ->label('Сумма')
                            ->money('RUB'),
                        TextEntry::make('created_at')
                            ->label('Дата создания')
                            ->dateTime(),
                    ])->columns(2),

                Section::make('Адрес и контакт')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Клиент')
                            ->placeholder('Гость'),
                        TextEntry::make('delivery_type')
                            ->label('Тип')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === 'pickup' ? 'Самовывоз' : 'Доставка'),
                        TextEntry::make('address.address')
                            ->label('Адрес')
                            ->hidden(fn ($record): bool => $record->isPickup()),
                        TextEntry::make('address.phone')
                            ->label('Телефон'),
                        TextEntry::make('comment')
                            ->label('Комментарий')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Товары')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Название'),
                                TextEntry::make('quantity')
                                    ->label('Кол-во'),
                                TextEntry::make('price')
                                    ->label('Цена')
                                    ->money('RUB'),
                            ])->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(fn (string $state): string => '#'.substr($state, 0, 8))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент')
                    ->placeholder('Гость')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'pickup' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'pickup' ? 'Самовывоз' : 'Доставка'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::PAID => 'success',
                        PaymentStatus::REFUNDED => 'gray',
                        PaymentStatus::PAYMENT_FAILED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed', 'cooking' => 'info',
                        'ready_for_pickup' => 'primary',
                        'delivering' => 'warning',
                        'delivered', 'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Новый',
                        'confirmed' => 'Подтвержден',
                        'cooking' => 'Готовится',
                        'ready_for_pickup' => 'Готов',
                        'delivering' => 'В доставке',
                        'delivered' => 'Доставлен',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Новые',
                        'cooking' => 'Готовятся',
                        'ready_for_pickup' => 'Готовы',
                        'completed' => 'Завершены',
                        'cancelled' => 'Отменены',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => OrderResource\Pages\ListOrders::route('/'),
            'view' => OrderResource\Pages\ViewOrder::route('/{record}'),
            'edit' => OrderResource\Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
