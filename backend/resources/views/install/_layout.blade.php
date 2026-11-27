<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title', 'Set up Daily Table')</title>
{{-- Self-contained styles: the wizard must work even before anything else is configured. --}}
<style>
*{box-sizing:border-box}
body{margin:0;font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#12302A;background:#F3F7F5}
.top{background:linear-gradient(120deg,#0F5C4D,#1D7564);padding:28px 20px 70px}
.top .in{max-width:860px;margin:0 auto;display:flex;align-items:center;gap:12px}
.logo{width:44px;height:44px;border-radius:14px;background:#BFE8D6;color:#0F5C4D;display:grid;place-items:center;font-weight:900;font-size:20px}
.top b{font-size:20px}
.wrap{max-width:860px;margin:-48px auto 40px;padding:0 16px}
.card{background:#fff;border-radius:22px;box-shadow:0 18px 40px -24px rgba(0,0,0,.35);padding:28px}
h1{margin:0 0 6px;font-size:26px}
h2{font-size:16px;margin:26px 0 10px}
.muted{color:#56706A}
.checks{list-style:none;padding:0;margin:0;border:1px solid #D9E6E1;border-radius:14px;overflow:hidden;columns:2;column-gap:0}
.checks li{display:flex;justify-content:space-between;gap:8px;padding:7px 12px;border-bottom:1px solid #E9F1EE;font-size:13px;break-inside:avoid}
.ok{color:#18803d;font-weight:700}.bad{color:#d2342b;font-weight:700}
fieldset{border:1px solid #D9E6E1;border-radius:16px;padding:16px 18px 18px;margin:18px 0 0}
legend{font-weight:800;padding:0 6px}
.fields{display:grid;grid-template-columns:1fr 1fr;gap:12px 16px}
.wide{grid-column:1/-1}
label{display:block;font-weight:600;font-size:13px;margin-bottom:4px}
input[type=text],input[type=email],input[type=password],input[type=number],select{width:100%;padding:11px 13px;border-radius:12px;border:1px solid #CFDDD8;background:#FBFDFC;font:inherit}
input:focus,select:focus{outline:none;border-color:#0F5C4D;box-shadow:0 0 0 3px rgba(15,92,77,.18)}
.is-invalid{border-color:#d2342b!important}
.hint{display:block;font-size:12px;color:#56706A;margin-top:4px}
.err{display:block;font-size:12px;color:#d2342b;margin-top:4px;font-weight:600}
.check{display:flex;gap:10px;align-items:flex-start;font-weight:500;font-size:14px}
.check input{margin-top:4px;width:17px;height:17px;accent-color:#0F5C4D}
.btn{display:inline-block;border:0;border-radius:999px;padding:14px 28px;font:inherit;font-weight:800;background:#0F5C4D;color:#fff;cursor:pointer;text-decoration:none;box-shadow:0 10px 24px -12px #0F5C4D}
.btn[disabled]{opacity:.5;cursor:not-allowed}
.actions{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:22px}
.flash{border-radius:14px;padding:12px 14px;margin:16px 0;font-size:14px}
.flash-error{background:#fdecea;color:#9c221b;border:1px solid #f5c2bd}
.flash-ok{background:#e8f7ee;color:#14532d;border:1px solid #b7e4c7}
.flash-warn{background:#fff4d6;color:#7a4b00;border:1px solid #ffd77a}
code{background:#E9F1EE;padding:1px 6px;border-radius:6px;font-size:.92em}
@media(max-width:640px){.fields{grid-template-columns:1fr}.checks{columns:1}.card{padding:20px}}
</style>
</head>
<body>
<div class="top"><div class="in"><span class="logo">DT</span><b style="color:#fff">Daily Table</b></div></div>
<div class="wrap"><div class="card">
@yield('content')
</div></div>
@stack('scripts')
</body>
</html>
