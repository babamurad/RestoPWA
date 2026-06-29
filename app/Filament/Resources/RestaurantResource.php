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

    protected static string | BackedEnum | null $navigationIcon = null;

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
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Генерируется автоматически'),
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
                        Forms\Components\Select::make('owner_id')
                            ->label('Владелец (Пользователь)')
                            ->relationship('owner', 'name', function ($query) {
                                return $query->where('role', 'restaurateur')->orWhere('role', 'admin');
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpanFull(),
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
                    ->description('Нарисуйте область доставки на карте. Координаты будут сохранены автоматически.')
                    ->schema([
                        Forms\Components\ViewField::make('delivery_zones')
                            ->label('Карта зоны доставки')
                            ->view('filament.forms.components.delivery-zone-map')
                            ->columnSpanFull(),

                        Section::make('Просмотр координат (GeoJSON)')
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
                            ->label('Статус зоны доставки')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'Сохраните ресторан, чтобы проверить зону доставки.';
                                }
                                $zones = $record->delivery_zones;
                                if (empty($zones)) {
                                    return '⚠️ Зона доставки не настроена. Все точки будут считаться вне зоны.';
                                }
                                return '✅ Зона доставки настроена.';
                            }),

                        \Filament\Schemas\Components\Fieldset::make('check_point_fieldset')
                            ->label('Проверить точку на карте')
                            ->columns(2)
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
                                    Actions\Action::make('check_point')
                                        ->label('Проверить')
                                        ->button()
                                        ->color('warning')
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
                                ])->columnSpanFull()
                            ]),
                    ])->columnSpanFull(),
            ]);
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
                        $zones = $record->getZonesArray();
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
