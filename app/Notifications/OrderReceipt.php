<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domains\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email receipt for the customer, in the customer's language. */
class OrderReceipt extends Notification implements ShouldQueue
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
        $locale = $notifiable->locale ?? config('app.locale');
        $t = fn (string $key, array $replace = []): string => __("notifications.receipt.{$key}", $replace, $locale);
        $money = fn (float $amount): string => number_format($amount, 2, ',', ' ').' TMT';

        $mail = (new MailMessage)
            ->subject($t('subject', ['number' => $order->order_number]))
            ->greeting($t('greeting'))
            ->line($t('intro', ['number' => $order->order_number, 'restaurant' => $order->restaurant?->name ?? '']));

        foreach ($order->items ?? [] as $item) {
            $mail->line('• '.($item['name'] ?? $item['product_name'] ?? '').' × '.($item['quantity'] ?? 1)
                .' — '.$money(((int) ($item['total_price'] ?? 0)) / 100));
        }

        if ((float) $order->delivery_fee > 0) {
            $mail->line($t('delivery', ['amount' => $money((float) $order->delivery_fee)]));
        }

        return $mail
            ->line($t('total', ['amount' => $money((float) $order->total)]))
            ->line($t('payment_cash'))
            ->action($t('action'), url('/profile'))
            ->salutation($t('salutation'));
    }
}
