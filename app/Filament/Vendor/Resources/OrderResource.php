<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Order\Models\Order;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Заказ';
    protected static ?string $pluralModelLabel = 'Заказы';
    protected static string | \UnitEnum | null $navigationGroup = 'Продажи';

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
                                'ready' => 'Готов',
                                'delivering' => 'Доставляется',
                                'delivered' => 'Доставлен',
                                'completed' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Статус оплаты')
                            ->options([
                                'pending' => 'Ожидает оплаты',
                                'paid' => 'Оплачен',
                                'failed' => 'Ошибка',
                                'refunded' => 'Возврат',
                            ])
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
                        \Filament\Infolists\Components\TextEntry::make('id')
                            ->label('ID заказа')
                            ->formatStateUsing(fn (string $state): string => '#' . substr($state, 0, 8)),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed', 'cooking' => 'info',
                                'ready' => 'primary',
                                'delivering' => 'warning',
                                'delivered', 'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('total')
                            ->label('Сумма')
                            ->money('RUB'),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Дата создания')
                            ->dateTime(),
                    ])->columns(2),

                Section::make('Адрес и контакт')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label('Клиент')
                            ->placeholder('Гость'),
                        \Filament\Infolists\Components\TextEntry::make('address.address')
                            ->label('Адрес'),
                        \Filament\Infolists\Components\TextEntry::make('address.phone')
                            ->label('Телефон'),
                        \Filament\Infolists\Components\TextEntry::make('comment')
                            ->label('Комментарий')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Товары')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('name')
                                    ->label('Название'),
                                \Filament\Infolists\Components\TextEntry::make('quantity')
                                    ->label('Кол-во'),
                                \Filament\Infolists\Components\TextEntry::make('price')
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
                    ->formatStateUsing(fn (string $state): string => '#' . substr($state, 0, 8))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент')
                    ->placeholder('Гость')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed', 'cooking' => 'info',
                        'ready' => 'primary',
                        'delivering' => 'warning',
                        'delivered', 'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Новый',
                        'confirmed' => 'Подтвержден',
                        'cooking' => 'Готовится',
                        'ready' => 'Готов',
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
                        'ready' => 'Готовы',
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
