{{--
    <head> en başı (AdminPanelProvider HEAD_START). Alpine başlamadan, ilk boyamadan
    ÖNCE çalışır ki sayfa önce varsayılan görünümde açılıp sonra değişmesin.

    Tema önceliği: hesaba kayıtlı tema (Ayarlar / topbar seçicisi) > tarayıcıdaki
    eski localStorage seçimi > klasik. Yoğunluk ve yazı boyutu yalnız hesaptan gelir.
--}}
@php
    $kullanici = auth()->user();
    $kayitli = $kullanici instanceof \App\Models\User ? (array) ($kullanici->ayarlar ?? []) : [];
    $ayar = $kullanici instanceof \App\Models\User ? \App\Support\KullaniciAyarlari::hepsi($kullanici) : null;

    $sunucu = [
        'tema' => isset($kayitli['tema']) ? \App\Support\KullaniciAyarlari::tema($kullanici) : null,
        'yogunluk' => $ayar['yogunluk'] ?? 'rahat',
        'yazi' => $ayar['yazi_boyutu'] ?? 'normal',
        'kaydetUrl' => $kullanici ? route('mehse.ayar.tema') : null,
        'csrf' => csrf_token(),
        'temalar' => array_keys(\App\Support\KullaniciAyarlari::TEMALAR),
    ];
@endphp
<script>
    // Tek seferlik geçiş: panel açık temaya çevrildi, ama tarayıcıda daha önce
    // kaydedilmiş "theme: dark" tercihi defaultThemeMode'u geçersiz kılıyordu
    // (bkz. filament/resources/js/dark-mode.js). Bir kereliğine "light"a zorla.
    (function () {
        try {
            if (!localStorage.getItem('mehse_acik_tema_geciti_20260904')) {
                localStorage.setItem('theme', 'light');
                localStorage.setItem('mehse_acik_tema_geciti_20260904', '1');
            }
        } catch (e) {}
    })();

    (function () {
        var sunucu = @json($sunucu);
        var kok = document.documentElement;

        // tema-secici.blade.php çağırır; kaydet=true ise hesaba da yazar.
        window.mehseTemaUygula = function (tema, kaydet) {
            if (sunucu.temalar.indexOf(tema) === -1) tema = 'klasik';
            kok.dataset.mehseTema = tema;
            try { localStorage.setItem('mehse_tema', tema); } catch (e) {}

            if (kaydet && sunucu.kaydetUrl) {
                fetch(sunucu.kaydetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': sunucu.csrf,
                    },
                    body: JSON.stringify({ tema: tema }),
                    credentials: 'same-origin',
                }).catch(function () {});
            }
        };

        var tema = sunucu.tema;
        if (!tema) {
            try { tema = localStorage.getItem('mehse_tema'); } catch (e) {}
        }
        window.mehseTemaUygula(tema || 'klasik', false);

        kok.dataset.mehseYogunluk = sunucu.yogunluk;
        kok.dataset.mehseYazi = sunucu.yazi;
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700&display=swap">
