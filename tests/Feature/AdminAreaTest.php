<?php

use App\Actions\Auth\CompleteAdminLogin;
use App\Models\Message;
use App\Models\User;
use App\Services\CacheService;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create([
        'password' => 'correct-horse-Battery9',
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_recovery_codes' => json_encode([]),
        'two_factor_confirmed_at' => now(),
    ]);
    $this->actingAs($this->user)->withSession([CompleteAdminLogin::SESSION_KEY => true]);
});

it('renders the branded dashboard with sidebar groups', function () {
    Message::factory()->count(2)->create();

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSeeInOrder(['CONTENT', 'INBOX', 'FILES', 'SITE', 'SYSTEM'])
        ->assertSee('aria-label="SR"', false)
        ->assertSee('data-bs-theme="dark"', false)
        ->assertSee('View site')
        ->assertSee('Unread messages');
});

it('links every sidebar item to a route that exists', function () {
    $missing = collect(config('admin.menu'))
        ->filter(fn ($item) => isset($item['route']) && ! Route::has($item['route']))
        ->pluck('label')
        ->all();

    expect($missing)->toBe([]);
    $this->get('/admin')->assertDontSee('aria-disabled="true"', false);
});

it('renders every admin component on the demo page', function () {
    $this->get('/admin/components')
        ->assertOk()
        ->assertSee('Image upload')
        ->assertSee('data-editor="rich"', false)
        ->assertSee('role="switch"', false)
        ->assertSee('badge text-bg-success', false)
        ->assertSee('data-confirm', false)
        ->assertSee('No items yet.');
});

it('shows flash messages as toasts', function () {
    $this->withSession([CompleteAdminLogin::SESSION_KEY => true, 'status' => 'Saved.'])
        ->get('/admin')->assertSee('Saved.')->assertSee('toast', false);
});

it('changes the password with the current one', function () {
    $this->put('/admin/password', [
        'current_password' => 'correct-horse-Battery9',
        'password' => 'Another-Long-Pass1',
        'password_confirmation' => 'Another-Long-Pass1',
    ])->assertRedirect()->assertSessionHas('status');

    expect(Hash::check('Another-Long-Pass1', $this->user->fresh()->password))->toBeTrue();
});

it('rejects a wrong current password or weak new password', function () {
    $this->put('/admin/password', [
        'current_password' => 'wrong',
        'password' => 'Another-Long-Pass1',
        'password_confirmation' => 'Another-Long-Pass1',
    ])->assertSessionHasErrors('current_password');

    $this->put('/admin/password', [
        'current_password' => 'correct-horse-Battery9',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});

it('clears the site cache from the admin', function () {
    $cache = app(CacheService::class);
    $calls = 0;
    $read = function () use ($cache, &$calls) {
        return $cache->remember(CacheService::CONTENT, 'k', null, function () use (&$calls) {
            return ++$calls;
        });
    };
    $read();
    $read();

    $this->post('/admin/cache/clear')->assertRedirect()->assertSessionHas('status');

    expect($read())->toBe(2);
});
