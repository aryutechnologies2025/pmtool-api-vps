<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build()
    {
        $email = $this->subject('Project Entry notification')
            ->view('emails.ClientNotification')
            ->with('details', $this->details);

        if (!empty($this->details['type_of_work']) && $this->details['type_of_work'] === 'thesis') {
            if (!empty($this->details['pdf_path']) && file_exists($this->details['pdf_path'])) {
                $email->attach($this->details['pdf_path'], [
                    'as' => 'ThesisDocument.pdf',
                    'mime' => 'application/pdf',
                ]);
            } else {
                Log::error("PDF not found at: " . $this->details['pdf_path']);
            }
        }

        return $email;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Project Entry notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
