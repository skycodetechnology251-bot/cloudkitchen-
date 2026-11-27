@extends('layouts.site')
@section('content')
<div class="s-wrap">
  <section class="page-hero">
    <span class="s-kicker" style="background:rgba(255,255,255,.18);color:#fff">📍 Lunch delivery · {{ $zone->name }}</span>
    <h1>Healthy home-style lunch in {{ $zone->name }}</h1>
    <p>Cooked fresh in your neighbourhood kitchen and delivered between 12 and 1. Order by 10:45 AM in the Daily Table app.</p>
    <a class="s-btn carrot big" style="margin-top:8px" href="{{ $seo['android_url'] }}">Order in the app</a>
  </section>

  <section style="padding-bottom:60px">
    <span class="s-kicker k-tomato">🍛 Today's menu</span>
    <h2 class="s-h2" style="font-size:clamp(28px,3vw,40px)">Cooked fresh for {{ $zone->name }}</h2>
    <div class="menu" style="margin-top:24px">
      @forelse($menu as $m)
        <div class="dish">
          @if($m->dish->image_url)<img src="{{ $m->dish->image_url }}" alt="{{ $m->dish->name }}" width="76" height="76" loading="lazy">@else @include('partials.plate', ['size' => 76]) @endif
          <div style="min-width:0;flex:1">
            <h3>{{ $m->dish->name }}</h3>
            @if($m->dish->description)<p style="margin:4px 0;font-size:13px;color:var(--soft);line-height:1.45">{{ $m->dish->description }}</p>@endif
            <div class="meta"><span class="tag {{ $m->dish->diet }}">{{ ['veg' => 'Veg', 'egg' => 'Egg', 'nonveg' => 'Non-veg'][$m->dish->diet] ?? 'Veg' }}</span>@if($m->dish->kcal)<span class="tag kcal">{{ $m->dish->kcal }} kcal</span>@endif</div>
            @include('partials.balance', ['dish' => $m->dish])
          </div>
          <span class="price">₹{{ number_format($m->price_paise / 100) }}</span>
        </div>
      @empty
        <div class="c lime" style="max-width:560px"><span class="ic">🥗</span><h3>Menu coming up</h3><p class="mut">Today's menu for {{ $zone->name }} appears here each morning.</p></div>
      @endforelse
    </div>
  </section>
</div>
@push('head')
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Restaurant','name'=>'Daily Table '.$zone->name,'servesCuisine'=>'South Indian','areaServed'=>$zone->name.', Chennai','url'=>url()->current(),'hasMenu'=>['@type'=>'Menu','hasMenuItem'=>$menu->map(fn($m)=>['@type'=>'MenuItem','name'=>$m->dish->name,'offers'=>['@type'=>'Offer','price'=>$m->price_paise/100,'priceCurrency'=>'INR']])->values()]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@endsection
