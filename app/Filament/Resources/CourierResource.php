<?php

namespace App\Filament\Resources;

use App\Domains\Logistics\Models\Courier;
use App\Enums\UserRole;
use App\Filament\Resources\CourierResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Логистика';

    protected static ?string $modelLabel = 'Курьер';

    protected static ?string $pluralModelLabel = 'Курьеры';

    public static function form(Schema $schema): Schema
    {
        $userQuery = User::where('role', UserRole::COURIER);

        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Пользователь')
                    ->options($userQuery->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('restaurants')
                    ->relationship('restaurants', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Рестораны')
                    ->helperText('Пусто — общий пул: курьер работает со всеми ресторанами. Привязанные курьеры получают заказы своих ресторанов первыми.')
                    ->searchable(),
                Forms\Components\Select::make('vehicle_type')
                    ->label('Тип транспорта')
                    ->options([
                        'walking' => 'Пешком',
                        'bike' => 'Велосипед',
                        'car' => 'Автомобиль',
                    ])
                    ->required()
                    ->default('walking'),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(Courier::STATUSES)
                    ->required()
                    ->default('offline'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('restaurants.name')
                    ->label('Рестораны')
                    ->badge()
                    ->placeholder('Общий пул'),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Транспорт')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'walking' => 'Пешком',
                        'bike' => 'Велосипед',
                        'car' => 'Автомобиль',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Courier::STATUS_AVAILABLE => 'success',
                        Courier::STATUS_BUSY => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Courier::STATUSES[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurants')
                    ->relationship('restaurants', 'name')
                    ->label('Ресторан'),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CourierResource\RelationManagers\EarningsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCouriers::route('/'),
            'create' => Pages\CreateCourier::route('/create'),
            'edit' => Pages\EditCourier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Restaurateur: couriers of their restaurants and the general pool
        if (Auth::user()->isRestaurateur()) {
            $restaurantIds = Auth::user()->getTenants(Filament::getCurrentOrDefaultPanel())->pluck('id');

            return $query->where(function (Builder $q) use ($restaurantIds) {
                $q->whereDoesntHave('restaurants')
                    ->orWhereHas('restaurants', fn (Builder $r) => $r->whereIn('restaurants.id', $restaurantIds));
            });
        }

        return $query;
    }
}
