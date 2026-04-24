<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->invitation->email,
            subject: 'Te invitaron a CACAO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invitation',
            with: [
                'acceptUrl' => route('invitation.show', $this->invitation->token),
                'expiresAt' => $this->invitation->expires_at->format('d/m/Y H:i'),
                'inviterName' => $this->invitation->invitedBy?->name ?? 'El equipo CACAO',
            ],
        );
    }
}
