<?php

use App\Enums\MessageStatus;
use App\Enums\PostStatus;
use App\Enums\ProjectStatus;
use App\Models\Experience;
use App\Models\Message;
use App\Models\Post;
use App\Models\Project;

it('casts statuses to enums', function () {
    expect(Post::factory()->create()->status)->toBe(PostStatus::Published)
        ->and(Project::factory()->create()->status)->toBe(ProjectStatus::Published)
        ->and(Message::factory()->create()->status)->toBe(MessageStatus::New);
});

it('casts json and date columns', function () {
    $project = Project::factory()->create(['tech_stack' => ['Laravel', 'MySQL']]);
    $experience = Experience::factory()->create(['achievements' => ['a', 'b']]);

    expect($project->fresh()->tech_stack)->toBe(['Laravel', 'MySQL'])
        ->and($experience->fresh()->achievements)->toBe(['a', 'b'])
        ->and($experience->fresh()->start_date)->toBeInstanceOf(DateTimeInterface::class);
});

it('splits the inbox from spam and unread', function () {
    Message::factory()->create();
    Message::factory()->create(['status' => MessageStatus::Read]);
    Message::factory()->create(['status' => MessageStatus::Spam]);

    expect(Message::inbox()->count())->toBe(2)
        ->and(Message::unread()->count())->toBe(1);
});

it('estimates reading time at 200 words per minute, minimum one', function () {
    expect(Post::readingMinutesFor('<p>short</p>'))->toBe(1)
        ->and(Post::readingMinutesFor(str_repeat('word ', 450)))->toBe(3);
});
