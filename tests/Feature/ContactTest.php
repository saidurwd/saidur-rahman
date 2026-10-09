<?php

use App\Enums\MessageStatus;
use App\Mail\ContactAutoReply;
use App\Mail\NewContactMessage;
use App\Models\Message;
use App\Services\FormToken;
use App\Services\SettingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
    app(SettingService::class)->setMany(['site_name' => 'Saidur Rahman', 'contact_email' => 'hello@saidur.test']);
});

function contactData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Jane Recruiter',
        'email' => 'jane@example.com',
        'company' => 'Acme Search',
        'subject' => 'Head of IT role',
        'message' => 'We have a senior IT leadership role in Doha and would like to talk.',
        'website' => '',
        'form_token' => Crypt::encryptString((string) now()->subMinute()->getTimestamp()),
    ], $overrides);
}

describe('the form', function () {
    it('renders with a token, a hidden honeypot and labelled fields', function () {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('name="form_token"', false)
            ->assertSee('name="website"', false)
            ->assertSee('tabindex="-1"', false)
            ->assertSee('<label for="email"', false)
            ->assertSee('hello@saidur.test')
            ->assertDontSee('cf-turnstile');
    });

    it('shows the Turnstile widget only when keys are configured', function () {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret']);

        $this->get('/contact')->assertSee('class="cf-turnstile"', false)->assertSee('data-sitekey="site-key"', false)->assertSee('challenges.cloudflare.com/turnstile', false);
    });

    it('issues a fresh token that is accepted after a few seconds', function () {
        $tokens = app(FormToken::class);
        $token = $tokens->issue();

        expect($tokens->check($token))->toBe('too_fast');
        $this->travel(5)->seconds();
        expect($tokens->check($token))->toBe('ok');
        $this->travel(2)->days();
        expect($tokens->check($token))->toBe('invalid')->and($tokens->check('garbage'))->toBe('invalid')->and($tokens->check(null))->toBe('invalid');
    });
});

describe('sending', function () {
    it('saves the message and queues a notification to me and an auto-reply to the sender', function () {
        $this->withHeader('User-Agent', 'Mozilla/5.0 Test')
            ->post('/contact', contactData())
            ->assertRedirect('/contact')
            ->assertSessionHas('contact_sent');

        $message = Message::firstOrFail();
        expect($message->status)->toBe(MessageStatus::New)
            ->and($message->name)->toBe('Jane Recruiter')
            ->and($message->email)->toBe('jane@example.com')
            ->and($message->company)->toBe('Acme Search')
            ->and($message->body)->toContain('senior IT leadership role')
            ->and($message->ip)->not->toBeNull()
            ->and($message->user_agent)->toBe('Mozilla/5.0 Test');

        Mail::assertQueued(NewContactMessage::class, fn ($mail) => $mail->hasTo('hello@saidur.test') && $mail->hasReplyTo('jane@example.com'));
        Mail::assertQueued(ContactAutoReply::class, fn ($mail) => $mail->hasTo('jane@example.com'));
        Mail::assertQueuedCount(2);
    });

    it('shows a thank-you after sending', function () {
        $this->post('/contact', contactData());

        $this->get('/contact')->assertSee('Message sent')->assertDontSee('name="form_token"', false);
    });

    it('falls back to the mail from address when no contact email is set', function () {
        app(SettingService::class)->set('contact_email', null);
        config(['mail.from.address' => 'from@saidur.test']);

        $this->post('/contact', contactData());

        Mail::assertQueued(NewContactMessage::class, fn ($mail) => $mail->hasTo('from@saidur.test'));
    });

    it('skips the auto-reply when it is switched off', function () {
        app(SettingService::class)->set('contact_autoreply', false);

        $this->post('/contact', contactData());

        Mail::assertQueued(NewContactMessage::class);
        Mail::assertNotQueued(ContactAutoReply::class);
    });

    it('renders both emails with the message and a link to the inbox', function () {
        $message = Message::factory()->create(['name' => 'Jane Recruiter', 'subject' => 'Head of IT role', 'body' => 'Hello there, quite a long message.']);

        $new = new NewContactMessage($message);
        expect($new->envelope()->subject)->toBe('New message: Head of IT role')
            ->and($new->render())->toContain('Hello there, quite a long message.')->toContain(route('admin.messages.show', $message));

        $reply = new ContactAutoReply($message);
        expect($reply->render())->toContain('Thanks, Jane')->toContain('Hello there, quite a long message.')->toContain('Saidur Rahman');
    });
});

describe('validation', function () {
    it('requires the essentials', function () {
        $this->post('/contact', contactData(['name' => '', 'email' => 'nope', 'message' => 'short']))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        expect(Message::count())->toBe(0);
        Mail::assertNothingQueued();
    });

    it('keeps the typed values and shows the errors on the page', function () {
        $this->from('/contact')->post('/contact', contactData(['email' => 'nope']))->assertRedirect('/contact');

        $this->get('/contact')->assertSee('value="Jane Recruiter"', false)->assertSee('aria-invalid="true"', false);
    });

    it('rejects an expired or tampered form token', function () {
        $this->post('/contact', contactData(['form_token' => 'garbage']))->assertSessionHasErrors('form_token');
        $this->post('/contact', contactData(['form_token' => Crypt::encryptString((string) now()->subDays(3)->getTimestamp())]))->assertSessionHasErrors('form_token');
        $this->post('/contact', contactData(['form_token' => '']))->assertSessionHasErrors('form_token');

        expect(Message::count())->toBe(0);
    });

    it('strips line breaks from single-line fields so mail headers cannot be injected', function () {
        $this->post('/contact', contactData([
            'name' => "Jane\r\nBcc: victim@example.com",
            'subject' => "Hello\r\nBcc: victim@example.com\r\n",
            'email' => 'jane@example.com',
        ]))->assertRedirect();

        $message = Message::firstOrFail();
        expect($message->name)->not->toContain("\n")->and($message->subject)->not->toContain("\n")->and($message->subject)->toBe('Hello Bcc: victim@example.com');
        Mail::assertQueued(NewContactMessage::class, fn ($mail) => ! str_contains($mail->envelope()->subject, "\n"));
    });

    it('limits field lengths', function () {
        $this->post('/contact', contactData(['name' => str_repeat('a', 200), 'message' => str_repeat('a', 6000)]))->assertSessionHasErrors(['name', 'message']);
    });
});

describe('spam defences', function () {
    it('drops submissions that fill the honeypot, and still says thank you', function () {
        $this->post('/contact', contactData(['website' => 'http://spam.example']))->assertRedirect('/contact')->assertSessionHas('contact_sent');

        expect(Message::count())->toBe(0);
        Mail::assertNothingQueued();
    });

    it('drops submissions that arrive faster than a person can type', function () {
        $this->post('/contact', contactData(['form_token' => app(FormToken::class)->issue()]))->assertSessionHas('contact_sent');

        expect(Message::count())->toBe(0);
        Mail::assertNothingQueued();
    });

    it('files link-stuffed messages under spam and sends no email', function () {
        $this->post('/contact', contactData(['message' => 'Buy now http://a.example http://b.example https://c.example and www.d.example today']))->assertRedirect();

        expect(Message::firstOrFail()->status)->toBe(MessageStatus::Spam);
        Mail::assertNothingQueued();
    });

    it('allows a couple of legitimate links', function () {
        $this->post('/contact', contactData(['message' => 'See my profile https://linkedin.com/in/jane and https://acme.example/job for the role.']));

        expect(Message::firstOrFail()->status)->toBe(MessageStatus::New);
    });

    it('stores a repeated submission once', function () {
        $this->post('/contact', contactData());
        $this->post('/contact', contactData())->assertSessionHas('contact_sent');

        expect(Message::count())->toBe(1);
        Mail::assertQueuedCount(2);
    });

    it('rate limits to three submissions a minute per address', function () {
        foreach (range(1, 3) as $i) {
            $this->post('/contact', contactData(['message' => "Message number {$i} with enough words"]))->assertRedirect();
        }

        $this->post('/contact', contactData(['message' => 'Message number four with enough words']))->assertStatus(429);
        expect(Message::count())->toBe(3);
    });
});

describe('turnstile', function () {
    beforeEach(function () {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret']);
    });

    it('accepts a verified token', function () {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->post('/contact', contactData(['cf-turnstile-response' => 'good-token']))->assertSessionHas('contact_sent');

        expect(Message::count())->toBe(1);
        Http::assertSent(fn ($request) => $request['secret'] === 'secret' && $request['response'] === 'good-token');
    });

    it('rejects a missing token without calling Cloudflare', function () {
        Http::fake();

        $this->post('/contact', contactData())->assertSessionHasErrors('cf-turnstile-response');

        Http::assertNothingSent();
        expect(Message::count())->toBe(0);
    });

    it('rejects a token Cloudflare says is bad', function () {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->post('/contact', contactData(['cf-turnstile-response' => 'bad']))->assertSessionHasErrors('cf-turnstile-response');

        expect(Message::count())->toBe(0);
    });

    it('fails closed when Cloudflare cannot be reached', function () {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->post('/contact', contactData(['cf-turnstile-response' => 'token']))->assertSessionHasErrors('cf-turnstile-response');

        expect(Message::count())->toBe(0);
    });
});
