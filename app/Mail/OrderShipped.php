<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Din beställning {$this->order->order_number} har skickats"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-shipped',
            with: [
                'order' => $this->order,
                'shopName' => setting('shop.name', config('app.name')),
            ],
        );
    }
}
