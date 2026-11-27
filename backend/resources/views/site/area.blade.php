@extends('layouts.site')
@section('content')
<section class="wrap" style="padding:40px 24px 60px">
  <span class="chip">LUNCH DELIVERY · {{ strtoupper($zone->name) }}</span>
  <h1 style="font-size:clamp(36px,5vw,60px);margin:14px 0">Healthy home-style lunch in {{ $zone->name }}</h1>
  <p class="muted" style="font-size:18px;max-width:640px">Cooked fresh in your neighbourhood kitchen and delivered between 12 and 1. Order by 10:45 AM in the Daily Table app.</p>
  <div class="grid g2 stagger" style="margin-top:28px">
    @forelse($menu as $m)
      <div class="card lift row" style="flex-wrap:nowrap">
        @if($m->dish->image_url)<img src="{{ $m->dish->image_url }}" alt="{{ $m->dish->name }}" width="72" height="72" style="border-radius:50%;object-fit:cover">@else @include('partials.plate',['size'=>72]) @endif
        <div style="flex:1"><h3 style="font-size:18px">{{ $m->dish->name }}</h3><p class="muted small" style="margin:4px 0">{{ $m->dish->description }}</p>@include('partials.balance',['dish'=>$m->dish])</div>
        <b>₹{{ number_format($m->price_paise/100) }}</b>
      </div>
    @empty
      <p class="muted">Today's menu for {{ $zone->name }} appears here each morning.</p>
    @endforelse
  </div>
  <a class="btn" style="margin-top:28px" href="{{ $seo['android_url'] }}">Order in the app</a>
</section>
@push('head')
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Restaurant','name'=>'Daily Table '.$zone->name,'servesCuisine'=>'South Indian','areaServed'=>$zone->name.', Chennai','url'=>url()->current(),'hasMenu'=>['@type'=>'Menu','hasMenuItem'=>$menu->map(fn($m)=>['@type'=>'MenuItem','name'=>$m->dish->name,'offers'=>['@type'=>'Offer','price'=>$m->price_paise/100,'priceCurrency'=>'INR']])->values()]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@endsection
