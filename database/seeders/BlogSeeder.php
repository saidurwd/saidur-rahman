<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $leadership = Category::query()->create(['name' => 'IT leadership']);
        $engineering = Category::query()->create(['name' => 'Engineering', 'sort_order' => 1]);
        $tags = collect(['ERP', 'Laravel', 'Digital transformation'])
            ->map(fn (string $name) => Tag::query()->create(['name' => $name]));

        $published = Post::query()->create([
            'title' => 'Sample published article',
            'excerpt' => 'Sample excerpt.',
            'body' => '<p>Sample article body. Replace with real writing.</p>',
            'category_id' => $leadership->id,
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(3),
            'reading_minutes' => 1,
        ]);
        $published->tags()->attach($tags->take(2)->pluck('id'));

        Post::query()->create([
            'title' => 'Sample draft article',
            'excerpt' => 'Draft excerpt.',
            'body' => '<p>Draft body.</p>',
            'category_id' => $engineering->id,
            'status' => PostStatus::Draft,
            'reading_minutes' => 1,
        ]);
    }
}
