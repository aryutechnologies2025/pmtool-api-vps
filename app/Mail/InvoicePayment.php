<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class InvoicePayment extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $invoicePath;

    public function __construct($details, $invoicePath = null)
    {
        $this->details = $details;
        $this->invoicePath = $invoicePath;
    }

    public function build()
    {
        $mail = $this->subject('New Project Assignment Notification')
                     ->view('emails.invoiceClientNotification')
                     ->with('details', $this->details);

        if ($this->invoicePath) {
            $url = 'https://stagingbackend.medicsresearch.com/' . ltrim($this->invoicePath, '/');
            
            Log::info('Downloading invoice from URL', ['url' => $url]);

            $tempFile = public_path('invoice.pdf');
            $response = Http::get($url);

            if ($response->successful()) {
                File::put($tempFile, $response->body());

                Log::info('Invoice file downloaded and saved', ['path' => $tempFile]);

                $mail->attach($tempFile, ['as' => basename($tempFile)]);

            
            } else {
                Log::warning('Failed to download invoice file', ['url' => $url]);
            }
        }

        

        return $mail;
    }

      public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice for ' . $this->details['project_title'] . ' - ' . $this->details['invoice_number'],
        );
    }
}
