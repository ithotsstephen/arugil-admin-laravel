<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #6b7280;
            --primary: #2563eb;
            --border: #e5e7eb;
            --sidebar: #0b1220;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); }
        .layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .sidebar { background: var(--sidebar); color: #e2e8f0; padding: 24px; }
        .sidebar h1 { font-size: 18px; margin: 0 0 24px; }
        .nav a { display: block; color: #e2e8f0; text-decoration: none; padding: 10px 12px; border-radius: 8px; margin-bottom: 6px; font-size: 14px; }
        .nav a:hover { background: rgba(148,163,184,0.15); }
        .content { padding: 28px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: var(--card); border: 1px solid var(--border); padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 16px; }
        .card h3 { margin: 0 0 8px; font-size: 14px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .card p { margin: 0; font-size: 24px; font-weight: 600; }
        .muted { color: var(--muted); }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
        table { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; }
        th { background: #f8fafc; color: var(--muted); font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; background: #e5e7eb; color: #374151; }
        .actions { display: flex; gap: 8px; }
        .btn { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff; cursor: pointer; font-size: 13px; }
        .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .status { margin-bottom: 16px; color: var(--primary); }
        .filters { display: flex; gap: 8px; align-items: center; margin-bottom: 16px; }
        .filters input, .filters select { padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h1>Arugil Admin</h1>
        <nav class="nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            
            @if(auth()->user()->hasRole('super_admin', 'moderator'))
                <a href="{{ route('admin.users.index') }}">Users</a>
                <a href="{{ route('admin.categories.index') }}">Categories</a>
            @endif
            
            <a href="{{ route('admin.businesses.index') }}">Businesses</a>
            
            @if(auth()->user()->hasRole('super_admin', 'moderator'))
                <a href="{{ route('admin.jobs.index') }}">Jobs</a>
                <a href="{{ route('admin.offers.index') }}">Special Offers</a>
                <a href="{{ route('admin.reviews.index') }}">Reviews</a>
                <a href="{{ route('admin.ads.index') }}">Advertisements</a>
                <a href="{{ route('admin.emergency.index') }}">Emergency</a>
                <a href="{{ route('admin.reports.index') }}">Reports</a>
                <a href="{{ route('admin.settings.index') }}">Settings</a>
            @endif
        </nav>
    </aside>
    <main class="content">
        <div class="topbar">
            <div>Signed in as {{ auth()->user()?->email ?? 'Guest' }}</div>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit">Logout</button>
                </form>
            @endauth
        </div>
        @yield('content')
    </main>
</div>
</body>
</html>
