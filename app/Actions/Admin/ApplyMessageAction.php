<?php

namespace App\Actions\Admin;

use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;

class ApplyMessageAction
{
    /**
     * @param  list<int|string>  $ids
     * @return int number of messages changed
     */
    public function __invoke(string $action, array $ids): int
    {
        $messages = Message::query()->whereKey($ids)->get();

        foreach ($messages as $message) {
            match ($action) {
                'read' => $this->setStatus($message, MessageStatus::Read),
                'unread' => $message->update(['status' => MessageStatus::New, 'read_at' => null]),
                'star' => $message->update(['starred' => true]),
                'unstar' => $message->update(['starred' => false]),
                'spam' => $message->update(['status' => MessageStatus::Spam]),
                'not-spam' => $message->update(['status' => MessageStatus::Read, 'read_at' => $message->read_at ?? now()]),
                default => $message->delete(), // 'delete', the only action left after request validation
            };
        }

        return $messages->count();
    }

    public function setStatus(Message $message, MessageStatus $status): void
    {
        $message->update([
            'status' => $status,
            'read_at' => $status === MessageStatus::New ? null : ($message->read_at ?? now()),
        ]);
    }

    /**
     * Filters shared by the inbox list and the CSV export.
     *
     * @param  Builder<Message>  $query
     */
    public static function filter(Builder $query, string $folder, string $term): void
    {
        match ($folder) {
            'unread' => $query->unread(),
            'starred' => $query->inbox()->where('starred', true),
            'spam' => $query->where('status', MessageStatus::Spam),
            'all' => null,
            default => $query->inbox(),
        };

        $query->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('company', 'like', "%{$term}%")
            ->orWhere('subject', 'like', "%{$term}%")
            ->orWhere('body', 'like', "%{$term}%")));
    }
}
