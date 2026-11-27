{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ url('/') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
@foreach($zones as $z)
  <url><loc>{{ route('area', $z) }}</loc><lastmod>{{ $z->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
  <url><loc>{{ route('privacy') }}</loc><priority>0.3</priority></url>
  <url><loc>{{ route('refunds') }}</loc><priority>0.3</priority></url>
</urlset>
