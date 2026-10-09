<x-mail::message>
# Thanks, {{ \Illuminate\Support\Str::before($contact->name, ' ') }}

Your message reached me. I read everything that comes in and will get back to you personally, usually within a few working days.

For your records, this is what you sent:

<x-mail::panel>
{{ $contact->body }}
</x-mail::panel>

If you did not send this message, you can ignore this email.

{{ $siteName }}
</x-mail::message>
