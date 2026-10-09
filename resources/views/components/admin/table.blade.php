@props(['headers' => [], 'empty' => 'Nothing here yet.'])

<div class="table-responsive">
    <table {{ $attributes->class(['table table-hover align-middle mb-0']) }}>
        @if ($headers)
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @if ($slot->isEmpty())
                <tr><td colspan="{{ max(count($headers), 1) }}" class="text-center text-body-secondary py-4">{{ $empty }}</td></tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>
