<?php

namespace App\Mail;

use App\Models\TryoutRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TryoutRegistrationReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public TryoutRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New tryout registration — {$this->registration->tryout->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tryouts.received',
            with: [
                'registration' => $this->registration->loadMissing('tryout'),
                'tryout' => $this->registration->tryout,
                'adminUrl' => route('admin.tryouts.registrations.index', $this->registration->tryout),
            ],
        );
    }
}
