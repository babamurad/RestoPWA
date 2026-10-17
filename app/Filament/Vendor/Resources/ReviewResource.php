<?php

namespace App\Filament\Vendor\Resources;

use App\Domains\Review\Models\Review;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Restaurateur: read the restaurant's reviews and reply. Scoped to the
 * current tenant through Review::restaurant(); ratings cannot be edited.
 */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $modelLabel = 'Отзыв';

    protected static ?string $pluralModelLabel = 'Отзывы';

    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rating')
                    ->label('Оценка')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент'),
                Tables\Columns\TextColumn::make('vendor_reply')
                    ->label('Ваш ответ')
                    ->placeholder('Нет ответа')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\IconColumn::make('is_hidden')
                    ->label('Скрыт модератором')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Оценка')
                    ->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
                Tables\Filters\TernaryFilter::make('vendor_reply')
                    ->label('Ответ')
                    ->nullable()
                    ->trueLabel('С ответом')
                    ->falseLabel('Без ответа'),
            ])
            ->actions([
                Actions\Action::make('reply')
                    ->label(fn (Review $record): string => $record->vendor_reply ? 'Изменить ответ' : 'Ответить')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->fillForm(fn (Review $record): array => ['vendor_reply' => $record->vendor_reply])
                    ->form([
                        Forms\Components\Textarea::make('vendor_reply')
                            ->label('Ответ ресторана')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->action(function (Review $record, array $data): void {
                        $record->update([
                            'vendor_reply' => $data['vendor_reply'],
                            'replied_at' => now(),
                        ]);
                    }),
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
