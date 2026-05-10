<?php

namespace App\Filament\Resources;

use App\Domains\Vendor\Models\Restaurant;
use App\Filament\Resources\RestaurantResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Рестораны';

    protected static ?string $modelLabel = 'Ресторан';

    protected static ?string $pluralModelLabel = 'Рестораны';

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
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image')
                            ->label('Изображение (Логотип)')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/images')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Обложка')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/covers')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Настройки')
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
                Section::make('Зона доставки')
                    ->description('Координаты полигона доставки в формате GeoJSON MULTIPOLYGON. Можно скопировать из любого онлайн-редактора.')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_zones')
                            ->label('GeoJSON MULTIPOLYGON')
                            ->placeholder('{"type":"MultiPolygon","coordinates":[[[[63.55,39.08],[63.56,39.08],[63.56,39.09],[63.55,39.09],[63.55,39.08]]]]}')
                            ->rows(6)
                            ->formatStateUsing(function ($state) {
                                if (is_array($state) || is_object($state)) {
                                    return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                                }
                                return $state;
                            })
                            ->dehydrateStateUsing(function ($state) {
                                $decoded = json_decode($state, true);
                                return json_decode($state) ? $decoded : $state;
                            })
                            ->helperText('Используйте http://geojson.io для создания полигона. Формат: {"type":"MultiPolygon","coordinates":[[[[lon,lat],[lon,lat],...]]]}')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('delivery_zone_status')
                            ->label('Статус зоны доставки')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'Сохраните ресторан, чтобы проверить зону доставки.';
                                }
                                $zones = $record->deliveryZones();
                                if (empty($zones)) {
                                    return '⚠️ Зона доставки не настроена. Все точки будут считаться вне зоны.';
                                }
                                return '✅ Зона доставки настроена ('.count($zones).' полигон(ов)).';
                            }),
                    ]),
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
                Tables\Columns\TextColumn::make('delivery_zones_summary')
                    ->label('Зона доставки')
                    ->getStateUsing(function ($record) {
                        $zones = $record->deliveryZones();
                        if (empty($zones)) {
                            return 'Не настроена';
                        }
                        return 'Настроена';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Настроена' ? 'success' : 'danger'),
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
            'index' => Pages\ManageRestaurants::route('/'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
