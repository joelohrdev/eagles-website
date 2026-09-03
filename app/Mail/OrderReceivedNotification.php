<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceivedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New paid {$this->order->type->label()} — {$this->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.received',
            with: [
                'order' => $this->order->loadMissing(['items', 'campRegistration.camp']),
                'adminUrl' => route('admin.orders.show', $this->order),
            ],
        );
    }
}
