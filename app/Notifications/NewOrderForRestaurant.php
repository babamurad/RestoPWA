<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domains\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email to the restaurant's owner about a new order: the backup channel
 * for when the orders board is closed and nobody hears its signal.
 */
class NewOrderForRestaurant extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $restaurant = $order->restaurant;

        $mail = (new MailMessage)
            ->subject("Новый заказ №{$order->order_number} — ".number_format((float) $order->total, 2, ',', ' ').' TMT')
            ->greeting('Новый заказ')
            ->line(($restaurant?->name ? "Ресторан: {$restaurant->name}. " : '').($order->isPickup() ? 'Самовывоз.' : 'Доставка.'));

        foreach ($order->items ?? [] as $item) {
            $mail->line('• '.($item['name'] ?? $item['product_name'] ?? 'Блюдо').' × '.($item['quantity'] ?? 1));
        }

        if ($order->comment) {
            $mail->line('Комментарий: '.$order->comment);
        }

        return $mail
            ->line('Сумма: '.number_format((float) $order->total, 2, ',', ' ').' TMT')
            ->action('Открыть заказы', url('/vendor'))
            ->line('Примите заказ на доске заказов, чтобы клиент увидел, что его начали готовить.');
    }
}
