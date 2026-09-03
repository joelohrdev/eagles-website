<?php

namespace App\Mail;

use App\Models\TryoutRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TryoutRegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public TryoutRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're registered — {$this->registration->tryout->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tryouts.confirmation',
            with: [
                'registration' => $this->registration->loadMissing('tryout'),
                'tryout' => $this->registration->tryout,
                'tryoutUrl' => route('tryouts.show', $this->registration->tryout),
            ],
        );
    }
}
