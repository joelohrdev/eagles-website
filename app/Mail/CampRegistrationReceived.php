<?php

namespace App\Mail;

use App\Models\CampRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampRegistrationReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CampRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New camp registration: {$this->registration->camp->name} — {$this->registration->playerName()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.camps.received',
            with: [
                'registration' => $this->registration->loadMissing('camp'),
                'camp' => $this->registration->camp,
                'adminUrl' => route('admin.camps.registrations.index', $this->registration->camp),
            ],
        );
    }
}
