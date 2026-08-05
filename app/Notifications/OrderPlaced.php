<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        $message = (new MailMessage)
            ->subject("Order {$order->order_number} confirmed")
            ->greeting("Thanks for your order, {$order->shipping_name}!")
            ->line("We've received order **{$order->order_number}** and will let you know as soon as it ships.");

        foreach ($order->items as $item) {
            $message->line("• {$item->product_name} × {$item->quantity} — {$order->currency} ".number_format((float) $item->line_total, 2));
        }

        if ((float) $order->discount_total > 0) {
            $message->line('Discount: −'.$order->currency.' '.number_format((float) $order->discount_total, 2));
        }

        return $message
            ->line('Delivery: '.$order->currency.' '.number_format((float) $order->shipping_total, 2))
            ->line('**Total: '.$order->currency.' '.number_format((float) $order->grand_total, 2).'**')
            ->action('View your order', route('order.show', $order))
            ->line('Delivering to: '.$order->shipping_address.', '.$order->shipping_city);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'grand_total' => $this->order->grand_total,
        ];
    }
}
