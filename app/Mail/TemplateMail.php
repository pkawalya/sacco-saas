<?php

namespace App\Mail;

use App\Models\Tenant\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic mailable for template-driven notification dispatch.
 *
 * Renders a NotificationTemplate with merge-field data and sends it
 * as a styled HTML email via the configured SMTP mailer.
 */
class TemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $renderedBody;

    public string $renderedSubject;

    /**
     * @param  array<string, string>  $mergeData
     */
    public function __construct(
        protected NotificationTemplate $template,
        protected array $mergeData = []
    ) {
        $this->renderedSubject = $template->renderSubject($mergeData);
        $this->renderedBody = $template->renderBody($mergeData);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject ?: config('app.name').' Notification');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'subject' => $this->renderedSubject,
            'body' => $this->renderedBody,
            'template' => $this->template,
        ]);
    }
}
