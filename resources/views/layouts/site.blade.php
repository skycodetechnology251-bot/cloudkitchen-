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
<meta name="theme-color" content="#0F5C4D">
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Tamil:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dt.css') }}">
<style>
.site-head a{color:#12302A;text-decoration:none}.site-head nav a:hover{color:#0F5C4D}
.site-foot a{color:#56706A;text-decoration:none}.site-foot a:hover{color:#0F5C4D}
@media (max-width: 860px){.site-head nav{display:none!important}}
</style>
@stack('head')
</head>
<body style="background:#F3F7F5">
@php($contact = \App\Models\Setting::get('app', 'support_email', config('mail.from.address')))
@php($fssai = \App\Models\Kitchen::whereNotNull('fssai')->value('fssai'))
<header class="site-head" style="max-width: 1240px; margin: 0 auto; padding: 20px 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; font-family: 'Manrope', sans-serif">
  <a href="{{ route('home') }}" style="display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 22px"><span style="width: 36px; height: 36px; border-radius: 10px; background: #0F5C4D; display: flex; align-items: center; justify-content: center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#BFE8D6" stroke-width="2.2" aria-hidden="true"><path d="M4 11h16a8 8 0 0 1-16 0ZM12 3v4M8 5v2M16 5v2"/></svg></span>Daily Table</a>
  <nav aria-label="Main" style="display: flex; flex-wrap: wrap; gap: 28px; font-size: 15px; font-weight: 600">
    <a href="{{ route('home') }}#idea">The idea</a><a href="{{ route('home') }}#how">How it works</a><a href="{{ route('home') }}#app">The app</a><a href="{{ route('home') }}#kitchens">Our kitchens</a><a href="{{ route('home') }}#areas">Areas</a><a href="{{ route('home') }}#faq">FAQ</a>
  </nav>
  <div style="display: flex; align-items: center; gap: 10px">
    <a href="{{ route('login') }}" style="height: 44px; padding: 0 18px; border-radius: 999px; border: 1.5px solid #CFDDD8; background: rgba(255,255,255,.7); display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Log in</a>
    <a href="{{ $seo['android_url'] ?? '#download' }}" style="height: 44px; padding: 0 20px; border-radius: 999px; background: #0F5C4D; color: #FFFFFF; display: flex; align-items: center; font-weight: 700; font-size: 14px">Get the app</a>
  </div>
</header>
<main>@yield('content')</main>
<footer class="site-foot" style="border-top: 1px solid #DCE7E2; padding: 28px 24px; font-family: 'Manrope', sans-serif">
  <div style="max-width: 1240px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; font-size: 14px; color: #56706A">
    <span>© {{ date('Y') }} Daily Table, Chennai{{ $fssai ? ' · FSSAI Reg. '.$fssai : '' }}</span>
    <span style="display: flex; flex-wrap: wrap; gap: 24px"><a href="{{ route('privacy') }}">Privacy &amp; consent</a><a href="{{ route('refunds') }}">Refund policy</a><a href="{{ route('terms') }}">Terms</a>@if($contact)<a href="mailto:{{ $contact }}?subject=Partner%20with%20Daily%20Table">Partner with us</a><a href="mailto:{{ $contact }}">Contact</a>@endif<a href="{{ route('login') }}">Staff login</a></span>
  </div>
</footer>
</body>
</html>
