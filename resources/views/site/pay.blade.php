<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pay · Daily Table</title>
<link rel="stylesheet" href="{{ asset('css/dt.css') }}"></head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(160deg,#F0F8E2,#FFF9EF 50%,#FFEBDD)">
<div class="card glass" style="text-align:center;max-width:340px"><h2>Opening secure payment…</h2><p class="muted small">Powered by Razorpay</p><button class="btn" id="retry" style="display:none" onclick="openPay()">Try again</button></div>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
  // Results go back to the Daily Table app through the WebView bridge.
  function send(msg){ if (window.ReactNativeWebView) window.ReactNativeWebView.postMessage(JSON.stringify(msg)); }
  function openPay(){
    var rzp = new Razorpay({
      key: @json($p['key']), order_id: @json($p['order']), amount: {{ (int) $p['amount'] }}, currency: 'INR',
      name: 'Daily Table', description: @json($p['desc'] ?? 'Payment'),
      prefill: { name: @json($p['name'] ?? ''), contact: @json($p['contact'] ?? ''), email: @json($p['email'] ?? '') },
      theme: { color: '#1F8A4C' },
      handler: function (r) { send({ type: 'success', razorpay_order_id: r.razorpay_order_id, razorpay_payment_id: r.razorpay_payment_id, razorpay_signature: r.razorpay_signature }); },
      modal: { ondismiss: function () { send({ type: 'dismissed' }); document.getElementById('retry').style.display = 'inline-flex'; } }
    });
    rzp.on('payment.failed', function (r) { send({ type: 'failed', reason: r.error && r.error.description }); });
    rzp.open();
  }
  openPay();
</script>
</body></html>
