@extends('layouts.site')
@section('content')
<article class="wrap card" style="max-width:820px;padding:40px;line-height:1.7">
@if($page === 'privacy')
  <h1>Privacy &amp; consent</h1>
  <p>We collect your name and mobile number to run your account, and your delivery address to bring your food. Email, date of birth, height, weight and health details are optional.</p>
  <h2 style="font-size:22px;margin-top:24px">Health details</h2>
  <p>We use health conditions only for meal suggestions, and only after you switch consent on. Kitchen staff, delivery partners and cashiers never see them, and they never appear in notifications. You can withdraw consent at any time in the app; we then stop using and delete your condition details.</p>
  <h2 style="font-size:22px;margin-top:24px">Who we share with</h2>
  <p>Payment details go to Razorpay; messages go through our SMS, WhatsApp, email and push providers; your address and order go to the kitchen and your delivery partner. We do not sell your information.</p>
  <h2 style="font-size:22px;margin-top:24px">Marketing</h2>
  <p>Offers are sent only if you opt in, per channel. Reply STOP on WhatsApp or SMS, or use the unsubscribe link in any email.</p>
  <p class="muted small">Contact: [SUPPORT EMAIL]. Review this policy with your legal adviser before launch.</p>
@elseif($page === 'refunds')
  <h1>Refund &amp; cancellation policy</h1>
  <p>Cancel free of charge until the cut-off shown at checkout (10:45 AM for lunch). After cooking starts, contact support from the order screen.</p>
  <p>If something is missing, wrong or late, raise it from the order. Approved refunds go to your Daily Table wallet instantly, or back to the original payment method in 5–7 working days. Refunds never exceed what you paid and are shown on a credit note against your invoice.</p>
@else
  <h1>Terms of use</h1>
  <p>[TERMS TO BE PROVIDED BY YOUR LEGAL ADVISER]</p>
@endif
</article>
@endsection
