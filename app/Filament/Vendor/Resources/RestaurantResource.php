<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Vendor\Models\Restaurant;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $modelLabel = 'Настройки ресторана';
    protected static ?string $pluralModelLabel = 'Настройки ресторана';
    protected static string | \UnitEnum | null $navigationGroup = 'Настройки';

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
                        Forms\Components\FileUpload::make('image')
                            ->label('Логотип')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/images'),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Обложка')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/covers'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Заведение открыто')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Условия доставки')
                    ->schema([
                        Forms\Components\TextInput::make('delivery_time')
                            ->label('Среднее время доставки (мин)')
                            ->placeholder('20-30'),
                        Forms\Components\TextInput::make('delivery_fee')
                            ->label('Стоимость доставки')
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('min_order')
                            ->label('Минимальная сумма заказа')
                            ->numeric()
                            ->prefix('₽'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Логотип')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Открыто')
                    ->boolean(),
                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Время'),
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Доставка')
                    ->money('RUB'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => RestaurantResource\Pages\ListRestaurants::route('/'),
            'edit' => RestaurantResource\Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
