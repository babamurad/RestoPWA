<?php

namespace App\Filament\Resources;

use App\Domains\Logistics\Models\Courier;
use App\Enums\UserRole;
use App\Filament\Resources\CourierResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';
    protected static string | \UnitEnum | null $navigationGroup = 'Логистика';
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
                Forms\Components\Select::make('vendor_id')
                    ->relationship('restaurant', 'name')
                    ->label('Ресторан (если привязан)')
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
                    ->options([
                        'offline' => 'Офлайн',
                        'online' => 'Онлайн',
                        'busy' => 'Занят (в пути)',
                    ])
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
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан'),
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
                        'offline' => 'gray',
                        'online' => 'success',
                        'busy' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'offline' => 'Офлайн',
                        'online' => 'Онлайн',
                        'busy' => 'В пути',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->relationship('restaurant', 'name')
                    ->label('Ресторан'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EarningsRelationManager::class,
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
        
        if (Auth::user()->isRestaurateur()) {
            return $query->where('vendor_id', Auth::user()->restaurant->id)
                         ->orWhereNull('vendor_id');
        }

        return $query;
    }
}
