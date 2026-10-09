<?php

namespace App\Models;

use App\Enums\MessageStatus;
use App\Observers\ContentObserver;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ContentObserver::class)]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['name', 'email', 'company', 'subject', 'body', 'ip', 'user_agent', 'status', 'read_at', 'starred'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MessageStatus::class,
            'read_at' => 'datetime',
            'starred' => 'boolean',
        ];
    }

    /** @param Builder<static> $query */
    public function scopeUnread(Builder $query): void
    {
        $query->where('status', MessageStatus::New);
    }

    /** @param Builder<static> $query */
    public function scopeInbox(Builder $query): void
    {
        $query->where('status', '!=', MessageStatus::Spam);
    }
}
