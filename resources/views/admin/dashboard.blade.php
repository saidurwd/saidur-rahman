@extends('admin.layouts.admin', ['breadcrumbs' => ['Dashboard' => null]])

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="Page views (30 days)" :value="number_format($stats['views'])" icon="graph-up" /></div>
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="Unread messages" :value="$stats['messages']" icon="envelope" tone="info" :href="Route::has('admin.messages.index') ? route('admin.messages.index') : null" /></div>
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="CV downloads" :value="number_format($stats['downloads'])" icon="download" tone="success" /></div>
        <div class="col-sm-6 col-lg-3"><x-admin.stat label="Published projects" :value="$stats['projects']" icon="kanban" tone="warning" /></div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <x-admin.card title="Views, last 30 days">
                <p class="text-body-secondary mb-0">Chart arrives with the analytics work in Phase 7.</p>
            </x-admin.card>
        </div>
        <div class="col-lg-5">
            <x-admin.card title="Latest drafts" flush>
                <x-admin.table empty="No drafts.">
                    @foreach ($drafts as $post)
                        <tr><td>{{ $post->title }}</td><td class="text-end"><x-admin.status-badge :status="$post->status" /></td></tr>
                    @endforeach
                </x-admin.table>
            </x-admin.card>
            <x-admin.card title="Latest messages" flush>
                <x-admin.table empty="No messages yet.">
                    @foreach ($latestMessages as $message)
                        <tr><td>{{ $message->name }}<div class="small text-body-secondary">{{ $message->subject }}</div></td><td class="text-end"><x-admin.status-badge :status="$message->status" /></td></tr>
                    @endforeach
                </x-admin.table>
            </x-admin.card>
        </div>
    </div>
@endsection
