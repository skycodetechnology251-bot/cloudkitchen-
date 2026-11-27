{{-- Fonts, favicon and font variables chosen in Admin → Branding --}}
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@foreach(\App\Support\Brand::fontUrls() as $fontUrl)<link href="{{ $fontUrl }}" rel="stylesheet">
@endforeach
@if(\App\Support\Brand::logoUrl())<link rel="icon" href="{{ \App\Support\Brand::logoUrl() }}">@else<link rel="icon" href="{{ asset('favicon.png') }}"><link rel="apple-touch-icon" href="{{ asset('images/app/app-icon.png') }}">@endif
<style>{!! \App\Support\Brand::cssVars() !!}</style>
