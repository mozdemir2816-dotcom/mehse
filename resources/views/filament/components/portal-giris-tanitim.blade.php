{{--
    /isyeri ve /egitim giriş ekranlarının sol tanıtım paneli (SIMPLE_LAYOUT_START).
    Parametreler: $etiket (rozet), $baslik, $metin, $maddeler (3-4 kısa satır), $vurgu$vurgu ('teal'|'blue'|'pano').
    Bu panellerde Blade ikon bileşenleri kapalı (DisableBladeIconComponents) — ikon kullanılmaz.
    Stiller: css/filament/portal/theme.css "Bölünmüş giriş ekranı".
--}}
<aside class="portal-giris portal-giris--{{ $vurgu }}" aria-label="mehse tanıtım">
    <div class="portal-giris-ust">
        <img src="{{ asset('images/marka/mehse-logo-acik.png') }}" alt="MEHSE İş Sağlığı ve Güvenliği" class="portal-giris-logo">
        <span class="portal-giris-etiket">{{ $etiket }}</span>
    </div>

    <div class="portal-giris-orta">
        <h2 class="portal-giris-baslik">{{ $baslik }}</h2>
        <p class="portal-giris-metin">{{ $metin }}</p>

        <ul class="portal-giris-liste">
            @foreach ($maddeler as $madde)
                <li>{{ $madde }}</li>
            @endforeach
        </ul>
    </div>

    <div class="portal-giris-dip">© {{ now()->year }} MEHSE İş Sağlığı ve Güvenliği</div>
</aside>
