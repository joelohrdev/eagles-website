<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceipt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Eagles Baseball receipt — Order {$this->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.receipt',
            with: [
                'order' => $this->order->loadMissing(['items', 'campRegistration.camp']),
            ],
        );
    }
}
