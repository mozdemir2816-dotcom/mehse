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

    // Adı değişmeyen dosyalar (resim, ?v='siz css/js): önbellekten hızlı göster, arka planda
    // tazele — sunucuda değişen logo/ikon bir sonraki açılışta gelir. Hash'li build
    // dosyaları ve ?v= sürümlü Filament dosyaları hiç değişmez: yalnız önbellek.
    const degismez = font || url.pathname.includes('/build/assets/') || url.searchParams.has('v');

    e.respondWith(caches.open(SURUM).then(async (c) => {
        const kayitli = await c.match(istek);
        if (kayitli && degismez) return kayitli;

        const ag = fetch(istek).then((yanit) => {
            if (yanit.ok || yanit.type === 'opaque') c.put(istek, yanit.clone());
            return yanit;
        });
        if (!kayitli) return ag;

        e.waitUntil(ag.catch(() => {}));
        return kayitli;
    }));
});

// ---- Telefon bildirimi (App\Support\WebPush / TelefonBildirimi) -------------
self.addEventListener('push', (e) => {
    let veri = {};
    try { veri = e.data ? e.data.json() : {}; } catch { veri = { body: e.data && e.data.text() }; }

    e.waitUntil(self.registration.showNotification(veri.title || 'MEHSE', {
        body: veri.body || '',
        tag: veri.tag || 'mehse',
        renotify: true,
        icon: KOK + 'images/pwa/ikon-192.png',
        badge: KOK + 'images/pwa/ikon-192.png',
        lang: 'tr',
        data: { url: veri.url || KOK + 'admin' },
    }));
});

// Dokununca: açık MEHSE penceresi varsa ona geç ve adrese git, yoksa yeni aç.
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const hedef = new URL(e.notification.data?.url || KOK + 'admin', self.location).href;

    e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((pencereler) => {
        const acik = pencereler.find((p) => p.url.startsWith(KOK));
        if (acik) return acik.focus().then((p) => (p || acik).navigate(hedef));
        return self.clients.openWindow(hedef);
    }));
});
