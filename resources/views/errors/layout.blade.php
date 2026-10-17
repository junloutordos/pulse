<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') - PULSE</title>
<style>
    :root { --bg:#eef1f7; --ink:#0f1b3a; --muted:#546078; --accent:#2347c5; --accent-ink:#fff; --surface:#fff; --line:#d1d9e8; }
    @media (prefers-color-scheme: dark) { :root { --bg:#090f20; --ink:#e6ecfa; --muted:#8f9dbd; --accent:#7fa2ff; --accent-ink:#08101f; --surface:#111b35; --line:#1f2c4f; } }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; display:grid; place-items:center; background:var(--bg); color:var(--ink); font-family: "Hanken Grotesk", system-ui, -apple-system, "Segoe UI", sans-serif; line-height:1.5; padding:24px; }
    main { max-width: 28rem; text-align:left; }
    .code { font-size: 4.5rem; font-weight: 800; letter-spacing: -0.04em; line-height: 1; margin: 0 0 8px; color: var(--accent); }
    h1 { font-size: 1.75rem; margin: 0 0 8px; letter-spacing: -0.02em; }
    p { margin: 0 0 20px; color: var(--muted); }
    a { display:inline-block; background:var(--accent); color:var(--accent-ink); text-decoration:none; font-weight:700; padding:10px 18px; border-radius:6px; }
    a:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
</style>
</head>
<body>
<main>
    <p class="code" aria-hidden="true">@yield('code')</p>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ url('/') }}">Back to PULSE</a>
</main>
</body>
</html>
