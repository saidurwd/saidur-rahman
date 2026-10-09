<?php

namespace App\Actions;

use App\Enums\MessageStatus;
use App\Mail\ContactAutoReply;
use App\Mail\NewContactMessage;
use App\Models\Message;
use App\Services\SettingService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StoreContactMessage
{
    private const MAX_LINKS = 2;

    public function __construct(private SettingService $settings) {}

    /**
     * Saves a contact message and queues the notification to me and the auto-reply to the sender.
     * Messages that look like spam are saved to the Spam folder and trigger no email at all.
     *
     * @param  array{name: string, email: string, company?: ?string, subject?: ?string, message: string}  $data
     */
    public function __invoke(array $data, ?string $ip, ?string $userAgent): Message
    {
        $spam = $this->looksLikeSpam($data['message'], $data['subject'] ?? '');

        $message = Message::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => $data['company'] ?: null,
            'subject' => $data['subject'] ?: null,
            'body' => $data['message'],
            'ip' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 250, '') : null,
            'status' => $spam ? MessageStatus::Spam : MessageStatus::New,
        ]);

        if ($spam) {
            return $message;
        }

        $to = $this->settings->get('contact_email') ?: config('mail.from.address');
        Mail::to($to)->queue(new NewContactMessage($message));

        if ($this->settings->get('contact_autoreply', true)) {
            Mail::to($message->email)->queue(new ContactAutoReply($message));
        }

        return $message;
    }

    private function looksLikeSpam(string $body, string $subject): bool
    {
        return preg_match_all('#https?://|www\.#i', $body.' '.$subject) > self::MAX_LINKS;
    }
}
