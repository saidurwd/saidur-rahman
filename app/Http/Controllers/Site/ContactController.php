<?php

namespace App\Http\Controllers\Site;

use App\Actions\StoreContactMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\FormToken;
use App\Services\SeoService;
use App\Services\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(FormToken $tokens, Turnstile $turnstile, SeoService $seo): View
    {
        return view('site.contact.show', [
            'formToken' => $tokens->issue(),
            'turnstileKey' => $turnstile->siteKey(),
            'schema' => $seo->breadcrumbs([['Contact', null]]),
        ]);
    }

    public function store(ContactRequest $request, FormToken $tokens, StoreContactMessage $store): RedirectResponse
    {
        $done = redirect()->route('contact')->with('contact_sent', true);

        // Bots get the same "thank you" as people, so they learn nothing about why they were dropped.
        if (filled($request->input('website'))) {
            Log::info('Contact form dropped: honeypot filled.', ['ip' => $request->ip()]);

            return $done;
        }

        $token = $tokens->check($request->input('form_token'));

        if ($token === 'invalid') {
            throw ValidationException::withMessages(['form_token' => 'This form has expired. Please reload the page and try again.']);
        }

        if ($token === 'too_fast') {
            Log::info('Contact form dropped: submitted too fast.', ['ip' => $request->ip()]);

            return $done;
        }

        // A double click or a re-sent form should not create (or email) the same message twice.
        $fingerprint = 'contact.'.sha1($request->string('email')->toString().'|'.$request->string('message')->toString());
        if (! Cache::add($fingerprint, 1, now()->addMinutes(10))) {
            return $done;
        }

        $store($request->safe()->only(['name', 'email', 'company', 'subject', 'message']), $request->ip(), $request->userAgent());

        return $done;
    }
}
