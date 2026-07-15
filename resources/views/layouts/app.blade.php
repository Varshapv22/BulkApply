<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bulk Apply')</title>
    <script>
        // Apply saved theme before first paint to avoid a flash.
        (function () {
            var t = localStorage.getItem('theme');
            if (t === 'light' || t === 'dark') {
                document.documentElement.setAttribute('data-theme', t);
            }
        })();
    </script>
    <style>
        :root, :root[data-theme="light"] {
            --bg: #f4f5f7; --card: #ffffff; --text: #1f2937; --muted: #6b7280;
            --border: #e5e7eb; --input-bg: #ffffff; --hover: #f3f4f6; --hover-strong: #e5e7eb;
            --primary: #4f46e5; --primary-dark: #4338ca;
            --green: #16a34a; --green-bg: #dcfce7; --amber: #b45309; --amber-bg: #fef3c7;
            --red: #dc2626; --red-bg: #fee2e2; --blue-bg: #dbeafe; --blue: #1d4ed8;
        }
        :root[data-theme="dark"] {
            --bg: #0f172a; --card: #1e293b; --text: #e2e8f0; --muted: #94a3b8;
            --border: #334155; --input-bg: #0f172a; --hover: #334155; --hover-strong: #475569;
            --primary: #818cf8; --primary-dark: #6366f1;
            --green: #4ade80; --green-bg: #14532d; --amber: #fbbf24; --amber-bg: #422006;
            --red: #f87171; --red-bg: #450a0a; --blue-bg: #172554; --blue: #93c5fd;
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme]) {
                --bg: #0f172a; --card: #1e293b; --text: #e2e8f0; --muted: #94a3b8;
                --border: #334155; --input-bg: #0f172a; --hover: #334155; --hover-strong: #475569;
                --primary: #818cf8; --primary-dark: #6366f1;
                --green: #4ade80; --green-bg: #14532d; --amber: #fbbf24; --amber-bg: #422006;
                --red: #f87171; --red-bg: #450a0a; --blue-bg: #172554; --blue: #93c5fd;
            }
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg); color: var(--text); }
        a { color: var(--primary); }
        header { background: var(--card); border-bottom: 1px solid var(--border); }
        .nav { max-width: 1000px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; gap: 24px; height: 60px; }
        .brand { font-weight: 700; font-size: 18px; }
        .brand span { color: var(--primary); }
        .nav a.link { color: var(--muted); text-decoration: none; font-weight: 500; padding: 6px 2px; }
        .nav a.link.active { color: var(--text); border-bottom: 2px solid var(--primary); }
        .nav .spacer { flex: 1; }
        .container { max-width: 1000px; margin: 24px auto; padding: 0 20px 60px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .card h2 { margin: 0 0 4px; font-size: 17px; }
        .card p.hint { margin: 0 0 16px; color: var(--muted); font-size: 13px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 12px 0 4px; }
        input[type=text], input[type=email], input[type=url], input[type=tel], textarea, input[type=file] {
            width: 100%; padding: 9px 11px; border: 1px solid var(--border); border-radius: 7px; font-size: 14px; font-family: inherit; background: var(--input-bg); color: var(--text); }
        input::placeholder, textarea::placeholder { color: var(--muted); }
        textarea { resize: vertical; min-height: 90px; }
        .row { display: flex; gap: 16px; flex-wrap: wrap; }
        .row > div { flex: 1; min-width: 200px; }
        .btn { display: inline-block; border: none; border-radius: 7px; padding: 9px 16px; font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-ghost { background: var(--hover); color: var(--text); }
        .btn-ghost:hover { background: var(--hover-strong); }
        .btn-danger { background: transparent; color: var(--red); border: 1px solid transparent; padding: 4px 8px; }
        .btn-danger:hover { background: var(--red-bg); }
        .btn-link { background: none; border: none; color: var(--primary); cursor: pointer; font-size: 13px; padding: 0; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: var(--green-bg); color: var(--green); }
        .alert-error { background: var(--red-bg); color: var(--red); }
        .stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .stat { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 14px 18px; flex: 1; min-width: 120px; }
        .stat .num { font-size: 24px; font-weight: 700; }
        .stat .lbl { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 12px; font-size: 13.5px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
        .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
        .badge.pending { background: var(--hover); color: var(--muted); }
        .badge.queued  { background: var(--blue-bg); color: var(--blue); }
        .badge.sent    { background: var(--green-bg); color: var(--green); }
        .badge.failed  { background: var(--red-bg); color: var(--red); }
        .muted { color: var(--muted); }
        .toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
        .toolbar .spacer { flex: 1; }
        details summary { cursor: pointer; font-weight: 600; font-size: 14px; }
        code { background: var(--hover); color: var(--text); padding: 1px 5px; border-radius: 4px; font-size: 12.5px; }
        .theme-toggle { background: var(--hover); color: var(--text); border: 1px solid var(--border); border-radius: 7px;
            width: 34px; height: 34px; cursor: pointer; font-size: 16px; line-height: 1; display: inline-flex;
            align-items: center; justify-content: center; }
        .theme-toggle:hover { background: var(--hover-strong); }
        .empty { text-align: center; padding: 40px 20px; color: var(--muted); }
        .banner-warn { background: var(--amber-bg); color: var(--amber); }
    </style>
</head>
<body>
    <header>
        <nav class="nav">
            <div class="brand">Bulk<span>Apply</span></div>
            <a class="link {{ request()->routeIs('jobs.*') ? 'active' : '' }}" href="{{ route('jobs.index') }}">Jobs</a>
            <a class="link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">Profile &amp; Template</a>
            <div class="spacer"></div>
            <a class="link" href="http://localhost:8025" target="_blank" rel="noopener">Mailpit inbox ↗</a>
            <button type="button" class="theme-toggle" id="themeToggle" title="Toggle light / dark" aria-label="Toggle light or dark theme">🌙</button>
        </nav>
    </header>

    <div class="container">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Please fix:</strong>
                <ul style="margin: 6px 0 0 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    <script>
        (function () {
            var root = document.documentElement;
            var btn = document.getElementById('themeToggle');

            function current() {
                if (root.getAttribute('data-theme')) return root.getAttribute('data-theme');
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            function paint() {
                // Show the icon for the mode you'd switch TO.
                btn.textContent = current() === 'dark' ? '☀️' : '🌙';
            }
            btn.addEventListener('click', function () {
                var next = current() === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                paint();
            });
            paint();
        })();
    </script>
</body>
</html>
