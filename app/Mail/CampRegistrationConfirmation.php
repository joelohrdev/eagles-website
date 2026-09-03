<?php

namespace App\Mail;

use App\Models\CampRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampRegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CampRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're registered: {$this->registration->camp->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.camps.confirmation',
            with: [
                'registration' => $this->registration->loadMissing('camp'),
                'camp' => $this->registration->camp,
            ],
        );
    }
}
