<?php

namespace App\Filament\Resources;

use BackedEnum;
use App\Domains\Vendor\Models\Restaurant;
use App\Filament\Resources\RestaurantResource\Pages;
use App\Filament\Resources\RestaurantResource\RelationManagers;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string | BackedEnum | null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Рестораны';

    protected static ?string $modelLabel = 'Ресторан';

    protected static ?string $pluralModelLabel = 'Рестораны';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\TextInput::make('delivery_time')
                            ->label('Время доставки'),
                        Forms\Components\TextInput::make('delivery_fee')
                            ->label('Стоимость доставки')
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('min_order')
                            ->label('Минимальный заказ')
                            ->numeric()
                            ->prefix('₽'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Товаров')
                    ->counts('products')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активные',
                        '0' => 'Неактивные',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRestaurants::route('/'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
