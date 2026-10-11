@php
    // Acil Durum Eylem Planı — kullanıcının FOM MAKİNA ADEP belgesinin düzeni
    // (kapak, 1-7. bölümler, risk tablosu, EK-1…EK-7, değişiklik tablosu).
    // Veriler AcilDurumBelgeVerisi'nden; Word çıktısı da aynı veriyi kullanır.
    $d ??= \App\Support\AcilDurumBelgeVerisi::hazirla($plan);
    $u = $d['u'];
    $imzali ??= true;
    $hz = $d['hazirlayan'];
    $hekim = $firma?->isyeriHekimi;
    $nokta = '........................';
    $n = fn ($v) => filled($v) ? $v : $nokta;
    $buyuk = fn (?string $s) => mb_strtoupper(str_replace('i', 'İ', (string) $s), 'UTF-8');
    $unvan = $d['unvan'];
    $ekipAd = config('isg.acil_durum.belge_ekipleri');
    $kolonlar = config('isg.acil_durum.risk_kolonlari');
    $kkd = config('isg.acil_durum.kkd_matrisi');
    $cerceve = match ($plan->kapak_cercevesi) {
        'altin' => 'double #b8860b', 'mavi_zarif' => 'double #1e3a8a', 'minimalist' => 'solid #111',
        'yesil_doga' => 'double #15803d', 'golgeli' => 'double #333', default => 'double #8c1e1e',
    };
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 96pt 32pt 74pt 32pt; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9.5pt; line-height: 1.35; }
    .cerceve { position: fixed; top: -84pt; left: -20pt; width: 562pt; height: 809pt; border: 4.5pt {{ $cerceve }}; }
    .ust { position: fixed; top: -72pt; left: 0; right: 0; }
    .alt { position: fixed; bottom: -52pt; left: 0; right: 0; }
    table { border-collapse: collapse; width: 100%; }
    .ust td, .alt td { border: 0.8pt solid #444; font-size: 7.5pt; padding: 2pt 4pt; }
    .ust .firma { font-size: 9.5pt; font-weight: bold; text-align: center; }
    .alt td { text-align: center; font-weight: bold; height: 30pt; vertical-align: top; }
    h1 { font-size: 22pt; text-align: center; margin: 26pt 0 18pt; }
    h2 { font-size: 13pt; color: #2e74b5; margin: 14pt 0 5pt; page-break-after: avoid; }
    h3 { font-size: 11pt; color: #2e74b5; margin: 10pt 0 4pt; page-break-after: avoid; }
    h4 { font-size: 10pt; color: #1f4d78; margin: 8pt 0 3pt; page-break-after: avoid; }
    p { margin: 0 0 5pt; text-align: justify; }
    ul, ol { margin: 0 0 6pt; padding-left: 16pt; }
    li { margin-bottom: 2pt; text-align: justify; }
    .metin { white-space: pre-line; }
    .yeni-sayfa { page-break-before: always; }
    .tablo td, .tablo th { border: 0.8pt solid #555; padding: 3pt 4pt; font-size: 8.5pt; vertical-align: top; text-align: left; }
    .tablo th { background: #dbe5f1; font-weight: bold; }
    .orta { text-align: center !important; }
    .kucuk { font-size: 8pt; color: #444; }
    .bos { color: #888; }
    .kroki { border: 1pt solid #555; padding: 6pt; }
</style>
</head>
<body>

{{-- Her sayfada: çerçeve, üst bilgi, alt imza şeridi (sayfa no page_script ile) --}}
<div class="cerceve"></div>
<div class="ust">
    <table>
        <tr>
            <td rowspan="4" style="width:14%;text-align:center">
                @if ($d['logo'])<img src="{{ $d['logo'] }}" style="max-width:62pt;max-height:44pt">@endif
            </td>
            <td rowspan="4" class="firma" style="width:52%">{{ $unvan }}</td>
            <td style="width:17%">Doküman No</td><td style="width:17%">{{ $d['dokuman_no'] }}</td>
        </tr>
        <tr><td>Yayın Tarihi</td><td>{{ $d['yayin'] }}</td></tr>
        <tr><td>Revizyon Tarihi</td><td>{{ $d['revizyon_tarihi'] }}</td></tr>
        <tr><td>Revizyon No</td><td>{{ $d['revizyon_no'] }}</td></tr>
    </table>
</div>
<div class="alt">
    <table>
        <tr>
            <td style="width:28%">İŞ GÜVENLİĞİ UZMANI</td>
            <td style="width:28%">İŞYERİ HEKİMİ</td>
            <td style="width:28%">İŞVEREN/İŞVEREN VEKİLİ</td>
            <td style="width:16%">Sayfa</td>
        </tr>
    </table>
</div>

{{-- KAPAK (Word çıktısında FOM şablonunun kendi kapağı kullanılır) --}}
<div class="kapak">
<div style="text-align:center;margin-top:40pt">
    <div style="font-size:15pt;font-weight:bold">{{ $unvan }}</div>
    <div style="font-size:10pt;margin-top:6pt">{{ $d['sgk'] }}</div>
    <h1 style="margin-top:110pt">ACİL DURUM EYLEM PLANI</h1>
</div>
<table class="tablo" style="margin-top:90pt">
    <tr><th class="orta" style="width:50%">HAZIRLAYAN</th><th class="orta">ONAYLAYAN</th></tr>
    <tr>
        <td class="orta" style="height:70pt;font-size:9.5pt">
            {{ $hz['ad'] }}@if ($hz['unvan'])<br>{{ $hz['unvan'] }}@endif
            <br><br>İmza: ................
        </td>
        <td class="orta" style="font-size:9.5pt">
            İşveren / İşveren Vekili<br>
            Adı Soyadı: {{ $d['isveren'] ?? $d['isveren_vekili'] ?? '................' }}
            <br><br>İmza / Tarih: ................
        </td>
    </tr>
</table>
<table class="tablo" style="margin-top:18pt;width:60%;margin-left:20%">
    <tr><th style="width:55%">YAPILDIĞI TARİH</th><td class="orta">{{ $d['yayin'] }}</td></tr>
    <tr><th>GEÇERLİLİK TARİHİ</th><td class="orta">{{ $d['gecerlilik'] }}</td></tr>
</table>
</div>

{{-- 1-7. BÖLÜMLER --}}
<div class="yeni-sayfa"></div>
<h2>1. GİRİŞ</h2>
<h3>1.1. AMAÇ</h3>
<p>Acil Durum Planı, herhangi bir acil durum oluştuğunda, panik yapmadan, organize olarak, düzenli bir şekilde müdahale etmek, ortaya çıkabilecek olan zararları en az seviyeye düşürmek maksadıyla hazırlanmıştır.</p>
<p>Bu plan; işyerindeki çalışanlar, müşteriler, ziyaretçiler ve hizmet sağlayıcıların güvenliği için yangın, patlama, deprem, zehirlenme ve diğer muhtemel acil durumlarda uygulanacak önleme, haberleşme, müdahale ve tahliye düzenini belirler. Senaryolar, gerçek faaliyetler ve risk değerlendirmesiyle birlikte uygulanır. Mevcut olmayan tesis ve ekipmanlara ilişkin hükümler, uygulanabilirlik değerlendirmesine göre işaretlenir.</p>
<h3>1.2. ACİL DURUM YÖNETİMİ POLİTİKASI</h3>
<p>Acil durumlar, bir işletmenin başına gelebilecek en kötü olaylardandır. İşyerinde, malzemede, cihazlarda hasar yaratarak, müşteri, tedarikçi ve itibar kaybına neden olarak maddi; personel zayiatına neden olarak manevi zararlar verir. Bu nedenle, acil durumlara hazırlıklı olmak, çok büyük bir önceliktir. Bu plan içinde verilmiş olan görevler titizlikle uygulanacak, bu konuda eğitimler yapılacak ve hiçbir şekilde taviz verilmeyecektir.</p>
<h3>1.3. KAPSAM</h3>
<p>Acil Durum Planı tüm çalışanlar, işyerinde bulunan tüm ziyaretçiler, müşteriler ve taşeron firmalar için geçerlidir.@if ($u['gecici_calisan_dahil']) Alt işveren ve geçici iş ilişkisiyle çalışanlar da plan kapsamındadır.@endif</p>
<p>Bu planın geçerli olduğu bölge {{ $unvan }} faaliyet/işletme sahasıdır{{ $d['adres'] ? ' ('.$d['adres'].')' : '' }}.@if ($u['coklu_isveren']) Çalışma alanı birden fazla işverence paylaşıldığından plan diğer işverenlerle koordineli uygulanır (Md.17).@endif</p>

<h2>2. TANIMLAR</h2>
<h3>2.1. ACİL DURUM</h3>
<p>Acil durum, çalışanlar, müşteriler, tedarikçiler, ziyaretçiler veya çevre halkı arasında, ölüm ve ciddi yaralanmaya neden olabilecek veya işin durmasına, faaliyetlerin aksamasına, fiziksel veya çevresel olarak zarar görmesine, işyerinin mali yapısının bozulmasına ve toplum içinde itibarının düşmesine neden olabilecek, plan ve istem dışı olaylardır.</p>
<h3>2.2. ACİL DURUM PLANI</h3>
<p>Acil durum planı; muhtemel acil durumların, önleyici ve sınırlandırıcı tedbirlerin, görevli kişilerin, müdahale ve tahliye yöntemlerinin, tatbikat ve yenileme esaslarının yazılı olarak düzenlendiği plandır. Dayanak: 6331 sayılı Kanun; İşyerlerinde Acil Durumlar Hakkında Yönetmelik; Binaların Yangından Korunması Hakkında Yönetmelik ve İlkyardım Yönetmeliği.</p>
<h3>2.3. İLK YARDIM</h3>
<p>Herhangi bir kaza veya yaşamı tehlikeye düşüren bir durumda, sağlık görevlilerinin yardımı sağlanıncaya kadar, hayatın kurtarılması ya da durumun kötüye gitmesini önleyebilmek amacıyla, olay yerinde, tıbbi araç gereç aranmaksızın, mevcut araç ve gereçlerle yapılan ilaçsız uygulamalardır.</p>
<h3>2.4. ACİL DURUM MÜDAHALESİ</h3>
<p>Acil durumların ortaya çıkmasından sonra yapılacak müdahaledir.</p>

<h2>3. ORGANİZASYON</h2>
<h3>3.1. ORGANİZASYON ŞEMASI</h3>
<p>Organizasyon: İşveren / işveren vekili → acil durum koordinatörü → Söndürme (Yangın), Kurtarma, Koruma, İlkyardım, Haberleşme ve Teknik Müdahale ekipleri. İlk dört ekip mevzuat kapsamındadır; haberleşme ve teknik müdahale işletme ihtiyaçları için ayrıca düzenlenmiştir. İletişim ve görevler Ek-2 / Ek-4’te yer alır.</p>
<h3>3.2. GÖREV YETKİ SORUMLULUKLAR</h3>
<p>Acil durum ekibinde bulunan kişiler aynı zamanda mevzuatta destek elemanı olarak tanımlanırlar.</p>
<h4>3.2.1 Acil Durum Koordinatörü (İşveren / İşveren Vekili)</h4>
<p>Acil durum koordinatörü müdahale ve tahliyeyi koordine eder. Alarm verme, 112’yi arama, ciddi ve yakın tehlikeden uzaklaşma ve güvenli tahliye için onay beklenmez. Çalışanlar yalnızca eğitimleri ve mevcut güvenli donanımları kapsamında görev yapar.</p>
<p>Acil durum faaliyet ve hazırlıklarını koordine eder. Acil durum planında belirtilmiş acil durum uygulama prosedürlerini uygulamaya koyar, uygulama sırasında tespit edilen veya meydana gelen değişik durumlar karşısında uygulama yöntemlerini revize eder.</p>
<p>İşveren gerekli araç, gereç, eğitim ve kontrolleri sağlar. Koordinatör adı / telefonu: <b>{{ $n($d['koordinator']['ad']) }} / {{ $n($d['koordinator']['tel']) }}</b>. Vekili / telefonu: <b>{{ $n($d['koordinator']['vekil']) }} / {{ $n($d['koordinator']['vekil_tel']) }}</b>.</p>
<h4>3.2.2 Söndürme Ekibi (Yangınla Mücadele Ekibi)</h4>
<p>Söndürme ekibi, eğitim ve donanımı kapsamında başlangıç aşamasındaki yangına, güvenli kaçış yolu açıkken uygun söndürücüyle müdahale eder. Yoğun duman, hızla büyüyen yangın, gaz kaçağı veya patlama tehlikesinde geri çekilir; itfaiyeye bilgi verir.</p>
<ul>
    <li>Alarm ve 112 çağrısının verildiğini kontrol eder; tahliyeyi geciktirmeden uygun söndürücüyü güvenli noktadan kullanır.</li>
    <li>Açık alanda rüzgârı arkasına alır; kapalı alanda dumana girmez ve çıkışı arkasında tutar. Eğitim, ekipman veya kaçış güvenliği yetersizse müdahale etmez.</li>
    <li>Kurtarma yalnızca ekibin eğitimi ve donanımı yeterliyse, ikinci kazaya yol açmadan yapılır. Dumanlı, oksijeni yetersiz veya çökme riski bulunan alana girilmez.</li>
    <li>Kullanılan ve boşalan söndürücüleri ekip liderine ve koordinatöre bildirir, yenilenmesini sağlar.</li>
    <li>İtfaiye ekipleri ulaştığında olay hakkında bilgi vererek profesyonel ekiplerin emrine girer.</li>
</ul>
<h4>3.2.3 Kurtarma Ekibi (Arama, Kurtarma ve Tahliye Ekibi)</h4>
<p>Acil durumlarda işyerindeki çalışanların, ziyaretçilerin ve canlıların güvenli bir şekilde tahliyesini, tehlike altında kalanların aranmasını ve kurtarılmasını organize eder.</p>
<ul>
    <li>Acil çıkış kapıları ve kaçış yollarının sürekli açık ve kullanılabilir olmasını kontrol eder.</li>
    <li>Tahliye esnasında panik ve kargaşayı önleyerek personelin düzenli biçimde acil toplanma alanına ulaşmasını sağlar.</li>
    <li>Mahsur kalan kişinin son bilinen konumunu 112 ve koordinatöre bildirir. Tehlikeli alana korumasız giriş yapmaz; teknik kurtarmayı profesyonel ekiplere bırakır.</li>
    <li>Tahliye edilenleri koruma ekibinin sayım noktasına yönlendirir; eksik kişi ve son bilinen konum bilgisini koruma ekibine iletir.</li>
    <li>Evrak veya eşya kurtarmak için binaya geri girilmez. Malzeme kurtarma ancak can güvenliği sağlandıktan ve yetkili ekipler alanı güvenli ilan ettikten sonra yapılır.</li>
</ul>
<h4>3.2.4 Koruma ve Güvenlik Ekibi Görevleri</h4>
<p>Koruma ekibi çevre emniyetini sağlar; panik ve kargaşayı önler, yetkisiz girişleri engeller ve sayımı yürütür. Haberleşme ekibiyle bilgi paylaşır; ekipler arası koordinasyon için koruma ekibinden sorumlu kişi belirlenir.</p>
<ul>
    <li>Olay yerinin çevre emniyet şeridini çekerek yetkisiz girişleri durdurur.</li>
    <li>Kurtarılan malzeme, evrak ve teçhizatın güvenliğini sağlar, yağma veya hasarı önler.</li>
    <li>Koruma ekibi sayım sonuçlarını ve eksik kişilerin son bilinen yerlerini koordinatöre / haberleşme ekibine bildirir. Hayati tehlikede 112 çağrısı için herhangi bir ekip veya onay beklenmez.</li>
    <li>Teknik müdahale ekibine güvenli enerji kesimi ihtiyacını bildirir; yetkisiz tesisat müdahalesi yapmaz.</li>
    <li>İtfaiye, ambulans ve kolluk için yaklaşım yolunu açık tutar; haberleşme ekibinin yönlendirdiği resmî ekiplerin alana erişimini sağlar.</li>
</ul>
<h4>3.2.5 İlk Yardım Ekibi</h4>
<p>Sağlık Bakanlığı onaylı geçerli İlkyardımcı Sertifikasına sahip personelden oluşur. Acil durumlarda meydana gelebilecek yaralanma ve rahatsızlıklarda profesyonel sağlık ekipleri gelene kadar hayat kurtarıcı ilk müdahaleyi yapar.</p>
<ul>
    <li>Acil durum bildiriminde ilk yardım çantasını ve sedyeyi alarak güvenli müdahale noktasına ulaşır.</li>
    <li>Eğitimindeki güncel ilkyardım esaslarına ve 112 yönlendirmesine göre değerlendirme yapar; çoklu yaralanmada durumu 112’ye bildirir. Tıbbi işlem ve ilaç uygulamaz.</li>
    <li>Yetkisiz kişilerin yaralıya yanlış müdahalede bulunmasını engeller.</li>
    <li>İlk yardım dolabı ve malzemelerinin miat ve stok kontrollerini düzenli takip eder.</li>
    <li>112 Ambulans ekipleri geldiğinde hastanın durumu ve yapılan ilk müdahale hakkında sağlık personeline detaylı bilgi verir.</li>
</ul>
<h4>3.2.6 Haberleşme Ekibi</h4>
<p>Olayı güvenli noktadan 112’ye bildirir; Ek-5’teki açık adresi, olay türünü, yaralı / mahsur kişi sayısını, gaz / kimyasal / elektrik tehlikelerini aktarır. Koordinatör ve koruma ekibinden sayım bilgisini alır. Ambulans / itfaiyeyi güvenli girişte karşılar; acil telefonları güncel tutar. Haberleşme arızasında güvenli alternatif yöntemi kullanır. Acil çağrı için işveren onayı beklenmez.</p>
<h4>3.2.7 Teknik Müdahale Ekibi</h4>
<p>Yalnız yetki ve eğitimi kapsamındaki elektrik, gaz, basınçlı hava ve makine enerjisini güvenli noktalardan izole eder; kilitleme / etiketleme ve depolanmış enerji kontrolünü uygular. Tehlikeli alana girmez, basınçlı kapları gelişigüzel boşaltmaz ve sıkışmış kişiyi kurtarmak için makineyi rastgele hareket ettirmez. Profesyonel ekiplere tesisat bilgisi verir; güvenlik kontrolü ve işveren izni olmadan enerjiyi geri vermez.</p>
<h3>3.3. İLETİŞİM</h3>
<p>Acil bildirim güvenli konumdan 112’ye yapılır. Telefon kullanılamıyorsa mevcut telsiz, sesli uyarı veya güvenli noktadan başka telefon kullanılır; bilgisayar bağlantısı beklenmez. Çağrıda Ek-5’teki açık adres, olay, etkilenen kişi sayısı ve giriş noktası bildirilir. Patlayıcı atmosferde elektrikli cihaz kullanılmaz.</p>
<h3>3.4. AİLELERLE İLETİŞİM</h3>
<p>Acil durum koordinatörü ve destek elemanı, ilk fırsatta personel aileleri ile temasa geçerek, acil durum hakkında bilgi verecek, aileleri hakkında öğrendiği bilgileri çalışanlara iletecektir. İletişim için öncelik sırası Acil durum koordinatörü tarafından saptanır. Ailelerin dışarıdan araması durumunda, aksine bir talimat verilmediği takdirde, telefonlar kişilere değil, Acil durum koordinatörüne bağlanacaktır.</p>
<h3>3.5. DUYURULAR</h3>
<p>Civarı etkileyen genel bir acil durum (afet) söz konusu olduğu takdirde, Acil durum koordinatörü tarafından radyo ve televizyonları takip etmek üzere personel görevlendirilir ve konu hakkında gelişmeler izlenir. Acil durum sırasında, mümkün olan her fırsatta personele bilgi verilmelidir, aksi takdirde dedikodu ve yanlış duyumlar dolaşmaya başlar ki, bu durumu daha da kötüleştirir. Ne zaman ve ne kadar bilgi verileceğinin kararını, Acil durum koordinatörü verir.</p>
<h4>3.5.1. ALARM VE UYARILAR</h4>
<p>Yangını ilk gören kişi aşağıdaki sırayı uygular:</p>
<p>1) Çevresini sesle uyarır; varsa güvenle erişilebilen alarm butonuna basar.<br>
2) Güvenli noktadan 112’yi arar / aratır; yakındakileri güvenli çıkışa yönlendirir.<br>
3) Yalnızca eğitimli ve donanımlıysa, başlangıç yangınına kaçışını koruyarak müdahale eder. Tehlike artarsa müdahaleyi bırakıp tahliye olur.</p>
<h3>3.6. ALARMIN VERİLMESİ</h3>
<p>Alarm yöntemi: <b>{{ $n($u['alarm_yontemi']) }}</b> Otomatik algılama ve ihbar butonu varsa yerleri Ek-7’de gösterilir. Sistem yoksa veya çalışmıyorsa yüksek sesle uyarı ve mevcut güvenli haberleşme yöntemi kullanılır. Alarmın tüm alanlarda ve işitme engelli kişilerce algılanması sağlanır.</p>
<h3>3.7. İNSAN HAYATI</h3>
<p>Bir acil durumda, insan hayatı birinci önceliktir. Bu nedenle yapılacak her şey insan hayatını korumak içindir.</p>

<h2>4. TAHLİYE</h2>
<p>Tahliye, tehlikeden uzak güvenli çıkış üzerinden yapılır. Koşulmaz, asansör kullanılmaz, eşya almak için geri dönülmez. Yangın ve dumanla kapanmış güzergâh kullanılmaz; alternatif güvenli çıkışa yönelinir. Çıkış yoksa güvenli konumdan 112’ye yer bildirilir.</p>
<p>Tahliye eden personel toplanma bölgesinden sayım yapılana kadar ayrılmayacaktır. Toplanma bölgesinde, ziyaretçilerin de sayımları yapılarak Acil durum koordinatörüne bilgi verilecektir.</p>
@if (filled($u['mudahale_yontemi']))
    <p><b>İşyerine özgü müdahale ve tahliye sırası:</b></p>
    <p class="metin">{{ $u['mudahale_yontemi'] }}</p>
@endif
<h3>4.1. TAHLİYE YOLLARI VE ÇIKIŞLAR</h3>
<p>Alarmda iş yalnızca gecikme ve ilave risk yaratmadan güvenli biçimde durdurulur; kapatma işlemi tahliyeyi geciktirmez. Tahliye güzergâhları Ek-7’de gösterilir. Yaşlı, engelli, gebe veya yardıma ihtiyaç duyan kişilere refakatçi atanır: <b>{{ $n($u['refakatci']) }}</b>. Müşteriler, çocuklar ve ziyaretçiler de yönlendirilir.</p>
@if (filled($u['ozel_destek']))
    <p class="metin">{{ $u['ozel_destek'] }}</p>
@endif
<p>Tahliye yollarını ve acil çıkışları belirten tahliye planları personelin görebileceği yerlerde asılı bulundurulmalıdır.</p>
<h3>4.2. TOPLANMA NOKTA/BÖLGELERİ VE MEVCUT ALMA/PERSONEL TESPİTİ</h3>
<p><b>TOPLANMA NOKTASI: {{ $n($d['toplanma']) }}.</b> Bina cephesi, yük istifi, araç yolu ve enerji hatlarından uzak güvenli bölüm işaretlenir; müdahale yolu açık tutulur. Yedek güvenli alan: <b>{{ $n($u['yedek_toplanma']) }}</b>.</p>
<p>Koruma ekibi güncel çalışan / vardiya listesiyle sayım yapar; ziyaretçi, müşteri ve hizmet sağlayıcı bilgilerini de kontrol eder. Eksik kişi, son görülen yer ve sayım sonucu koordinatöre ve müdahale ekiplerine iletilir. Kimse arama amacıyla kendiliğinden geri girmez.</p>
<p>Alınan mevcutlar, Koruma Ekibi tarafından birleştirilip kontrol edilerek, eksik personel Acil durum koordinatörüne bildirilir.</p>
<p>Mevcut alma işlemi, içeride personel kalıp kalmadığını anlamak için yapılmaktadır. Hatalı bir sonuç, ya bir veya birkaç kişinin tehlike içinde unutulmasına, ya da içeride kaldığı sanılan birini müdahale ekibini tehlikeye atarak boşu boşuna aratmak demektir. Bu işlem mümkün olduğu kadar kısa sürede yapılmalıdır.</p>
<h3>4.3. EĞİTİM VE BİLGİ</h3>
<p>Personele, acil durumlarda ne yapacağını bildiren eğitimler İş sağlığı ve güvenliği eğitimi kapsamında anlatılacaktır. Aşağıda açıklanan Acil durum eğitimleri, İşveren / İşveren Vekili tarafından koordine edilir.</p>
<h4>4.3.1. Oryantasyon Eğitimleri</h4>
<p>Yeni başlayan ve geçici görevlendirilen çalışanlara işe başlamadan önce alarm, çıkışlar, toplanma alanı, ekipler ve özel tehlikeler anlatılır. Plan değiştiğinde etkilenen çalışanlar tekrar bilgilendirilir; eğitim / bilgilendirme kayıtları tutulur.</p>
<h4>4.3.2. Yenileme Eğitimleri</h4>
<p>Çalışanlara ihtiyaçlara göre ve plan değişikliklerinde tazeleme eğitimi verilir. Ekip eğitimleri görevlerine özgü teorik ve uygulamalı olarak planlanır; genel bir 4 saat kuralı ilkyardımcı belgesinin yerine geçmez. Eğitim tarihi, konusu, eğitici ve katılımcı imzaları kayıt altına alınır.</p>
<h4>4.3.3. Masabaşı Eğitimleri</h4>
<p>Koordinatör ve altı acil durum ekibi yılda en az bir masa başı değerlendirmesinde haberleşme, tahliye ve görev paylaşımını gözden geçirir. Bu çalışma fiilî tatbikatın yerine geçmez.</p>
<h4>4.3.4. Fonksiyonel Eğitimler</h4>
<p>Müdahale ekiplerinin alacakları eğitimlerdir. Bu eğitimler;</p>
<ul>
    <li>Söndürme ekibi: Başlangıç yangını, uygun söndürücü seçimi, güvenli yaklaşma ve geri çekilme.</li>
    <li>Koruma ekibi: Sayım, haberleşme, çevre güvenliği ve profesyonel ekipleri karşılama.</li>
    <li>Kurtarma ekibi: Güvenli tahliye, refakat, arama sınırları ve teknik kurtarma ekibine bilgi aktarımı.</li>
    <li>İlkyardım ekibi: Yetkili eğitim merkezinden ilkyardımcı eğitimi; geçerli belge ve güncelleme takibi.</li>
    <li>Teknik müdahale ekibi: Yetkili olduğu tesisatta güvenli enerji izolasyonu, depolanmış enerji ve yeniden devreye alma koşulları.</li>
    <li>Haberleşme ekibi: 112 bildirimi, doğru adres / konum aktarımı, ekiplerle iletişim ve dış müdahale ekiplerini karşılama. Kimyasal dökülme eğitimi ayrıca yetkilendirilen personele verilir.</li>
</ul>
<h4>4.3.5. Fonksiyonel Gözden Geçirme Eğitimleri</h4>
<p>Müdahale ekiplerinin kendi aralarında yapacakları fiili eğitimdir. Masabaşı eğitimleri yapıldıktan sonra yapılır.</p>
<h4>4.3.6. Genel Tahliye Eğitimi</h4>
<p>İşyerinde en geç yılda bir fiilî acil durum tatbikatı yapılır. Tahliye ve sayım süresi, vardiyalar, müşteriler ve refakat ihtiyacı değerlendirilir. Yıllık tatbikat tarihi / sorumlusu: <b>{{ $n($d['sonraki_tatbikat']) }} / {{ $n($u['tatbikat_sorumlusu']) }}</b>.@if ($d['son_tatbikat']) Son tatbikat: <b>{{ $d['son_tatbikat'] }}</b>{{ filled($u['tutanak_ref']) ? ' (kayıt: '.$u['tutanak_ref'].')' : '' }}.@endif Ortak binada yönetimle koordinasyon sağlanır.</p>
<h4>4.3.7. Simülasyonlu Uygulama Eğitimi</h4>
<p>Tatbikat senaryosu, tarihi, katılanlar, gözlenen eksiklikler, düzeltici faaliyetler, sorumlular ve tamamlanma tarihleri kayıt altına alınır. Tatbikat formu: Tarih / senaryo; katılımcı sayısı; tahliye-sayım süresi; eksiklik; DÖF / sorumlu / termin; kapanış kontrolü / imza. Sonuçlara göre plan güncellenir.</p>
<h3>4.4. KORUYUCU EKİPMAN KULLANIMI</h3>
<p>Ek-3’teki KKD seçimleri gerçek faaliyet, risk değerlendirmesi ve SDS’ye göre kesinleştirilir. Filtreli maske; dumanlı, oksijeni yetersiz veya içeriği bilinmeyen atmosfere giriş izni vermez. Eğitim ve uygun donanım olmadan yangın, kapalı alan veya teknik kurtarmaya girişilmez.</p>

<h2>5. İŞYERİ KORUNMASI</h2>
<h3>5.1. KORUMA SİSTEMLERİ</h3>
<p>Koruma sistemleri ve ekipmanların gerçek durumu sahada kontrol edilerek kaydedilir:</p>
<ul>
    <li>Çıkış sayısı / kat / kullanım alanı: <b>{{ $n($u['bina_bilgisi']) }}</b>. Kaçış kapıları ve yolları engelsiz tutulur; ana girişin acil çıkış için uygunluğu ayrıca değerlendirilir.</li>
    <li>Yeterlilik, alan ve yangın türüne göre doğrulanır. İlkyardım çantası, acil aydınlatma, varsa alarm ve gaz kesme sistemleri kontrol edilir; konumları Ek-7’ye işlenir. Söndürücü tür / kapasite / adet / yer / kontrol tarihi ve acil durum ekipmanları:@if (filled($u['ekipman_kkd']))<br><span class="metin">{{ $u['ekipman_kkd'] }}</span>@else <b>{{ $nokta }}</b>@endif</li>
</ul>
<p><b>İşyerine özgü önleyici ve sınırlandırıcı tedbirler:</b></p>
@if (filled($u['onleyici_tedbirler']))
    <p class="metin">{{ $u['onleyici_tedbirler'] }}</p>
@else
    <p class="bos">{{ $nokta }}</p>
@endif
<p>Kimyasal yayılım, parlama / patlama gibi özel riskli bölümler: <b>{{ $n($d['ozel_risk']) }}</b></p>
@if (filled($u['olcum_notu']))
    <p><b>Ölçüm, kontrol ve kayıtlar:</b></p>
    <p class="metin">{{ $u['olcum_notu'] }}</p>
@endif
<h3>5.2. İŞYERİNİN KAPATILMASI</h3>
<p>Elektrik ve gaz yalnızca güvenli noktadan, yetkilendirilmiş kişilerce kesilir. Gaz kaçağı şüphesinde içeride anahtar, fiş veya elektrikli cihaz kullanılmaz. Kesme işlemi için tehlikeli alana girilmez; güvenli tahliye ve 112 / 187 bildirimi önceliklidir.</p>
<p>İşyerinin kapanması ise:</p>
<ul>
    <li>Koordinatör güvenli kapatma ve profesyonel ekiplerle koordinasyonu yürütür; acil tahliye için onay beklenmez.</li>
    <li>Kesme noktası / yetkili kişi / telefon: <b>{{ $n($d['enerji_kesme']) }}</b> Çalışanlar yalnızca güvenliyse kendi ekipmanını durdurur; yetkisiz tesisat müdahalesi yapmaz.</li>
</ul>
<h3>5.3. KAYITLARIN MUHAFAZASI</h3>
<p>Kritik kayıtlar düzenli yedeklenir; yedekler aynı olaydan etkilenmeyecek güvenli bir yerde tutulur. Mevcut yedekleme yeri / sorumlusu: <b>{{ $n($u['kayit_yedekleme']) }}</b></p>
<p>Bilgisayar ortamında olmayan her tür kritik kayıt ya bilgisayar ortamına transfer edilip yedeklenecek, ya da bu mümkün değilse kopyaları (fotokopi, fotoğraf veya video) çıkarılarak yedeklenecektir.</p>

<h2>6. HALKLA İLİŞKİLER</h2>
<p>Bir acil durumda, her türlü halkla ilişkiler faaliyeti, destek elemanı tarafından kontrol ve koordine edilecektir. Acil durum koordinatöründe alınan kararlar ve hazırlanan bilgi dışında bir bilgi verilmeyecektir.</p>
<h3>6.1. RESMİ KURULUŞLARLA İLİŞKİLER</h3>
<p>Hangi acil durumda hangi resmi kurumun aranacağını gösterir liste ve aranan kuruma verilecek bilgiler "EK-2 Acil İletişim Listesi"nde yer almaktadır.</p>
<h3>6.2. TOPLUMSAL HİZMETLER</h3>
<p>Genel bir acil durumda öncelikle personel aileleri olmak üzere, çevre topluma destek verilecektir. Bu destek, üretim devam edebiliyorsa, üretimi kesintiye uğratmadan, üretim kesilmişse, toparlanma faaliyetlerine engel olmadan verilmelidir. Bu durum önceden öngörülebilecek bir durum olmadığından, ne şekilde destek verileceği destek elemanının yetki ve sorumluluğundadır.</p>
<p>Acil durumlarda verilebilecek destekler işletme imkanları da dikkate alınarak ulaşım, barındırma ve sıcak yemek de dahil olmak üzere ön hazırlık yapılması ve sayının belirlenmesi faydalı olacaktır.</p>
<h3>6.3. TOPLUMU BİLGİLENDİRME</h3>
<p>İşyeri içinde ortaya çıkan bir acil durum, işyeri sınırları dışına taşıyorsa; resmi makamlar, olayın yapısını, toplum sağlığı veya güvenliğinin tehlikede olup olmadığını, sorunu çözmek için ne yapıldığını ve bu durumun ortaya çıkmaması için neler yapılmış olduğunu öğrenmek isteyeceklerdir.</p>
<p>Çevreyi etkileyen olaylarda koordinatör ilgili resmî kurumlara doğrulanmış bilgi verir; kamuya açıklama yetkili kişi tarafından yürütülür. Kişisel sağlık bilgileri korunur. İlgili taraflar olayın niteliğine göre aşağıdakilerden seçilir:</p>
<ul>
    @foreach (['Çevre halkı', 'Basın yayın kuruluşları', 'Çalışanlar ve emekliler', 'Sendikalar', 'Satıcılar ve tedarikçiler', 'Müşteriler', 'Ortaklar', 'Acil durum müdahale organizasyonları', 'Resmi makamlar', 'Mülki amir ve belediye başkanı', 'Özel ilgi gruplar', 'Komşu kuruluşlar'] as $taraf)
        <li>{{ $taraf }}</li>
    @endforeach
</ul>

<h2>7. TOPARLANMA VE TEKRAR BAŞLAMA</h2>
<p>Bir acil durum sonrası mümkün olduğu kadar kısa sürede toparlanarak, tekrar işe dönmek gerekir. Aksi takdirde, çalışanlar motivasyonlarını, işyeri müşteri ve tedarikçilerini kaybetmeye başlar.</p>
<h3>7.1. SİGORTA</h3>
<p>İşveren sigorta işlemlerini yürütür. Olay kayıtları can güvenliğini tehlikeye atmadan alınır; kurtarma ve ilkyardım geciktirilmez. Deliller, yetkili makamların incelemesini engellemeyecek şekilde korunur.</p>
<h3>7.2. FAALİYETE TEKRAR BAŞLAMA</h3>
<p>Toparlanma ve faaliyete tekrar başlama, Acil durum koordinatörü koordinatörlüğünde yapılacak planlamayla yürütülecektir.</p>
<ul>
    <li>İşveren, teknik kontrol ve toparlanma görevlerini yetkili kişilere dağıtır; öncelik can güvenliğidir.</li>
    <li>İşyerindeki personelin emniyeti sürdürülür ve olay yerinin güvenliği sağlanır.</li>
    <li>Ayrıntılı kayıt tutulur, tüm kararların mümkünse sesli kaydı tutulur. Hasarın fotoğrafları çekilir veya video kaydı yapılır.</li>
    <li>Hasar maliyetleri çıkarılır. Alımlar için özel bütçe kodları ayarlanır.</li>
    <li>Duman, su ve molozun uzaklaştırılması ancak yetkili ekipler alanın güvenliğini değerlendirdikten sonra yapılır. Elektrik / gaz tesisatı teknik kontrolden önce devreye alınmaz.</li>
    <li>Enkaz kaldırma faaliyetlerine başlanır. Hasarlı kısımlar hasar görmemiş kısımlardan ayrılır.</li>
    <li>Hasarlı malzemenin envanteri çıkarılır. Bu iş hasarlı malzemenin değerini saptamak içindir ve genellikle eksper veya eksperin görevlendireceği birinin gözetiminde yapılmalıdır.</li>
    <li>Hasarlı malzemenin değerlendirilmesi konusunda sigortayla görüştükten sonra karar verilir.</li>
    <li>Bina, tesisat ve makineler yetkili kişilerce güvenli bulunup işveren yeniden başlama izni vermeden çalıştırılmaz. Tahliye edilenler yetkili izin olmadan binaya dönmez.</li>
    <li>Büyük onarım ve restorasyon faaliyetlerine, resmi makamlarla ve sigorta şirketiyle görüştükten sonra başlanır.</li>
    <li>Hasarlı kısmın değeri çıkarılır, işin kesilmesinin verdiği zarar hesaplanır.</li>
</ul>
<p>Plan; işyeri / çevre değişikliği, ekip veya iletişim değişikliği ve tatbikat / olay bulgularında gözden geçirilir. En geç yenileme süreleri çok tehlikeli 2, tehlikeli 4, az tehlikeli 6 yıldır; bu plan için en geç yenileme tarihi <b>{{ $n($d['gecerlilik']) }}</b>. Tahliye planı giriş, çıkış ve katlarda görünür biçimde asılır; ekip listesi duyurulur ve onaylı plan ekiplere erişilebilir tutulur. Gözden geçirme sorumlusu: İşveren / acil durum koordinatörü.</p>

{{-- OLASI ACİL DURUMLAR VE RİSK DEĞERLENDİRMESİ --}}
<div class="yeni-sayfa"></div>
<h2>OLASI ACİL DURUMLAR ve RİSK DEĞERLENDİRMESİ</h2>
<table class="tablo">
    <tr>
        <th class="orta" style="width:6%">SIRA NO</th>
        <th style="width:24%">RİSKLER</th>
        @foreach ($kolonlar as $kolon)
            <th class="orta" style="font-size:7pt">{{ $buyuk($kolon) }}</th>
        @endforeach
        <th class="orta" style="font-size:7pt">TOPLAM</th>
    </tr>
    @foreach ($d['riskler'] as $i => $r)
        <tr>
            <td class="orta">{{ $i + 1 }}.</td>
            <td>{{ $r['ad'] }}</td>
            @foreach ($r['puan'] as $p)
                <td class="orta">{{ $p ?? '—' }}</td>
            @endforeach
            <td class="orta"><b>{{ $r['toplam'] ?? '—' }}</b></td>
        </tr>
    @endforeach
</table>
<p class="kucuk" style="margin-top:4pt">1 → 5: 1 düşük, 5 yüksek değerlendirmeyi ifade eder. Puanlar {{ $unvan }} saha risk değerlendirmesine göre doldurulur; başka işyerinin puanları kullanılmaz. Bu tablo risk değerlendirmesinin yerine geçmez. Uygulanmayan durum gerekçesiyle işaretlenir.</p>

{{-- EK-1 SENARYOLAR --}}
@foreach ($d['senaryolar'] as $i => $s)
    <div class="yeni-sayfa"></div>
    @if ($i === 0)
        <h2>EK-1 ACİL DURUMLAR VE MÜDAHALE YÖNTEMLERİ</h2>
    @endif
    <h3>{{ $i + 1 }}. ACİL DURUM: {{ $buyuk($s['ad']) }}</h3>
    @if ($s['akis'])
        <div style="text-align:center"><img src="{{ $s['akis'] }}" style="height:330pt"></div>
    @endif
    @if ($s['aciklama'])
        <p>{{ $s['aciklama'] }}</p>
    @endif
    @if ($s['mudahale'])
        <h4>MÜDAHALE ADIMLARI</h4>
        <ul>@foreach ($s['mudahale'] as $m)<li>{{ $m }}</li>@endforeach</ul>
    @endif
    @if ($s['olay_sonrasi'])
        <h4>OLAY SONRASI</h4>
        <ul>@foreach ($s['olay_sonrasi'] as $m)<li>{{ $m }}</li>@endforeach</ul>
    @endif
@endforeach

{{-- EK-2 İLETİŞİM --}}
<div class="yeni-sayfa"></div>
<h2>EK-2 ACİL İLETİŞİM LİSTESİ</h2>
<h3>1. İÇ İLETİŞİM LİSTESİ</h3>
<table class="tablo">
    <tr><th style="width:28%"></th><th>İSİM SOYİSİM</th><th style="width:28%">İLETİŞİM BİLGİLERİ</th></tr>
    @foreach ($d['ic_iletisim'] as $r)
        <tr><td><b>{{ $r['rol'] }}</b></td><td>{{ $n($r['ad']) }}</td><td>{{ $n($r['tel']) }}</td></tr>
    @endforeach
</table>
<h3>2. DIŞ İLETİŞİM LİSTESİ</h3>
<table class="tablo">
    <tr><th style="width:34%">BİRİM ADI</th><th style="width:18%">TELEFON</th><th>ADRES-TELEFON BİLGİLERİ</th></tr>
    @foreach ($d['dis_iletisim'] as $r)
        <tr>
            <td><b>{{ $r['birim'] }}</b></td>
            <td>{{ ($r['doldurulacak'] ?? false) ? $n($r['tel']) : $r['tel'] }}</td>
            <td>{{ ($r['doldurulacak'] ?? false) ? $n($r['aciklama']) : $r['aciklama'] }}</td>
        </tr>
    @endforeach
</table>
<p class="kucuk" style="margin-top:4pt">İtfaiye, ambulans, polis, jandarma, sahil güvenlik, orman yangın ve AFAD hatları 112 Acil Çağrı Merkezinde birleşmiştir (Md.12/1-f-5).</p>

{{-- EK-3 KKD --}}
<div class="yeni-sayfa"></div>
<h2>EK-3 FAALİYETLERE GÖRE KİŞİSEL KORUYUCU EKİPMAN LİSTESİ</h2>
<table class="tablo">
    <tr>
        <th style="width:20%"></th>
        @foreach ($kkd['faaliyetler'] as $f)
            <th class="orta" style="font-size:6.5pt">{{ $f }}</th>
        @endforeach
    </tr>
    @foreach ($kkd['kkd'] as $ad => $isaret)
        <tr>
            <td style="font-size:7.5pt"><b>{{ $ad }}</b></td>
            @foreach (str_split($isaret) as $x)
                <td class="orta">{{ $x === '1' ? 'D' : '–' }}</td>
            @endforeach
        </tr>
    @endforeach
</table>
<p class="kucuk" style="margin-top:4pt">D: Faaliyet gerçekten yapılıyorsa risk değerlendirmesi / SDS / elektrik ve düşme tehlikesine göre uygun özellikte KKD seçilir; otomatik zorunluluk veya yasak anlamına gelmez.</p>

{{-- EK-4 DESTEK ELEMANI ATAMA --}}
<div class="yeni-sayfa"></div>
<h2>EK-4 DESTEK ELEMANI ATAMA YAZISI</h2>
<p>Aşağıdaki çalışanlar işveren tarafından belirtilen acil durum görevlerine atanır. İşyerinde {{ $d['calisan'] ?: $nokta }} çalışan ve {{ $d['tehlike'] ? mb_strtolower($d['tehlike'], 'UTF-8') : $nokta }} sınıf esas alınmıştır. Söndürme, kurtarma ve koruma ekipleri için ayrı ayrı en az birer eğitimli çalışan; ilkyardım için en az bir geçerli belgeli ilkyardımcı görevlendirilir. Haberleşme ve teknik müdahale ekipleri ayrıca düzenlenir.@if ($d['calisan'] > 0 && $d['calisan'] < 10) 10’dan az çalışanı olan bu işyerinde söndürme, kurtarma ve koruma görevleri için en az bir eğitimli kişi yeterlidir (Md.11).@endif Vardiya / devamsızlıkta görev devamlılığı sağlanır.</p>
<table class="tablo">
    <tr><th style="width:46%">Ad / Soyad (Rol)</th><th style="width:30%">Görevi (Acil Durum Ekibi)</th><th>İMZA</th></tr>
    @foreach ($ekipAd as $anahtar => $ekip)
        @forelse ($d['ekipler'][$anahtar] ?? [] as $uye)
            <tr>
                <td>
                    Adı soyadı: <b>{{ $uye['ad'] }}</b>{{ $uye['lider'] ? ' (Ekip başı)' : '' }}<br>
                    Görevi / vardiya: {{ $n(implode(' / ', array_filter([$uye['gorev'], $uye['vardiya']]))) }}<br>
                    Telefon: {{ $n($uye['telefon']) }}<br>
                    {{ $uye['yedek'] ? 'Yedek görevli' : 'Asıl görevli' }}{{ $uye['belge_no'] ? ' · Belge: '.$uye['belge_no'] : '' }}{{ $uye['belge_bitis'] ? ' (geçerlilik: '.\Illuminate\Support\Carbon::parse($uye['belge_bitis'])->format('d.m.Y').')' : '' }}
                </td>
                <td><b>{{ $ekip['ad'] }}</b><br>Sorumluluk: {{ $uye['bolum'] ?: 'işyeri geneli' }}</td>
                <td>Tebellüğ<br>Tarih: ........<br>İmza: ........</td>
            </tr>
        @empty
            <tr>
                <td>Adı soyadı: ............<br>Görevi / vardiya: ............<br>Telefon: ............<br>Yedek / telefon: ............</td>
                <td><b>{{ $ekip['ad'] }}</b><br>Sorumluluk: işyeri geneli</td>
                <td>Tebellüğ<br>Tarih: ........<br>İmza: ........</td>
            </tr>
        @endforelse
    @endforeach
</table>
<p class="kucuk" style="margin-top:4pt">Liste imzalandıktan sonra çalışanların görüş seviyesinde görünür yere asılır; görevli ayrılırsa veya yeri değişirse yeni atama yapılır. Görev çakışması ve vardiyada bulunabilirlik kontrol edilir (Md.11).</p>
<p style="margin-top:10pt">İşveren / İşveren Vekili: <b>{{ $n($d['isveren'] ?? $d['isveren_vekili']) }}</b> &nbsp;&nbsp; İmza / Tarih: ........................</p>

{{-- EK-5 FİRMA BİLGİLERİ --}}
<div class="yeni-sayfa"></div>
<h2>EK-5 FİRMA BİLGİLERİ</h2>
<table class="tablo">
    <tr><th style="width:18%">İŞYERİNİN</th><td colspan="2"><b>{{ $unvan }}</b></td></tr>
    <tr><th></th><td>İşveren / vekili: {{ $n(implode(' / ', array_filter([$d['isveren'], $d['isveren_vekili']]))) }}</td><td style="width:30%">ÇALIŞAN SAYISI: {{ $d['calisan'] ?: $nokta }}</td></tr>
    <tr><th>İGU / İH</th><td>İGU: {{ $hz['ad'] }}{{ $hz['unvan'] ? ' — '.$hz['unvan'] : '' }}</td><td>İşyeri hekimi: ........................</td></tr>
    <tr><th>FAALİYETİN TÜRÜ</th><td colspan="2">
        Faaliyet: {{ $n($d['faaliyet']) }}<br>
        NACE: {{ $n($firma?->nace_kodu) }} &nbsp;·&nbsp; Tehlike sınıfı: {{ $n($d['tehlike']) }}<br>
        Vardiyalar: {{ $n($u['vardiyalar']) }}
    </td></tr>
</table>
<p style="margin-top:8pt">Açık adres / kat: <b>{{ $n($d['adres']) }}</b>. İşyeri telefonu: <b>{{ $n($d['telefon']) }}</b>. SGK sicil no: <b>{{ $n($d['sgk']) }}</b>. Kullanım alanı / kat sayısı: <b>{{ $n($u['bina_bilgisi']) }}</b>.</p>
<p>Makine / tesisat listesi: <b>{{ $d['makineler'] ? implode(', ', $d['makineler']) : $nokta }}</b>.</p>
<p>Kimyasallar / gazlar (SDS kayıtları Kimyasal Sicili’nde): <b>{{ $d['kimyasallar'] ? implode(', ', $d['kimyasallar']) : $nokta }}</b>.</p>
<p>Toplanma yeri: <b>{{ $n($d['toplanma']) }}</b>. Yedek alan / refakat görevlisi: <b>{{ $n(implode(' / ', array_filter([$u['yedek_toplanma'], $u['refakatci']]))) }}</b>. Hazırlama: <b>{{ $d['yayin'] }}</b>; en geç yenileme: <b>{{ $d['gecerlilik'] }}</b>. Değişiklik halinde süre beklenmeden revizyon yapılır.</p>

{{-- EK-6 ÇEVRESEL RİSKLER --}}
<div class="yeni-sayfa"></div>
<h2>EK-6 ÇEVRESEL RİSKLER VE KOMŞU TESİSLER DEĞERLENDİRMESİ</h2>
<p>Yönetmeliğin 8 ve 10/7. maddeleri kapsamında aşağıdaki etkenlerin varlığı sahada doğrulanır. Her satıra VAR / YOK, komşu adı / konumu, etki ve gerekli tedbir yazılır. Liste, bu tesislerin gerçekten bulunduğu anlamına gelmez.</p>
<table class="tablo">
    <tr><th class="orta" style="width:6%">S.No</th><th style="width:32%">Komşu Tesis / Çevresel Risk Faktörü</th><th class="orta" style="width:11%">Durum</th><th>Etki Durumu ve Olası Tehlike</th></tr>
    @foreach ($d['cevresel'] as $i => $r)
        <tr>
            <td class="orta">{{ $i + 1 }}</td>
            <td>{{ $r['ad'] }}</td>
            <td class="orta"><b>{{ ['var' => 'VAR', 'yok' => 'YOK'][$r['durum']] ?? 'VAR / YOK' }}</b></td>
            <td>{{ $r['etki'] }}@if ($r['durum'] !== 'yok')<br>Konum / tedbir / sorumlu: {{ $n($r['not']) }}@endif</td>
        </tr>
    @endforeach
</table>
@if ($d['disaridan'])
    <p style="margin-top:6pt"><b>İşyerini dışarıdan etkileyebilecek işyerleri ve çevre:</b></p>
    <p class="metin">{{ $d['disaridan'] }}</p>
@endif

{{-- EK-7 TAHLİYE VE ÇIKIŞ PLANI --}}
<div class="yeni-sayfa"></div>
<h2>EK-7 TAHLİYE VE ÇIKIŞ PLANI</h2>
<div class="kroki">
    @if ($d['tahliye_gorsel'])
        <div style="text-align:center"><img src="{{ $d['tahliye_gorsel'] }}" style="max-width:510pt;max-height:460pt"></div>
    @else
        <p style="text-align:center;font-weight:bold;margin:40pt 0">İŞYERİNE AİT GERÇEK TAHLİYE KROKİSİ BURAYA EKLENECEKTİR.</p>
    @endif
    <p style="margin-top:6pt">Kat / bölüm: <b>{{ $n($u['kroki_kat']) }}</b> &nbsp;·&nbsp; Çizimi hazırlayan / tarih: <b>{{ $hz['ad'] }} / {{ $d['yayin'] }}</b></p>
    <p class="kucuk">Krokide gösterilecekler: bulunulan konum; kaçış yönleri, kapılar ve alternatif güvenli güzergâhlar · asıl ve yedek toplanma alanı; müdahale ekiplerinin erişim yolu · söndürücüler, ilkyardım malzemesi ve varsa alarm butonları · elektrik / gaz kesme noktaları ve vanalar · kimyasal, parlama / patlama ve diğer özel riskli bölümler · acil irtibatlar ve Ek-4 görevlilerinin iletişim bilgileri. Saha ölçüleri ve yerleşim doğrulanarak hazırlanır; tamamlanan kroki giriş, çıkış ve katlarda görünür biçimde asılır (Md.12/3).</p>
</div>

{{-- DEĞİŞME VE DÜZELTMELER + ONAY --}}
<div class="yeni-sayfa"></div>
<h2>DEĞİŞME VE DÜZELTMELER</h2>
<table class="tablo">
    <tr><th class="orta" style="width:9%">SIRA NO</th><th>DEĞİŞME/DÜZELTME KONUSU</th><th style="width:26%">DEĞİŞME/DÜZELTMEYİ YAPAN</th><th class="orta" style="width:14%">TARİH</th></tr>
    @foreach ($d['degisiklikler'] as $r)
        <tr><td class="orta">{{ $r['no'] }}</td><td>{{ $r['konu'] }}</td><td>{{ $r['yapan'] }}</td><td class="orta">{{ $r['tarih'] }}</td></tr>
    @endforeach
    @for ($i = count($d['degisiklikler']); $i < 8; $i++)
        <tr><td style="height:16pt"></td><td></td><td></td><td></td></tr>
    @endfor
</table>

<p style="margin-top:14pt;font-weight:bold;text-align:center">{{ $unvan }}</p>
<p style="text-align:center;font-size:8.5pt">BU ACİL DURUM PLANININ SAYFALARI NUMARALANDIRILMIŞTIR; TOPLAM SAYFA SAYISI ALT BİLGİDE GÖSTERİLMİŞTİR. İŞYERİNE ÖZGÜ BİLGİLER, GÖREVLENDİRMELER VE KROKİ TAMAMLANARAK İŞVEREN ONAYINA SUNULUR. HAZIRLAYANLAR HER SAYFAYI PARAFLAR VE SON SAYFAYI İMZALAR (Md.12/2).</p>
<table class="tablo" style="margin-top:10pt">
    <tr>
        <td class="orta" style="width:33%;height:80pt">
            @if ($imzali && $hz['kase'])<img src="{{ storage_path('app/public/'.$hz['kase']) }}" style="max-height:38pt"><br>@endif
            @if ($imzali && $hz['imza'])<img src="{{ storage_path('app/public/'.$hz['imza']) }}" style="max-height:30pt"><br>@endif
            <b>Hazırlayan</b><br>{{ $hz['ad'] }}@if ($hz['unvan'])<br>{{ $hz['unvan'] }}@endif
        </td>
        <td class="orta" style="width:33%">
            @if ($imzali && $hekim?->kase_gorseli)<img src="{{ storage_path('app/public/'.$hekim->kase_gorseli) }}" style="max-height:38pt"><br>@endif
            @if ($imzali && $hekim?->imza_gorseli)<img src="{{ storage_path('app/public/'.$hekim->imza_gorseli) }}" style="max-height:30pt"><br>@endif
            <b>İşyeri Hekimi</b><br>(İmza – Kaşe)
        </td>
        <td class="orta">
            <b>Onaylayan — İşveren / İşveren Vekili</b><br>{{ $d['isveren'] ?? $d['isveren_vekili'] }}<br>(İmza – Kaşe – Onay Tarihi)
        </td>
    </tr>
</table>

</body>
</html>
