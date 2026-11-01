{{-- Herkese açık site sayfaları (yasal sayfalar, /sektor/*) — tanıtım sayfasının renk ve tipografisiyle.
     Alt sayfalar 'govde' bölümünü doldurur; 'seo_baslik' varsa <title> odur. --}}
@php
    $logoIkon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8.2-8 9-4.8-.8-8-4.5-8-9V6l8-3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>';
    $saglayici = config('isg.kayit.saglayici');
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('seo_baslik') @yield('seo_baslik') @else @yield('baslik') | Mehse İSG @endif</title>
    <meta name="description" content="@yield('aciklama')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    {{-- Open Graph / Twitter (01.11.2026): paylaşım kartı tüm site sayfalarında; görsel ana sayfayla aynı. --}}
    @php
        $ogGorsel = 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1200&h=630&q=70';
        $ogBaslik = $__env->hasSection('seo_baslik') ? trim($__env->yieldContent('seo_baslik')) : trim($__env->yieldContent('baslik')).' | Mehse İSG';
        $ogAciklama = trim($__env->yieldContent('aciklama'));
        // Bölüm içerikleri (section ikinci argümanı) Blade tarafından zaten kaçışlı → aşağıda {!! !!}.
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Mehse İSG">
    <meta property="og:title" content="{!! $ogBaslik !!}">
    <meta property="og:description" content="{!! $ogAciklama !!}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogGorsel }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{!! $ogBaslik !!}">
    <meta name="twitter:description" content="{!! $ogAciklama !!}">
    <meta name="twitter:image" content="{{ $ogGorsel }}">
    <meta name="theme-color" content="#0F172A">
    <link rel="stylesheet" href="{{ asset('css/tanitim.css') }}">
    @stack('head')
</head>
<body>
<a href="#icerik" class="atla">İçeriğe geç</a>

<header class="ust">
    <div class="kap">
        <a href="{{ url('/') }}" class="logo" aria-label="Mehse ana sayfa"><span class="logo-kutu">{!! $logoIkon !!}</span>Mehse</a>
        <div class="ust-sag">
            <a href="{{ url('/') }}" class="ust-link">Ana Sayfa</a>
            <a href="@yield('teklif_url', url('/').'#teklif')" class="dugme dugme-yesil dugme-kucuk">Teklif Al</a>
        </div>
    </div>
</header>

@yield('govde')

<footer class="alt-bar">
    <div class="telif">
        <div class="kap yasal-alt">
            <p>© {{ date('Y') }} Mehse İSG. Tüm hakları saklıdır.</p>
            <p>
                <a href="{{ route('kvkk') }}">KVKK Aydınlatma Metni</a> ·
                <a href="{{ route('cerez') }}">Çerez Politikası</a> ·
                <a href="mailto:{{ $saglayici['eposta'] }}">{{ $saglayici['eposta'] }}</a> ·
                <a href="tel:+905064754855">{{ $saglayici['telefon'] }}</a>
            </p>
        </div>
    </div>
</footer>
</body>
</html>
