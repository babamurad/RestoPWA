<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Menu\Models\Product;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'Товар';
    protected static ?string $pluralModelLabel = 'Товары';
    protected static string | \UnitEnum | null $navigationGroup = 'Меню';

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
                        Forms\Components\Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),
                        Forms\Components\TextInput::make('weight_g')
                            ->label('Вес (г)')
                            ->numeric(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Изображение')
                            ->image()
                            ->disk('public')
                            ->directory('products'),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Доступен для заказа')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Модификаторы')
                    ->schema([
                        Forms\Components\Repeater::make('modifiers')
                            ->label('Модификаторы')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Название модификатора')
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->label('Тип')
                                    ->options([
                                        'radio' => 'Радио (один выбор)',
                                        'checkbox' => 'Чекбокс (множественный выбор)',
                                        'counter' => 'Счетчик (количество)',
                                    ])
                                    ->required(),
                                Forms\Components\Repeater::make('options')
                                    ->label('Опции')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Название опции')
                                            ->required(),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Доп. цена')
                                            ->numeric()
                                            ->prefix('₽')
                                            ->default(0),
                                        Forms\Components\Toggle::make('is_default')
                                            ->label('По умолчанию'),
                                    ])
                                    ->columns(3)
                                    ->required(),
                            ])
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Фото')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_g')
                    ->label('Вес')
                    ->suffix(' г')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Доступен')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Доступность'),
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
            'index' => ProductResource\Pages\ListProducts::route('/'),
            'create' => ProductResource\Pages\CreateProduct::route('/create'),
            'edit' => ProductResource\Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
