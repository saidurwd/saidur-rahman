<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to me when someone uses the contact form. Reply goes straight to the sender. */
class NewContactMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Message $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contact->email, $this->contact->name)],
            subject: 'New message: '.($this->contact->subject ?: 'from '.$this->contact->name),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.new-contact-message', with: [
            'inboxUrl' => route('admin.messages.show', $this->contact),
        ]);
    }
}
