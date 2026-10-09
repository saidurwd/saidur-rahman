<?php

namespace App\Mail;

use App\Models\Message;
use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Confirms to the sender that their message arrived. */
class ContactAutoReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Message $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Thanks, I received your message');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact-auto-reply', with: [
            'siteName' => app(SettingService::class)->get('site_name', config('app.name')),
        ]);
    }
}
