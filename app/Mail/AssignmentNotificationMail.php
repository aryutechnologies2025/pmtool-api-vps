<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssignmentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Define the envelope (subject, from, etc.)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->details['project_id'] . ' Assigned',
        );
    }

    /**
     * Define the content (view and data)
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.assignmentNotification',
            with: [
                'details' => $this->details,
            ],
        );
    }

    /**
     * Attachments (optional)
     */
    public function attachments(): array
    {
        return [];
    }
}
