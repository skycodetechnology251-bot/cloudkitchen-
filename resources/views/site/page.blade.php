@extends('layouts.site')
@section('content')
<div class="s-wrap">
<section class="page-hero" style="max-width:860px;margin-left:auto;margin-right:auto">
  <span class="s-kicker" style="background:rgba(255,255,255,.18);color:#fff">{{ ['privacy' => '🔒 Your data', 'refunds' => '💚 Fair and simple', 'terms' => '📄 The legal bit'][$page] ?? 'Daily Table' }}</span>
  <h1>{{ ['privacy' => 'Privacy & consent', 'refunds' => 'Refund & cancellation policy', 'terms' => 'Terms of use'][$page] ?? '' }}</h1>
</section>
<article class="doc">
@if($page === 'privacy')
  <p>We collect your name and mobile number to run your account, and your delivery address to bring your food. Email, date of birth, height, weight and health details are optional.</p>
  <h2>Health details</h2>
  <p>We use health conditions only for meal suggestions, and only after you switch consent on. Kitchen staff, delivery partners and cashiers never see them, and they never appear in notifications. You can withdraw consent at any time in the app; we then stop using and delete your condition details.</p>
  <h2>Who we share with</h2>
  <p>Payment details go to Razorpay; messages go through our SMS, WhatsApp, email and push providers; your address and order go to the kitchen and your delivery partner. We do not sell your information.</p>
  <h2>Marketing</h2>
  <p>Offers are sent only if you opt in, per channel. Reply STOP on WhatsApp or SMS, or use the unsubscribe link in any email.</p>
  <p class="muted small">Contact: [SUPPORT EMAIL]. Review this policy with your legal adviser before launch.</p>
@elseif($page === 'refunds')
  <p>Cancel free of charge until the cut-off shown at checkout (10:45 AM for lunch). After cooking starts, contact support from the order screen.</p>
  <p>If something is missing, wrong or late, raise it from the order. Approved refunds go to your Daily Table wallet instantly, or back to the original payment method in 5–7 working days. Refunds never exceed what you paid and are shown on a credit note against your invoice.</p>
@else
  <p>[TERMS TO BE PROVIDED BY YOUR LEGAL ADVISER]</p>
@endif
</article>
</div>
@endsection
