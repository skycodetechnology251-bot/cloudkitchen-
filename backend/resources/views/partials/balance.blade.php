@php($b = $dish->balance ?? null)
@if($b)
<span aria-label="Plate balance" style="display:flex;gap:2px;height:6px;border-radius:99px;overflow:hidden;max-width:160px">
  <span class="grow" style="width:{{ $b['grains'] ?? 0 }}%;background:#E9B949"></span><span class="grow" style="width:{{ $b['protein'] ?? 0 }}%;background:#E07A4F"></span><span class="grow" style="width:{{ $b['veg'] ?? 0 }}%;background:#5DB37E"></span><span class="grow" style="width:{{ $b['dairy'] ?? 0 }}%;background:#9CC7EC"></span>
</span>
@endif
