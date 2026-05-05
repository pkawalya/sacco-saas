<?php

namespace App\Mail;

use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends login credentials to a newly onboarded SACCO staff member.
 */
class StaffWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $staffUser,
        public string $plainPassword,
        public string $saccoName,
        public string $panelUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Welcome to {$this->saccoName} — Your Login Details");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-welcome');
    }
}
