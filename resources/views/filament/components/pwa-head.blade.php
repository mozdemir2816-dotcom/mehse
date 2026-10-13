{{--
    PWA (13.10.2026): telefona "uygulama" olarak kurulabilsin — manifest, ikonlar,
    service worker kaydı. AdminPanelProvider HEAD_END. Ayrıntı: public/sw.js
--}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#0A345A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="MEHSE">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@js(asset('sw.js'))).catch(function () {});
        });
    }
</script>
