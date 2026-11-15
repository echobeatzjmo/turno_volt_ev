<?php

namespace App\Mail;

use App\Models\ReservationObservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReservationObservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ReservationObservation $observation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva observación registrada - Turno Volt EV',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation-observation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
