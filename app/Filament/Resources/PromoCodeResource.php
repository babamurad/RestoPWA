<?php

namespace App\Filament\Resources;

use App\Domains\Promo\Models\PromoCode;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Promo codes. The discount comes off the food only; the restaurant receives (and pays the
 * platform commission on) the amount after the discount.
 */
class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Финансы';

    protected static ?string $modelLabel = 'Промокод';

    protected static ?string $pluralModelLabel = 'Промокоды';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Код')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код (клиент вводит без учёта регистра)')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state) => $state === null ? null : PromoCode::normalize($state)),
                        Forms\Components\TextInput::make('description')
                            ->label('Описание (видит клиент)')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Действует')
                            ->default(true),
                    ])->columns(2),
                Section::make('Скидка')
                    ->description('Задайте либо процент, либо сумму. Скидка берётся только со стоимости блюд, не с доставки.')
                    ->schema([
                        Forms\Components\TextInput::make('percent')
                            ->label('Процент')
                            ->numeric()->minValue(1)->maxValue(100)->suffix('%')
                            ->requiredWithout('amount')
                            ->prohibits('amount'),
                        Forms\Components\TextInput::make('amount')
                            ->label('Фиксированная сумма')
                            ->numeric()->minValue(0.01)->suffix('TMT')
                            ->requiredWithout('percent')
                            ->prohibits('percent'),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Максимум скидки (для процента)')
                            ->numeric()->minValue(0)->suffix('TMT'),
                        Forms\Components\TextInput::make('min_order')
                            ->label('Минимальная сумма блюд')
                            ->numeric()->minValue(0)->default(0)->suffix('TMT'),
                    ])->columns(2),
                Section::make('Где и когда')
                    ->schema([
                        Forms\Components\Select::make('restaurant_id')
                            ->label('Ресторан (пусто — любой)')
                            ->relationship('restaurant', 'name')
                            ->searchable()->preload(),
                        Forms\Components\DateTimePicker::make('starts_at')->label('Начало'),
                        Forms\Components\DateTimePicker::make('expires_at')->label('Окончание'),
                    ])->columns(3),
                Section::make('Лимиты')
                    ->schema([
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Сколько раз всего (пусто — без ограничения)')
                            ->numeric()->minValue(1),
                        Forms\Components\TextInput::make('per_user_limit')
                            ->label('Сколько раз на одного клиента')
                            ->numeric()->minValue(1)->default(1)->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Код')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Скидка')
                    ->state(fn (PromoCode $record) => $record->percent !== null
                        ? rtrim(rtrim(number_format($record->percent, 2, '.', ''), '0'), '.').'%'
                        : number_format($record->amount, 2, '.', '').' TMT'),
                Tables\Columns\TextColumn::make('restaurant.name')->label('Ресторан')->placeholder('Любой'),
                Tables\Columns\TextColumn::make('used')
                    ->label('Использовано')
                    ->state(fn (PromoCode $record) => $record->used_count.($record->usage_limit ? ' из '.$record->usage_limit : '')),
                Tables\Columns\TextColumn::make('expires_at')->label('До')->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Действует')->boolean(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => PromoCodeResource\Pages\ListPromoCodes::route('/'),
            'create' => PromoCodeResource\Pages\CreatePromoCode::route('/create'),
            'edit' => PromoCodeResource\Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
