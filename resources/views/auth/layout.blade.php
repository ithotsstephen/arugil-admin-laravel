<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --accent: #2563eb;
            --text: #e2e8f0;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, -apple-system, sans-serif; background: radial-gradient(circle at top, #1f2937, #0f172a); color: var(--text); min-height: 100vh; display: grid; place-items: center; }
        .card { width: 420px; background: var(--card); border: 1px solid rgba(148,163,184,0.2); border-radius: 16px; padding: 28px; box-shadow: 0 24px 50px rgba(0,0,0,0.35); }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0 0 20px; color: var(--muted); }
        label { display: block; margin-bottom: 6px; font-size: 13px; color: var(--muted); }
        input { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(148,163,184,0.3); background: #0b1220; color: var(--text); margin-bottom: 14px; }
        button { width: 100%; padding: 12px 14px; border: none; border-radius: 10px; background: var(--accent); color: #fff; font-weight: 600; cursor: pointer; }
        .error { color: #f87171; font-size: 13px; margin-bottom: 10px; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
