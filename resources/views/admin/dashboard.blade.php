@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => null]])

@section('title', 'Dashboard')

@php
    $trafficChart = [
        'type' => 'line',
        'labels' => $days->pluck('label')->all(),
        'series' => [
            ['name' => 'Page views', 'data' => $days->pluck('views')->all()],
            ['name' => 'Visitors', 'data' => $days->pluck('visitors')->all()],
        ],
    ];
    $cvChart = [
        'type' => 'bar',
        'height' => 220,
        'labels' => $cvVersions->pluck('label')->all(),
        'series' => [
            ['name' => 'Last 30 days', 'data' => $cvVersions->pluck('recent')->all()],
            ['name' => 'All time', 'data' => $cvVersions->pluck('downloads')->all()],
        ],
    ];
@endphp

@section('content')
    @foreach ($systemProblems as $problem)
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert"><i class="bi bi-exclamation-triangle-fill mt-1"></i><div>{{ $problem }}</div></div>
    @endforeach

    <div class="row">
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="Page views (30 days)" :value="number_format($stats['views'])" icon="graph-up" /></div>
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="Daily visitors, summed" :value="number_format($stats['visitors'])" icon="people" tone="success" /></div>
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="Unread messages" :value="$stats['unread']" icon="envelope" tone="info" :href="route('admin.messages.index', ['folder' => 'unread'])" /></div>
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="CV downloads (30 days)" :value="number_format($stats['downloads'])" icon="download" tone="warning" /></div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card title="Traffic, last 30 days">
                <div data-chart="{{ json_encode($trafficChart) }}"
                     role="img" aria-label="Line chart of page views and visitors per day over the last 30 days. {{ number_format($stats['views']) }} page views in total."></div>
                <details class="mt-3">
                    <summary class="small text-body-secondary">View as a table</summary>
                    <div class="table-responsive mt-2" style="max-height: 16rem">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Day</th><th class="text-end">Views</th><th class="text-end">Visitors</th></tr></thead>
                            <tbody>@foreach ($days->reverse() as $day)<tr><td>{{ $day['label'] }}</td><td class="text-end">{{ $day['views'] }}</td><td class="text-end">{{ $day['visitors'] }}</td></tr>@endforeach</tbody>
                        </table>
                    </div>
                </details>
                <p class="small text-body-secondary mt-3 mb-0">Counted without cookies. Visitors are people seen on a given day; bots and visitors who send Do Not Track are left out.</p>
            </x-admin.card>

            <x-admin.card title="Top pages" flush>
                <x-admin.table :headers="['Page', 'Views']" empty="No page views recorded yet.">
                    @foreach ($topPages as $page)
                        <tr><td><a href="{{ url($page->path) }}" target="_blank" rel="noopener" class="text-decoration-none">{{ $page->path }}</a></td><td class="text-end">{{ number_format($page->views) }}</td></tr>
                    @endforeach
                </x-admin.table>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="CV downloads by version">
                @if ($cvVersions->isEmpty())
                    <p class="text-body-secondary mb-0">No CV uploaded yet. <a href="{{ route('admin.cv-files.create') }}">Upload one</a>.</p>
                @else
                    <div data-chart="{{ json_encode($cvChart) }}"
                         role="img" aria-label="Bar chart of CV downloads by version"></div>
                    <ul class="list-unstyled small mt-2 mb-0">
                        @foreach ($cvVersions as $cv)
                            <li class="d-flex justify-content-between"><span>{{ $cv->label }} @unless ($cv->is_active)<span class="text-body-secondary">(inactive)</span>@endunless</span><span>{{ $cv->recent }} / {{ number_format($cv->downloads) }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            <x-admin.card title="Where visitors come from" flush>
                <x-admin.table :headers="['Site', 'Views']" empty="No referrals yet.">
                    @foreach ($referrers as $referrer)<tr><td>{{ $referrer->referrer }}</td><td class="text-end">{{ number_format($referrer->views) }}</td></tr>@endforeach
                </x-admin.table>
            </x-admin.card>

            <x-admin.card title="Latest messages" flush>
                <x-admin.table empty="No messages yet.">
                    @foreach ($latestMessages as $message)
                        <tr>
                            <td><a href="{{ route('admin.messages.show', $message) }}" class="text-decoration-none">{{ $message->name }}</a><div class="small text-body-secondary">{{ $message->subject ?: \Illuminate\Support\Str::limit($message->body, 40) }}</div></td>
                            <td class="text-end"><x-admin.status-badge :status="$message->status" /></td>
                        </tr>
                    @endforeach
                </x-admin.table>
            </x-admin.card>

            <x-admin.card title="Latest drafts" flush>
                <x-admin.table empty="No drafts.">
                    @foreach ($drafts as $post)
                        <tr><td><a href="{{ route('admin.posts.edit', $post) }}" class="text-decoration-none">{{ $post->title }}</a></td><td class="text-end"><x-admin.status-badge :status="$post->status" /></td></tr>
                    @endforeach
                </x-admin.table>
            </x-admin.card>
        </div>
    </div>
@endsection
