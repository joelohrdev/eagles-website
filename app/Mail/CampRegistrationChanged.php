<?php

namespace App\Mail;

use App\Models\CampRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when staff take days off a registration: the schedule the player is
 * still booked on, and any refund on its way.
 */
class CampRegistrationChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  int  $refund  Cents being given back for the dropped days.
     */
    public function __construct(public CampRegistration $registration, public int $refund) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your registration changed: {$this->registration->camp->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.camps.changed',
            with: [
                'registration' => $this->registration->loadMissing(['camp', 'ageGroup', 'order', 'days.times', 'days.session']),
                'camp' => $this->registration->camp,
                'refund' => $this->refund,
            ],
        );
    }
}
