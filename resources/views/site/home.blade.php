@extends('layouts.site')
@push('head')
<link rel="preload" as="image" href="{{ asset('images/app/home.webp') }}">
@endpush
@section('content')
@php
  $live = $zones->where('status', 'live')->pluck('name');
  $marquee = $menu->pluck('dish.name')->filter()->values();
  if ($marquee->count() < 5) {
      $marquee = collect(['Millet meals', 'Sambar rice & poriyal', 'Lemon & curd rice', 'Chickpea sundal', 'Ragi malt', 'Seasonal fruit cup', 'Neer mor', 'Bisi bele bath']);
  }
  $android = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg>';
  $features = [
    ['meal.webp', 'Meal details screen: plate breakdown into grains, dal, vegetables and curd with nutrition', 'KNOW YOUR FOOD', 'See exactly what\'s on the leaf', ['Every dish lists its ingredients, portion and declared allergens. Allergy conflicts are flagged clearly before you add to basket.', 'Optional food preferences help us suggest meals reviewed by a dietitian — you can always browse the full menu.'], 'leaf', '#E4F5E9'],
    ['checkout.webp', 'Checkout screen: address, delivery time, price with taxes and pay from wallet plus UPI', 'NO SURPRISES', 'The full price before you pay', ['Meal, delivery fee and taxes shown upfront. Choose your slot, pay by UPI or card, and see the cancellation cut-off right there.', 'If a payment goes wrong, you are never charged twice — and refunds are tracked in the app.'], 'carrot', '#FFEBDD'],
    ['tracking.webp', 'Order tracking screen: arriving 12 to 1 PM, rider details, delivery code and order status', 'KITCHEN TO DOOR', 'Follow your lunch all the way', ['See when your meal is cooking, packed and picked up. Call your rider through the app and hand over with a one-time delivery code.', 'Something missing or late? Raise it from the order and support sees everything instantly.'], 'beet', '#F8E5EF'],
    ['wallet.webp', 'Wallet screen: balance, lunches covered, top-up amounts and low-balance reminder settings', 'WALLET', 'Top up once, eat every day', ['Add ₹500 to ₹3,000 and each lunch is paid from your wallet in one tap. The app shows how many lunches your balance covers.', 'Before it runs low you get a reminder, and top-ups of ₹2,000 or more earn bonus reward points.'], 'turmeric', '#FFF5D6'],
    ['addresses.webp', 'Delivery addresses screen: saved home, work and family addresses, with the current one picked automatically', 'YOUR ADDRESSES', 'It knows where you are', ['Save home, work and family addresses with a pin on the map, plus flat, street, landmark and pincode for the rider.', 'When you are at one of your saved places, the app picks that address for you automatically.'], 'tomato', '#FDE6E4'],
    ['menu.webp', 'Full menu screen: meals, bowls and sides with balance bars, calories, prices and add buttons', 'THE FULL MENU', 'Every dish, every day, in one list', ['Browse the whole day\'s menu by meals, bowls, sides and drinks. Each dish shows its balance of grains, protein, vegetables and dairy, its calories and price.', 'Sold-out dishes are marked clearly, and the menu always shows when ordering closes.'], 'lime', '#F0F8E2'],
  ];
  $numColor = ['leaf' => '#1F8A4C', 'carrot' => '#F2711C', 'beet' => '#9C2A6B', 'turmeric' => '#E0A100', 'tomato' => '#E0433B', 'lime' => '#6FA62C'];
  $faq = [
    ['Will saying “Yes” at 10 AM charge me?', 'No. Yes only opens today\'s menu. You are charged only when you check out and pay.', '#1F8A4C'],
    ['Do I need a subscription?', 'No. Order single meals whenever you like. Meal plans come later and will always be optional.', '#F2711C'],
    ['Can I cancel an order?', 'Yes, free until the cut-off shown at checkout. After cooking starts, our support team will help.', '#E0433B'],
    ['How does the wallet work?', 'Top up any amount from ₹100 by UPI or card. Each lunch is paid from the wallet first; if it runs short, the rest goes to UPI or card. The app shows how many lunches your balance covers and reminds you before it runs low. Refunds can come back to the wallet instantly.', '#F4B400'],
    ['Who is the dietitian and what do they do?', 'The dietitian on our team reviews every recipe before it is marked “Dietitian-reviewed”: ingredients, portion, calories, protein, carbs, fat, fibre, salt and allergens. They also mark which dishes suit needs like diabetes or high blood pressure. If a recipe changes, it is reviewed again.', '#8CC63F'],
    ['Will the dietitian give me personal medical advice?', 'No. The dietitian reviews our recipes and the suggestions in the app so you can choose balanced meals. For any health condition, please follow your own doctor\'s advice.', '#1F8A4C'],
    ['Do I get a GST invoice?', 'Yes. Every paid order gets a proper GST invoice in the app, and the counter gives a printed receipt.', '#9C2A6B'],
    ['Is there an iPhone app?', $seo['ios_url'] ? 'Yes. Daily Table is on the App Store and Google Play.' : "We're starting with Android. iPhone is planned for a later release.", '#8CC63F'],
  ];
@endphp

{{-- HERO --}}
<section class="hero">
  <div class="s-wrap">
    <div class="hero-copy rise">
      <span class="s-kicker k-leaf" style="align-self:flex-start">🌿 Chennai's healthy daily lunch</span>
      <h1>Fresh lunch,<br>sorted by <span class="squiggle">10 AM.</span></h1>
      <p>Every morning the app asks one question: <b style="color:var(--ink)">lunch today?</b> Tap yes, pick from a fresh home-style menu cooked in your neighbourhood kitchen, and it reaches you between 12 and 1.</p>
      <div class="trust">
        <span><b style="background:var(--leaf)"></b>Home-style South Indian</span>
        <span><b style="background:var(--carrot)"></b>Dietitian-reviewed recipes</span>
        <span><b style="background:var(--turmeric)"></b>Cooked to order</span>
        <span><b style="background:var(--tomato)"></b>Calories &amp; allergens shown</span>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
        <a href="{{ $seo['android_url'] }}" class="s-btn leaf big">{!! $android !!}<span class="s2"><small>Download for</small>Android</span></a>
        <a href="#how" class="s-btn line big">See how it works</a>
      </div>
      <div style="font-size:14px;color:var(--soft);font-weight:600">📍 Now serving {{ $live->join(', ') ?: 'Chennai' }} · more Chennai areas opening soon{{ $seo['ios_url'] ? '' : ' · iPhone app later' }}</div>
    </div>
    <div class="hero-art">
      <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div><div class="blob b4"></div>
      <div class="float-card" style="top:120px;left:0"><i style="background:linear-gradient(135deg,var(--lime),var(--leaf))">🥗</i><span>Balanced plate<small>grains · protein · veg · curd</small></span></div>
      <div class="float-card" style="bottom:140px;right:0;animation-delay:1.5s"><i style="background:linear-gradient(135deg,var(--turmeric),var(--carrot))">🛵</i><span>Arrives 12 – 1 PM<small>track your rider live</small></span></div>
      <div style="position:relative;transform:rotate(-6deg) translate(40px,-10px);z-index:1">
        <div class="phone" style="width:254px"><div style="width:254px;height:549px"><img src="{{ asset('images/app/tracking.webp') }}" alt="Order tracking screen: arriving 12 to 1 PM, rider details, delivery code and order status" width="390" height="844" loading="lazy" decoding="async"></div></div>
      </div>
      <div style="position:relative;z-index:2;transform:translateX(-30px)">
        <div class="phone" style="width:293px;padding:12px;border-radius:48px"><div style="width:293px;height:633px;border-radius:36px"><img src="{{ asset('images/app/home.webp') }}" alt="Home screen: lunch today question, offer banner, wallet reminder and today&#39;s balanced plate" width="390" height="844" loading="eager" decoding="async"></div></div>
      </div>
    </div>
  </div>
</section>

{{-- Food marquee --}}
<div class="marq" aria-hidden="true"><div>
  @for($i = 0; $i < 2; $i++)<span>@foreach($marquee as $name)<span>{{ $name }}</span><em>✿</em>@endforeach</span>@endfor
</div></div>

{{-- IDEA --}}
<section class="s-sec" id="idea">
  <div class="s-wrap">
    <div style="max-width:780px">
      <span class="s-kicker k-carrot">The idea</span>
      <h2 class="s-h2">A neighbourhood kitchen that feeds you like home — every working day.</h2>
    </div>
    <div class="cards" style="margin-top:36px">
      <div class="c tomato">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9.5a3 3 0 1 1 3.5 3c-.5.2-.5.8-.5 1.5M12 17v.5"/></svg></span>
        <h3>The everyday problem</h3>
        <p class="mut">Deciding lunch every day is tiring. Delivery apps mean scrolling restaurants, guessing portions and waiting on unpredictable times.</p>
      </div>
      <div class="c leaf">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg></span>
        <h3>Our answer</h3>
        <p>One fresh daily menu from a kitchen near you, clear ingredients and portions, a fixed delivery window, and real people on support.</p>
      </div>
      <div class="c turmeric">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20a7 7 0 0 1 14 0M15 20a5 5 0 0 1 7-4.5"/></svg></span>
        <h3>Made for</h3>
        <p class="mut">Office workers, apartment families and elderly parents who want dependable, home-style food without the daily hassle.</p>
      </div>
    </div>
  </div>
</section>

{{-- HEALTHY PLATE --}}
<section id="plate" style="padding:0 0 88px">
  <div class="s-wrap">
    <div class="plate-sec">
      <div style="flex:0 0 auto;margin:0 auto"><div class="plate" role="img" aria-label="Example balanced plate: grains, protein, vegetables and dairy"><span>Balanced<br>plate<small>example</small></span></div></div>
      <div style="flex:1 1 420px;min-width:0">
        <span class="s-kicker k-lime">🥗 Eat well, every day</span>
        <h2 class="s-h2">Know what's on your plate</h2>
        <p class="s-lead">Every dish in the app shows its balance of grains, protein, vegetables and dairy, with calories, ingredients and allergens. Recipes and nutrition labels are checked by a dietitian before they go on the menu.</p>
        <div class="legend4">
          <div><i style="background:var(--turmeric)"></i><span><b>Grains &amp; millets</b><small>rice, millet, ragi for steady energy</small></span></div>
          <div><i style="background:var(--carrot)"></i><span><b>Protein</b><small>dal, sambar, sundal, egg or chicken</small></span></div>
          <div><i style="background:var(--leaf)"></i><span><b>Vegetables</b><small>poriyal, kootu and seasonal greens</small></span></div>
          <div><i style="background:#7FB8E8"></i><span><b>Dairy</b><small>curd and buttermilk to finish</small></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- DIETITIAN --}}
<section class="s-sec" id="dietitian" style="background:linear-gradient(180deg,#F0F8E2 0%,#FFF9EF 100%)">
  <div class="s-wrap">
    <div class="diet">
      <div>
        <span class="s-kicker k-lime">🩺 Our dietitian</span>
        <h2 class="s-h2">A dietitian checks every dish before it reaches you</h2>
        <p class="s-lead">Before a dish is marked <span class="badge-rev">✓ Dietitian-reviewed</span>, our dietitian goes through its recipe and nutrition label line by line.</p>
        <div class="checklist" aria-label="What the dietitian checks">
          <span><b>✓</b>Ingredients</span><span><b>✓</b>Portion size</span><span><b>✓</b>Calories</span>
          <span><b>✓</b>Protein, carbs &amp; fat</span><span><b>✓</b>Fibre &amp; salt level</span><span><b>✓</b>Allergens</span>
        </div>
        <p class="s-lead" style="font-size:15px">If the kitchen changes a recipe, the mark comes off until the dietitian checks it again — so the label always matches what's on your plate.</p>
        <h3 style="font-size:22px;margin-top:22px!important">Dishes reviewed as suitable for</h3>
        <div class="conds">
          <span style="border-color:#F4B400">Diabetes</span><span style="border-color:#E0433B">High blood pressure</span><span style="border-color:#F2711C">High cholesterol</span>
          <span style="border-color:#9C2A6B">Thyroid</span><span style="border-color:#C04A8C">PCOS</span><span style="border-color:#E0433B">Heart care</span><span style="border-color:#1F8A4C">Weight management</span>
        </div>
      </div>
      <div class="diet-flow">
        <div class="fs"><i style="background:linear-gradient(135deg,#FFC93C,var(--carrot))">1</i><div><h3>Tell us what you need — only if you want to</h3><p>In the app you can add allergies, vegetarian preference and health needs. Health details are off by default and used only after you give consent.</p></div></div>
        <div class="fs"><i style="background:linear-gradient(135deg,var(--lime),var(--leaf))">2</i><div><h3>The menu highlights what fits you</h3><p>Dishes the dietitian reviewed as suitable for <b>all</b> your health needs are marked “Fits your preferences”. Anything with one of your allergens is flagged before you add it.</p></div></div>
        <div class="fs"><i style="background:linear-gradient(135deg,#C04A8C,var(--beet))">3</i><div><h3>Your details stay private</h3><p>Only our dietitian and doctor can see the health details you share. Kitchen staff, riders and cashiers never do, and they never appear in notifications or on food labels.</p></div></div>
        <div class="fs" style="background:var(--turmeric-l);border-color:#F6DE9A"><i style="background:var(--turmeric)">!</i><div><h3>Good food, not medical treatment</h3><p>Our dietitian's reviews help you choose balanced meals. They are not medical advice — please follow your own doctor's guidance for any health condition.</p></div></div>
      </div>
    </div>
  </div>
</section>

{{-- HOW IT WORKS --}}
<section class="s-sec" id="how" style="background:#fff">
  <div class="s-wrap">
    <div class="s-center"><span class="s-kicker k-turmeric">How it works</span><h2 class="s-h2">One simple morning routine</h2></div>
    <div class="steps" style="margin-top:36px">
      <div class="step st1"><span class="t">10:00 AM</span><h3>You get one question</h3><p>“Lunch today?” Yes opens the menu. No skips today. Ignore it and nothing happens.</p></div>
      <div class="step st2"><span class="t">10:45 AM</span><h3>Pick and pay</h3><p>Choose your meal, see the full price, pay by UPI or card before the cut-off.</p></div>
      <div class="step st3"><span class="t">11:00 AM</span><h3>Cooked fresh</h3><p>The kitchen cooks only what's ordered, then packs and checks every meal.</p></div>
      <div class="step st4"><span class="t">12 – 1 PM</span><h3>At your door</h3><p>Track the rider live and confirm with a delivery code.</p></div>
    </div>
  </div>
</section>

{{-- TODAY'S MENU --}}
@if($menu->isNotEmpty())
<section class="s-sec">
  <div class="s-wrap">
    <div class="row between" style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:16px">
      <div><span class="s-kicker k-tomato">🍛 On the menu today</span><h2 class="s-h2">Cooked fresh this morning</h2></div>
      <a href="{{ $seo['android_url'] }}" class="s-btn carrot">Order in the app</a>
    </div>
    <div class="menu" style="margin-top:28px">
      @foreach($menu as $m)
        <div class="dish">
          @if($m->dish->image_url)<img src="{{ $m->dish->image_url }}" alt="{{ $m->dish->name }}" width="76" height="76" loading="lazy">@else @include('partials.plate', ['size' => 76]) @endif
          <div style="min-width:0;flex:1">
            <h3>{{ $m->dish->name }}</h3>
            <div class="meta"><span class="tag {{ $m->dish->diet }}">{{ ['veg' => 'Veg', 'egg' => 'Egg', 'nonveg' => 'Non-veg'][$m->dish->diet] ?? 'Veg' }}</span>@if($m->dish->kcal)<span class="tag kcal">{{ $m->dish->kcal }} kcal</span>@endif</div>
            @include('partials.balance', ['dish' => $m->dish])
          </div>
          <span class="price">₹{{ number_format($m->price_paise / 100) }}</span>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- APP --}}
<section class="s-sec" id="app" style="background:#fff">
  <div class="s-wrap" style="display:flex;flex-direction:column;gap:72px">
    <div class="s-center"><span class="s-kicker k-beet">Inside the app</span><h2 class="s-h2">Everything you need, nothing you don't</h2></div>
    @foreach($features as $i => [$img, $alt, $kicker, $title, $paras, $tone, $bg])
      <div class="feat {{ $i % 2 ? 'rev' : '' }}">
        @if($i % 2 === 0)
          <div class="art" style="--bg:{{ $bg }}"><div class="phone" style="width:254px"><div style="width:254px;height:549px"><img src="{{ asset('images/app/'.$img) }}" alt="{{ $alt }}" width="390" height="844" loading="lazy" decoding="async"></div></div></div>
        @endif
        <div class="txt">
          <span class="num" style="color:{{ $numColor[$tone] }}"><b style="background:{{ $numColor[$tone] }}">0{{ $i + 1 }}</b>{{ $kicker }}</span>
          <h3>{{ $title }}</h3>
          @foreach($paras as $p)<p>{{ $p }}</p>@endforeach
        </div>
        @if($i % 2 === 1)
          <div class="art" style="--bg:{{ $bg }}"><div class="phone" style="width:254px"><div style="width:254px;height:549px"><img src="{{ asset('images/app/'.$img) }}" alt="{{ $alt }}" width="390" height="844" loading="lazy" decoding="async"></div></div></div>
        @endif
      </div>
    @endforeach
  </div>
</section>

{{-- HEALTH & PRIVACY --}}
<section class="s-sec">
  <div class="s-wrap">
    <div class="health">
      <div style="flex:1 1 340px;min-width:0">
        <span class="s-kicker" style="background:rgba(140,198,63,.2);color:#CDEB9F">Your health, your choice</span>
        <h2 style="margin-top:14px">Share preferences only if you want to</h2>
        <p>Health details are optional and used only for meal suggestions, with your permission. Skip them and the app works exactly the same.</p>
      </div>
      <div class="checks">
        <div><b>✓</b><span>Consent is off by default — you can withdraw any time</span></div>
        <div><b>✓</b><span>Kitchen staff and riders never see your health profile</span></div>
        <div><b>✓</b><span>No health conditions in notifications or on food labels</span></div>
        <div><b>✓</b><span>Suggestions, not medical advice — no cure claims, ever</span></div>
      </div>
    </div>
  </div>
</section>

{{-- KITCHENS --}}
<section class="s-sec" id="kitchens" style="background:#fff">
  <div class="s-wrap" style="display:flex;flex-wrap:wrap;gap:48px;align-items:center">
    <div style="flex:1 1 420px;min-width:0">
      <span class="s-kicker k-leaf">Our kitchens</span>
      <h2 class="s-h2">Cooked close to you, held to one standard</h2>
      <p class="s-lead">A main kitchen develops every recipe, sets quality checks and sources ingredients. Neighbourhood kitchens across Chennai cook and pack your meal locally, so it arrives fresh and on time.</p>
    </div>
    <div class="cards" style="flex:1 1 480px;grid-template-columns:repeat(auto-fit,minmax(min(210px,100%),1fr))">
      <div class="c leaf" style="padding:22px"><span class="ic">🛡️</span><h3 style="font-size:19px">FSSAI-registered</h3><p>Every kitchen site licensed and hygiene-logged daily.</p></div>
      <div class="c lime" style="padding:22px"><span class="ic">🥬</span><h3 style="font-size:19px">Dietitian-reviewed</h3><p class="mut">Recipes and nutrition labels checked before they go on the menu.</p></div>
      <div class="c turmeric" style="padding:22px"><span class="ic">🏷️</span><h3 style="font-size:19px">Batch-tracked</h3><p class="mut">Each meal is labelled with its batch and packing time.</p></div>
      <div class="c carrot" style="padding:22px"><span class="ic">🍲</span><h3 style="font-size:19px">Cooked to order</h3><p class="mut">We cook what's ordered by the cut-off, which keeps waste low.</p></div>
    </div>
  </div>
</section>

{{-- AREAS --}}
<section class="s-sec" id="areas" style="padding-bottom:40px">
  <div class="s-wrap">
    <div class="areas">
      <div style="flex:1 1 380px;min-width:0">
        <span class="s-kicker" style="background:rgba(255,255,255,.16);color:#fff">📍 Delivery areas</span>
        <h2 style="margin-top:12px">Where we deliver</h2>
        <p>We open new Chennai areas as each neighbourhood kitchen gets ready. Not covered yet? Tell us where you are and we'll let you know first.</p>
        @if (session('status'))<p role="status" class="note" style="background:var(--turmeric-l);color:var(--ink)">{{ session('status') }}</p>@endif
        @error('area')<p role="alert" class="note" style="background:var(--tomato-l);color:#8E1F19">{{ $message }}</p>@enderror
        <form method="post" action="{{ route('notify-me') }}#areas" class="pill-form">@csrf
          <label for="pin" class="sr">Your area or pincode</label>
          <input id="pin" name="area" required maxlength="80" value="{{ old('area') }}" placeholder="Your area or pincode">
          <button type="submit" class="s-btn carrot" style="height:48px">Notify me</button>
        </form>
      </div>
      <div style="flex:1 1 380px;display:flex;flex-wrap:wrap;gap:10px">
        @forelse ($zones as $z)
          @if ($z->status === 'live')
            <a href="{{ route('area', $z) }}" class="zone live">{{ $z->name }} · Live</a>
          @else
            <span class="zone soon">{{ $z->name }} · {{ $z->status === 'testing' ? 'Soon' : 'Planned' }}</span>
          @endif
        @empty
          <span class="zone soon">Chennai · Opening soon</span>
        @endforelse
      </div>
    </div>
  </div>
</section>

{{-- COMING NEXT --}}
<section style="padding:48px 0 88px">
  <div class="s-wrap">
    <h2 style="font-size:32px;margin-bottom:20px!important">Coming next</h2>
    <div class="next">
      <div style="border-color:#F4B400;background:var(--turmeric-l)"><span style="color:#7A5700">PHASE 2</span><h3>Breakfast</h3><p>Idli, pongal and more with an earlier morning reminder.</p></div>
      <div style="border-color:#8CC63F;background:var(--lime-l)"><span style="color:#3F6512">PHASE 2</span><h3>Weekly &amp; monthly plans</h3><p>Prepaid meal plans you can pause before the deadline.</p></div>
      <div style="border-color:#C04A8C;background:var(--beet-l)"><span style="color:var(--beet)">LATER</span><h3>Medicines</h3><p>Through a licensed pharmacy partner, kept fully separate from food.</p></div>
    </div>
  </div>
</section>

{{-- FAQ --}}
<section class="s-sec" id="faq" style="background:#fff">
  <div class="s-wrap">
    <div class="s-center" style="margin-bottom:28px"><span class="s-kicker k-tomato">FAQ</span><h2 class="s-h2">Questions people ask</h2></div>
    <div class="faq">
      @foreach($faq as $i => [$q, $a, $c])
        <details style="--c:{{ $c }}" @if($i === 0) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
      @endforeach
    </div>
  </div>
</section>

{{-- DOWNLOAD --}}
<section class="s-sec" id="download">
  <div class="s-wrap">
    <div class="cta">
      <div style="flex:1 1 420px;min-width:0"><h2>Tomorrow's lunch starts with one tap.</h2><p>Download Daily Table and we'll ask you at 10 AM.</p></div>
      <a href="{{ $seo['android_url'] }}" class="s-btn dark big">{!! $android !!}<span class="s2"><small>Download for</small>Android</span></a>
    </div>
  </div>
</section>
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Restaurant','name'=>'Daily Table','servesCuisine'=>'South Indian','areaServed'=>'Chennai','url'=>url('/'),'priceRange'=>'₹₹'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
