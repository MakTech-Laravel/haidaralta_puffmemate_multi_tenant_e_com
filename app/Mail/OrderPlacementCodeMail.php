<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPlacementCodeMail extends Mailable
{
    public function __construct(
        public Order $order,
        public string $code,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delivery code for '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.orders.placement-code',
            text: 'mail.orders.placement-code-text',
            with: [
                'orderNumber' => $this->order->order_number,
                'code' => $this->code,
                'recipientName' => $this->recipientName,
                'appName' => config('app.name', 'PuffMeMate'),
            ],
        );
    }
}
