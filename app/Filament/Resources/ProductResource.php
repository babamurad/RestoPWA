<?php

namespace App\Filament\Resources;

use App\Domains\Menu\Models\Product;
use App\Filament\Resources\ProductResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | BackedEnum | null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('description')
                            ->label('Описание')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Привязка')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Ресторан')
                            ->relationship('restaurant', 'name')
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable(),
                    ])->columns(2),
                Section::make('Цена и наличие')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Цена')
                            ->required()
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Доступен')
                            ->default(true),
                    ])->columns(2),
                Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\TextInput::make('weight_g')
                            ->label('Вес (г)')
                            ->numeric(),
                        Forms\Components\TextInput::make('image')
                            ->label('URL изображения')
                            ->url(),
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
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Доступен')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Ресторан')
                    ->relationship('restaurant', 'name'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Доступность'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
            'index' => Pages\ManageProducts::route('/'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
