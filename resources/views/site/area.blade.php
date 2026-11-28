@extends('layouts.site')
@section('content')
<div class="s-wrap">
  <section class="page-hero">
    <span class="s-kicker" style="background:rgba(255,255,255,.18);color:#fff">📍 Meal delivery · {{ $zone->name }}</span>
    <h1>Healthy home-style meals in {{ $zone->name }}</h1>
    <p>Breakfast, lunch, break-time snacks and dinner, cooked fresh in your neighbourhood kitchen. Order in the Daily Table app.</p>
    <a class="s-btn carrot big" style="margin-top:8px" href="{{ $seo['android_url'] }}">Order in the app</a>
  </section>

  <section style="padding-bottom:60px">
    <span class="s-kicker k-tomato">🍛 On the menu</span>
    <h2 class="s-h2" style="font-size:clamp(28px,3vw,40px)">Cooked fresh for {{ $zone->name }}</h2>
    @if($branches->count() > 1)<p class="s-lead">{{ $branches->count() }} branches deliver to {{ $zone->name }}. Each has its own menu — the app uses the one you choose.</p>@endif
    @if($branches->isNotEmpty())
      @include('site.partials.branch-menus', ['branches' => $branches])
    @else
      <div class="c lime" style="max-width:560px;margin-top:20px"><span class="ic">🥗</span><h3>Menu coming up</h3><p class="mut">The next menu for {{ $zone->name }} appears here as soon as it is published.</p></div>
    @endif
  </section>
</div>
@push('head')
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Restaurant','name'=>'Daily Table '.$zone->name,'servesCuisine'=>'South Indian','areaServed'=>$zone->name.', Chennai','url'=>url()->current(),'hasMenu'=>['@type'=>'Menu','hasMenuItem'=>$menu->map(fn($m)=>['@type'=>'MenuItem','name'=>$m->dish->name,'offers'=>['@type'=>'Offer','price'=>$m->price_paise/100,'priceCurrency'=>'INR']])->values()]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@endsection
