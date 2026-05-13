<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domains\Geo\Models\LocalPlace;
use App\Filament\Resources\LocalPlaceResource\Pages;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocalPlaceResource extends Resource
{
    protected static ?string $model = LocalPlace::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Локальные адреса';

    protected static ?string $modelLabel = 'Локальный адрес';

    protected static ?string $pluralModelLabel = 'Локальные адреса';

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
                        Forms\Components\TextInput::make('city')
                            ->label('Город')
                            ->default('Туркменабат')
                            ->maxLength(100),
                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'district' => 'Район',
                                'street' => 'Улица',
                                'landmark' => 'Ориентир',
                                'market' => 'Рынок',
                                'school' => 'Школа',
                                'hospital' => 'Больница',
                                'restaurant' => 'Ресторан',
                                'building' => 'Здание',
                                'other' => 'Другое',
                            ])
                            ->default('other')
                            ->required(),
                        Forms\Components\TagsInput::make('aliases')
                            ->label('Алиасы (синонимы)')
                            ->placeholder('Добавить синоним'),
                    ])->columns(2),
                Forms\Components\Section::make('Координаты')
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.0000001)
                            ->required(),
                        Forms\Components\TextInput::make('lon')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.0000001)
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Модерация')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицировано')
                            ->default(false),
                        Forms\Components\TextInput::make('popularity')
                            ->label('Популярность')
                            ->numeric()
                            ->default(0),
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
                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type_label')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lat')
                    ->label('Широта')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 6))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lon')
                    ->label('Долгота')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 6))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Вериф.')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('popularity')
                    ->label('Попул.')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'district' => 'Район',
                        'street' => 'Улица',
                        'landmark' => 'Ориентир',
                        'market' => 'Рынок',
                        'school' => 'Школа',
                        'hospital' => 'Больница',
                        'restaurant' => 'Ресторан',
                        'building' => 'Здание',
                        'other' => 'Другое',
                    ]),
                Tables\Filters\Filter::make('is_verified')
                    ->label('Только верифицированные')
                    ->query(fn ($query) => $query->where('is_verified', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('popularity', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalPlaces::route('/'),
            'create' => Pages\CreateLocalPlace::route('/create'),
            'edit' => Pages\EditLocalPlace::route('/{record}/edit'),
        ];
    }
}
