<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingDetailsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $pdfContents,
        public readonly string $bookingNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Booking details - {$this->bookingNumber}");
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.booking-details',
            with: ['bookingNumber' => $this->bookingNumber],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->pdfContents,
                "booking-details-{$this->bookingNumber}.pdf",
            )
                ->withMime('application/pdf'),
        ];
    }
}
