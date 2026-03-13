<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class CorrectionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The email details.
     */
    public array $details;

    /**
     * Create a new message instance.
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Define the envelope (metadata like subject, to, cc, etc.)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->details['project_id'] . ' - ' . $this->details['status'],
            to: [
                new Address($this->details['projectManagerEmail'], 'Project Manager'),
                // new Address($this->details['publicationManagerEmail'], 'Publication Manager')
            ],
            cc: [
                // new Address($this->details['employee'], 'employee'),
                new Address($this->details['teamManagerEmail'], 'Team Coordinator'),
                new Address($this->details['adminEmail'], 'Admin'),
            ],
        );
    }

    /**
     * Define the content (view and data).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.correctionNotification',
            with: [
                'details' => $this->details,
            ],
        );
    }

    /**
     * Attachments (if any).
     */
    public function attachments(): array
    {
        return [];
    }
}
