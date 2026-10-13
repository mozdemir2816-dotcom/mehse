/*
 * MEHSE service worker (PWA, 13.10.2026).
 *
 * Bilerek dar tutuldu: yalnız sürümlü statik dosyalar (Vite build, Filament
 * css/js, ikon, font) önbelleğe alınır. Sayfalar, Livewire istekleri ve
 * /storage dosyaları ASLA saklanmaz — çalışan / sağlık verisi telefonda
 * kalmasın (KVKK). Bağlantı yoksa sayfa yerine offline.html gösterilir.
 *
 * Değişiklikte SURUM'u artırın; eski önbellek activate'te silinir.
 */
const SURUM = 'mehse-v1';
const KOK = new URL('./', self.location).href;
const CEVRIMDISI = KOK + 'offline.html';
// offline.html kendi içinde (ikon gömülü) — hangi adreste gösterilirse gösterilsin çalışır.
const ON_YUKLE = [CEVRIMDISI];

// Uzun ömürlü önbelleğe alınabilecek yollar (adları ya hash'li ya ?v= sürümlü).
const STATIK = /\/(build\/assets|css|js|fonts|images)\//;

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(SURUM).then((c) => c.addAll(ON_YUKLE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((adlar) => Promise.all(adlar.filter((a) => a !== SURUM).map((a) => caches.delete(a))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (e) => {
    const istek = e.request;
    if (istek.method !== 'GET') return;

    const url = new URL(istek.url);

    // Sayfa açılışı: her zaman ağdan; bağlantı yoksa çevrimdışı sayfası.
    if (istek.mode === 'navigate') {
        e.respondWith(fetch(istek).catch(() => caches.match(CEVRIMDISI)));
        return;
    }

    // Google Fonts dosyaları (değişmez) ve kendi statik dosyalarımız: önce önbellek.
    const font = url.hostname === 'fonts.gstatic.com';
    const statik = url.origin === self.location.origin && STATIK.test(url.pathname) && !url.pathname.includes('/storage/');
    if (!font && !statik) return;

    e.respondWith(
        caches.open(SURUM).then((c) =>
            c.match(istek).then((kayitli) => kayitli || fetch(istek).then((yanit) => {
                if (yanit.ok || yanit.type === 'opaque') c.put(istek, yanit.clone());
                return yanit;
            })),
        ),
    );
});
