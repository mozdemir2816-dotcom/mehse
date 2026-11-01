{{-- /sektor/{slug} — App\Http\Controllers\SektorController; içerik config/sektorler.php. --}}
@extends('layouts.site')

@php
    $rozet = ['Az Tehlikeli' => 'az', 'Tehlikeli' => 'orta', 'Çok Tehlikeli' => 'cok'];
    $ikonUyari = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>';
    $ikonKonum = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg>';
    $ok = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    $avantajlar = [
        ['<path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>', 'Otomotiv mühendisliği altyapısı', 'Üretim hattını, makineyi ve prosesi mühendis gözüyle okuyoruz; önlemler kâğıtta kalmaz, hatta uygulanabilir olur.'],
        ['<path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>', 'Bursa içi yerinde hızlı keşif', 'Bursa\'daki OSB\'lere ve sanayi bölgelerine kısa sürede ulaşıyor, ücretsiz ön keşfi işyerinizde yapıyoruz.'],
        ['<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 17.5h7M17.5 14v7"/>', 'Fine-Kinney ve 5×5 dijital raporlama', 'Risk değerlendirmesi, saha bulguları ve DÖF\'ler bulut panelde tutulur; Excel ve PDF raporlar tek tıkla hazır.'],
    ];
@endphp

@section('seo_baslik', $sektor['seo_baslik'])
@section('baslik', $sektor['baslik'])
@section('aciklama', $sektor['aciklama'])
@section('teklif_url', $teklifUrl)

@section('govde')
<main id="icerik">
    <section class="sektor-ust">
        <div class="kap">
            <p class="ust-yazi">BURSA SANAYİSİ İÇİN İSG</p>
            <h1>{{ $sektor['baslik'] }} İş Güvenliği Hizmeti</h1>
            <p class="sektor-giris">{{ $sektor['giris'] }}</p>
            <div class="sektor-rozetler">
                <span class="tehlike-rozet tehlike-{{ $rozet[$sektor['tehlike']] ?? 'orta' }}">{!! $ikonUyari !!} {{ $sektor['tehlike'] }} sınıf</span>
                @foreach ($sektor['bolgeler'] as $b)
                    <span class="bolge-rozet">{!! $ikonKonum !!} {{ $b }}</span>
                @endforeach
            </div>
            <a href="{{ $teklifUrl }}" class="dugme dugme-yesil">Ücretsiz Keşif ve Teklif İsteyin {!! $ok !!}</a>
        </div>
    </section>

    <section class="bolum">
        <div class="kap">
            <p class="ust-yazi">RİSK HARİTASI</p>
            <h2>Öne Çıkan Kritik Riskler ve Denetim Başlıkları</h2>
            <p class="giris">Ziyaretlerimizde bu başlıkları sahada kontrol eder, her bulguyu fotoğraf, sorumlu ve termin ile takibe alırız.</p>
            <div class="kartlar risk-kartlar">
                @foreach ($sektor['riskler'] as [$baslik, $aciklama])
                    <article class="kart">
                        <span class="ikon ikon-sari">{!! $ikonUyari !!}</span>
                        <h3>{{ $baslik }}</h3>
                        <p>{{ $aciklama }}</p>
                    </article>
                @endforeach
            </div>

            <div class="nace-kutu">
                <h3>Tehlike sınıfı NACE koduna göre belirlenir</h3>
                <p>Bu sektördeki işyerlerinin çoğu <strong>{{ $sektor['tehlike'] }}</strong> sınıftadır; ancak işyerinizin kesin sınıfı SGK'ya kayıtlı NACE koduna göre belirlenir. Örnekler (İşyeri Tehlike Sınıfları Tebliği):</p>
                <ul>
                    @foreach ($sektor['nace'] as [$kod, $tanim, $sinif])
                        <li><span class="nace-kod">{{ $kod }}</span> {{ $tanim }} <span class="tehlike-rozet kucuk tehlike-{{ $rozet[$sinif] ?? 'orta' }}">{{ $sinif }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="bolum sektor-avantaj">
        <div class="kap">
            <p class="ust-yazi">NEDEN MEHSE</p>
            <h2>Mehse İSG ile Çalışmanın Avantajları</h2>
            <div class="kartlar avantaj-kartlar">
                @foreach ($avantajlar as [$yol, $baslik, $aciklama])
                    <article class="kart">
                        <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $yol !!}</svg></span>
                        <h3>{{ $baslik }}</h3>
                        <p>{{ $aciklama }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="sektor-cta">
        <div class="kap">
            <h2>{{ $sektor['baslik'] }} işletmeniz için ücretsiz ön keşif</h2>
            <p>Formu doldurun; sektörünüz ve tehlike sınıfınız forma otomatik eklenir, en kısa sürede size dönüş yapalım.</p>
            <a href="{{ $teklifUrl }}" class="dugme dugme-yesil">Teklif Formuna Git {!! $ok !!}</a>
        </div>
    </section>
</main>
@endsection
