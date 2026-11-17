<?php

namespace App\Filament\Resources;

use App\Domains\Geo\Models\GeocodingLog;
use App\Filament\Resources\GeocodingLogResource\Pages;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class GeocodingLogResource extends Resource
{
    protected static ?string $model = GeocodingLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Логи геокодинга';

    protected static ?string $modelLabel = 'Лог';

    protected static ?string $pluralModelLabel = 'Логи геокодинга';

    protected static string | UnitEnum | null $navigationGroup = 'Гео';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success', 'in_zone', 'suggest_results' => 'success',
                        'failed', 'failed_all', 'outside_zone', 'suggest_empty' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Провайдер')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('query')
                    ->label('Запрос')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lat')
                    ->label('Широта')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 6) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lon')
                    ->label('Долгота')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 6) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vendor_id')
                    ->label('Vendor ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('error_code')
                    ->label('Код ошибки')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Сообщение')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'success' => 'Успех',
                        'failed' => 'Ошибка',
                        'failed_all' => 'Все провайдеры не сработали',
                        'in_zone' => 'В зоне доставки',
                        'outside_zone' => 'Вне зоны доставки',
                        'suggest_results' => 'Подсказки найдены',
                        'suggest_empty' => 'Подсказок нет',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Провайдер')
                    ->options([
                        'local' => 'Локальная БД',
                        'yandex' => 'Yandex',
                        'nominatim' => 'Nominatim',
                        'google' => 'Google',
                        'postgis' => 'PostGIS',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from'),
                        \Filament\Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getWidgets(): array
    {
        return [
            GeocodingLogResource\Widgets\GeocodingStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeocodingLogs::route('/'),
        ];
    }
}
