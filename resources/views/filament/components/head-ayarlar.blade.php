{{--
    <head> en başı (AdminPanelProvider HEAD_START). Alpine başlamadan, ilk boyamadan
    ÖNCE çalışır ki sayfa önce varsayılan görünümde açılıp sonra değişmesin.

    Tema önceliği: hesaba kayıtlı tema (Ayarlar / topbar seçicisi) > tarayıcıdaki
    eski localStorage seçimi (yalnız misafir sayfaları) > varsayılan (mehse). Yoğunluk ve yazı boyutu yalnız hesaptan gelir.
--}}
@php
    $kullanici = auth()->user();
    $ayar = $kullanici instanceof \App\Models\User ? \App\Support\KullaniciAyarlari::hepsi($kullanici) : null;

    $sunucu = [
        // Giriş yapmışsa tema her zaman hesaptan (kayıt yoksa VARSAYILAN_TEMA): tarayıcıda
        // kalmış eski 'klasik' seçimi yeni varsayılanı ezmesin (13.10.2026).
        'tema' => $kullanici instanceof \App\Models\User ? \App\Support\KullaniciAyarlari::tema($kullanici) : null,
        'varsayilan' => \App\Support\KullaniciAyarlari::VARSAYILAN_TEMA,
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
            if (sunucu.temalar.indexOf(tema) === -1) tema = sunucu.varsayilan;
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
        window.mehseTemaUygula(tema || sunucu.varsayilan, false);

        kok.dataset.mehseYogunluk = sunucu.yogunluk;
        kok.dataset.mehseYazi = sunucu.yazi;
    })();

    // Yazı boyutu "küçük/büyük" CSS zoom ile yapılıyor (tasarim.css). Filament'in açılır menü
    // konumlandırıcısı (floating-ui) zoom'u hesaba katmıyor: left/top zoom oranıyla çarpılıp menü
    // sol üst köşede açılıyordu (08.11.2026, Risk Kütüphanesi "İçe / Dışa Aktar").
    // floating-ui konum yazdıkça menü, düğmenin ekrandaki yerine göre yeniden yerleştirilir
    // (yerleşim x-float.placement.* değerinden; aşağıda yer yoksa yukarı açılır). Normal yazıda dokunmaz.
    (function () {
        function toplamZoom(el) {
            var z = 1;
            for (var e = el; e && e.nodeType === 1; e = e.parentElement) {
                var d = parseFloat(getComputedStyle(e).zoom);
                if (d && d !== 1) z *= d;
            }
            return z;
        }

        function yerlesim(panel) {
            for (var i = 0; i < panel.attributes.length; i++) {
                var ad = panel.attributes[i].name;
                if (ad.indexOf('x-float') === 0) {
                    var m = ad.match(/\.placement\.([a-z-]+)/);
                    return m ? m[1] : 'bottom';
                }
            }
            return 'bottom';
        }

        function duzelt(panel) {
            var s = panel.style;
            if (!s.left || !s.top) return;
            if (panel.dataset.mehseZoomKonum === s.left + '|' + s.top) return; // kendi yazdığımız değer

            var z = toplamZoom(panel);
            if (Math.abs(z - 1) < 0.001) return;

            var tetik = panel.previousElementSibling;
            if (!tetik || !tetik.classList.contains('fi-dropdown-trigger')) return;

            var yer = yerlesim(panel).split('-');
            if (yer[0] !== 'bottom' && yer[0] !== 'top') return; // yan açılanlara dokunma

            // left/top = 0 iken panelin ekrandaki yeri = kapsayıcının başlangıç noktası.
            s.left = '0px';
            s.top = '0px';
            var o = panel.getBoundingClientRect();
            var r = tetik.getBoundingClientRect();
            var w = o.width, h = o.height, bosluk = 8 * z, kenar = 8;

            var asagi = yer[0] === 'bottom';
            if (asagi && r.bottom + bosluk + h > innerHeight && r.top - bosluk - h >= 0) asagi = false;
            else if (!asagi && r.top - bosluk - h < 0 && r.bottom + bosluk + h <= innerHeight) asagi = true;

            var x = yer[1] === 'end' ? r.right - w : (yer[1] === 'start' ? r.left : r.left + (r.width - w) / 2);
            x = Math.max(kenar, Math.min(x, innerWidth - w - kenar));
            var y = asagi ? r.bottom + bosluk : r.top - bosluk - h;

            s.left = ((x - o.left) / z) + 'px';
            s.top = ((y - o.top) / z) + 'px';
            panel.dataset.mehseZoomKonum = s.left + '|' + s.top;
        }

        new MutationObserver(function (kayitlar) {
            var yazi = document.documentElement.dataset.mehseYazi;
            if (!yazi || yazi === 'normal') return;
            for (var i = 0; i < kayitlar.length; i++) {
                var el = kayitlar[i].target;
                if (el.classList && el.classList.contains('fi-dropdown-panel')) duzelt(el);
            }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['style'], subtree: true });
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700&family=Source+Serif+4:wght@600&display=swap">
