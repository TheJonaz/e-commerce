<?php

namespace App\Mail;

use App\Models\Order;
use App\Modules\PaymentRegistry;
use App\Modules\ShippingRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $forAdmin = false) {}

    public function envelope(): Envelope
    {
        $shopName = setting('shop.name', config('app.name'));

        $subject = $this->forAdmin
            ? "Ny order {$this->order->order_number} – {$shopName}"
            : "Tack för din beställning {$this->order->order_number}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order-placed',
            with: [
                'order' => $this->order->loadMissing('items'),
                'shopName' => setting('shop.name', config('app.name')),
                'currency' => $this->order->currency,
                'payment' => app(PaymentRegistry::class)->find($this->order->payment_method),
                'shipping' => app(ShippingRegistry::class)->find($this->order->shipping_method),
                'forAdmin' => $this->forAdmin,
            ],
        );
    }
}
