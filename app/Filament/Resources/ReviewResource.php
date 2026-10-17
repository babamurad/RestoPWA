<?php

namespace App\Filament\Resources;

use App\Domains\Review\Models\Review;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin: moderate reviews across all restaurants. Hiding a review removes it
 * from the public list and from the restaurant's rating (ReviewObserver).
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $modelLabel = 'Отзыв';

    protected static ?string $pluralModelLabel = 'Отзывы';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Оценка')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(200)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент'),
                Tables\Columns\TextColumn::make('vendor_reply')
                    ->label('Ответ ресторана')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\IconColumn::make('is_hidden')
                    ->label('Скрыт')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_hidden')->label('Скрытые'),
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Оценка')
                    ->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
            ])
            ->actions([
                Actions\Action::make('hide')
                    ->label('Скрыть')
                    ->icon('heroicon-m-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => ! $record->is_hidden)
                    ->action(fn (Review $record) => $record->update(['is_hidden' => true])),
                Actions\Action::make('show')
                    ->label('Показать')
                    ->icon('heroicon-m-eye')
                    ->visible(fn (Review $record): bool => $record->is_hidden)
                    ->action(fn (Review $record) => $record->update(['is_hidden' => false])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ReviewResource\Pages\ListReviews::route('/'),
        ];
    }
}
