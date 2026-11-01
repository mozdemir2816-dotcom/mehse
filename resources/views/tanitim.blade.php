@php
    $eposta = 'mozdemir2816@gmail.com';
    $telefon = '0506 475 48 55';
    $telUri = '+905064754855';
    $waNo = '905064754855';
    $waLink = 'https://wa.me/905064754855?text=Merhaba,%20%C4%B0SG%20hizmetleri%20ve%20yaz%C4%B1l%C4%B1m%20hakk%C4%B1nda%20bilgi%20almak%20istiyorum.';
    $girisUrl = url('/admin/login');
    $kayitUrl = url('/admin/register');
    $osgbUrl = url('/admin/osgb-basvuru');
    // Sektör sayfasından gelindiyse (/?sektor=slug#teklif): sektör forma eklenir, tehlike sınıfı önceden seçilir.
    $sektorSlug = old('sektor', request()->query('sektor'));
    $formSektor = is_string($sektorSlug) ? (config('sektorler', [])[$sektorSlug] ?? null) : null;
    $seciliSinif = old('sinif', $formSektor['tehlike'] ?? null);
    $baslik = 'Mehse İSG | Mühendislik Temelli İSG Danışmanlığı ve Bulut Yönetim Platformu';
    $aciklama = '6331 ve ISO 45001 uyumlu mühendislik danışmanlığı, saha denetimi, risk analizi ve İSG profesyonelleri için bulut yönetim yazılımı.';
    $ikon = fn (string $yol) => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$yol.'</svg>';
    $moduller = [
        ['<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>', 'Risk Değerlendirmesi', '5×5 Matris, Fine-Kinney ve sektörel şablonlarla dakikalar içinde risk analizi.'],
        ['<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>', 'Kontrol Merkezi', 'Hangi işyerinde hangi evrak eksik, süresi yaklaşan yenilemeler tek ekranda.'],
        ['<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>', 'Eğitim &amp; Sertifikasyon', 'Katılım formları, ön/son testler, uzaktan eğitim portalı ve tek tıkla sertifika.'],
        ['<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>', 'İSG Kurulu Yönetimi', 'Zorunlu üye denetimi, periyodik kurul gündemi, kararlar ve resmî tutanak.'],
        ['<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>', 'Periyodik Kontrol Takibi', 'İş ekipmanları envanteri, periyodik muayene takvimi ve teftiş paketi.'],
        ['<path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/>', 'Acil Durum Eylem Planı', 'Otomatik senaryolar, tahliye planları, ekip atamaları ve afiş paketi.'],
    ];
    $sss = [
        ['İşyerimizin İSG hizmeti alma yasal zorunluluğu var mı?', '6331 sayılı Kanun gereğince çalışan sayısı ve tehlike sınıfına göre her işyerinin iş güvenliği uzmanı, işyeri hekimi görevlendirmesi ve risk değerlendirmesi yaptırması zorunludur.'],
        ['Ücretsiz ön keşif süreci nasıl işler?', 'Uzman mühendisimiz işyerinizi sahada ziyaret ederek mevcut riskleri, ortam şartlarını ve yasal evrak durumunu inceler; hiçbir taahhüt gerektirmeyen durum raporu sunar.'],
        ['Mehse İSG Bulut Yazılımını kimler kullanabilir?', 'Bireysel İSG uzmanları ücretsiz uzman hesabı açabilir; OSGB firmaları ise 90 günlük ücretsiz deneme için başvurabilir. Hesabınız onaylanıp erişiminiz tanımlandığında portföyünüzdeki işletmeleri tek panelden yönetirsiniz.'],
        ['İSG-KATİP sözleşmesi ne kadar sürede tamamlanır?', 'Karşılıklı mutabakat ve e-Devlet/İSG-KATİP onaylarının ardından resmi atama süreci aynı iş günü içinde tamamlanarak yasal koruma başlar.'],
    ];
    $matris = [
        ['m1', 'm1', 'm2', 'm3', 'm3'],
        ['m1', 'm1', 'm2', 'm3', 'm4'],
        ['m1', 'm2', 'm3', 'm4', 'm4'],
        ['m2', 'm3', 'm4', 'm5', 'm5'],
        ['m3', 'm4', 'm5', 'm5', 'm6'],
    ];
    $tik = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>';
    $ok = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    $wa = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.2 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.8-.1 1.3z"/></svg>';
    $logoIkon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.2 8.2-8 9-4.8-.8-8-4.5-8-9V6l8-3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>';
    $jsonBayrak = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;
    $jsonLdHizmet = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ProfessionalService',
        'name' => 'Mehse İSG',
        'description' => 'Mühendislik temelli iş sağlığı ve güvenliği danışmanlığı, saha denetimi, eğitim hizmetleri ve İSG uzmanları için bulut yönetim yazılımı.',
        'url' => 'https://mehse.com/',
        'email' => $eposta,
        'telephone' => $telUri,
        'areaServed' => ['Bursa', 'Türkiye'],
        'serviceType' => ['Risk Değerlendirmesi', 'Saha Denetimi', 'Acil Durum Eylem Planı', 'İSG Eğitimi', 'Periyodik Kontrol Koordinasyonu'],
    ], $jsonBayrak);
    $jsonLdSss = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($m) => ['@type' => 'Question', 'name' => $m[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $m[1]]], $sss),
    ], $jsonBayrak);
    // Alt bölüm: Mühendislik Standartları & Yasal Altyapı — [başlık, açıklama, etiket, rozet rengi].
    $standartlar = [
        ['Fine-Kinney & 5×5 Matris', 'Olasılık, frekans ve şiddet parametreleriyle matematiksel risk skorlama.', 'Risk Analizi', 'mavi'],
        ['LOTO / EKED Prosedürleri', 'Bakım-onarım sırasında tehlikeli enerjinin kesilmesi, kilitlenmesi, etiketlenmesi ve doğrulanması.', 'Enerji Kontrolü', 'kirmizi'],
        ['TS EN 15635 Depo Raf Kontrolü', 'Ağır yük raflarında yeşil / turuncu / kırmızı hasar sınıflandırması ve periyodik muayene.', 'Depolama', 'sari'],
        ['ATEX / Patlamadan Korunma Dokümanı', 'EN 60079-10 esaslı bölge (zon) sınıflandırması; yanıcı toz ve gaz güvenliği.', 'Patlayıcı Ortam', 'mor'],
        ['EN 388 & Mekanik KKD Standartları', 'Kesilme direnci seviyeleri ve dönen millerde kapılmayı önleyen eldiven seçim matrisi.', 'KKD', 'yesil'],
        ['İSG-KATİP & 6331 Mevzuat Uyumu', 'Süre atamaları, kurul periyotları ve eğitim takvimi mevzuata göre takip edilir.', 'Mevzuat', 'mavi'],
    ];
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $baslik }}</title>
    <meta name="description" content="{{ $aciklama }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://mehse.com/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Mehse İSG">
    <meta property="og:title" content="{{ $baslik }}">
    <meta property="og:description" content="{{ $aciklama }}">
    <meta property="og:url" content="https://mehse.com/">
    <meta property="og:image" content="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&amp;fit=crop&amp;w=1200&amp;h=630&amp;q=70">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $baslik }}">
    <meta name="twitter:description" content="{{ $aciklama }}">
    <meta name="twitter:image" content="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&amp;fit=crop&amp;w=1200&amp;h=630&amp;q=70">
    <meta name="theme-color" content="#0F172A">
    <link rel="stylesheet" href="{{ asset('css/tanitim.css') }}">
    {{-- JSON-LD üstteki php bloğunda kurulur (yorumda bile at-php yazmayın: Blade blok başı sanıyor): şablonda '@context' yazılırsa Blade onu @context komutu sanıyor. --}}
    <script type="application/ld+json">{!! $jsonLdHizmet !!}</script>
    <script type="application/ld+json">{!! $jsonLdSss !!}</script>
</head>
<body>
<a href="#icerik" class="atla">İçeriğe geç</a>

<header class="ust">
    <div class="kap">
        <a href="#" class="logo" aria-label="Mehse ana sayfa"><span class="logo-kutu">{!! $logoIkon !!}</span>Mehse</a>
        <nav class="menu" aria-label="Ana menü">
            <a href="#hizmetler">Hizmetler</a>
            <a href="#sektorler">Sektörler</a>
            <a href="#yazilim">İSG Yazılımı</a>
            <a href="#surec">Süreç</a>
            <a href="#sss">S.S.S.</a>
        </nav>
        <div class="ust-sag">
            <a href="{{ $girisUrl }}" class="ust-link uye-giris">Üye Girişi</a>
            <a href="{{ $kayitUrl }}" class="dugme dugme-cerceve dugme-kucuk ust-kayit">Uzman Kaydı</a>
            <a href="#teklif" class="dugme dugme-yesil dugme-kucuk ust-teklif">Teklif Al</a>
            <button type="button" class="menu-dugme" id="menuDugme" aria-controls="mobilMenu" aria-expanded="false" aria-label="Menüyü aç">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
    <nav class="mobil" id="mobilMenu" aria-label="Mobil menü">
        <a href="#hizmetler">Hizmetler</a>
        <a href="#sektorler">Sektörler</a>
        <a href="#yazilim">İSG Yazılımı</a>
        <a href="#surec">Süreç</a>
        <a href="#sss">S.S.S.</a>
        <a href="{{ $girisUrl }}">Üye Girişi</a>
        <a href="{{ $kayitUrl }}" class="dugme dugme-cerceve">Uzman Kaydı</a>
        <a href="#teklif" class="dugme dugme-yesil">Teklif Al</a>
    </nav>
</header>

<main id="icerik">

    {{-- ============ HERO ============ --}}
    <section class="hero">
        <img class="hero-gorsel" src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1800&q=70" alt="" width="1800" height="1200" fetchpriority="high">
        <div class="hero-katman"></div>
        <div class="hero-izgara"></div>
        <div class="kap">
            <div>
                <p class="rozet"><i></i>6331 SAYILI KANUN · ISO 45001 UYUMLU</p>
                <h1>Evrakla Değil, <em>Mühendislikle</em> Güvenli İşyeri.</h1>
                <p class="hero-alt">Bursa ve sanayi bölgelerinde iş kazası risklerini ve cezaları sıfıra indiren saha mühendisliği. İSG profesyonelleri için ise tüm firmalarını tek ekrandan yöneten bulut platformu.</p>
                <div class="hero-dugmeler">
                    <a href="#teklif" class="dugme dugme-yesil">Fabrikanız İçin Keşif &amp; Teklif Alın {!! $ok !!}</a>
                    <a href="{{ $osgbUrl }}" class="dugme dugme-cerceve">OSGB'ler İçin 90 Gün Ücretsiz Deneme</a>
                </div>
            </div>
            <div aria-hidden="true">
                <div class="matris-kart">
                    <div class="matris-ust"><b>5×5 RİSK MATRİSİ</b><span>Örnek görünüm</span></div>
                    <div class="matris">
                        @foreach ($matris as $satir) @foreach ($satir as $h)<i class="{{ $h }}"></i>@endforeach @endforeach
                    </div>
                    <div class="matris-alt"><span>Olasılık → Şiddet</span><span>Kabul edilebilir · Acil önlem</span></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ GÜVEN BARI ============ --}}
    <section class="guven" aria-label="Güven unsurları">
        <div class="kap">
            <ul>
                <li>
                    <span class="ikon ikon-yesil"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg></span>
                    <div><p class="buyuk">0</p><p class="baslik">Kaza Hedefi</p><p class="alt">Her işyerinde ölçülebilir, izlenebilir hedef.</p></div>
                </li>
                <li>
                    <span class="ikon ikon-mavi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 12l2 2 4-4"/><path d="M12 3l8 3v6c0 4.5-3.2 8.2-8 9-4.8-.8-8-4.5-8-9V6l8-3z"/></svg></span>
                    <div><p class="orta">Tam Uyum</p><p class="baslik">Mevzuat &amp; İSG-KATİP</p><p class="alt">Sözleşme, atama ve bildirimler eksiksiz yönetilir.</p></div>
                </li>
                <li>
                    <span class="ikon ikon-sari"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg></span>
                    <div><p class="orta">Uçtan uca</p><p class="baslik">Saha Gözlem &amp; DÖF Yönetimi</p><p class="alt">Tespit edilen her uygunsuzluk kapanana kadar izlenir.</p></div>
                </li>
            </ul>
        </div>
    </section>

    {{-- ============ KİMLER İÇİN (SEGMENT) — 3 kitle ============ --}}
    <section class="segment" aria-labelledby="segment-baslik">
        <div class="kap">
            <p class="ust-yazi">KİMLER İÇİN</p>
            <h2 id="segment-baslik" class="segment-baslik">Kime Nasıl Çözüm Üretiyoruz?</h2>
            <div class="segment-izgara uclu">
                <article class="seg-kart">
                    <span class="seg-rozet">Kurumsal OSGB Çözümü</span>
                    <p class="seg-etiket">OSGB YÖNETİCİLERİ İÇİN</p>
                    <h3>OSGB Firmaları İçin Merkezi Operasyon &amp; Yazılım</h3>
                    <ul class="tik-liste">
                        <li>{!! $tik !!}<span>Çoklu uzman, hekim ve sağlık personeli portföy yönetimi</span></li>
                        <li>{!! $tik !!}<span>İSG-KATİP sözleşme periyotları ve atama takvimi</span></li>
                        <li>{!! $tik !!}<span>Kurumsal logonuzla tek tıkla çıkan DÖF, kurul ve denetim raporları</span></li>
                        <li>{!! $tik !!}<span>90 gün ücretsiz deneme ve bulut altyapısı</span></li>
                    </ul>
                    <a href="{{ $osgbUrl }}" class="dugme dugme-yesil">OSGB Paneli Kurumsal Başvuru {!! $ok !!}</a>
                </article>
                <article class="seg-kart seg-one">
                    <span class="seg-rozet">Uzman &amp; Hekim Çözümü</span>
                    <p class="seg-etiket">BİREYSEL ÇALIŞAN İSG UZMANLARI İÇİN</p>
                    <h3>Serbest Çalışan Uzmanlar İçin Dijital Saha Çantası</h3>
                    <ul class="tik-liste">
                        <li>{!! $tik !!}<span>Fine-Kinney ve 5×5 matrisli otomatik risk analiz modülü</span></li>
                        <li>{!! $tik !!}<span>Sahada cep telefonundan fotoğraflı uygunsuzluk (DÖF) raporlama</span></li>
                        <li>{!! $tik !!}<span>Yasal eğitim katılım tutanağı ve sınav değerlendirme otomasyonu</span></li>
                        <li>{!! $tik !!}<span>Ekipman periyodik kontrol ve ortam ölçümü hatırlatma takvimi</span></li>
                    </ul>
                    <a href="{{ $kayitUrl }}" class="dugme dugme-yesil">Uzman Hesabınızı Oluşturun {!! $ok !!}</a>
                </article>
                <article class="seg-kart">
                    <span class="seg-rozet">Bursa Yerinde Danışmanlık</span>
                    <p class="seg-etiket">FABRİKA VE SANAYİ İŞVERENLERİ İÇİN</p>
                    <h3>Sanayi Tesisleri İçin Mühendislik &amp; İSG Saha Danışmanlığı</h3>
                    <ul class="tik-liste">
                        <li>{!! $tik !!}<span>Otomotiv, talaşlı imalat ve lojistikte pres güvenliği, LOTO ve PKD çalışmaları</span></li>
                        <li>{!! $tik !!}<span>Müfettiş ve kalite denetimlerine (IATF 16949, ISO 45001) hazır, düzenli yasal arşiv</span></li>
                        <li>{!! $tik !!}<span>A Sınıfı İş Güvenliği Uzmanı ve Otomotiv Mühendisi olarak yerinde rehberlik</span></li>
                        <li>{!! $tik !!}<span>Tesisinize özel acil durum ve tahliye tatbikatı kurguları</span></li>
                    </ul>
                    <a href="#teklif" class="dugme dugme-yesil">Ücretsiz Saha Keşfi İsteyin {!! $ok !!}</a>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ HİBRİD MODEL: YAZILIM + YERİNDE DANIŞMANLIK ============ --}}
    <section class="bolum sinerji" aria-labelledby="sinerji-baslik">
        <div class="kap">
            <p class="ust-yazi">HİBRİD MODEL</p>
            <h2 id="sinerji-baslik">Tek Başına Yazılım Yetmez, Sahasız Danışmanlık Eksik Kalır</h2>
            <div class="sinerji-izgara">
                <article class="sinerji-kart sinerji-gri">
                    <p class="sinerji-ust">Dijital Altyapı</p>
                    <p class="sinerji-etiket"><span class="ikon ikon-mavi"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.5 19a4.5 4.5 0 100-9h-1.8A7 7 0 104 16.3"/><path d="M8 17h9.5"/></svg></span>Mehse İSG Bulut Platformu</p>
                    <ul class="sinerji-liste">
                        <li><strong>Mobilden fotoğraflı DÖF:</strong> Sahada çekilen fotoğraflarla uygunsuzluk kaydı ve PDF DÖF çıktısı.</li>
                        <li><strong>Periyodik kontrol alarmları:</strong> Kompresör, vinç, forklift ve paratoner muayeneleri yaklaşınca otomatik hatırlatma.</li>
                        <li><strong>Yasal eğitim takvimi:</strong> Eğitim yenileme tarihleri, katılım tutanakları ve sınavlar tek yerde.</li>
                        <li><strong>Veri güvenliği:</strong> KVKK’ya uygun işleme, SSL/TLS şifreli bağlantı, Türkiye’deki sunucularda her gün yedekleme.</li>
                    </ul>
                    <a href="{{ $kayitUrl }}" class="dugme dugme-lacivert">Platformu Ücretsiz Deneyin {!! $ok !!}</a>
                </article>
                <div class="sinerji-arti" aria-hidden="true"><span>+</span><small>Hibrid</small></div>
                <article class="sinerji-kart sinerji-yesil">
                    <p class="sinerji-ust">Saha Gücü</p>
                    <p class="sinerji-etiket"><span class="ikon ikon-yesil"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3h8l1 4 4 2v12H3V9l4-2 1-4z"/><path d="M8 3c0 2 1.8 3 4 3s4-1 4-3M12 6v15M3 14h6M15 14h6"/></svg></span>Yerinde Mühendislik &amp; Danışmanlık</p>
                    <ul class="sinerji-liste">
                        <li><strong>Makine ve pres emniyeti:</strong> Işık bariyeri, çift el kumandası, koruyucular ve acil durdurma kontrolü.</li>
                        <li><strong>LOTO / EKED prosedürleri:</strong> Bakım-onarımda tehlikeli enerjinin kesilmesi, kilitlenmesi ve doğrulanması.</li>
                        <li><strong>IATF 16949 denetim hazırlığı:</strong> Müşteri ve kalite denetimlerine hazır, düzenli İSG kayıtları.</li>
                        <li><strong>Uygulamalı yangın tatbikatları:</strong> Tesise özel senaryolarla tahliye tatbikatı ve çalışan eğitimi.</li>
                    </ul>
                    <a href="#teklif" class="dugme dugme-yesil">Saha Keşfi İsteyin {!! $ok !!}</a>
                </article>
            </div>
            <p class="sinerji-serit"><span class="sinerji-serit-rozet">Hibrid Avantaj</span> Saha danışmanlığı verdiğimiz tesisler Mehse İSG yazılımını <strong>ücretsiz</strong> kullanır; yazılımı kullanan uzmanlar saha raporlarını kâğıtla uğraşmadan, sahada hazırlar.</p>
        </div>
    </section>

    {{-- ============ HİZMETLER ============ --}}
    <section id="hizmetler" class="bolum">
        <div class="kap">
            <p class="ust-yazi">HİZMETLERİMİZ</p>
            <h2>Riski kâğıtta değil, sahada yönetiyoruz</h2>
            <p class="giris">Beş ana hizmet alanı, tek bir takip sistemi altında birleşir.</p>

            <div class="kartlar">
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 17.5h7M17.5 14v7"/></svg></span>
                    <h3>Risk Değerlendirmesi</h3>
                    <p>Müfettiş denetimlerine ve ISO 45001 kriterlerine tam uyumlu Fine-Kinney ve FMEA puanlamaları.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h9"/></svg></span>
                    <h3>Saha Denetimi &amp; Dijital DÖF</h3>
                    <p>Sahadaki her uygunsuzluk fotoğraflanır, sorumlu atanır ve giderilene kadar sistemde izlenir.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg></span>
                    <h3>Acil Durum &amp; Tatbikat</h3>
                    <p>Yangın, deprem, tahliye planları, acil durum ekipleri ve resmi tatbikat raporlama.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg></span>
                    <h3>Yasal İSG Eğitimleri</h3>
                    <p>Çalışanlara yönelik uygulamalı eğitimler, sınavlar ve denetime hazır arşivleme.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart genis">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 00-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 005.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5z"/></svg></span>
                    <h3>Periyodik Kontrol &amp; Ölçüm</h3>
                    <p>Kaldırma, basınçlı kap kontrolleri ve ortam ölçümlerinin (gürültü, toz, kimyasal) eksiksiz takibi.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ İSG YAZILIMI ============ --}}
    <section id="yazilim" class="bolum yazilim">
        <div class="kap">
            <p class="ust-yazi">İSG BULUT YAZILIMI</p>
            <h2>İSG uzmanları ve OSGB'ler için tek panel</h2>
            <p class="giris">Firmalarınızın risk, eğitim, kurul, periyodik kontrol ve acil durum süreçlerini tek bulut panelden yönetin.</p>
            <div class="modul-izgara">
                @foreach ($moduller as [$yol, $ad, $metin])
                    <article class="modul">
                        <span class="modul-icon-wrapper">{!! $ikon($yol) !!}</span>
                        <div><h3>{!! $ad !!}</h3><p>{{ $metin }}</p></div>
                    </article>
                @endforeach
            </div>
            <div class="yazilim-cta">
                <a href="{{ $kayitUrl }}" class="dugme dugme-yesil">Ücretsiz Uzman Kaydı {!! $ok !!}</a>
                <a href="{{ $osgbUrl }}" class="dugme dugme-acik">OSGB: 90 Gün Ücretsiz Deneme</a>
                <p>Zaten üye misiniz? <a href="{{ $girisUrl }}">Giriş yapın</a></p>
            </div>
        </div>
    </section>

    {{-- ============ SEKTÖRLER ============ --}}
    <section id="sektorler" style="padding:80px 0">
        <div class="kap">
            <div class="sektor-kutu bolum" style="padding:32px">
                <div>
                    <p class="ust-yazi">SEKTÖRLER</p>
                    <h2>Sektörün tehlikesine göre çalışırız</h2>
                    <p class="giris">Her sektörün tehlike profili farklıdır; risk kütüphanemiz de faaliyete özeldir.</p>
                </div>
                <ul class="sektor-liste">
                    <li><a href="{{ route('sektor.show', 'otomotiv-yan-sanayi') }}">Otomotiv Yan Sanayi →</a></li>
                    <li><a href="{{ route('sektor.show', 'metal-talasli-imalat') }}">Metal &amp; Talaşlı İmalat →</a></li>
                    <li><a href="{{ route('sektor.show', 'lojistik-ve-depolama') }}">Depolama &amp; Lojistik →</a></li>
                    <li>İnşaat &amp; Şantiye</li><li>Gıda &amp; İçecek</li><li>Kimya &amp; Boya</li><li>Sağlık Tesisleri</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ============ SÜREÇ ============ --}}
    <section id="surec" class="bolum surec">
        <div class="kap">
            <p class="ust-yazi">SÜREÇ</p>
            <h2>3 adımda sürekli güvenlik</h2>
            <ol class="adimlar">
                <li class="adim"><span class="no">1</span><h3>Keşif &amp; Ön İnceleme</h3><p>İşyerini yerinde geziyor, faaliyetleri, ekipmanları ve mevcut evrakları inceliyoruz. Ücretsiz ön analiz çıktısı elinize geçer.</p></li>
                <li class="adim"><span class="no">2</span><h3>Risk &amp; Uygunsuzluk Raporu</h3><p>Tehlikeler puanlanır, uygunsuzluklar kök nedenleriyle raporlanır; her madde için önlem, sorumlu ve termin belirlenir.</p></li>
                <li class="adim"><span class="no">3</span><h3>Sürekli Saha Takibi &amp; İSG-KATİP Sözleşmesi</h3><p>Düzenli ziyaretlerle DÖF'ler kapatılır; sözleşme, atama ve eğitim kayıtları İSG-KATİP ile uyumlu yürütülür.</p></li>
            </ol>
        </div>
    </section>

    {{-- ============ TEKLİF FORMU ============ --}}
    <section id="teklif" class="bolum">
        <div class="kap teklif-izgara">
            <div>
                <p class="ust-yazi">TEKLİF AL</p>
                <h2>Ücretsiz ön keşif ve analiz isteyin</h2>
                <p class="giris">Formu doldurun, işyerinizin çalışan sayısı ve tehlike sınıfına göre size uygun kapsamı ve fiyatı iş günü içinde iletelim.</p>
                <ul class="tik-liste">
                    <li>{!! $tik !!}Yerinde keşif, taahhütsüz</li>
                    <li>{!! $tik !!}Mevcut risk ve evrak durumunuzun kısa değerlendirmesi</li>
                    <li>{!! $tik !!}Net kapsam ve sabit aylık bedel</li>
                </ul>
                <p class="giris" style="margin-top:28px;font-size:.92rem">
                    Doğrudan ulaşın:
                    <a href="tel:+905064754855" style="font-weight:600;color:var(--lacivert-900)">{{ $telefon }}</a> ·
                    <a href="mailto:{{ $eposta }}" style="font-weight:600;color:var(--lacivert-900)">{{ $eposta }}</a>
                </p>
            </div>

            @if (session('teklif_gonderildi'))
                <div class="form basarili" role="status">
                    <h3>Talebiniz alındı, teşekkürler.</h3>
                    <p>En kısa sürede sizi arayacağız. Acil ise hemen yazabilirsiniz:</p>
                    <a href="https://wa.me/{{ $waNo }}?text={{ rawurlencode('Merhaba, sitenizden teklif talebi gönderdim.') }}" target="_blank" rel="noopener" class="dugme dugme-yesil">{!! $wa !!} WhatsApp'tan Yazın</a>
                </div>
            @else
                <form class="form" method="post" action="{{ route('tanitim.teklif') }}" aria-label="Teklif formu">
                    @csrf
                    {{-- Bot tuzağı: insanlara görünmez, doluysa talep kaydedilmez. --}}
                    <div class="bal" aria-hidden="true"><label>Web sitesi<input type="text" name="web" tabindex="-1" autocomplete="off"></label></div>
                    @if ($formSektor)
                        <input type="hidden" name="sektor" value="{{ $sektorSlug }}">
                        <p class="form-sektor">Sektör: {{ $formSektor['baslik'] }}</p>
                    @endif

                    @if ($errors->any())
                        <div class="hata-kutu" role="alert">
                            @foreach ($errors->all() as $hata)<div>{{ $hata }}</div>@endforeach
                        </div>
                    @endif

                    <label class="alan">Firma Adı
                        <input type="text" name="firma" value="{{ old('firma') }}" required maxlength="150" autocomplete="organization">
                    </label>
                    <div class="iki">
                        <label class="alan">İletişim Kişisi
                            <input type="text" name="kisi" value="{{ old('kisi') }}" required maxlength="100" autocomplete="name">
                        </label>
                        <label class="alan">Telefon
                            <input type="tel" name="telefon" value="{{ old('telefon') }}" required maxlength="30" autocomplete="tel" inputmode="tel" placeholder="05xx xxx xx xx">
                        </label>
                    </div>
                    <div class="iki">
                        <label class="alan">Çalışan Sayısı
                            <input type="number" name="calisan" value="{{ old('calisan') }}" min="1" max="100000" required inputmode="numeric">
                        </label>
                        <label class="alan">Tehlike Sınıfı
                            <select name="sinif" required>
                                <option value="" disabled @selected(! $seciliSinif)>Seçin…</option>
                                @foreach (\App\Models\TeklifTalebi::TEHLIKE_SINIFLARI as $s)
                                    <option @selected($seciliSinif === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="kvkk"><input type="checkbox" name="kvkk" value="1" required><span><a href="{{ route('kvkk') }}" target="_blank" rel="noopener">KVKK Aydınlatma Metni</a>'ni okudum; kişisel verilerimin teklif sürecinde işlenmesi hakkında bilgilendirildim.</span></label>
                    <button type="submit" class="dugme dugme-yesil">Teklif Talebi Gönder</button>
                    <p class="form-not">Bilgileriniz yalnızca teklif için sizinle iletişime geçmekte kullanılır.</p>
                </form>
            @endif
        </div>
    </section>

    {{-- ============ SSS ============ --}}
    <section id="sss" class="bolum sss">
        <div class="kap">
            <p class="ust-yazi">SIKÇA SORULAN SORULAR</p>
            <h2>Aklınızdaki soruların kısa cevapları</h2>
            <div class="sss-liste">
                @foreach ($sss as $i => [$soru, $cevap])
                    <details class="sss-madde" @if ($i === 0) open @endif>
                        <summary>{{ $soru }}<span class="sss-isaret" aria-hidden="true"></span></summary>
                        <p>{{ $cevap }}</p>
                    </details>
                @endforeach
            </div>
            <p class="sss-alt">Başka bir sorunuz mu var?
                <a href="{{ $waLink }}" target="_blank" rel="noopener">WhatsApp'tan sorun</a> veya
                <a href="tel:+905064754855">{{ $telefon }}</a> numarasından arayın.</p>
        </div>
    </section>
    {{-- ============ MÜHENDİSLİK STANDARTLARI & YASAL ALTYAPI (koyu, 6 kart) ============ --}}
    <section class="standart-bolum" aria-labelledby="standart-baslik">
        <div class="kap">
            <p class="ust-yazi">METODOLOJİ</p>
            <h2 id="standart-baslik">Mühendislik Standartları &amp; Yasal Altyapı</h2>
            <div class="standart-izgara">
                @foreach ($standartlar as [$ad, $aciklama, $etiket, $renk])
                    <article class="standart-kart">
                        <span class="standart-etiket standart-{{ $renk }}">{{ $etiket }}</span>
                        <h3>{{ $ad }}</h3>
                        <p>{{ $aciklama }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</main>

<footer class="alt-bar">
    <div class="kap alt-izgara">
        <div>
            <a href="#" class="logo"><span class="logo-kutu">{!! $logoIkon !!}</span>Mehse</a>
            <p class="aciklama">Mühendislik temelli iş sağlığı ve güvenliği danışmanlığı, saha denetimi ve İSG uzmanları için bulut yönetim yazılımı.</p>
        </div>
        <nav aria-label="Hızlı menü">
            <h3>Hızlı Menü</h3>
            <ul>
                <li><a href="#hizmetler">Hizmetler</a></li>
                <li><a href="#sektorler">Sektörler</a></li>
                <li><a href="#yazilim">İSG Yazılımı</a></li>
                <li><a href="#sss">Sıkça Sorulanlar</a></li>
                <li><a href="#teklif">Teklif Al</a></li>
                <li><a href="{{ $girisUrl }}">Üye Girişi</a></li>
            </ul>
        </nav>
        <div>
            <h3>Mevzuat</h3>
            <ul>
                <li>6331 sayılı İş Sağlığı ve Güvenliği Kanunu</li>
                <li>İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği</li>
                <li>ISO 45001 Standardı</li>
            </ul>
        </div>
        <div>
            <h3>İletişim</h3>
            <ul>
                <li>E-posta: <a href="mailto:{{ $eposta }}">{{ $eposta }}</a></li>
                <li>Telefon: <a href="tel:+905064754855">{{ $telefon }}</a></li>
            </ul>
            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="dugme dugme-yesil dugme-kucuk">{!! $wa !!} WhatsApp Destek</a>
        </div>
    </div>
    <div class="telif"><div class="kap"><p>© {{ date('Y') }} Mehse İSG. Tüm hakları saklıdır. · <a href="{{ route('kvkk') }}">KVKK Aydınlatma Metni</a> · <a href="{{ route('cerez') }}">Çerez Politikası</a></p></div></div>
</footer>

<a href="{{ $waLink }}" target="_blank" rel="noopener" class="wa-sabit" aria-label="WhatsApp ile yazın">{!! $wa !!}</a>

<script>
    (function () {
        var dugme = document.getElementById('menuDugme');
        var menu = document.getElementById('mobilMenu');
        dugme.addEventListener('click', function () {
            var acik = menu.classList.toggle('acik');
            dugme.setAttribute('aria-expanded', acik ? 'true' : 'false');
        });
        menu.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { menu.classList.remove('acik'); dugme.setAttribute('aria-expanded', 'false'); });
        });
    })();
</script>
</body>
</html>
