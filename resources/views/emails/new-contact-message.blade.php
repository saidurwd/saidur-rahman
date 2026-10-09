<x-mail::message>
# New message

**{{ $contact->name }}**@if ($contact->company) · {{ $contact->company }}@endif<br>
{{ $contact->email }}

@if ($contact->subject)
**Subject:** {{ $contact->subject }}
@endif

<x-mail::panel>
{{ $contact->body }}
</x-mail::panel>

<x-mail::button :url="$inboxUrl">
Open in the inbox
</x-mail::button>

Replying to this email goes straight to {{ $contact->name }}.
</x-mail::message>
