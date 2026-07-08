<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'AutoSync Hub' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7f9;
            --surface: #ffffff;
            --text: #1d2433;
            --muted: #687385;
            --line: #dce1e8;
            --accent: #1f7a5c;
            --danger: #b42318;
            --warning: #9a6700;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        header { background: var(--surface); border-bottom: 1px solid var(--line); }
        nav, main { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
        nav { min-height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        a { color: var(--accent); text-decoration: none; }
        h1 { margin: 28px 0 8px; font-size: 30px; letter-spacing: 0; }
        h2 { margin: 28px 0 12px; font-size: 20px; letter-spacing: 0; }
        p { color: var(--muted); }
        table { width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { font-size: 12px; text-transform: uppercase; color: var(--muted); background: #eef2f6; }
        tr:last-child td { border-bottom: 0; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 18px; }
        .label { display: block; color: var(--muted); font-size: 12px; text-transform: uppercase; margin-bottom: 4px; }
        .badge { display: inline-flex; align-items: center; min-height: 24px; padding: 3px 8px; border-radius: 999px; background: #e9f5ef; color: var(--accent); font-size: 12px; font-weight: 700; }
        .badge.failed, .badge.rejected { background: #fff0ee; color: var(--danger); }
        .badge.processing, .badge.pending, .badge.requires_action { background: #fff8e5; color: var(--warning); }
        .actions { display: flex; align-items: center; gap: 10px; margin: 16px 0 22px; }
        button { border: 0; border-radius: 6px; background: var(--accent); color: #fff; padding: 10px 14px; font-weight: 700; cursor: pointer; }
        .muted { color: var(--muted); }
        @media (max-width: 760px) {
            .grid { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <strong>AutoSync Hub</strong>
            <a href="{{ route('vehicles.index') }}">Vehicles</a>
        </nav>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
