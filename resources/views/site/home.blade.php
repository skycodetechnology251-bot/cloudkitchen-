@extends('layouts.site')
@push('head')
<link rel="preload" as="image" href="{{ asset('images/app/home.webp') }}">
<style>
.dt-home a{color:inherit;text-decoration:none}.dt-home a:hover{color:#0F5C4D}
summary{cursor:pointer;list-style:none}summary::-webkit-details-marker{display:none}
@keyframes dtRise{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}@keyframes dtFade{from{opacity:0}to{opacity:1}}@keyframes dtGrow{from{transform:scaleX(0)}to{transform:scaleX(1)}}@keyframes dtZoom{from{transform:scale(1)}to{transform:scale(1.08)}}@keyframes dtPop{0%{transform:scale(.9);opacity:0}60%{transform:scale(1.03);opacity:1}100%{transform:scale(1)}}
.dt-stagger>*{animation:dtRise .6s cubic-bezier(.2,.8,.2,1) both}.dt-stagger>*:nth-child(2){animation-delay:.06s}.dt-stagger>*:nth-child(3){animation-delay:.12s}.dt-stagger>*:nth-child(4){animation-delay:.18s}.dt-stagger>*:nth-child(5){animation-delay:.24s}.dt-stagger>*:nth-child(6){animation-delay:.3s}.dt-stagger>*:nth-child(n+7){animation-delay:.36s}
.dt-rise{animation:dtRise .7s cubic-bezier(.2,.8,.2,1) both}.dt-fade{animation:dtFade .8s ease both}.dt-pop{animation:dtPop .5s cubic-bezier(.2,.8,.2,1) both}.dt-grow{transform-origin:left center;animation:dtGrow 1s cubic-bezier(.2,.8,.2,1) .25s both}.dt-zoom{animation:dtZoom 12s ease-in-out infinite alternate}
.dt-home button,.dt-home a{transition:transform .16s ease,background-color .2s,box-shadow .2s,opacity .2s,border-color .2s}.dt-home button:active,.dt-home a:active{transform:scale(.97)}.dt-lift{transition:transform .2s,box-shadow .2s}.dt-lift:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(18,48,42,.10)}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}
.dt-home h1,.dt-home h2,.dt-home h3{color:inherit}
@media (max-width: 720px){.dt-home .dt-hero-phones{min-height:560px!important;transform:scale(.82);transform-origin:top center}}
</style>
@endpush
@section('content')
<div class="dt-home" style="background: #F3F7F5; font-family: 'Manrope', 'Noto Sans Tamil', sans-serif; color: #12302A">
<section style="max-width: 1240px; margin: 0 auto; padding: 24px 24px 72px; display: flex; flex-wrap: wrap; align-items: center; gap: 48px">
    <div class="dt-stagger" style="flex: 1 1 500px; min-width: 0; display: flex; flex-direction: column; gap: 22px">
      <span style="align-self: flex-start; font-size: 13px; font-weight: 700; letter-spacing: 1px; color: #0F5C4D; background: #E3F1EC; padding: 6px 12px; border-radius: 999px">CHENNAI'S DAILY LUNCH APP</span>
      <h1 style="margin: 0; font-size: clamp(42px, 5.6vw, 76px); line-height: 1.02; font-weight: 800; letter-spacing: -1.6px">Lunch, sorted<br>by <span style="color: #0F5C4D">10 AM.</span></h1>
      <p style="margin: 0; font-size: 19px; line-height: 1.55; color: #405A54; max-width: 540px">Every morning the app asks one question: <b style="color: #12302A">lunch today?</b> Tap yes, pick from a fresh home-style menu cooked in your neighbourhood kitchen, and it reaches you between 12 and 1.</p>
      <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center">
        <a href="{{ $seo['android_url'] }}" style="height: 56px; padding: 0 22px; border-radius: 16px; background: #12302A; color: #FFFFFF; display: flex; align-items: center; gap: 12px"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#BFE8D6" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg><span style="display: flex; flex-direction: column; line-height: 1.15"><span style="font-size: 11px; color: #A9C4BC">Download for</span><span style="font-size: 17px; font-weight: 700">Android</span></span></a>
        <a href="#how" style="height: 56px; padding: 0 22px; border-radius: 16px; border: 1.5px solid #12302A; display: flex; align-items: center; font-weight: 700; font-size: 15px">See how it works</a>
      </div>
      <div style="font-size: 14px; color: #56706A">Now serving {{ $zones->where('status', 'live')->pluck('name')->join(', ') ?: 'Chennai' }} · more Chennai areas opening soon{{ $seo['ios_url'] ? '' : ' · iPhone app later' }}</div>
    </div>
    <div class="dt-hero-phones" style="flex: 1 1 420px; min-width: 0; display: flex; justify-content: center; align-items: flex-end; gap: 0; position: relative; min-height: 680px">
      <div style="position: absolute; width: min(100%, 560px); aspect-ratio: 1; border-radius: 50%; background: #E3F1EC; top: 40px"></div>
      <div style="position: relative; transform: rotate(-6deg) translate(40px, -10px); z-index: 0; opacity: 0.95">
        <div style="width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box">
          <div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/tracking.webp') }}" alt="Order tracking screen: arriving 12 to 1 PM, rider details, delivery code and order status" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div>
        </div>
      </div>
      <div style="position: relative; z-index: 1; transform: translateX(-30px)">
        <div style="width: 293px; padding: 12px; border-radius: 46px; background: #12302A; box-sizing: content-box">
          <div style="width: 293px; height: 633px; border-radius: 34px; overflow: hidden"><img src="{{ asset('images/app/home.webp') }}" alt="Home screen: lunch today question, offer banner, wallet reminder and today&#39;s balanced plate" width="390" height="844" loading="eager" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div>
        </div>
      </div>
    </div>
  </section>

  <section class="dt-rise" id="idea" style="background: #FFFFFF; padding: 80px 24px">
    <div style="max-width: 1240px; margin: 0 auto; display: flex; flex-direction: column; gap: 36px">
      <div style="max-width: 760px"><div style="font-size: 14px; font-weight: 700; color: #0F5C4D; letter-spacing: 1px">THE IDEA</div><h2 style="margin: 8px 0 0; font-size: clamp(32px, 3.6vw, 46px); line-height: 1.1; font-weight: 800; letter-spacing: -0.8px">A neighbourhood kitchen that feeds you like home — every working day.</h2></div>
      <div style="display: flex; flex-wrap: wrap; gap: 20px">
        <div style="flex: 1 1 300px; background: #F3F7F5; border-radius: 24px; padding: 28px; display: flex; flex-direction: column; gap: 10px">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#8A3A17" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9.5a3 3 0 1 1 3.5 3c-.5.2-.5.8-.5 1.5M12 17v.5"/></svg>
          <h3 style="margin: 4px 0 0; font-size: 21px">The everyday problem</h3>
          <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #56706A">Deciding lunch every day is tiring. Delivery apps mean scrolling restaurants, guessing portions and waiting on unpredictable times.</p>
        </div>
        <div style="flex: 1 1 300px; background: #0F5C4D; color: #FFFFFF; border-radius: 24px; padding: 28px; display: flex; flex-direction: column; gap: 10px">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#BFE8D6" stroke-width="2" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
          <h3 style="margin: 4px 0 0; font-size: 21px">Our answer</h3>
          <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #CFE6DF">One fresh daily menu from a kitchen near you, clear ingredients and portions, a fixed delivery window, and real people on support.</p>
        </div>
        <div style="flex: 1 1 300px; background: #F3F7F5; border-radius: 24px; padding: 28px; display: flex; flex-direction: column; gap: 10px">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0F5C4D" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20a7 7 0 0 1 14 0M15 20a5 5 0 0 1 7-4.5"/></svg>
          <h3 style="margin: 4px 0 0; font-size: 21px">Made for</h3>
          <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #56706A">Office workers, apartment families and elderly parents who want dependable, home-style food without the daily hassle.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="dt-rise" id="how" style="max-width: 1240px; margin: 0 auto; padding: 80px 24px; display: flex; flex-direction: column; gap: 36px">
    <div><div style="font-size: 14px; font-weight: 700; color: #0F5C4D; letter-spacing: 1px">HOW IT WORKS</div><h2 style="margin: 8px 0 0; font-size: clamp(32px, 3.6vw, 46px); font-weight: 800; letter-spacing: -0.8px">One simple morning routine</h2></div>
    <div style="display: flex; flex-wrap: wrap; gap: 16px">
      <div class="dt-lift" style="flex: 1 1 230px; background: #FFFFFF; border-radius: 24px; padding: 24px; display: flex; flex-direction: column; gap: 8px"><span style="font-size: 28px; font-weight: 800; color: #0F5C4D">10:00 AM</span><h3 style="margin: 0; font-size: 18px">You get one question</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #56706A">“Lunch today?” Yes opens the menu. No skips today. Ignore it and nothing happens.</p></div>
      <div class="dt-lift" style="flex: 1 1 230px; background: #FFFFFF; border-radius: 24px; padding: 24px; display: flex; flex-direction: column; gap: 8px"><span style="font-size: 28px; font-weight: 800; color: #0F5C4D">10:45 AM</span><h3 style="margin: 0; font-size: 18px">Pick and pay</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #56706A">Choose your meal, see the full price, pay by UPI or card before the cut-off.</p></div>
      <div class="dt-lift" style="flex: 1 1 230px; background: #FFFFFF; border-radius: 24px; padding: 24px; display: flex; flex-direction: column; gap: 8px"><span style="font-size: 28px; font-weight: 800; color: #0F5C4D">11:00 AM</span><h3 style="margin: 0; font-size: 18px">Cooked fresh</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #56706A">The kitchen cooks only what's ordered, then packs and checks every meal.</p></div>
      <div style="flex: 1 1 230px; background: #BFE8D6; border-radius: 24px; padding: 24px; display: flex; flex-direction: column; gap: 8px"><span style="font-size: 28px; font-weight: 800">12 – 1 PM</span><h3 style="margin: 0; font-size: 18px">At your door</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #27403A">Track the rider live and confirm with a delivery code.</p></div>
    </div>
  </section>

  <section class="dt-rise" id="app" style="background: #FFFFFF; padding: 80px 24px">
    <div style="max-width: 1240px; margin: 0 auto; display: flex; flex-direction: column; gap: 72px">
      <div style="text-align: center; max-width: 720px; margin: 0 auto"><div style="font-size: 14px; font-weight: 700; color: #0F5C4D; letter-spacing: 1px">INSIDE THE APP</div><h2 style="margin: 8px 0 0; font-size: clamp(32px, 3.6vw, 46px); font-weight: 800; letter-spacing: -0.8px">Everything you need, nothing you don't</h2></div>

      <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 56px; justify-content: center">
        <div style="flex: 0 0 auto; width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box"><div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/meal.webp') }}" alt="Meal details screen: plate breakdown into grains, dal, vegetables and curd with nutrition" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div></div>
        <div style="flex: 1 1 380px; max-width: 480px; display: flex; flex-direction: column; gap: 14px">
          <span style="font-size: 13px; font-weight: 700; color: #0F5C4D">01 · KNOW YOUR FOOD</span>
          <h3 style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: 800">See exactly what's on the leaf</h3>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Every dish lists its ingredients, portion and declared allergens. Allergy conflicts are flagged clearly before you add to basket.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Optional food preferences help us suggest meals reviewed by a dietitian — you can always browse the full menu.</p>
        </div>
      </div>

      <div style="display: flex; flex-wrap: wrap-reverse; align-items: center; gap: 56px; justify-content: center">
        <div style="flex: 1 1 380px; max-width: 480px; display: flex; flex-direction: column; gap: 14px">
          <span style="font-size: 13px; font-weight: 700; color: #0F5C4D">02 · NO SURPRISES</span>
          <h3 style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: 800">The full price before you pay</h3>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Meal, delivery fee and taxes shown upfront. Choose your slot, pay by UPI or card, and see the cancellation cut-off right there.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">If a payment goes wrong, you are never charged twice — and refunds are tracked in the app.</p>
        </div>
        <div style="flex: 0 0 auto; width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box"><div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/checkout.webp') }}" alt="Checkout screen: address, delivery time, price with taxes and pay from wallet plus UPI" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div></div>
      </div>

      <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 56px; justify-content: center">
        <div style="flex: 0 0 auto; width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box"><div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/tracking.webp') }}" alt="Order tracking screen: arriving 12 to 1 PM, rider details, delivery code and order status" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div></div>
        <div style="flex: 1 1 380px; max-width: 480px; display: flex; flex-direction: column; gap: 14px">
          <span style="font-size: 13px; font-weight: 700; color: #0F5C4D">03 · KITCHEN TO DOOR</span>
          <h3 style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: 800">Follow your lunch all the way</h3>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">See when your meal is cooking, packed and picked up. Call your rider through the app and hand over with a one-time delivery code.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Something missing or late? Raise it from the order and support sees everything instantly.</p>
        </div>
      </div>

      <div style="display: flex; flex-wrap: wrap-reverse; align-items: center; gap: 56px; justify-content: center">
        <div style="flex: 1 1 380px; max-width: 480px; display: flex; flex-direction: column; gap: 14px">
          <span style="font-size: 13px; font-weight: 700; color: #0F5C4D">04 · WALLET</span>
          <h3 style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: 800">Top up once, eat every day</h3>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Add ₹500 to ₹3,000 and each lunch is paid from your wallet in one tap. The app shows how many lunches your balance covers.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Before it runs low you get a reminder, and top-ups of ₹2,000 or more earn bonus reward points.</p>
        </div>
        <div style="flex: 0 0 auto; width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box"><div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/wallet.webp') }}" alt="Wallet screen: balance, lunches covered, top-up amounts and low-balance reminder settings" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div></div>
      </div>

      <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 56px; justify-content: center">
        <div style="flex: 0 0 auto; width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box"><div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/addresses.webp') }}" alt="Delivery addresses screen: saved home, work and family addresses, with the current one picked automatically" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div></div>
        <div style="flex: 1 1 380px; max-width: 480px; display: flex; flex-direction: column; gap: 14px">
          <span style="font-size: 13px; font-weight: 700; color: #0F5C4D">05 · YOUR ADDRESSES</span>
          <h3 style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: 800">It knows where you are</h3>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Save home, work and family addresses with a pin on the map, plus flat, street, landmark and pincode for the rider.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">When you are at one of your saved places, the app picks that address for you automatically.</p>
        </div>
      </div>

      <div style="display: flex; flex-wrap: wrap-reverse; align-items: center; gap: 56px; justify-content: center">
        <div style="flex: 1 1 380px; max-width: 480px; display: flex; flex-direction: column; gap: 14px">
          <span style="font-size: 13px; font-weight: 700; color: #0F5C4D">06 · THE FULL MENU</span>
          <h3 style="margin: 0; font-size: 30px; line-height: 1.15; font-weight: 800">Every dish, every day, in one list</h3>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Browse the whole day's menu by meals, bowls, sides and drinks. Each dish shows its balance of grains, protein, vegetables and dairy, its calories and price.</p>
          <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">Sold-out dishes are marked clearly, and the menu always shows when ordering closes.</p>
        </div>
        <div style="flex: 0 0 auto; width: 254px; padding: 10px; border-radius: 40px; background: #12302A; box-sizing: content-box"><div style="width: 254px; height: 549px; border-radius: 30px; overflow: hidden"><img src="{{ asset('images/app/menu.webp') }}" alt="Full menu screen: meals, bowls and sides with balance bars, calories, prices and add buttons" width="390" height="844" loading="lazy" decoding="async" style="display:block;width:100%;height:100%;object-fit:cover"></div></div>
      </div>
    </div>
  </section>

  <section style="max-width: 1240px; margin: 0 auto; padding: 80px 24px">
    <div style="background: #12302A; color: #FFFFFF; border-radius: 32px; padding: 44px; display: flex; flex-wrap: wrap; gap: 36px">
      <div style="flex: 1 1 340px; min-width: 0"><div style="font-size: 14px; font-weight: 700; color: #BFE8D6; letter-spacing: 1px">YOUR HEALTH, YOUR CHOICE</div><h2 style="margin: 10px 0 0; font-size: 34px; line-height: 1.15; font-weight: 800">Share preferences only if you want to</h2><p style="margin: 14px 0 0; font-size: 16px; line-height: 1.6; color: #A9C4BC">Health details are optional and used only for meal suggestions, with your permission. Skip them and the app works exactly the same.</p></div>
      <div style="flex: 1 1 340px; display: flex; flex-direction: column; gap: 14px; font-size: 15px; line-height: 1.5">
        <div style="display: flex; gap: 12px"><span style="color: #BFE8D6; font-weight: 800">✓</span><span>Consent is off by default — you can withdraw any time</span></div>
        <div style="display: flex; gap: 12px"><span style="color: #BFE8D6; font-weight: 800">✓</span><span>Kitchen staff and riders never see your health profile</span></div>
        <div style="display: flex; gap: 12px"><span style="color: #BFE8D6; font-weight: 800">✓</span><span>No health conditions in notifications or on food labels</span></div>
        <div style="display: flex; gap: 12px"><span style="color: #BFE8D6; font-weight: 800">✓</span><span>Suggestions, not medical advice — no cure claims, ever</span></div>
      </div>
    </div>
  </section>

  <section class="dt-rise" id="kitchens" style="background: #FFFFFF; padding: 80px 24px">
    <div style="max-width: 1240px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 48px; align-items: center">
      <div style="flex: 1 1 420px; min-width: 0; display: flex; flex-direction: column; gap: 16px">
        <div style="font-size: 14px; font-weight: 700; color: #0F5C4D; letter-spacing: 1px">OUR KITCHENS</div>
        <h2 style="margin: 0; font-size: clamp(32px, 3.6vw, 46px); line-height: 1.1; font-weight: 800; letter-spacing: -0.8px">Cooked close to you, held to one standard</h2>
        <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #56706A">A main kitchen develops every recipe, sets quality checks and sources ingredients. Neighbourhood kitchens across Chennai cook and pack your meal locally, so it arrives fresh and on time.</p>
      </div>
      <div style="flex: 1 1 460px; display: flex; flex-wrap: wrap; gap: 14px">
        <div style="flex: 1 1 200px; background: #F3F7F5; border-radius: 20px; padding: 20px"><h3 style="margin: 0 0 6px; font-size: 17px">FSSAI-registered</h3><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #56706A">Every kitchen site licensed and hygiene-logged daily.</p></div>
        <div style="flex: 1 1 200px; background: #F3F7F5; border-radius: 20px; padding: 20px"><h3 style="margin: 0 0 6px; font-size: 17px">Dietitian-reviewed</h3><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #56706A">Recipes and nutrition labels checked before they go on the menu.</p></div>
        <div style="flex: 1 1 200px; background: #F3F7F5; border-radius: 20px; padding: 20px"><h3 style="margin: 0 0 6px; font-size: 17px">Batch-tracked</h3><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #56706A">Each meal is labelled with its batch and packing time.</p></div>
        <div style="flex: 1 1 200px; background: #F3F7F5; border-radius: 20px; padding: 20px"><h3 style="margin: 0 0 6px; font-size: 17px">Cooked to order</h3><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #56706A">We cook what's ordered by the cut-off, which keeps waste low.</p></div>
      </div>
    </div>
  </section>

  <section class="dt-rise" id="areas" style="max-width: 1240px; margin: 0 auto; padding: 80px 24px 40px">
    <div style="background: #0F5C4D; color: #FFFFFF; border-radius: 32px; padding: 44px; display: flex; flex-wrap: wrap; gap: 32px; align-items: center; justify-content: space-between">
      <div style="flex: 1 1 380px; min-width: 0">
        <h2 style="margin: 0; font-size: 34px; font-weight: 800">Where we deliver</h2>
        <p style="margin: 10px 0 0; font-size: 16px; line-height: 1.55; color: #CFE6DF">We open new Chennai areas as each neighbourhood kitchen gets ready. Not covered yet? Tell us where you are and we'll let you know first.</p>
        @if (session('status'))<p role="status" style="margin: 14px 0 0; background: #BFE8D6; color: #12302A; border-radius: 14px; padding: 10px 14px; font-weight: 700; font-size: 14px; max-width: 460px">{{ session('status') }}</p>@endif
        @error('area')<p role="alert" style="margin: 14px 0 0; background: #FBEAE2; color: #8A3A17; border-radius: 14px; padding: 10px 14px; font-weight: 700; font-size: 14px; max-width: 460px">{{ $message }}</p>@enderror
        <form method="post" action="{{ route('notify-me') }}#areas" style="margin-top: 18px; display: flex; flex-wrap: wrap; gap: 8px; background: #FFFFFF; border-radius: 999px; padding: 6px; max-width: 460px">@csrf
          <label for="pin" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0)">Your area or pincode</label>
          <input id="pin" name="area" required maxlength="80" value="{{ old('area') }}" placeholder="Your area or pincode" style="flex: 1 1 180px; min-width: 0; height: 46px; border: 0; padding: 0 16px; font-family: inherit; font-size: 15px; outline: none; background: transparent; color: #12302A">
          <button type="submit" style="height: 46px; padding: 0 20px; border-radius: 999px; border: 0; background: #BFE8D6; cursor: pointer; font-family: inherit; font-weight: 700; font-size: 14px; color: #12302A">Notify me</button>
        </form>
      </div>
      <div style="flex: 1 1 380px; display: flex; flex-wrap: wrap; gap: 10px">
        @forelse ($zones as $z)
          @if ($z->status === 'live')
            <a href="{{ route('area', $z) }}" style="padding: 10px 16px; border-radius: 999px; background: #BFE8D6; color: #12302A; font-weight: 700; font-size: 14px">{{ $z->name }} · Live</a>
          @else
            <span style="padding: 10px 16px; border-radius: 999px; border: 1px solid #5E9C8E; font-weight: 600; font-size: 14px">{{ $z->name }} · {{ $z->status === 'testing' ? 'Soon' : 'Planned' }}</span>
          @endif
        @empty
          <span style="padding: 10px 16px; border-radius: 999px; border: 1px solid #5E9C8E; font-weight: 600; font-size: 14px">Chennai · Opening soon</span>
        @endforelse
      </div>
    </div>
  </section>

  <section style="max-width: 1240px; margin: 0 auto; padding: 40px 24px 80px; display: flex; flex-direction: column; gap: 24px">
    <h2 style="margin: 0; font-size: 30px; font-weight: 800">Coming next</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 16px">
      <div style="flex: 1 1 260px; border: 1.5px dashed #A9BDB6; border-radius: 22px; padding: 24px"><span style="font-size: 12px; font-weight: 700; color: #56706A">PHASE 2</span><h3 style="margin: 6px 0 6px; font-size: 19px">Breakfast</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #56706A">Idli, pongal and more with an earlier morning reminder.</p></div>
      <div style="flex: 1 1 260px; border: 1.5px dashed #A9BDB6; border-radius: 22px; padding: 24px"><span style="font-size: 12px; font-weight: 700; color: #56706A">PHASE 2</span><h3 style="margin: 6px 0 6px; font-size: 19px">Weekly &amp; monthly plans</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #56706A">Prepaid meal plans you can pause before the deadline.</p></div>
      <div style="flex: 1 1 260px; border: 1.5px dashed #A9BDB6; border-radius: 22px; padding: 24px"><span style="font-size: 12px; font-weight: 700; color: #56706A">LATER</span><h3 style="margin: 6px 0 6px; font-size: 19px">Medicines</h3><p style="margin: 0; font-size: 14px; line-height: 1.55; color: #56706A">Through a licensed pharmacy partner, kept fully separate from food.</p></div>
    </div>
  </section>

  <section class="dt-rise" id="faq" style="background: #FFFFFF; padding: 80px 24px">
    <div style="max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px">
      <h2 style="margin: 0 0 12px; font-size: clamp(30px, 3.4vw, 42px); font-weight: 800; text-align: center">Questions people ask</h2>
      <details open style="background: #F3F7F5; border-radius: 18px; padding: 20px 22px"><summary style="font-size: 17px; font-weight: 700">Will saying “Yes” at 10 AM charge me?</summary><p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: #56706A">No. Yes only opens today's menu. You are charged only when you check out and pay.</p></details>
      <details style="background: #F3F7F5; border-radius: 18px; padding: 20px 22px"><summary style="font-size: 17px; font-weight: 700">Do I need a subscription?</summary><p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: #56706A">No. Order single meals whenever you like. Meal plans come later and will always be optional.</p></details>
      <details style="background: #F3F7F5; border-radius: 18px; padding: 20px 22px"><summary style="font-size: 17px; font-weight: 700">Can I cancel an order?</summary><p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: #56706A">Yes, free until the cut-off shown at checkout. After cooking starts, our support team will help.</p></details>
      <details style="background: #F3F7F5; border-radius: 18px; padding: 20px 22px"><summary style="font-size: 17px; font-weight: 700">How does the wallet work?</summary><p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: #56706A">Top up any amount from ₹100 by UPI or card. Each lunch is paid from the wallet first; if it runs short, the rest goes to UPI or card. The app shows how many lunches your balance covers and reminds you before it runs low. Refunds can come back to the wallet instantly.</p></details>
      <details style="background: #F3F7F5; border-radius: 18px; padding: 20px 22px"><summary style="font-size: 17px; font-weight: 700">Do I get a GST invoice?</summary><p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: #56706A">Yes. Every paid order gets a proper GST invoice in the app, and the counter gives a printed receipt.</p></details>

      <details style="background: #F3F7F5; border-radius: 18px; padding: 20px 22px"><summary style="font-size: 17px; font-weight: 700">Is there an iPhone app?</summary><p style="margin: 10px 0 0; font-size: 15px; line-height: 1.6; color: #56706A">{{ $seo['ios_url'] ? 'Yes. Daily Table is on the App Store and Google Play.' : "We're starting with Android. iPhone is planned for a later release." }}</p></details>
    </div>
  </section>

  <section class="dt-rise" id="download" style="max-width: 1240px; margin: 0 auto; padding: 80px 24px">
    <div style="background: #BFE8D6; border-radius: 36px; padding: 52px 44px; display: flex; flex-wrap: wrap; gap: 28px; align-items: center; justify-content: space-between">
      <div style="flex: 1 1 420px; min-width: 0"><h2 style="margin: 0; font-size: clamp(32px, 4vw, 52px); line-height: 1.05; font-weight: 800; letter-spacing: -1px">Tomorrow's lunch starts with one tap.</h2><p style="margin: 12px 0 0; font-size: 17px; color: #27403A">Download Daily Table and we'll ask you at 10 AM.</p></div>
      <a href="{{ $seo['android_url'] }}" style="height: 60px; padding: 0 26px; border-radius: 18px; background: #12302A; color: #FFFFFF; display: flex; align-items: center; gap: 12px"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#BFE8D6" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg><span style="display: flex; flex-direction: column; line-height: 1.15"><span style="font-size: 11px; color: #A9C4BC">Download for</span><span style="font-size: 18px; font-weight: 700">Android</span></span></a>
    </div>
  </section>
</div>
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Restaurant','name'=>'Daily Table','servesCuisine'=>'South Indian','areaServed'=>'Chennai','url'=>url('/'),'priceRange'=>'₹₹'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
