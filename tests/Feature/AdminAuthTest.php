<?php

use App\Actions\Auth\CompleteAdminLogin;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->withoutVite();
    RateLimiter::clear('x');
});

function adminWithTwoFactor(array $attributes = []): User
{
    $secret = app(TwoFactorService::class)->generateSecret();

    return User::factory()->create(array_merge([
        'password' => 'correct-horse-Battery9',
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => json_encode(['aaaaa-bbbbb', 'ccccc-ddddd']),
        'two_factor_confirmed_at' => now(),
    ], $attributes));
}

function totp(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);
}

it('redirects guests from every admin page to the login', function (string $path) {
    $this->get($path)->assertRedirect('/admin/login');
})->with(['/admin', '/admin/password', '/admin/two-factor', '/admin/two-factor/challenge']);

it('has no registration route', function () {
    $this->get('/admin/register')->assertNotFound();
    $this->get('/register')->assertNotFound();
});

it('rejects bad credentials', function () {
    $user = adminWithTwoFactor();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('throttles login to five attempts per minute', function () {
    $user = adminWithTwoFactor();

    foreach (range(1, 5) as $i) {
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    // Even the correct password is refused once locked out.
    $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
    expect(session('errors')->first('email'))->toContain('Too many');
});

it('sends a signed-in user without 2FA to the setup page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/two-factor');
    $this->actingAs($user)->get('/admin/password')->assertRedirect('/admin/two-factor');
});

it('requires the 2FA challenge after password login', function () {
    $user = adminWithTwoFactor();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9'])
        ->assertRedirect();
    $this->assertAuthenticatedAs($user);

    $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
    $this->get('/admin/two-factor/challenge')->assertOk()->assertSee('Two-factor check');
});

it('lets the user in with a valid code', function () {
    $user = adminWithTwoFactor();
    $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9']);

    $this->post('/admin/two-factor/challenge', ['code' => totp($user)])->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertSee('Dashboard');
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects an invalid code', function () {
    $user = adminWithTwoFactor();
    $this->actingAs($user)->post('/admin/two-factor/challenge', ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
});

it('accepts a recovery code once', function () {
    $user = adminWithTwoFactor();

    $this->actingAs($user)->post('/admin/two-factor/challenge', ['recovery_code' => 'aaaaa-bbbbb'])
        ->assertRedirect('/admin');
    expect($user->fresh()->recoveryCodes())->toBe(['ccccc-ddddd']);

    $this->flushSession();
    $this->actingAs($user->fresh())->post('/admin/two-factor/challenge', ['recovery_code' => 'aaaaa-bbbbb'])
        ->assertSessionHasErrors('recovery_code');
});

it('throttles two-factor attempts', function () {
    $user = adminWithTwoFactor();

    foreach (range(1, 5) as $i) {
        $this->actingAs($user)->post('/admin/two-factor/challenge', ['code' => '000000'])->assertSessionHasErrors('code');
    }

    $this->actingAs($user)->post('/admin/two-factor/challenge', ['code' => totp($user)])
        ->assertSessionHasErrors('code');
    $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
});

it('enables 2FA after confirming a valid code and shows recovery codes once', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/two-factor')->assertOk()->assertSee('<svg', false);

    $secret = session('two_factor_pending_secret');
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->post('/admin/two-factor', ['code' => $code])->assertRedirect('/admin/two-factor');

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->recoveryCodes())->toHaveCount(8);

    $this->get('/admin/two-factor')->assertOk()->assertSee($user->recoveryCodes()[0]);
    $this->get('/admin/two-factor')->assertOk()->assertDontSee($user->recoveryCodes()[0]);
    $this->get('/admin')->assertOk();
});

it('does not enable 2FA with a wrong code', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/two-factor');

    $this->post('/admin/two-factor', ['code' => '123456'])->assertSessionHasErrors('code');
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('does not expose the 2FA secret outside the setup page', function () {
    $user = adminWithTwoFactor();

    expect($user->toArray())->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes'])
        ->and($user->getRawOriginal('two_factor_secret'))->not->toBe($user->two_factor_secret);
});

it('regenerates recovery codes only with the current password', function () {
    $user = adminWithTwoFactor();
    $session = [CompleteAdminLogin::SESSION_KEY => true];

    $this->actingAs($user)->withSession($session)->post('/admin/two-factor/recovery-codes', ['current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');

    $this->post('/admin/two-factor/recovery-codes', ['current_password' => 'correct-horse-Battery9'])
        ->assertRedirect('/admin/two-factor');
    expect($user->fresh()->recoveryCodes())->toHaveCount(8);
});

it('logs out and forgets the 2FA pass', function () {
    $user = adminWithTwoFactor();
    $this->actingAs($user)->withSession([CompleteAdminLogin::SESSION_KEY => true])
        ->post('/admin/logout')->assertRedirect('/admin/login');

    $this->assertGuest();
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('redirects signed-in users away from the login page', function () {
    $this->actingAs(adminWithTwoFactor())->get('/admin/login')->assertRedirect('/admin');
});
