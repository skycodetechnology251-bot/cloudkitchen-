@extends('layouts.admin')
@section('title', 'Branding')
@push('head')
@foreach($fonts as $key => $f)<link href="https://fonts.googleapis.com/css2?family={{ $f[2] }}&display=swap" rel="stylesheet">
@endforeach
<style>
  .bd-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:18px;align-items:start}
  @media (max-width:1100px){.bd-grid{grid-template-columns:1fr}}
  .bd-sec{margin-bottom:16px}
  .bd-sec h2{font-size:18px;display:flex;align-items:center;gap:10px;margin:0 0 4px}
  .bd-sec h2 .n{width:28px;height:28px;border-radius:9px;display:grid;place-items:center;color:#fff;font-size:14px;font-family:var(--font-body)}
  .logo-drop{display:flex;gap:16px;align-items:center;flex-wrap:wrap;border:2px dashed #E7C5D6;border-radius:18px;padding:16px;background:#FFF7FB}
  .logo-box{width:150px;height:90px;border-radius:14px;background:repeating-conic-gradient(#F1F1F1 0 25%,#fff 0 50%) 0 0/16px 16px;display:flex;align-items:center;justify-content:center;border:1px solid var(--line);overflow:hidden}
  .logo-box img{max-width:90%;max-height:80%;object-fit:contain}
  .fonts{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
  .fcard{position:relative;border:2px solid var(--line);border-radius:16px;padding:12px;cursor:pointer;background:#fff;display:flex;flex-direction:column;gap:2px;transition:border-color .15s,transform .15s}
  .fcard:hover{transform:translateY(-2px)}
  .fcard input{position:absolute;opacity:0;pointer-events:none}
  .fcard .aa{font-size:30px;line-height:1.1}
  .fcard b{font-size:13px;font-family:var(--font-body)}
  .fcard small{font-size:11px;color:var(--muted);font-family:var(--font-body)}
  .fcard:has(input:checked){border-color:var(--acc1);background:var(--acc-soft);box-shadow:0 0 0 3px var(--acc-soft)}
  .fcard:has(input:focus-visible){outline:3px solid var(--acc2);outline-offset:2px}
  .pv{position:sticky;top:16px}
  .pv-site{border-radius:22px;overflow:hidden;border:1px solid var(--line);background:#FFF9EF}
  .pv-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:rgba(255,255,255,.8);border-bottom:1px solid #EDE3D2}
  .pv-logo{display:flex;align-items:center;gap:8px;font-weight:800;font-size:18px}
  .pv-logo img{height:32px;width:auto;max-width:130px;object-fit:contain}
  .pv-ico{width:32px;height:32px;border-radius:10px;background:conic-gradient(from 200deg,#8CC63F,#1F8A4C,#F4B400,#F2711C,#8CC63F);display:flex;align-items:center;justify-content:center}
  .pv-ico svg{background:#fff;border-radius:7px;padding:3px}
  .pv-body{padding:22px}
  .pv-body h3{font-size:34px;line-height:1.05;margin:8px 0 10px;color:#1A2E22}
  .pv-body p{color:#5A6B60;line-height:1.6;margin:0 0 14px}
  .pv-btn{display:inline-flex;height:40px;align-items:center;padding:0 16px;border-radius:999px;background:linear-gradient(135deg,#8CC63F,#1F8A4C);color:#fff;font-weight:800;font-size:14px}
  .pv-kick{display:inline-flex;font-size:11px;font-weight:800;letter-spacing:.08em;padding:5px 10px;border-radius:999px;background:#E4F5E9;color:#0F4D2C}
  .pv-admin{margin-top:12px;border-radius:18px;overflow:hidden;display:flex;border:1px solid var(--line);background:#fff}
  .pv-side{width:120px;background:linear-gradient(180deg,#0F5C4D,#24224F);color:#fff;padding:12px;font-size:12px;display:flex;flex-direction:column;gap:8px}
  .pv-side .pv-logo{font-size:13px;color:#fff}
  .pv-side .pv-logo img{height:24px;background:#fff;border-radius:6px;padding:2px}
  .pv-side span{opacity:.75}
  .pv-main{flex:1;padding:14px}
  .pv-main h4{font-size:20px;margin:0 0 8px}
</style>
@endpush
@section('content')
<div class="row between"><div><h1>Branding</h1><p class="muted" style="margin:4px 0 0">Your logo, business name and fonts, used on the website, admin, staff login, invoices and payslips.</p></div></div>

<form method="post" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" id="bf">@csrf
<div class="bd-grid">
  <div>
    <section class="card bd-sec">
      <h2><span class="n t-pink">1</span> Logo &amp; name</h2>
      <p class="small muted" style="margin:0 0 12px">A wide logo with a transparent background (PNG) looks best. It is also used as the browser tab icon.</p>
      <div class="logo-drop">
        <div class="logo-box" id="logoBox">@if($logo)<img src="{{ $logo }}" alt="Current logo" id="logoImg">@else<span class="small muted" id="logoEmpty">No logo yet</span>@endif</div>
        <div style="flex:1;min-width:220px">
          <label for="logo" class="small" style="font-weight:800;display:block;margin-bottom:6px">Upload a logo (PNG, JPG or WEBP, up to 2 MB)</label>
          <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" style="min-height:auto;padding:8px;background:#fff">
          @error('logo')<div class="small" style="color:var(--bad-ink);font-weight:700;margin-top:4px">{{ $message }}</div>@enderror
          @if($logo)<label class="row small" style="font-weight:700;margin-top:8px;gap:6px"><input type="checkbox" name="remove_logo" value="1" id="removeLogo"> Remove the logo and use the built-in leaf mark</label>@endif
        </div>
      </div>
      <div class="grid g2" style="margin-top:14px;gap:12px">
        <div class="field" style="margin:0"><label for="name">Business name</label><input id="name" name="name" maxlength="60" required value="{{ old('name', $name) }}"></div>
        <label class="row" style="font-weight:700;align-self:end;gap:8px;min-height:44px"><input type="checkbox" name="show_name" value="1" id="showName" @checked(old('show_name', $showName))> Show the name next to the logo</label>
      </div>
      <p class="small muted" style="margin:8px 0 0">Turn the name off if your logo already contains it.</p>
    </section>

    <section class="card bd-sec">
      <h2><span class="n t-purple">2</span> Heading font</h2>
      <p class="small muted" style="margin:0 0 12px">For big titles on the website and page titles in the admin.</p>
      <div class="fonts" role="radiogroup" aria-label="Heading font">
        @foreach($fonts as $key => [$label, $stack, $spec, $style])
          <label class="fcard"><input type="radio" name="font_head" value="{{ $key }}" data-stack="{{ $stack }}" @checked(old('font_head', $head) === $key)><span class="aa" style="font-family:{{ $stack }}">Aa Lunch</span><b>{{ $label }}</b><small>{{ $style }}</small></label>
        @endforeach
      </div>
    </section>

    <section class="card bd-sec">
      <h2><span class="n t-blue">3</span> Text font</h2>
      <p class="small muted" style="margin:0 0 12px">For paragraphs, menus, buttons and forms everywhere. Pick an easy-to-read one.</p>
      <div class="fonts" role="radiogroup" aria-label="Text font">
        @foreach($fonts as $key => [$label, $stack, $spec, $style])
          <label class="fcard"><input type="radio" name="font_body" value="{{ $key }}" data-stack="{{ $stack }}" @checked(old('font_body', $body) === $key)><span style="font-family:{{ $stack }};font-size:14px;line-height:1.4">Fresh, home-style lunch at 12–1.</span><b>{{ $label }}</b><small>{{ $style }}</small></label>
        @endforeach
      </div>
    </section>

    <div class="row" style="gap:10px;position:sticky;bottom:0;padding:14px 0;background:linear-gradient(transparent,var(--bg) 30%)">
      <button class="btn">Save branding</button>
      <a class="btn ghost" href="{{ route('home') }}" target="_blank" rel="noopener">Open the website</a>
    </div>
  </div>

  <aside class="pv" aria-label="Live preview">
    <section class="card">
      <h2 style="font-size:17px;margin-bottom:10px">Live preview</h2>
      <div class="pv-site" id="pv">
        <div class="pv-head"><div class="pv-logo pv-l"></div><span class="pv-btn" style="height:32px;font-size:12px">Get the app</span></div>
        <div class="pv-body">
          <span class="pv-kick">🌿 CHENNAI'S HEALTHY DAILY LUNCH</span>
          <h3 class="pv-h">Fresh lunch, sorted by 10 AM.</h3>
          <p class="pv-t">Every morning the app asks one question: lunch today? Pick from a fresh home-style menu and it reaches you between 12 and 1.</p>
          <span class="pv-btn pv-t">See today's menu</span>
        </div>
      </div>
      <div class="pv-admin">
        <div class="pv-side"><div class="pv-logo pv-l"></div><span class="pv-t">Overview</span><span class="pv-t">Orders</span><span class="pv-t">Daily menu</span></div>
        <div class="pv-main"><h4 class="pv-h">Good evening 👋</h4><p class="small muted pv-t" style="margin:0">Orders today: 27 · Sales ₹6,752</p></div>
      </div>
      <p class="small muted" style="margin:10px 0 0">The preview changes as you choose. Nothing is saved until you press Save branding.</p>
    </section>
  </aside>
</div>
</form>
@endsection
@push('scripts')
<script>
(function () {
  var f = document.getElementById('bf');
  var logoSrc = @json($logo);
  var leaf = '<span class="pv-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1F8A4C" stroke-width="2.2" stroke-linecap="round"><path d="M4 11h16a8 8 0 0 1-16 0Z"/><path d="M12 7c0-2 1.5-3.5 3.5-3.5 0 2-1.5 3.5-3.5 3.5ZM12 7c0-1.6-1-3-3-3"/></svg></span>';
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function stack(name) { var r = f.querySelector('input[name="' + name + '"]:checked'); return r ? r.dataset.stack : ''; }
  function render() {
    var remove = document.getElementById('removeLogo');
    var src = remove && remove.checked ? null : logoSrc;
    var name = esc(document.getElementById('name').value || 'Your business');
    var show = document.getElementById('showName').checked || !src;
    var html = (src ? '<img src="' + src + '" alt="">' : leaf) + (show ? '<span class="pv-h">' + name + '</span>' : '');
    document.querySelectorAll('.pv-l').forEach(function (el) { el.innerHTML = html; });
    document.querySelectorAll('.pv-h').forEach(function (el) { el.style.fontFamily = stack('font_head'); });
    document.querySelectorAll('.pv-t').forEach(function (el) { el.style.fontFamily = stack('font_body'); });
  }
  document.getElementById('logo').addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      logoSrc = e.target.result;
      var box = document.getElementById('logoBox');
      box.innerHTML = '<img src="' + logoSrc + '" alt="New logo">';
      var rm = document.getElementById('removeLogo'); if (rm) rm.checked = false;
      render();
    };
    reader.readAsDataURL(file);
  });
  f.addEventListener('input', render);
  f.addEventListener('change', render);
  render();
})();
</script>
@endpush
