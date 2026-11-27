<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Staff login · Daily Table</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="{{ asset('css/dt.css') }}"></head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;isolation:isolate;overflow:hidden">
<span class="blob" style="width:420px;height:420px;left:-120px;top:-100px;background:rgba(128,214,182,.7)"></span>
<span class="blob" style="width:360px;height:360px;right:-100px;bottom:-80px;background:rgba(244,196,96,.4);animation-delay:-6s"></span>
<form method="post" action="/staff/login" class="glass rise" style="width:min(400px,92vw);border-radius:28px;padding:32px">@csrf
  <div class="row" style="margin-bottom:18px">@include('partials.logo')<b style="font-size:20px">Daily Table staff</b></div>
  @if($errors->any())<div class="flash bad">{{ $errors->first() }}</div>@endif
  <div class="field"><label for="email">Work email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus></div>
  <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required></div>
  <label class="row" style="font-weight:600"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
  <button class="btn" style="width:100%;margin-top:14px">Sign in</button>
  <p class="small muted" style="margin-top:14px">Every sign-in is recorded. Delivery partners and customers use the mobile apps.</p>
</form>
</body></html>
