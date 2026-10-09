@extends('site.layouts.app')

@section('seo')
    <x-site.seo title="Styleguide" description="Design system reference." :noindex="true" />
@endsection

@php
    $paletteCols = ['Brand' => 'lg:grid-cols-4', 'Support' => 'lg:grid-cols-3'];
    $palette = [
        'Brand' => [
            ['Ink', '#0F1B2D', 'bg-ink', 'Base in dark mode, text in light'],
            ['Paper', '#F7F5F0', 'bg-paper', 'Base in light mode, text in dark'],
            ['Tea leaf', '#2F7D5B', 'bg-leaf', 'The one accent: actions, links, active states'],
            ['Brass', '#B08D57', 'bg-brass', 'Small highlights only: eyebrows, rules, the monogram curve'],
        ],
        'Support' => [
            ['Ink soft', '#15243A', 'bg-ink-soft', 'Dark surface'],
            ['Ink raised', '#1B2D49', 'bg-ink-raised', 'Dark lines'],
            ['Paper deep', '#ECE9E1', 'bg-paper-deep', 'Light dividers'],
            ['Leaf bright', '#5FB28D', 'bg-leaf-bright', 'Accent on dark'],
            ['Leaf deep', '#1F5A40', 'bg-leaf-deep', 'Pressed accent'],
            ['Brass deep', '#7A5F2E', 'bg-brass-deep', 'Brass text on light'],
        ],
    ];
    $semantic = ['bg' => 'Page', 'surface' => 'Cards', 'fg' => 'Text', 'muted' => 'Secondary text', 'line' => 'Rules', 'accent' => 'Accent', 'highlight' => 'Highlight'];
@endphp

@section('content')
<div class="wrap py-12 md:py-16">
    <header class="blueprint pb-16">
        <p class="eyebrow mb-5">Design system</p>
        <h1 class="text-display">The Architect's Ledger</h1>
        <p class="mt-6 max-w-2xl text-lg text-muted">An editorial, blueprint-inspired look: calm and confident, closer to an annual report than a portfolio template. Toggle dark mode in the header to check both themes.</p>
        <nav class="mt-8 flex flex-wrap gap-x-6 gap-y-2 label" aria-label="Sections">
            @foreach (['colour' => 'Colour', 'type' => 'Type', 'buttons' => 'Buttons & links', 'cards' => 'Cards & tags', 'headings' => 'Section headings', 'grid' => 'Blueprint grid', 'timeline' => 'Timeline', 'motion' => 'Motion'] as $id => $label)
                <a href="#{{ $id }}" class="no-underline hover:text-accent">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    {{-- Colour --}}
    <section id="colour" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="01" title="Colour">One accent, one highlight. Every colour in the interface comes from these tokens.</x-site.section-heading>

        @foreach ($palette as $group => $swatches)
            <p class="label mb-4 mt-10">{{ $group }}</p>
            <div class="grid gap-4 sm:grid-cols-2 {{ $paletteCols[$group] }}">
                @foreach ($swatches as [$name, $hex, $class, $note])
                    <div>
                        <div class="{{ $class }} h-24 rounded border border-line"></div>
                        <p class="mt-3 font-medium">{{ $name }} <span class="label ml-1">{{ $hex }}</span></p>
                        <p class="text-sm text-muted">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach

        <p class="label mb-4 mt-10">Semantic tokens (switch with the theme)</p>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
            @foreach ($semantic as $token => $use)
                <div>
                    <div class="h-16 rounded border border-line" style="background: var(--{{ $token }})"></div>
                    <p class="mt-2 font-mono text-xs">--{{ $token }}</p>
                    <p class="text-xs text-muted">{{ $use }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-8 max-w-2xl text-sm text-muted">Accessibility: body text and muted text meet WCAG AA on both backgrounds. Brass is for decoration and large text only; use brass deep (light) or the lightened highlight (dark) when it carries words.</p>
    </section>

    {{-- Type --}}
    <section id="type" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="02" title="Type">Fraunces for headings, Inter for reading, JetBrains Mono for labels, dates and tech tags. All self-hosted.</x-site.section-heading>

        <div class="mt-10 space-y-8">
            @foreach ([
                ['text-display', 'Display', 'I lead IT and I still build the systems.'],
                ['text-h1', 'Heading 1', 'Enterprise systems, delivered'],
                ['text-h2', 'Heading 2', 'From problem to production'],
                ['text-h3', 'Heading 3', 'Architecture and outcomes'],
            ] as [$class, $name, $sample])
                <div class="grid gap-2 md:grid-cols-[9rem_1fr] md:gap-8">
                    <p class="label pt-2">{{ $name }}</p>
                    <p class="font-serif {{ $class }}">{{ $sample }}</p>
                </div>
            @endforeach
            <div class="grid gap-2 md:grid-cols-[9rem_1fr] md:gap-8">
                <p class="label pt-1">Body</p>
                <p class="max-w-2xl">Twenty-one years of running enterprise IT has taught me that systems fail at the seams between people, process and technology. Case studies on this site read like consulting reports for that reason: context, challenge, my role, architecture, outcome.</p>
            </div>
            <div class="grid gap-2 md:grid-cols-[9rem_1fr] md:gap-8">
                <p class="label pt-1">Muted</p>
                <p class="max-w-2xl text-muted">Secondary text for captions, bylines and supporting detail.</p>
            </div>
            <div class="grid gap-2 md:grid-cols-[9rem_1fr] md:gap-8">
                <p class="label pt-1">Mono label</p>
                <p><span class="label">Jan 2018 — Present</span> &nbsp; <span class="font-mono text-sm">Laravel · MySQL · Redis</span></p>
            </div>
        </div>
    </section>

    {{-- Buttons and links --}}
    <section id="buttons" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="03" title="Buttons & links" />

        <div class="mt-10 flex flex-wrap items-center gap-4">
            <x-site.button href="#">View work</x-site.button>
            <x-site.button href="#" variant="outline">Download CV</x-site.button>
            <x-site.button href="#" variant="quiet">Read the case study →</x-site.button>
        </div>
        <p class="mt-8 max-w-2xl">Inline links look like <a href="#" class="link">this</a>, with a thin underline that thickens on hover. Keyboard focus shows a tea-leaf outline, try tabbing through this page.</p>
    </section>

    {{-- Cards and tags --}}
    <section id="cards" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="04" title="Cards & tags">Flat and ruled, like a page in a report. A tea-leaf rule slides in on hover for linked cards.</x-site.section-heading>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <a href="#cards" class="card block no-underline" data-reveal>
                <p class="label mb-3">Case study · 2023</p>
                <h3 class="font-serif text-h3">Hospital management system</h3>
                <p class="mt-2 text-muted">A linked card. Hover or focus it to see the accent rule.</p>
                <div class="mt-5 flex flex-wrap gap-2"><x-site.tag>Laravel</x-site.tag><x-site.tag>MySQL</x-site.tag><x-site.tag :accent="true">Featured</x-site.tag></div>
            </a>
            <div class="card" data-reveal>
                <p class="label mb-3">Static card</p>
                <h3 class="font-serif text-h3">Capability pillar</h3>
                <p class="mt-2 text-muted">A plain card for content that is not a link.</p>
            </div>
        </div>
    </section>

    {{-- Section headings --}}
    <section id="headings" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="05" title="Section headings">An eyebrow with a brass rule, a serif title and an optional lede.</x-site.section-heading>
        <div class="mt-10 rounded border border-line bg-surface p-8">
            <x-site.section-heading eyebrow="Selected work" title="Systems I built and led">Three flagship projects, written up like consulting reports.</x-site.section-heading>
        </div>
    </section>

    {{-- Blueprint grid --}}
    <section id="grid" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="06" title="Blueprint grid">Fine grid lines fade out from the centre. Connector lines are drawn on load in the hero.</x-site.section-heading>

        <div class="blueprint mt-10 overflow-hidden rounded border border-line">
            <svg class="draw mx-auto block w-full max-w-3xl text-accent" viewBox="0 0 640 220" fill="none" stroke="currentColor" stroke-width="1.5" role="img" aria-label="Connector diagram">
                <path pathLength="1" d="M60 110H200" />
                <path pathLength="1" d="M200 110C260 110 260 50 320 50H440" />
                <path pathLength="1" d="M200 110C260 110 260 170 320 170H440" />
                <path pathLength="1" d="M440 50H580M440 170H580" />
                <g fill="var(--color-bg)">
                    <circle cx="60" cy="110" r="9" /><circle cx="200" cy="110" r="9" />
                    <circle cx="440" cy="50" r="9" /><circle cx="440" cy="170" r="9" />
                    <circle cx="580" cy="50" r="9" /><circle cx="580" cy="170" r="9" />
                </g>
            </svg>
        </div>
    </section>

    {{-- Timeline --}}
    <section id="timeline" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="07" title="Career as a system map">A vertical connector with a node per role. Each node expands to show scope and achievements.</x-site.section-heading>

        <ol class="timeline mt-12 max-w-2xl">
            <x-site.timeline-node role="Head of IT" company="Sample Company" period="2018 — Present" :current="true" :open="true">
                <p>Leads IT strategy, infrastructure, security and in-house systems.</p>
                <ul class="mt-3 list-disc space-y-1 pl-5"><li>Sample achievement one.</li><li>Sample achievement two.</li></ul>
            </x-site.timeline-node>
            <x-site.timeline-node role="IT Manager" company="Sample Company" period="2008 — 2017">
                <p>Ran enterprise IT operations and ERP rollouts.</p>
            </x-site.timeline-node>
            <x-site.timeline-node role="Systems Engineer" company="Sample Company" period="2004 — 2008">
                <p>Built and supported internal systems.</p>
            </x-site.timeline-node>
        </ol>
    </section>

    {{-- Motion --}}
    <section id="motion" class="scroll-mt-24 border-t border-line py-14">
        <x-site.section-heading eyebrow="08" title="Motion">Subtle and optional.</x-site.section-heading>
        <ul class="mt-8 max-w-2xl list-disc space-y-2 pl-5 text-muted">
            <li>Fade-up on scroll for sections and cards, switched off when the visitor prefers reduced motion.</li>
            <li>Header shrinks and gains a rule after a short scroll.</li>
            <li>Connector lines draw on load; the timeline expands without animation.</li>
            <li>No carousels, no auto-playing media, no skill percentage bars.</li>
        </ul>
    </section>
</div>
@endsection
