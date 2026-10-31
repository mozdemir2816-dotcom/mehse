@php
    $eposta = 'mozdemir2816@gmail.com';
    $telefon = '0506 475 48 55';
    $telUri = '+905064754855';
    $waNo = '905064754855';
    $waLink = 'https://wa.me/905064754855?text=Merhaba,%20saha%20ke%C5%9Ffi%20ve%20%C4%B0SG%20hizmetleri%20hakk%C4%B1nda%20bilgi%20almak%20istiyorum.';
    $sss = [
        ['İşyerimizin İSG hizmeti alma yasal zorunluluğu var mı?', '6331 sayılı Kanun kapsamında çalışan sayısı ve tehlike sınıfına göre her işletmenin İSG yükümlülükleri ve uzman bulundurma periyotları mevcuttur.'],
        ['Ücretsiz ön keşif süreci nasıl işler?', 'İşyerinizi sahada ziyaret ederek mevcut riskleri ve evrak durumunu inceler, herhangi bir taahhüt gerektirmeden size durum raporu sunarız.'],
        ['Dijital DÖF takibi işletmemize ne kazandırır?', 'Tespit edilen eksikler kağıt üzerinde unutulmaz; sorumlu personeller ve termin tarihleri üzerinden dijital olarak kapanana kadar izlenir.'],
        ['İSG-KATİP sözleşmesi ne kadar sürede aktif olur?', 'Karşılıklı mutabakat ve e-Devlet/İSG-KATİP onaylarının ardından aynı gün içinde resmi atama ve sözleşme süreci tamamlanır.'],
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
        'description' => 'Mühendislik temelli iş sağlığı ve güvenliği danışmanlığı, saha denetimi ve eğitim hizmetleri.',
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
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mehse İSG | Mühendislik Temelli İş Sağlığı ve Güvenliği Danışmanlığı</title>
    <meta name="description" content="6331 sayılı Kanun ve ISO 45001 uyumlu mühendislik temelli İSG danışmanlığı, risk analizi, dijital DÖF ve saha takip hizmetleri.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://mehse.com/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Mehse İSG">
    <meta property="og:title" content="Mehse İSG | Mühendislik Temelli İş Sağlığı ve Güvenliği Danışmanlığı">
    <meta property="og:description" content="6331 sayılı Kanun ve ISO 45001 uyumlu mühendislik temelli İSG danışmanlığı, risk analizi, dijital DÖF ve saha takip hizmetleri.">
    <meta property="og:url" content="https://mehse.com/">
    <meta property="og:image" content="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&amp;fit=crop&amp;w=1200&amp;h=630&amp;q=70">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Mehse İSG | Mühendislik Temelli İş Sağlığı ve Güvenliği Danışmanlığı">
    <meta name="twitter:description" content="6331 sayılı Kanun ve ISO 45001 uyumlu mühendislik temelli İSG danışmanlığı, risk analizi, dijital DÖF ve saha takip hizmetleri.">
    <meta name="theme-color" content="#0F172A">
    <link rel="stylesheet" href="{{ asset('css/tanitim.css') }}">
    {{-- JSON-LD @php bloğunda kurulur: şablonda '@context' yazılırsa Blade onu @context komutu sanıyor. --}}
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
            <a href="#surec">Süreç</a>
            <a href="#sss">SSS</a>
        </nav>
        <div class="ust-sag">
            <a href="{{ url('/admin') }}" class="dugme dugme-cerceve dugme-kucuk uye-giris">Üye Girişi</a>
            <a href="#teklif" class="dugme dugme-yesil dugme-kucuk ust-teklif">Hızlı Teklif Al</a>
            <button type="button" class="menu-dugme" id="menuDugme" aria-controls="mobilMenu" aria-expanded="false" aria-label="Menüyü aç">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
    <nav class="mobil" id="mobilMenu" aria-label="Mobil menü">
        <a href="#hizmetler">Hizmetler</a>
        <a href="#sektorler">Sektörler</a>
        <a href="#surec">Süreç</a>
        <a href="#sss">SSS</a>
        <a href="#teklif" class="dugme dugme-yesil">Hızlı Teklif Al</a>
        <a href="{{ url('/admin') }}" class="dugme dugme-cerceve">Üye Girişi</a>
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
                <p class="hero-alt">Bursa ve çevre sanayi bölgeleri için olası iş kazası risklerini ve yasal cezaları sıfıra indiren saha mühendisliği. Dijital DÖF altyapısıyla uygunsuzluklar kapanana kadar kesintisiz takip.</p>
                <div class="hero-dugmeler">
                    <a href="#teklif" class="dugme dugme-yesil">Ücretsiz Ön Keşif İste {!! $ok !!}</a>
                    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="dugme dugme-cerceve">{!! $wa !!} WhatsApp Destek</a>
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
                    <p>Müfettiş denetimlerine ve ISO 45001 kriterlerine tam uyumlu Fine-Kinney, 5×5 ve FMEA analizleri.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h9"/></svg></span>
                    <h3>Saha Denetimi &amp; Dijital DÖF</h3>
                    <p>Sahadaki her uygunsuzluk fotoğraflanır, sorumlu atanır ve giderilene kadar sistem üzerinden dijital takip edilir.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg></span>
                    <h3>Acil Durum &amp; Tatbikat</h3>
                    <p>Yangın, deprem, tahliye planları, acil durum ekiplerinin kurulması ve resmi tatbikat raporlama.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg></span>
                    <h3>Yasal İSG Eğitimleri</h3>
                    <p>Çalışanlara yönelik uygulamalı saha eğitimleri, sınav kayıtları ve denetime hazır arşivleme.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
                <article class="kart genis">
                    <span class="ikon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 00-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 005.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5z"/></svg></span>
                    <h3>Periyodik Kontrol &amp; Ölçüm</h3>
                    <p>Vinç, kompresör, topraklama kontrolleri ile ortam ölçümlerinin (gürültü, toz, kimyasal) yetkili kuruluşlarla eksiksiz koordinasyonu.</p>
                    <a href="#teklif">Teklif iste →</a>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ SEKTÖRLER ============ --}}
    <section id="sektorler" style="padding-bottom:80px">
        <div class="kap">
            <div class="sektor-kutu bolum" style="padding:32px">
                <div>
                    <p class="ust-yazi">SEKTÖRLER</p>
                    <h2>Sektörün tehlikesine göre çalışırız</h2>
                    <p class="giris">Her sektörün tehlike profili farklıdır; risk kütüphanemiz de faaliyete özeldir.</p>
                </div>
                <ul class="sektor-liste">
                    <li>Metal &amp; Makine İmalat</li><li>İnşaat &amp; Şantiye</li><li>Gıda &amp; İçecek</li>
                    <li>Kimya &amp; Boya</li><li>Depolama &amp; Lojistik</li><li>Sağlık Tesisleri</li>
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
                    <a href="tel:{{ $telUri }}" style="font-weight:600;color:var(--lacivert-900)">{{ $telefon }}</a> ·
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
                                <option value="" disabled @selected(! old('sinif'))>Seçin…</option>
                                @foreach (\App\Models\TeklifTalebi::TEHLIKE_SINIFLARI as $s)
                                    <option @selected(old('sinif') === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="kvkk"><input type="checkbox" name="kvkk" value="1" required><span>Kişisel verilerimin teklif sürecinde işlenmesini kabul ediyorum.</span></label>
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
                <a href="tel:{{ $telUri }}">{{ $telefon }}</a> numarasından arayın.</p>
        </div>
    </section>
</main>

<footer class="alt-bar">
    <div class="kap alt-izgara">
        <div>
            <a href="#" class="logo"><span class="logo-kutu">{!! $logoIkon !!}</span>Mehse</a>
            <p class="aciklama">Mühendislik temelli iş sağlığı ve güvenliği danışmanlığı ve saha denetimi.</p>
        </div>
        <nav aria-label="Hızlı menü">
            <h3>Hızlı Menü</h3>
            <ul>
                <li><a href="#hizmetler">Hizmetler</a></li>
                <li><a href="#sektorler">Sektörler</a></li>
                <li><a href="#surec">Süreç</a></li>
                <li><a href="#sss">SSS</a></li>
                <li><a href="#teklif">Teklif Al</a></li>
                <li><a href="{{ url('/admin') }}">Üye Girişi</a></li>
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
                <li>Telefon: <a href="tel:{{ $telUri }}">{{ $telefon }}</a></li>
            </ul>
            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="dugme dugme-yesil dugme-kucuk">{!! $wa !!} WhatsApp Destek</a>
        </div>
    </div>
    <div class="telif"><div class="kap"><p>© {{ date('Y') }} Mehse İSG. Tüm hakları saklıdır.</p></div></div>
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
