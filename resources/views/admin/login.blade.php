<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Staff login · {{ \App\Support\Brand::name() }}</title>
<meta name="theme-color" content="#1F8A4C">
<link rel="stylesheet" href="{{ \App\Support\Asset::v('css/dt.css') }}"><link rel="stylesheet" href="{{ \App\Support\Asset::v('css/site.css') }}">
@include('partials.brand-head')
<style>
  .lg{min-height:100vh;display:flex;flex-wrap:wrap;background:var(--cream)}
  .lg-art{flex:1 1 420px;position:relative;overflow:hidden;background:linear-gradient(150deg,#8CC63F 0%,#1F8A4C 45%,#0F4D2C 100%);color:#fff;padding:48px;display:flex;flex-direction:column;justify-content:space-between;gap:28px}
  .lg-art h1{font-family:var(--font-head);font-size:clamp(36px,4vw,56px);line-height:1.05;color:#fff;margin:0}
  .lg-art p{color:#DDF1E3;font-size:17px;line-height:1.6;max-width:440px}
  .lg-art .blob{position:absolute;border-radius:50%}
  .lg-form{flex:1 1 420px;display:flex;align-items:center;justify-content:center;padding:32px}
  .lg-card{width:min(420px,100%);background:linear-gradient(90deg,#8CC63F,#1F8A4C,#F4B400,#F2711C,#E0433B) top/100% 8px no-repeat,#fff;border-radius:30px;padding:42px 34px 34px;box-shadow:0 30px 60px -36px rgba(26,46,34,.5);overflow:hidden}
  .lg-card .field label{color:#0F4D2C}
  .lg-card input:focus{outline:none;border-color:#1F8A4C;box-shadow:0 0 0 4px #E4F5E9}
  .lg-card input[type=checkbox]{accent-color:#1F8A4C}
  .lg-roles{display:flex;flex-wrap:wrap;gap:8px}
  .lg-roles span{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:7px 12px;font-size:13px;font-weight:700}
</style>
</head>
<body class="s-body">
<div class="lg">
  <section class="lg-art" aria-hidden="false">
    <span class="blob" style="width:220px;height:220px;right:-60px;top:-50px;background:radial-gradient(circle at 35% 30%,#FFD36B,#F4B400)"></span>
    <span class="blob" style="width:140px;height:140px;right:90px;bottom:60px;background:radial-gradient(circle at 35% 30%,#FF8A80,#E0433B);opacity:.9"></span>
    <span class="blob" style="width:90px;height:90px;left:-20px;bottom:180px;background:radial-gradient(circle at 35% 30%,#FFB27A,#F2711C);opacity:.9"></span>
    <a href="{{ route('home') }}" class="s-logo" style="color:#fff;position:relative">@include('partials.brand-mark', ['size' => 40])</a>
    <div style="position:relative">
      <h1>Good food starts with a good team.</h1>
      <p>Sign in to run today's lunch: orders, the counter POS, kitchen boards, menus, branches and reports.</p>
      <div class="lg-roles"><span>🍳 Kitchen</span><span>🧾 Counter POS</span><span>📊 Reports</span><span>🏪 Branches</span></div>
    </div>
    <a href="{{ route('home') }}" style="position:relative;font-weight:700;color:#DDF1E3">← Back to the website</a>
  </section>
  <section class="lg-form">
    <form method="post" action="/staff/login" class="lg-card">@csrf
      <span class="s-kicker k-leaf">Staff only</span>
      <h2 style="font-family:var(--font-head);font-size:32px;margin:12px 0 18px">Sign in</h2>
      @if($errors->any())<div class="flash bad">{{ $errors->first() }}</div>@endif
      <div class="field"><label for="email">Work email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
      <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
      <label class="row" style="font-weight:600"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
      <button class="s-btn leaf" style="width:100%;margin-top:16px;height:52px">Sign in</button>
      <p class="small" style="margin-top:14px;color:var(--soft)">Every sign-in is recorded. Delivery partners and customers use the mobile apps.</p>
    </form>
  </section>
</div>
</body></html>
