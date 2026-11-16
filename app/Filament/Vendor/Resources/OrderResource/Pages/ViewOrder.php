<?php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Domains\Order\Exceptions\InvalidStatusTransition;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Enums\OrderCancelReason;
use App\Filament\Vendor\Resources\OrderResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Declining needs a reason: the customer is told why the order will not come
            Actions\Action::make('decline')
                ->label('Отклонить заказ')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (Order $record): bool => $record->canTransitionTo(Order::STATUS_CANCELLED))
                ->requiresConfirmation()
                ->modalHeading('Отклонить заказ?')
                ->modalDescription('Клиент получит уведомление с причиной. Вернуть заказ в работу будет нельзя.')
                ->form([
                    Forms\Components\Select::make('reason')
                        ->label('Причина')
                        ->options(OrderCancelReason::restaurantOptions())
                        ->required(),
                    Forms\Components\Textarea::make('comment')
                        ->label('Комментарий для клиента')
                        ->maxLength(500)
                        ->rows(2),
                ])
                ->action(function (Order $record, array $data): void {
                    try {
                        app(OrderService::class)->cancel(
                            $record,
                            Order::CANCELLED_BY_RESTAURANT,
                            OrderCancelReason::from($data['reason']),
                            $data['comment'] ?? null,
                        );
                    } catch (InvalidStatusTransition) {
                        Notification::make()->title('Этот заказ уже нельзя отклонить')->warning()->send();

                        return;
                    }

                    Notification::make()->title('Заказ отклонён, клиент уведомлён')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            Actions\EditAction::make(),
        ];
    }
}
