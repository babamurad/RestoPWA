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

                Forms\Components\Section::make('Зона доставки')
                    ->description('Настройте полигон доставки в формате GeoJSON')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_zones')
                            ->label('GeoJSON MULTIPOLYGON')
                            ->placeholder('{"type":"MultiPolygon","coordinates":[[[[lon,lat],[lon,lat],[lon,lat],[lon,lat],[lon,lat]]]]}')
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
                            ->helperText('Используйте http://geojson.io для создания полигона. Координаты в формате [lon, lat]. Сохраните изменения после редактирования.')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('delivery_zone_status')
                            ->label('Статус зоны')
                            ->content(function ($record) {
                                if (! $record) return 'Сохраните, чтобы проверить.';
                                $zones = $record->deliveryZones();
                                if (empty($zones)) return '⚠️ Зона не настроена. Заказы не будут приниматься.';
                                return '✅ Зона настроена ('.count($zones).' полигонов).';
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
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Открыто')
                    ->boolean(),
                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Время'),
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Доставка')
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('delivery_zones_summary')
                    ->label('Зона')
                    ->getStateUsing(function ($record) {
                        $zones = $record->deliveryZones();
                        return empty($zones) ? 'Не настроена' : 'Готова';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Готова' ? 'success' : 'danger'),
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
