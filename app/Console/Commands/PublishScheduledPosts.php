<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('posts:publish-scheduled')]
#[Description('Mark scheduled articles whose publish time has arrived as published')]
class PublishScheduledPosts extends Command
{
    public function handle(): int
    {
        $due = Post::query()
            ->where('status', PostStatus::Scheduled)
            ->where('published_at', '<=', now())
            ->get();

        // One save per post so the activity log and the public cache clear as for any other publish.
        $due->each(fn (Post $post) => $post->update(['status' => PostStatus::Published]));

        $this->info("Published {$due->count()} scheduled article(s).");

        return self::SUCCESS;
    }
}
