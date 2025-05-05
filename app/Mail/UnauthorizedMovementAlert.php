<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnauthorizedMovementAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $alert;

    /**
     * Create a new message instance.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Security Alert: Unauthorized Movement Detected at {$this->alert->door->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.security.unauthorized-movement',
            with: [
                'alert' => $this->alert,
                'doorName' => $this->alert->door->name,
                'doorLocation' => $this->alert->door->location,
                'triggeredAt' => $this->alert->triggered_at->format('Y-m-d H:i:s'),
                'viewUrl' => route('dashboard.alerts.show', $this->alert->id)
            ],
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
