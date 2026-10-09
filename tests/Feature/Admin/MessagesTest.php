<?php

use App\Enums\MessageStatus;
use App\Models\Message;

beforeEach(function () {
    signInAsAdmin();
});

it('keeps guests out', function () {
    auth()->logout();
    $message = Message::factory()->create();

    foreach ([['get', '/admin/messages'], ['get', "/admin/messages/{$message->id}"], ['post', "/admin/messages/{$message->id}/star"], ['post', "/admin/messages/{$message->id}/status"], ['delete', "/admin/messages/{$message->id}"], ['post', '/admin/messages/bulk'], ['get', '/admin/messages/export'], ['get', '/admin/messages/trash']] as [$method, $url]) {
        $this->flushSession()->{$method}($url)->assertRedirect('/admin/login');
    }
});

describe('inbox', function () {
    it('lists the inbox without spam, newest first, with tab counts', function () {
        Message::factory()->create(['name' => 'Older Person', 'created_at' => now()->subDays(2)]);
        Message::factory()->create(['name' => 'Newer Person', 'created_at' => now()->subHour(), 'status' => MessageStatus::Read]);
        Message::factory()->create(['name' => 'Spammer Sam', 'status' => MessageStatus::Spam]);

        $this->get('/admin/messages')
            ->assertOk()
            ->assertSeeInOrder(['Newer Person', 'Older Person'])
            ->assertDontSee('Spammer Sam')
            ->assertViewHas('counts', ['inbox' => 2, 'unread' => 1, 'starred' => 0, 'spam' => 1]);
    });

    it('filters by folder', function () {
        Message::factory()->create(['name' => 'Unread One']);
        Message::factory()->create(['name' => 'Read One', 'status' => MessageStatus::Read]);
        Message::factory()->create(['name' => 'Starred One', 'status' => MessageStatus::Read, 'starred' => true]);
        Message::factory()->create(['name' => 'Spam One', 'status' => MessageStatus::Spam]);

        $this->get('/admin/messages?folder=unread')->assertSee('Unread One')->assertDontSee('Read One')->assertDontSee('Spam One');
        $this->get('/admin/messages?folder=starred')->assertSee('Starred One')->assertDontSee('Unread One');
        $this->get('/admin/messages?folder=spam')->assertSee('Spam One')->assertDontSee('Unread One');
        $this->get('/admin/messages?folder=all')->assertSee('Spam One')->assertSee('Unread One');
        $this->get('/admin/messages?folder=nonsense')->assertOk()->assertSee('Unread One');
    });

    it('searches names, email, company, subject and text', function () {
        Message::factory()->create(['name' => 'Alpha', 'body' => 'about zebras']);
        Message::factory()->create(['name' => 'Beta', 'company' => 'Giraffe Ltd']);

        $this->get('/admin/messages?q=zebras')->assertSee('Alpha')->assertDontSee('Beta');
        $this->get('/admin/messages?q=giraffe')->assertSee('Beta')->assertDontSee('Alpha');
    });

    it('shows the unread count in the sidebar and navbar', function () {
        Message::factory()->count(3)->create();
        Message::factory()->create(['status' => MessageStatus::Read]);

        $this->get('/admin')->assertSee('nav-badge badge text-bg-primary me-3">3<', false)->assertSee('navbar-badge badge text-bg-danger">3<', false);
    });

    it('shows an empty state', function () {
        $this->get('/admin/messages')->assertOk()->assertSee('No messages here.');
    });
});

describe('reading', function () {
    it('opens a message, marks it read once, and offers a mailto reply with the original quoted', function () {
        $message = Message::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com', 'subject' => 'Role in Doha', 'body' => "Line one\nLine two"]);

        $this->get("/admin/messages/{$message->id}")
            ->assertOk()
            ->assertSee('Role in Doha')->assertSee('Line one')
            ->assertSee('mailto:jane@example.com?subject='.rawurlencode('Re: Role in Doha'), false)
            ->assertSee(rawurlencode("> Line one\n> Line two"), false);

        $message->refresh();
        expect($message->status)->toBe(MessageStatus::Read)->and($message->read_at)->not->toBeNull();

        $firstRead = $message->read_at;
        $this->travel(1)->hour();
        $this->get("/admin/messages/{$message->id}")->assertOk();
        expect($message->fresh()->read_at->equalTo($firstRead))->toBeTrue();
    });

    it('does not change a replied or spam message back to read', function () {
        $replied = Message::factory()->create(['status' => MessageStatus::Replied]);
        $spam = Message::factory()->create(['status' => MessageStatus::Spam]);

        $this->get("/admin/messages/{$replied->id}");
        $this->get("/admin/messages/{$spam->id}");

        expect($replied->fresh()->status)->toBe(MessageStatus::Replied)->and($spam->fresh()->status)->toBe(MessageStatus::Spam);
    });

    it('escapes message text so a visitor cannot inject markup', function () {
        $message = Message::factory()->create(['body' => '<script>alert(1)</script><img src=x onerror=alert(1)>', 'name' => '<b>Bold</b>']);

        $this->get("/admin/messages/{$message->id}")->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<b>Bold</b>', false)->assertSee('&lt;script&gt;', false);
    });

    it('404s for unknown messages', function () {
        $this->get('/admin/messages/999')->assertNotFound();
    });
});

describe('actions', function () {
    it('stars and unstars', function () {
        $message = Message::factory()->create();

        $this->post("/admin/messages/{$message->id}/star")->assertRedirect();
        expect($message->fresh()->starred)->toBeTrue();

        $this->post("/admin/messages/{$message->id}/star");
        expect($message->fresh()->starred)->toBeFalse();
    });

    it('marks as replied, unread and spam, and back', function () {
        $message = Message::factory()->create(['status' => MessageStatus::Read, 'read_at' => now()]);

        $this->post("/admin/messages/{$message->id}/status", ['status' => 'replied'])->assertRedirect()->assertSessionHas('status');
        expect($message->fresh()->status)->toBe(MessageStatus::Replied);

        $this->post("/admin/messages/{$message->id}/status", ['status' => 'new']);
        expect($message->fresh()->status)->toBe(MessageStatus::New)->and($message->fresh()->read_at)->toBeNull();

        $this->post("/admin/messages/{$message->id}/status", ['status' => 'spam']);
        expect($message->fresh()->status)->toBe(MessageStatus::Spam);
        $this->get('/admin')->assertDontSee('nav-badge badge text-bg-primary', false);

        $this->post("/admin/messages/{$message->id}/status", ['status' => 'read']);
        expect($message->fresh()->status)->toBe(MessageStatus::Read);
    });

    it('rejects an unknown status', function () {
        $message = Message::factory()->create();

        $this->post("/admin/messages/{$message->id}/status", ['status' => 'banana'])->assertSessionHasErrors('status');
    });

    it('applies bulk actions', function () {
        $ids = Message::factory()->count(2)->create()->pluck('id')->all();

        $this->post('/admin/messages/bulk', ['action' => 'read', 'ids' => $ids])->assertRedirect()->assertSessionHas('status');
        expect(Message::unread()->count())->toBe(0);

        $this->post('/admin/messages/bulk', ['action' => 'unread', 'ids' => $ids]);
        expect(Message::unread()->count())->toBe(2);

        $this->post('/admin/messages/bulk', ['action' => 'star', 'ids' => $ids]);
        expect(Message::where('starred', true)->count())->toBe(2);
        $this->post('/admin/messages/bulk', ['action' => 'unstar', 'ids' => $ids]);
        expect(Message::where('starred', true)->count())->toBe(0);

        $this->post('/admin/messages/bulk', ['action' => 'spam', 'ids' => $ids]);
        expect(Message::where('status', MessageStatus::Spam)->count())->toBe(2);
        $this->post('/admin/messages/bulk', ['action' => 'not-spam', 'ids' => $ids]);
        expect(Message::where('status', MessageStatus::Read)->count())->toBe(2);

        $this->post('/admin/messages/bulk', ['action' => 'delete', 'ids' => $ids]);
        expect(Message::count())->toBe(0)->and(Message::onlyTrashed()->count())->toBe(2);
    });

    it('rejects unknown bulk actions and empty selections', function () {
        $this->post('/admin/messages/bulk', ['action' => 'explode', 'ids' => [1]])->assertSessionHasErrors('action');
        $this->post('/admin/messages/bulk', ['action' => 'read'])->assertSessionHasErrors('ids');
    });

    it('moves to Trash, restores and force deletes', function () {
        $message = Message::factory()->create(['name' => 'Trash Person']);

        $this->delete("/admin/messages/{$message->id}")->assertRedirect('/admin/messages');
        $this->get('/admin/messages/trash')->assertSee('Trash Person');
        $this->get('/admin/messages')->assertSee('Trash (1)');

        $this->post("/admin/messages/{$message->id}/restore")->assertRedirect('/admin/messages/trash');
        expect(Message::find($message->id))->not->toBeNull();

        $message->delete();
        $this->delete("/admin/messages/{$message->id}/force")->assertRedirect();
        expect(Message::withTrashed()->find($message->id))->toBeNull();
    });
});

describe('export', function () {
    it('downloads a CSV of the current folder', function () {
        Message::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'subject' => 'Hello, "world"', 'body' => "Multi\nline", 'company' => 'Acme']);
        Message::factory()->create(['name' => 'Spam Sam', 'status' => MessageStatus::Spam]);

        $response = $this->get('/admin/messages/export')->assertOk()->assertDownload('messages-'.now()->format('Y-m-d').'.csv');
        $csv = $response->streamedContent();

        expect($csv)->toStartWith('ID,Received,Status,Starred,Name,Email,Company,Subject,Message,IP')
            ->toContain('jane@example.com')->toContain('"Hello, ""world"""')->toContain("Multi\nline")
            ->not->toContain('Spam Sam');

        expect($this->get('/admin/messages/export?folder=spam')->streamedContent())->toContain('Spam Sam')->not->toContain('Jane Doe');
    });

    it('neutralises spreadsheet formulas in exported text', function () {
        Message::factory()->create(['name' => '=HYPERLINK("http://evil.example")', 'subject' => '@SUM(1+1)', 'body' => '+cmd|calc']);

        $csv = $this->get('/admin/messages/export')->streamedContent();

        expect($csv)->toContain("'=HYPERLINK")->toContain("'@SUM")->toContain("'+cmd")->not->toMatch('/(^|,)=HYPERLINK/m');
    });
});
