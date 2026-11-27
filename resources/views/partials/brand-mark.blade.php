{{-- Logo from Admin → Branding (or the built-in leaf mark), with the business name unless turned off. --}}
@if(\App\Support\Brand::logoUrl())
  <img src="{{ \App\Support\Brand::logoUrl() }}" alt="{{ \App\Support\Brand::name() }}" class="brand-logo" style="height:{{ $size ?? 40 }}px;width:auto;max-width:{{ ($size ?? 40) * 4 }}px;object-fit:contain;display:block">
@else
  <i class="brand-icon"><svg width="{{ round(($size ?? 40) * .75) }}" height="{{ round(($size ?? 40) * .75) }}" viewBox="0 0 24 24" fill="none" stroke="#1F8A4C" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 11h16a8 8 0 0 1-16 0Z"/><path d="M12 7c0-2 1.5-3.5 3.5-3.5 0 2-1.5 3.5-3.5 3.5ZM12 7c0-1.6-1-3-3-3"/></svg></i>
@endif
@if(\App\Support\Brand::showName() || ! \App\Support\Brand::logoUrl())<span class="brand-name">{{ \App\Support\Brand::name() }}</span>@endif
