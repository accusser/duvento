@props(['step' => null])

<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Установка Duvento</title>
    <style>
        :root { color-scheme: light; --green:#007257; --ink:#172321; --muted:#63706d; --line:#dce5e2; --bg:#f2f6f5; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; background:var(--bg); color:var(--ink); font:15px/1.5 Inter,system-ui,sans-serif; }
        .installer { width:min(720px,100%); background:#fff; border:1px solid var(--line); border-radius:20px; box-shadow:0 18px 60px rgba(23,35,33,.09); overflow:hidden; }
        header { padding:28px 34px 20px; border-bottom:1px solid var(--line); }
        .brand { display:flex; align-items:center; gap:12px; font-size:21px; font-weight:750; }
        .mark { width:38px; height:38px; display:grid; place-items:center; border-radius:10px; background:var(--green); color:#fff; }
        .steps { display:flex; gap:7px; margin-top:22px; }
        .steps span { height:4px; flex:1; background:#e7eeec; border-radius:9px; }
        .steps span.active { background:var(--green); }
        main { padding:32px 34px 36px; }
        h1 { margin:0 0 8px; font-size:27px; line-height:1.2; }
        p.lead { margin:0 0 26px; color:var(--muted); }
        label { display:block; margin:0 0 16px; font-weight:650; }
        input, select { width:100%; margin-top:7px; padding:12px 13px; border:1px solid #cbd7d3; border-radius:10px; font:inherit; background:#fff; }
        input:focus, select:focus { outline:3px solid rgba(0,114,87,.13); border-color:var(--green); }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:0 16px; }
        .actions { display:flex; justify-content:flex-end; gap:10px; margin-top:26px; }
        button,.button { border:0; border-radius:10px; padding:12px 20px; background:var(--green); color:#fff; font:inherit; font-weight:700; cursor:pointer; text-decoration:none; }
        button.secondary { background:#e8f2ef; color:var(--green); }
        .choice { display:flex; gap:12px; }
        .choice label { flex:1; border:1px solid var(--line); padding:14px; border-radius:12px; cursor:pointer; }
        .choice input { width:auto; margin:0 8px 0 0; }
        .check { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:11px 0; border-bottom:1px solid #edf1f0; }
        .ok { color:#087a4c; font-weight:700; } .bad { color:#b42318; font-weight:700; }
        .errors { padding:13px 15px; margin-bottom:20px; border-radius:10px; background:#fff1f0; color:#9d1c13; }
        code { display:block; overflow:auto; padding:13px; border-radius:9px; background:#f4f7f6; color:#33413e; }
        small { display:block; color:var(--muted); font-weight:400; margin-top:5px; }
        @media(max-width:620px){ body{padding:0}.installer{min-height:100vh;border-radius:0}.grid{grid-template-columns:1fr}header,main{padding-left:22px;padding-right:22px} }
    </style>
</head>
<body>
<section class="installer">
    <header>
        <div class="brand"><span class="mark">D</span> Duvento</div>
        @if($step)
            @php($current = array_search($step, \App\Install\InstallerState::STEPS, true))
            <div class="steps" aria-label="Прогресс установки">
                @foreach(\App\Install\InstallerState::STEPS as $index => $name)
                    <span class="{{ $index <= $current ? 'active' : '' }}"></span>
                @endforeach
            </div>
        @endif
    </header>
    <main>{{ $slot }}</main>
</section>
</body>
</html>
