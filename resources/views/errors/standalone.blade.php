{{-- Self-contained error page: no database, no Vite build, so it still renders when those are what failed. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $heading }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { color-scheme: light dark; --bg:#f7f5f0; --fg:#0f1b2d; --muted:#556075; --line:#d9d5c9; --accent:#2f7d5b; --brass:#7a5f2e; }
        @media (prefers-color-scheme: dark) { :root { --bg:#0f1b2d; --fg:#f1eee6; --muted:#9aa7bb; --line:#26385a; --accent:#5fb28d; --brass:#c9a56a; } }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:1.5rem; background:var(--bg); color:var(--fg);
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; line-height:1.6; }
        main { max-width:34rem; width:100%; background-image:linear-gradient(var(--line) 1px, transparent 1px),linear-gradient(90deg, var(--line) 1px, transparent 1px); background-size:2rem 2rem; background-position:-1px -1px; padding:0; }
        .panel { background:var(--bg); margin:2rem 0 2rem 2rem; padding:2rem 1.5rem 2rem 0; }
        .code { font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size:.8rem; letter-spacing:.12em; text-transform:uppercase; color:var(--brass); }
        h1 { font-family: 'Iowan Old Style', 'Palatino Linotype', Georgia, serif; font-weight:500; font-size:clamp(2rem, 6vw, 3rem); line-height:1.1; margin:.75rem 0 1rem; }
        p { color:var(--muted); margin:0 0 1.5rem; }
        a { display:inline-block; background:var(--accent); color:var(--bg); padding:.7rem 1.25rem; border-radius:.25rem; text-decoration:none; font-weight:500; }
        a:focus-visible { outline:2px solid var(--accent); outline-offset:3px; }
    </style>
</head>
<body>
<main>
    <div class="panel">
        <p class="code" style="margin:0">Error {{ $code }}</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        <a href="/">Back to the home page</a>
    </div>
</main>
</body>
</html>
