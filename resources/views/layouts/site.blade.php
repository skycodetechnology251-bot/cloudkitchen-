<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:title" content="{{ $seo['title'] }}"><meta property="og:description" content="{{ $seo['description'] }}"><meta property="og:type" content="website">
<meta property="og:image" content="{{ asset('images/app/home.webp') }}">
<meta name="theme-color" content="#1F8A4C">
<link rel="stylesheet" href="{{ asset('css/dt.css') }}">
<link rel="stylesheet" href="{{ asset('css/site.css') }}">
@include('partials.brand-head')
@stack('head')
</head>
<body class="s-body">
@php($contact = \App\Models\Setting::get('app', 'support_email', config('mail.from.address')))
@php($fssai = \App\Models\Kitchen::whereNotNull('fssai')->value('fssai'))
<header class="s-head">
  <div class="s-wrap">
    <a href="{{ route('home') }}" class="s-logo">@include('partials.brand-mark', ['size' => 40])</a>
    <nav class="s-nav" aria-label="Main">
      <a href="{{ route('home') }}#idea">The idea</a><a href="{{ route('home') }}#plate">Healthy plate</a><a href="{{ route('home') }}#dietitian">Dietitian</a><a href="{{ route('home') }}#wellness">Wellness</a><a href="{{ route('home') }}#how">How it works</a><a href="{{ route('home') }}#app">The app</a><a href="{{ route('home') }}#areas">Areas</a><a href="{{ route('home') }}#faq">FAQ</a>
    </nav>
    <div class="s-actions">
      <a href="{{ route('login') }}" class="s-btn line" style="height:44px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Log in</a>
      <a href="{{ $seo['android_url'] ?? '#download' }}" class="s-btn leaf" style="height:44px">Get the app</a>
    </div>
  </div>
</header>
<main>@yield('content')</main>
<footer class="s-foot">
  <div class="s-wrap">
    <div style="max-width:360px">
      <div class="s-logo" style="color:#fff">@include('partials.brand-mark', ['size' => 40])</div>
      <p style="margin:12px 0 0;line-height:1.6">Fresh, home-style lunch from your neighbourhood kitchen, delivered 12–1 PM across Chennai.</p>
      <p style="margin:10px 0 0;font-size:13px;opacity:.8">© {{ date('Y') }} {{ \App\Support\Brand::name() }}, Chennai{{ $fssai ? ' · FSSAI Reg. '.$fssai : '' }}</p>
    </div>
    <div class="links" style="align-self:flex-end">
      <a href="{{ route('privacy') }}">Privacy &amp; consent</a><a href="{{ route('refunds') }}">Refund policy</a><a href="{{ route('terms') }}">Terms</a>@if($contact)<a href="mailto:{{ $contact }}?subject=Partner%20with%20Daily%20Table">Partner with us</a><a href="mailto:{{ $contact }}">Contact</a>@endif<a href="{{ route('login') }}">Staff login</a>
    </div>
  </div>
</footer>
</body>
</html>
