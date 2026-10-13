{{--
    "Telefona yükle" bandı (PWA, 13.10.2026) — yalnız telefonda/tablette ve uygulama
    henüz kurulu değilken. Android/Chrome: tarayıcının kurulum penceresini açar.
    iPhone/iPad Safari: kurulum penceresi olmadığından Paylaş → Ana Ekrana Ekle tarifi.
    "Kapat" 14 gün hatırlanır. Stiller: mobil.css "PWA kurulum bandı".
--}}
<div class="mehse-pwa-bant" id="mehse-pwa-bant" hidden role="dialog" aria-label="MEHSE uygulamasını yükle">
    <img src="{{ asset('images/pwa/ikon-192.png') }}" alt="" class="mehse-pwa-bant-ikon">
    <div class="mehse-pwa-bant-metin">
        <strong>MEHSE'yi telefona yükle</strong>
        <span data-android>Ana ekrandan tek dokunuşla, tam ekran açılır.</span>
        <span data-ios hidden>Alttaki <b>Paylaş</b> <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12M7 8l5-5 5 5M5 12v8h14v-8"/></svg> düğmesine, sonra <b>Ana Ekrana Ekle</b>'ye dokunun.</span>
    </div>
    <button type="button" class="mehse-pwa-bant-yukle" data-android>Yükle</button>
    <button type="button" class="mehse-pwa-bant-kapat" aria-label="Kapat">&times;</button>
</div>
<script>
    (function () {
        var bant = document.getElementById('mehse-pwa-bant');
        if (!bant) return;

        var ANAHTAR = 'mehse_pwa_bant_kapali';
        var kurulu = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        var telefon = window.matchMedia('(max-width: 1023px)').matches;
        var kapali = false;
        try { kapali = Number(localStorage.getItem(ANAHTAR) || 0) > Date.now(); } catch (e) {}
        if (kurulu || !telefon || kapali) return;

        var istem = null;
        var goster = function (ios) {
            bant.querySelectorAll('[data-android]').forEach(function (e) { e.hidden = ios; });
            bant.querySelectorAll('[data-ios]').forEach(function (e) { e.hidden = !ios; });
            bant.hidden = false;
        };
        var gizle = function (hatirla) {
            bant.hidden = true;
            if (hatirla) { try { localStorage.setItem(ANAHTAR, String(Date.now() + 14 * 864e5)); } catch (e) {} }
        };

        // Android / Chrome / Edge / Samsung: tarayıcı kurulabilir olduğunu söyleyince.
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            istem = e;
            goster(false);
        });
        window.addEventListener('appinstalled', function () { gizle(true); });

        // iPhone / iPad Safari (Chrome iOS ve diğerleri Ana Ekrana Ekle'yi desteklemez).
        var ua = navigator.userAgent;
        var ios = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        if (ios && /Safari/.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua)) goster(true);

        bant.querySelector('.mehse-pwa-bant-yukle').addEventListener('click', function () {
            if (!istem) return;
            istem.prompt();
            istem.userChoice.finally(function () { istem = null; gizle(true); });
        });
        bant.querySelector('.mehse-pwa-bant-kapat').addEventListener('click', function () { gizle(true); });
    })();
</script>
