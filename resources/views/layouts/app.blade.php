<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'IT Staffer' }}</title>
{{--    <link rel="stylesheet" href="{{ asset('css/app.css') }}">--}}
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/line.css">
    <style>
        :root{
            --bg:#e9f6f3;
            --sidebar:#f3fbf8;
            --sidebar-accent:#d6f0ea;
            --primary:#2eb97f;
            --primary-dark:#22a36d;
            --text:#1f2937;
            --muted:#6b7280;
            --line:#e5e7eb;
            --card:#ffffff;
            --shadow:0 2px 12px rgba(0,0,0,.06);
            --radius:14px;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0;
            background:var(--bg);
            color:var(--text);
            font:14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji','Segoe UI Emoji';
        }
        a{color:#0866c2;text-decoration:none}
        a:hover{text-decoration:underline}

        .app{display:grid;grid-template-columns:260px 1fr;min-height:100vh;gap:16px;padding:12px}

        /* Sidebar */
        .sidebar{
            background:var(--sidebar);
            border:1px solid var(--line);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            padding:14px 10px;
            display:flex;flex-direction:column;
        }
        .logo{display:flex;align-items:center;gap:10px;padding:8px 12px;margin-bottom:6px}
        .logo .mark{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#4ade80,#22c55e);display:grid;place-items:center;color:#fff;font-weight:700}
        .logo .brand{font-weight:700}
        .sidebar .hr{height:1px;background:var(--line);margin:8px 0 6px}

        .nav{list-style:none;margin:0;padding:4px}
        .nav li{margin:2px 0}
        .nav a{
            display:flex;align-items:center;gap:10px;
            padding:10px 12px;border-radius:10px;color:inherit;
        }
        .nav a .ico{width:18px;height:18px;opacity:.9}
        .nav a.active{background:var(--primary);color:#fff}
        .nav a:hover{background:var(--sidebar-accent)}

        .sidebar .stick-bottom{margin-top:auto;display:flex;align-items:center;gap:8px;color:var(--muted);padding:10px 12px}

        /* Content */
        .content{display:flex;flex-direction:column;gap:12px}

        .topbar{display:flex;align-items:center;gap:10px;justify-content:space-between}
        .topbar-left{display:flex;align-items:center;gap:8px}
        .topbar .iconbtn{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);background:#fff;display:grid;place-items:center;box-shadow:var(--shadow);cursor:pointer}
        .topbar .right{display:flex;align-items:center;gap:10px}

        /* Title row */
        .title-row{display:flex;align-items:center;gap:10px}
        .title-row h2{margin:0;font-size:20px}

        .btn-primary{background:var(--primary);border:none;color:#fff;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer;box-shadow:var(--shadow)}
        .btn-primary:hover{background:var(--primary-dark)}

        /* Card/table */
        .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--line)}
        .table-wrap{overflow:auto;border-radius:var(--radius)}

        table{width:100%;border-collapse:separate;border-spacing:0}
        thead th{position:sticky;top:0;background:#f9fafb;border-bottom:1px solid var(--line);font-weight:600;color:#374151;z-index:2}
        th,td{padding:12px 10px;border-bottom:1px solid var(--line);text-align:left;white-space:nowrap}
        tbody tr:hover{background:#f8fafc}

        .checkbox{appearance:none;width:18px;height:18px;border:1.5px solid #cbd5e1;border-radius:5px;display:grid;place-items:center;cursor:pointer}
        .checkbox:checked{background:var(--primary);border-color:var(--primary)}
        .checkbox:checked::after{content:"";width:8px;height:8px;background:#fff;border-radius:2px}

        .th-flex{display:flex;align-items:center;gap:6px}
        .th-actions{display:flex;gap:6px}
        .sort{cursor:pointer;display:inline-flex;flex-direction:column;gap:2px}
        .sort svg{width:10px;height:10px;opacity:.35}
        .sort[data-dir="asc"] .up{opacity:.9}
        .sort[data-dir="desc"] .down{opacity:.9}
        .filter{opacity:.6}

        .badge{display:inline-flex;align-items:center;gap:6px;border:1px dashed #e5e7eb;border-radius:24px;padding:6px 10px;color:var(--muted)}
        .badge .payu{width:20px;height:14px;border-radius:4px;background:#1e293b;display:grid;place-items:center;color:#fff;font-size:10px;font-weight:700}

        .status{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;border:1px solid #e5e7eb;color:#374151;background:#fff}

        .progress{position:relative;height:8px;background:#eef2f7;border-radius:999px;overflow:hidden;min-width:140px}
        .progress>span{position:absolute;left:0;top:0;height:100%;background:linear-gradient(90deg,#22c55e,#2dd4bf);width:var(--v,0%)}
        .muted{color:var(--muted)}

        .actions{display:flex;gap:10px}
        .icon{width:18px;height:18px;opacity:.8;cursor:pointer}
        .icon:hover{opacity:1}

        /* Footer (pagination) */
        .table-footer{display:flex;align-items:center;justify-content:space-between;padding:10px;border-top:1px solid var(--line);background:#fafafa;border-bottom-left-radius:var(--radius);border-bottom-right-radius:var(--radius)}
        .pager{display:flex;align-items:center;gap:6px}
        .pager button{border:1px solid var(--line);background:#fff;padding:6px 10px;border-radius:8px;cursor:pointer}
        .pager button.active{background:var(--primary);color:#fff;border-color:var(--primary)}
        .found{color:var(--muted)}
        select{border:1px solid var(--line);border-radius:8px;padding:6px 8px;background:#fff}

        .w-16{width:16px}
    </style>
</head>
<body>
<div class="app">

    {{-- Левое меню --}}
    <x-menu />

    {{-- Контентная область --}}
    <main class="content">
        {{-- Верхняя панель --}}
        <div class="topbar">
            <div class="topbar-left">
                <button class="iconbtn" title="Меню" aria-label="Меню">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
                <button class="iconbtn" title="Поиск" aria-label="Поиск">
                    <svg width="18" height="18" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke="#111827" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="right">
                <button class="iconbtn" title="Уведомления">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M6 8a6 6 0 1112 0c0 4 2 5 2 7H4c0-2 2-3 2-7Z" stroke="#111827" stroke-width="2"/><path d="M10 20a2 2 0 004 0" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
                <button class="iconbtn" title="Настройки">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 8a4 4 0 100 8 4 4 0 000-8z" stroke="#111827" stroke-width="2"/><path d="M3 12h2m14 0h2M12 3v2m0 14v2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M5 19l1.5-1.5" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>

        {{-- Заголовок страницы --}}
        <div class="title-row">
            <h2>{{ $pageTitle ?? 'Страница' }}</h2>
            @isset($pageSubtitle)
                <span class="muted">• {{ $pageSubtitle }}</span>
            @endisset
        </div>

        {{-- Контент --}}
        <div class="page-content">
            @yield('content')
        </div>
    </main>
</div>

{{-- Скрипты --}}
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
