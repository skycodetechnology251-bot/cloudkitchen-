@extends('install._layout')
@section('title', $updated ? 'Daily Table updated' : 'Daily Table is ready')
@section('content')
  <h1>{{ $updated ? 'Daily Table is updated' : 'Daily Table is ready' }}</h1>
  <div class="flash flash-ok">
    @if ($updated)
      Your existing database was backed up and updated; customers, orders, wallets and invoices are kept.
    @else
      The tables, roles and permissions are set up.
    @endif
    @if ($created)
      Sign in as <b>{{ $email }}</b> with the password you chose.
    @else
      Sign in with your existing admin account ({{ $email }}).
    @endif
  </div>
  @if ($demo_password)
    <div class="flash flash-warn">Demo staff — <code>kitchen@dailytable.test</code>, <code>cashier@dailytable.test</code> (POS PIN <code>1234</code>) and the rider <code>rider@dailytable.test</code> (phone 9000000001) — use the password <code>{{ $demo_password }}</code>. It is shown only once. Remove demo staff before going live.</div>
  @endif
  <h2>Next steps</h2>
  <ol class="muted" style="padding-left:20px;margin:0">
    <li>Add a cron job in your hosting panel (Cron Jobs → every minute): <code>{{ $cron }}</code>. Without it, background tasks run after page visits, and the 9:30 / 10:00 reminders may be missed.</li>
    <li>Admin → Integrations: Razorpay keys, SMS (OTP), WhatsApp, push, email, Google sign-in and OpenAI.</li>
    <li>In the Razorpay dashboard set the webhook to <code>{{ $url }}/api/v1/webhooks/razorpay</code>.</li>
    <li>Admin → Zones and Kitchens: draw your delivery areas and add your kitchens, then publish menus.</li>
  </ol>
  <div class="actions"><a class="btn" href="{{ $loginUrl }}">Go to sign in</a></div>
  <p class="hint" style="margin-top:14px">This setup page is now switched off. To update Daily Table later, upload the new files and open Admin → System.</p>
@endsection
