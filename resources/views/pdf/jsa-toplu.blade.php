@php
    /** Toplu JSA çıktısı — kütüphaneden seçilen birden çok analiz tek belgede,
        her biri kendi sayfasında (bkz. jsa-stiller: `.sayfa + .sayfa`). */
    $uzman = $firma?->igu;
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
@include('pdf.partials.jsa-stiller')
</head>
<body>
@foreach ($sablonlar as $sablon)
    @include('pdf.partials.jsa-govde', [
        'sablon' => $sablon,
        'firma' => $firma,
        'uzman' => $uzman,
        'yeniSayfa' => ! $loop->first,
    ])
@endforeach
</body>
</html>
