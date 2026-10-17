<?php

namespace App\Filament\Resources;

use App\Domains\Geo\Models\District;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * City districts shared by all restaurants. Restaurants then set their own
 * delivery price per district in their restaurant settings.
 */
class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'Район';

    protected static ?string $pluralModelLabel = 'Районы';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Район')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->label('Город')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок (при пересечении границ выигрывает меньший)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])->columns(2),
                Section::make('Граница района')
                    ->description('Нарисуйте границу района на карте.')
                    ->schema([
                        Forms\Components\ViewField::make('boundary')
                            ->hiddenLabel()
                            ->view('filament.forms.components.delivery-zone-map')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city')->label('Город')->sortable(),
                Tables\Columns\IconColumn::make('boundary')
                    ->label('Граница')
                    ->boolean()
                    ->state(fn (District $record): bool => ! empty($record->boundary)),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => DistrictResource\Pages\ListDistricts::route('/'),
            'create' => DistrictResource\Pages\CreateDistrict::route('/create'),
            'edit' => DistrictResource\Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
