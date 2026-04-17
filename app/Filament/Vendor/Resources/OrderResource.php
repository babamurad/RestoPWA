<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Order\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Заказ';
    protected static ?string $pluralModelLabel = 'Заказы';
    protected static ?string $navigationGroup = 'Продажи';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Статус заказа')
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

                Forms\Components\Section::make('Детали заказа')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Основная информация')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID заказа')
                            ->formatStateUsing(fn (string $state): string => '#' . substr($state, 0, 8)),
                        Infolists\Components\TextEntry::make('status')
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
                        Infolists\Components\TextEntry::make('total')
                            ->label('Сумма')
                            ->money('RUB'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Дата создания')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Адрес и контакт')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Клиент')
                            ->placeholder('Гость'),
                        Infolists\Components\TextEntry::make('address.address')
                            ->label('Адрес'),
                        Infolists\Components\TextEntry::make('address.phone')
                            ->label('Телефон'),
                        Infolists\Components\TextEntry::make('comment')
                            ->label('Комментарий')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Товары')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Название'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Кол-во'),
                                Infolists\Components\TextEntry::make('price')
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
