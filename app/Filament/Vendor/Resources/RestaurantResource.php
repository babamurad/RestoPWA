<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Vendor\Models\Restaurant;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

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
                \Filament\Schemas\Components\Group::make()
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Основная информация')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Название')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Описание')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ]),
                            
                        \Filament\Schemas\Components\Section::make('Медиа и статус')
                            ->schema([
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
                                    ->default(true)
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                \Filament\Schemas\Components\Group::make()
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Условия доставки')
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
                            ])->columns(1),
                    ])->columnSpan(['lg' => 1]),

                \Filament\Schemas\Components\Section::make('Зона доставки')
                    ->description('Настройте полигон доставки в формате GeoJSON')
                    ->schema([
                        Forms\Components\ViewField::make('delivery_zones')
                            ->label('Карта зоны доставки')
                            ->view('filament.forms.components.delivery-zone-map')
                            ->columnSpanFull(),

                        \Filament\Schemas\Components\Section::make('Просмотр координат (GeoJSON)')
                            ->collapsed()
                            ->compact()
                            ->schema([
                                Forms\Components\Placeholder::make('delivery_zones_json')
                                    ->label('Текущий GeoJSON')
                                    ->content(function ($record) {
                                        if (!$record || !$record->delivery_zones) return 'Пусто';
                                        return json_encode($record->delivery_zones, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                                    })
                            ]),
                        Forms\Components\Placeholder::make('delivery_zone_status')
                            ->label('Статус зоны')
                            ->content(function ($record) {
                                if (! $record) return 'Сохраните, чтобы проверить.';
                                $zones = $record->getZonesArray();
                                if (empty($zones)) return '⚠️ Зона не настроена. Заказы не будут приниматься.';
                                
                                $polygonCount = 0;
                                if (isset($zones['type']) && $zones['type'] === 'MultiPolygon' && isset($zones['coordinates'])) {
                                    $polygonCount = count($zones['coordinates']);
                                } elseif (isset($zones['type']) && $zones['type'] === 'Polygon') {
                                    $polygonCount = 1;
                                } else {
                                    $polygonCount = count($zones);
                                }
                                
                                return '✅ Зона настроена ('.$polygonCount.' полигонов).';
                            }),

                        \Filament\Schemas\Components\Fieldset::make('check_point_fieldset')
                            ->label('Проверить точку на карте')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('check_lat')
                                            ->label('Широта')
                                            ->numeric()
                                            ->placeholder('39.0886'),
                                        Forms\Components\TextInput::make('check_lon')
                                            ->label('Долгота')
                                            ->numeric()
                                            ->placeholder('63.5593'),
                                        \Filament\Schemas\Components\Actions::make([
                                            \Filament\Actions\Action::make('check_point')
                                                ->label('Проверить')
                                                ->button()
                                                ->color('warning')
                                                ->extraAttributes(['class' => 'mt-8'])
                                                ->action(function ($get, ?Restaurant $record) {
                                                    $lat = $get('check_lat');
                                                    $lon = $get('check_lon');
                                                    if (!$lat || !$lon) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Ошибка')
                                                            ->body('Пожалуйста, введите широту и долготу.')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    if (!$record) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Ошибка')
                                                            ->body('Пожалуйста, сохраните ресторан перед проверкой точки.')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }

                                                    $geoService = app(\App\Domains\Geo\Services\GeoService::class);
                                                    $result = $geoService->checkDeliveryZone((float) $lat, (float) $lon, $record->id);
                                                    
                                                    $color = $result->isAllowed() ? 'success' : 'danger';
                                                    $statusLabel = match($result->status) {
                                                        'inside' => 'Внутри зоны доставки',
                                                        'outside' => 'Вне зоны доставки',
                                                        'zone_missing' => 'Зона доставки не настроена',
                                                        'invalid_geometry' => 'Некорректная геометрия зоны',
                                                        'postgis_error' => 'Ошибка PostGIS',
                                                        default => $result->status,
                                                    };

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Результат проверки')
                                                        ->body("Статус: **{$statusLabel}**\n\nСообщение: {$result->messageForUser()}")
                                                        ->color($color)
                                                        ->persistent()
                                                        ->send();
                                                })
                                        ])->alignEnd()
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Логотип')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->image_url),
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
                        $zones = $record->getZonesArray();
                        return empty($zones) ? 'Не настроена' : 'Готова';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Готова' ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
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
