<?php

use App\Actions\Auth\CompleteAdminLogin;
use App\Models\User;
use App\Services\SettingService;
use App\Services\TrustedDevices;
use App\Services\TwoFactorService;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->withoutVite();
});

function adminWithApp(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'password' => 'correct-horse-Battery9',
        'two_factor_secret' => app(TwoFactorService::class)->generateSecret(),
        'two_factor_recovery_codes' => json_encode(['aaaaa-bbbbb', 'ccccc-ddddd']),
        'two_factor_confirmed_at' => now(),
    ], $attributes));
}

function currentCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);
}

function trustCookie(User $user): array
{
    $cookie = app(TrustedDevices::class)->cookie($user);

    return [$cookie->getName(), $cookie->getValue()];
}

describe('trusting a browser', function () {
    it('offers the option on the challenge page', function () {
        $user = adminWithApp();
        $this->actingAs($user)->get('/admin/two-factor/challenge')->assertSee('Trust this browser for 30 days');
    });

    it('sets a long-lived, http-only cookie scoped to /admin when asked', function () {
        $user = adminWithApp();

        $response = $this->actingAs($user)->post('/admin/two-factor/challenge', ['code' => currentCode($user), 'remember_device' => 1])->assertRedirect('/admin');
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === TrustedDevices::COOKIE);

        expect($cookie)->not->toBeNull()->and($cookie->isHttpOnly())->toBeTrue()->and($cookie->getPath())->toBe('/admin')
            ->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->getTimestamp())->and($cookie->getExpiresTime())->toBeLessThanOrEqual(now()->addDays(30)->getTimestamp());
    });

    it('sets no cookie when not asked', function () {
        $user = adminWithApp();

        $response = $this->actingAs($user)->post('/admin/two-factor/challenge', ['code' => currentCode($user)])->assertRedirect('/admin');

        expect(collect($response->headers->getCookies())->pluck('name')->all())->not->toContain(TrustedDevices::COOKIE);
    });

    it('lets a trusted browser sign in with the password alone', function () {
        $user = adminWithApp();
        [$name, $value] = trustCookie($user);

        $this->withCookie($name, $value)->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9'])->assertRedirect();

        $this->get('/admin')->assertOk()->assertSee('Dashboard');
        expect($user->fresh()->last_login_at)->not->toBeNull();
    });

    it('still asks for a code from a browser that is not trusted', function () {
        $user = adminWithApp();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9']);

        $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
    });

    it('never trusts a wrong password, even with a valid cookie', function () {
        $user = adminWithApp();
        [$name, $value] = trustCookie($user);

        $this->withCookie($name, $value)->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('stops trusting after the time is up', function () {
        $user = adminWithApp();
        [$name, $value] = trustCookie($user);

        $this->travel(31)->days();
        $this->withCookie($name, $value)->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9']);

        $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
    });

    it('rejects a cookie that was tampered with or belongs to someone else', function () {
        $user = adminWithApp();
        $other = adminWithApp(['email' => 'other@example.com']);
        [$name, $value] = trustCookie($user);
        [, $parts] = [null, explode('|', $value)];
        $forged = $user->id.'|'.(now()->addYears(5)->getTimestamp()).'|'.$parts[2];
        [, $otherValue] = trustCookie($other);

        foreach ([$forged, $otherValue, 'garbage', ''] as $bad) {
            $this->flushSession()->withCookie($name, $bad)->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9']);
            $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
            auth()->logout();
        }
    });

    it('ends the trust when the password changes', function () {
        $user = adminWithApp();
        [$name, $value] = trustCookie($user);
        $user->update(['password' => 'a-brand-New-Pass1']);

        $this->withCookie($name, $value)->post('/admin/login', ['email' => $user->email, 'password' => 'a-brand-New-Pass1']);

        $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
    });

    it('can forget every trusted browser', function () {
        $user = adminWithApp();
        [$name, $value] = trustCookie($user);

        $this->actingAs($user)->withSession([CompleteAdminLogin::SESSION_KEY => true])->post('/admin/two-factor/devices/forget')->assertRedirect()->assertSessionHas('status');
        auth()->logout();

        $this->flushSession()->withCookie($name, $value)->post('/admin/login', ['email' => $user->email, 'password' => 'correct-horse-Battery9']);
        $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
    });
});

describe('turning two-factor off and on', function () {
    beforeEach(function () {
        $this->user = adminWithApp();
        $this->actingAs($this->user)->withSession([CompleteAdminLogin::SESSION_KEY => true]);
    });

    it('shows the controls on the security page', function () {
        $this->get('/admin/two-factor')->assertOk()->assertSee('Turn off two-factor authentication')->assertSee('Forget trusted browsers');
    });

    it('turns it off with the password and a valid code', function () {
        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => currentCode($this->user)])
            ->assertRedirect('/admin/two-factor')->assertSessionHas('warning');

        $user = $this->user->fresh();
        expect($user->hasTwoFactorEnabled())->toBeFalse()->and($user->getRawOriginal('two_factor_secret'))->toBeNull()->and($user->two_factor_recovery_codes)->toBeNull()
            ->and(app(SettingService::class)->get('two_factor_required'))->toBeFalse();
    });

    it('turns it off with a recovery code, which is then used up', function () {
        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'recovery_code' => 'aaaaa-bbbbb'])->assertRedirect();

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeFalse();
    });

    it('refuses without the right password or code', function () {
        $this->post('/admin/two-factor/disable', ['current_password' => 'nope', 'code' => currentCode($this->user)])->assertSessionHasErrors('current_password');
        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9'])->assertSessionHasErrors(['code', 'recovery_code']);

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    });

    it('limits guesses at the code', function () {
        foreach (range(1, 5) as $i) {
            $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => '000000'])->assertSessionHasErrors('code');
        }

        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => currentCode($this->user)])->assertSessionHasErrors('code');
        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    });

    it('needs a verified session to even try', function () {
        $this->flushSession()->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => currentCode($this->user)])->assertRedirect('/admin/two-factor/challenge');

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    });

    it('lets the admin in with just a password afterwards, with a warning, and stops trusting old browsers', function () {
        [$name, $value] = trustCookie($this->user);
        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => currentCode($this->user)]);
        auth()->logout();

        $this->flushSession()->post('/admin/login', ['email' => $this->user->email, 'password' => 'correct-horse-Battery9'])->assertRedirect();

        $this->get('/admin')->assertOk()->assertSee('Two-factor authentication is off');
        $this->get('/admin/two-factor')->assertOk()->assertSee('Set up an authenticator app');
        expect($value)->not->toBeEmpty();
    });

    it('can be turned back on, which makes it required again', function () {
        $this->post('/admin/two-factor/disable', ['current_password' => 'correct-horse-Battery9', 'code' => currentCode($this->user)]);

        $this->get('/admin/two-factor');
        $secret = session('two_factor_pending_secret');
        $this->post('/admin/two-factor', ['code' => app(Google2FA::class)->getCurrentOtp($secret)])->assertRedirect('/admin/two-factor');

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue()->and(app(SettingService::class)->get('two_factor_required'))->toBeTrue();
        auth()->logout();
        $this->flushSession()->post('/admin/login', ['email' => $this->user->email, 'password' => 'correct-horse-Battery9']);
        $this->get('/admin')->assertRedirect('/admin/two-factor/challenge');
    });
});

describe('the requirement', function () {
    it('applies by default: a new admin must set up two-factor first', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertRedirect('/admin/two-factor');
    });

    it('does not apply once it was deliberately turned off', function () {
        app(SettingService::class)->set('two_factor_required', false, 'security');

        $this->actingAs(User::factory()->create())->get('/admin')->assertOk();
    });
});
