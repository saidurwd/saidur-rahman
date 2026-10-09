<?php

use App\Models\Post;
use App\Models\Tag;

beforeEach(function () {
    signInAsAdmin();
});

it('keeps guests out', function () {
    auth()->logout();

    $this->flushSession()->get('/admin/tags')->assertRedirect('/admin/login');
});

it('lists tags with article counts and searches', function () {
    $erp = Tag::factory()->create(['name' => 'ERP']);
    Tag::factory()->create(['name' => 'Cloud']);
    Post::factory()->create()->tags()->attach($erp->id);

    $this->get('/admin/tags')->assertOk()->assertSee('ERP')->assertSee('Cloud');
    $this->get('/admin/tags?q=erp')->assertSee('ERP')->assertDontSee('Cloud');
});

it('creates a tag with a generated slug', function () {
    $this->post('/admin/tags', ['name' => 'Digital transformation'])->assertRedirect('/admin/tags');

    expect(Tag::first()->slug)->toBe('digital-transformation');
});

it('validates and keeps slugs unique', function () {
    Tag::factory()->create(['slug' => 'taken']);

    $this->post('/admin/tags', ['name' => ''])->assertSessionHasErrors('name');
    $this->post('/admin/tags', ['name' => 'Other', 'slug' => 'taken'])->assertSessionHasErrors('slug');
});

it('renames a tag', function () {
    $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);

    $this->put("/admin/tags/{$tag->id}", ['name' => 'Renamed', 'slug' => 'old'])->assertRedirect('/admin/tags');

    expect($tag->fresh()->name)->toBe('Renamed');
});

it('deletes a tag and detaches it from articles, singly or in bulk', function () {
    $a = Tag::factory()->create();
    $b = Tag::factory()->create();
    $post = Post::factory()->create();
    $post->tags()->attach([$a->id, $b->id]);

    $this->delete("/admin/tags/{$a->id}")->assertRedirect('/admin/tags');
    expect($post->fresh()->tags)->toHaveCount(1);

    $this->post('/admin/tags/bulk', ['action' => 'delete', 'ids' => [$b->id]])->assertRedirect();
    expect(Tag::count())->toBe(0)->and($post->fresh()->tags)->toHaveCount(0);
});
