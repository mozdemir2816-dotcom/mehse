{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($sayfalar as $s)
    <url>
        <loc>{{ $s['adres'] }}</loc>
        <lastmod>{{ $s['lastmod'] }}</lastmod>
        <changefreq>{{ $s['changefreq'] }}</changefreq>
        <priority>{{ $s['priority'] }}</priority>
    </url>
@endforeach
</urlset>
