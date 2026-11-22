<?php

/*
| Herkese açık sektör sayfaları (/sektor/{slug}) — App\Http\Controllers\SektorController,
| resources/views/pages/sektor_detay.blade.php. Sitemap'e priority 0.8 ile girer.
|
| tehlike: sayfa rozeti ve teklif formunda önceden seçilen sınıf (TeklifTalebi::TEHLIKE_SINIFLARI).
| nace: database/data/nace-kodlari.php'deki (İşyeri Tehlike Sınıfları Tebliği EK-1) kod/sınıf —
| sayfada "işyerinizin kesin sınıfı NACE koduna göre belirlenir" notuyla gösterilir.
*/

return [

    'otomotiv-yan-sanayi' => [
        'baslik' => 'Otomotiv Yan Sanayi',
        'seo_baslik' => 'Bursa Otomotiv Yan Sanayi İSG Hizmeti | NOSAB, DOSAB, BOSB',
        'aciklama' => 'Bursa otomotiv yan sanayi fabrikalarına iş güvenliği uzmanlığı: pres güvenliği, robotik hat, kaynak ve boya prosesleri için sahada risk değerlendirmesi ve dijital takip.',
        'giris' => 'Pres hatlarından robotik kaynak hücrelerine, yüksek tempolu otomotiv tedarik zincirinde iş güvenliğini sahada yönetiyoruz. Müşteri denetimlerine (OEM audit) hazır kayıt düzeni kuruyoruz.',
        'bolgeler' => ['NOSAB', 'DOSAB', 'BOSB', 'Nilüfer', 'Osmangazi'],
        'tehlike' => 'Tehlikeli',
        'nace' => [
            ['29.32.20', 'Motorlu kara taşıtları için parça ve aksesuar imalatı', 'Tehlikeli'],
            ['25.50.01', 'Metallerin dövülmesi, preslenmesi, baskılanması', 'Tehlikeli'],
            ['25.61.01', 'Isıl işlem, galvaniz ve kimyasal metal kaplama', 'Çok Tehlikeli'],
        ],
        'riskler' => [
            ['Pres güvenliği', 'Çift el kumandası, ışık perdesi ve emniyet kilitlerinin çalışırlığı; kalıp değişiminde enerji izolasyonu (LOTO) ve sıkışma-ezilme noktalarının kontrolü.'],
            ['Robotik hat ve otomasyon', 'Robot hücresi çevre korumaları, kilitli erişim kapıları, acil durdurma zinciri ve bakım-arıza müdahalesinde güvenli çalışma talimatları.'],
            ['Kaynak hatları', 'Kaynak dumanı ve UV ışınımına karşı lokal havalandırma ile kişisel koruyucular; kıvılcıma bağlı yangın riski ve ortam ölçümleri.'],
            ['Boya ve kimyasal prosesler', 'Güvenlik bilgi formlarıyla (SDS) kimyasal envanteri, solvent buharı, patlayıcı ortam (ATEX) değerlendirmesi ve dökülme müdahalesi.'],
            ['Fabrika içi forklift trafiği', 'Yaya ve araç yollarının ayrılması, kör noktalar, hız sınırları ve operatör belgelerinin takibi.'],
            ['Gürültü ve titreşim', 'Pres ve taşlama bölümlerinde gürültü ölçümü, kulak koruyucu seçimi ve maruziyete göre sağlık gözetimi.'],
        ],
        'sss' => [
            ['Otomotiv yan sanayide pres emniyeti denetimleri ne sıklıkla yapılmalıdır?', 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği uyarınca hidrolik ve eksantrik preslerin emniyet aksamlarının (ışık bariyeri, çift el kumandası) çalışırlığı her kullanım öncesinde kontrol edilmeli; pres, yetkili kişilerce periyodik kontrolden geçirilmelidir. Mehse İSG, presler için günlük denetim ve LOTO prosedürlerini devreye alır.'],
            ['IATF 16949 denetimlerinde İSG risk analizi nasıl hazırlanır?', 'IATF 16949 bir kalite yönetim standardıdır; ancak denetimlerde acil durum planları, çalışma ortamı ve makine emniyeti de sorgulanır. Bu denetimler için robotik hücreler, kimyasal buhar ve ergonomi risklerini kapsayan Fine-Kinney veya 5x5 raporları sunuyoruz.'],
            ['Bursa genelinde OSB içi yerinde ön keşif süresi nedir?', 'NOSAB, DOSAB, BTSO OSB ve Hasanağa OSB bölgelerindeki otomotiv tesislerine 24 saat içinde yerinde ücretsiz ön keşif gerçekleştiriyoruz.'],
        ],
    ],

    'metal-talasli-imalat' => [
        'baslik' => 'Metal İşleme ve Talaşlı İmalat',
        'seo_baslik' => 'Bursa Metal İşleme ve CNC Atölyeleri İSG Hizmeti',
        'aciklama' => 'Bursa CNC, torna, freze ve lazer kesim atölyelerine iş güvenliği uzmanlığı: tezgah koruyucuları, bor yağı, taşlama ve kaldırma ekipmanları için sahada risk değerlendirmesi.',
        'giris' => 'CNC işleme merkezlerinden konvansiyonel torna-frezeye, talaşlı imalat atölyelerinde makine kaynaklı kazaları sahada önlemeye odaklanıyoruz.',
        'bolgeler' => ['NOSAB', 'DOSAB', 'BOSB', 'Nilüfer', 'Osmangazi', 'Yıldırım'],
        'tehlike' => 'Tehlikeli',
        'nace' => [
            ['25.62.02', 'Metallerin makinede işlenmesi (torna, freze, delme, kaynak vb.)', 'Tehlikeli'],
            ['25.73.03', 'Metalden kalıp ve döküm modeli imalatı', 'Tehlikeli'],
            ['25.62.01', 'Metallerin lazerle kesilmesi', 'Çok Tehlikeli'],
        ],
        'riskler' => [
            ['CNC tezgah güvenliği', 'Kabin kapısı emniyet kilidi, program denemesinde güvenli çalışma, fırlayan parça ve talaşa karşı koruyucular.'],
            ['Torna ve freze', 'Dönen parçalara kapılma (eldiven, bol giysi, takı), ayna anahtarı unutma ve tezgah koruyucularının yerinde olması.'],
            ['Bor yağı ve soğutma sıvıları', 'Cilt teması kaynaklı dermatit, yağ buharı (aerosol) maruziyeti, sıvı bakımı ve uygun eldiven seçimi.'],
            ['Taşlama ve zımpara', 'Taş patlaması riskine karşı devir uyumu ve siper; metal tozu, göz yaralanmaları ve kıvılcım.'],
            ['Kaldırma ekipmanları', 'Tavan vinci ve caraskalların periyodik kontrolleri, sapan-zincir uygunluğu ve yük altında durmama kuralı.'],
            ['Lazer kesim', 'Lazer ışınımı, kesim dumanı ve yangın riski; bu faaliyet tebliğde "Çok Tehlikeli" sınıftadır.'],
        ],
        'sss' => [
            ['Talaşlı imalat ve CNC atölyelerinde Patlamadan Korunma Dokümanı (PKD) zorunlu mu?', 'Metal tozu çıkaran taşlama/parlatma ve yanıcı gaz kullanılan kaynaklı sahalarda patlayıcı ortam oluşma ihtimali varsa, Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik gereği PKD hazırlanması zorunludur. Bu ihtimal risk değerlendirmesiyle belirlenir.'],
            ['Metal işleme sektöründe hangi eldiven standartları (EN 388) uygulanmalıdır?', 'Dönen parçalara kapılma riskinde eldivensiz çalışma; çapak alma ve sac taşımada ise EN 388 Seviye D veya F kesilme dirençli eldiven matrisi uygulanır.'],
            ['CNC tezgahlarında bor yağı (metal işleme sıvısı) maruziyeti nasıl yönetilir?', 'Bor yağı Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik kapsamındadır: güvenlik bilgi formu esas alınarak risk değerlendirmesi yapılır, yağ buharı için kabin emişi/yağ buharı filtresi kullanılır, emülsiyonun konsantrasyon, pH ve bakteri durumu düzenli kontrol edilir. Cilt teması dermatit yaptığından nitril eldiven ve koruyucu krem kullanılır, maruz kalan çalışanlar sağlık gözetimine alınır. CNC kabin kapısı emniyet kilidi (interlock) de aynı denetimde test edilir.'],
            ['Kompresör ve basınçlı kap kontrolleri nasıl takip edilir?', 'Hava tankı ve kompresörler, İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği gereği yetkili kişilerce periyodik kontrole tabidir; kontrol periyodu ve yapılacak testler yönetmeliğin Ek-III’ünde belirlenmiştir. Panelimiz son muayene ve bir sonraki kontrol tarihlerini otomatik izler.'],
        ],
    ],

    'lojistik-ve-depolama' => [
        'baslik' => 'Lojistik ve Depolama',
        'seo_baslik' => 'Bursa Depo ve Lojistik Merkezleri İSG Hizmeti | Raf ve Forklift Güvenliği',
        'aciklama' => 'Bursa depo ve lojistik merkezlerine iş güvenliği uzmanlığı: raf statiği, forklift trafiği, yükleme rampaları ve yangın güvenliği için sahada risk değerlendirmesi.',
        'giris' => 'Yüksek raflı depolardan cross-dock merkezlerine, malzeme akışının hızlı olduğu alanlarda raf, araç ve yangın güvenliğini birlikte yönetiyoruz.',
        'bolgeler' => ['NOSAB', 'DOSAB', 'BOSB', 'Gürsu', 'Kestel', 'Nilüfer'],
        'tehlike' => 'Tehlikeli',
        'nace' => [
            ['52.10.90', 'Depolama ve antrepoculuk faaliyetleri', 'Tehlikeli'],
            ['52.24.10', 'Kara yolu kargo yükleme boşaltma hizmetleri', 'Tehlikeli'],
            ['52.10.04', 'Petrol, kimyasal ve gaz depolama', 'Çok Tehlikeli'],
        ],
        'riskler' => [
            ['Raf statiği', 'Raf ayaklarında darbe hasarı, taşıma kapasitesi etiketleri, ankraj ve düzenli raf muayenesi (TS EN 15635 esaslı).'],
            ['Forklift trafiği', 'Yaya yolu ayrımı, ayna ve uyarı ışıkları, hız sınırı, şarj alanı güvenliği ve operatör belgeleri.'],
            ['Yükleme rampaları', 'Tır kaymasına karşı takoz ve kilit, rampa köprüsü kontrolü, yükleme sırasında düşme riski.'],
            ['Yüksekte çalışma', 'Raf üst seviyelerine erişimde uygun kişi kaldırma platformu; forklift çatalında insan taşınmaması.'],
            ['Yangın güvenliği', 'Yüksek yangın yükü, sprinkler ve yangın dolabı erişimi, kaçış yollarının açık tutulması ve tatbikatlar.'],
            ['Elle taşıma ve ergonomi', 'Ağır ve tekrarlı kaldırma işlerinde bel yaralanmalarını önleyen yöntem ve yardımcı ekipman seçimi.'],
        ],
        'sss' => [
            ['Endüstriyel ağır yük depo raflarının periyodik kontrolü zorunlu mudur?', 'Raf güvenliğini sağlamak 6331 sayılı Kanun kapsamında işverenin yükümlülüğüdür. TS EN 15635 standardı bu kontrolün kabul gören yöntemidir: endüstriyel raf sistemlerinin yılda en az bir kez teknik yetkin kişilerce denetlenmesini ve hasarların yeşil, turuncu, kırmızı risk seviyelerinde raporlanmasını öngörür.'],
            ['Depolarda forklift ve yaya yolu ayrımı nasıl projelendirilir?', 'Fiziki bariyerler, darbe koruyucular, kör nokta aynaları ve epoksi zemin güvenlik çizgileri ile sıfır kaza standartlarında saha trafik planı çizilir.'],
            ['Forklift kullanımında hangi belge ve kontroller zorunludur?', 'Forklift operatörlerinin eğitimli ve yetkilendirilmiş olması gerekir; forklift operatörlüğü MYK mesleki yeterlilik belgesi zorunlu meslekler arasındadır. Forkliftler kaldırma ekipmanı olarak İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği Ek-III uyarınca yılda en az bir kez yetkili kişilerce periyodik kontrolden geçirilir; vardiya başı kontrol listesi, emniyet kemeri ve hız sınırı saha kuralları ayrıca uygulanır.'],
            ['Depo yangın yükü ve acil durum tahliye planı nasıl hazırlanır?', 'Binaların Yangından Korunması Hakkında Yönetmelik kapsamında depolanan malzemeler, palet yükseklikleri ve sprinkler yerleşimi analiz edilerek tahliye şemaları hazırlanır.'],
        ],
    ],

    'insaat-ve-santiye' => [
        'baslik' => 'İnşaat ve Şantiye',
        'seo_baslik' => 'İnşaat & Şantiye İSG Yazılımı ve Risk Yönetimi | Mehse İSG',
        'aciklama' => 'İnşaat ve şantiye projelerinde yüksekte çalışma, iskele/kalıp, taşeron evrak takibi, günlük yoklama, iş izni (PTW) ve QR ziyaretçi giriş yönetimi.',
        'giris' => 'Değişken iş kalemleri ve çoklu taşeron yapısına sahip şantiyelerde yüksekte çalışma, kazı, kaldırma ve alt işveren İSG süreçlerini tek ekrandan sahada yönetiyoruz.',
        'bolgeler' => ['Türkiye Geneli', 'Bursa', 'İstanbul', 'Ankara', 'İzmir', 'Kocaeli'],
        'tehlike' => 'Çok Tehlikeli',
        'nace' => [
            ['41.20.02', 'İkamet amaçlı binaların inşaatı', 'Çok Tehlikeli'],
            ['42.11.01', 'Kara yolları, otoyollar ve köprülerin inşaatı', 'Çok Tehlikeli'],
            ['43.99.15', 'Yapı iskelesi ve çalışma platformu kurma ve sökme', 'Çok Tehlikeli'],
        ],
        'riskler' => [
            ['Yüksekte çalışma ve düşme', 'Yaşam hatları, korkuluk sistemleri, güvenlik ağları, tam vücut emniyet kemeri ve yüksekte çalışma iş izinleri (PTW).'],
            ['İskele ve kalıp güvenliği', 'TS EN 12810/12811 uyumlu iskele kontrolleri, etiketleme, zemin taşıma gücü ve rüzgar kısıtlamaları.'],
            ['Kazı ve iksa çalışmaları', 'Şev açısı, iksa projesi, yeraltı elektrik/doğalgaz hatları tespiti ve göçük riskine karşı günlük kontrol.'],
            ['Kule vinç ve mobil vinçler', 'EKİPNET periyodik kontrolleri, sapan-halat muayenesi, işaretçi-sapancı yetkinlikleri ve yük yolu izolasyonu.'],
            ['Alt işveren / taşeron yönetimi', 'Şantiyeye girmeden önce SGK bildirgesi, MYK belgeleri, sağlık raporu ve eğitim evraklarının self-servis QR portalıyla doğrulanması.'],
            ['Günlük şantiye yoklaması', 'Acil tahliye ve göçük/yangın anında sahada fiilen bulunan çalışan ve ziyaretçi sayısının anlık bilinmesi.'],
        ],
        'sss' => [
            ['Şantiyede Sağlık ve Güvenlik Planı (SGP) ne zaman zorunludur?', 'Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği uyarınca yapı işine başlamadan önce proje hazırlık aşamasında Sağlık ve Güvenlik Planı hazırlanmalı ve şantiye ilerledikçe güncellenmelidir.'],
            ['Alt işveren çalışanlarının evrakları sahaya girmeden nasıl kontrol edilir?', 'Mehse İSG Taşeron Self-Servis QR Portalı ile alt işveren tüm zorunlu belgelerini (SGK, MYK, EK-2, eğitim) linkten yükler; İSG uzmanı onaylamadan saha girişi açılmaz.'],
            ['Yüksekte çalışma ve sıcak işler için dijital İş İzni (PTW) kullanabilir miyiz?', 'Evet, İş İzin Formu modülüyle yüksekte çalışma, sıcak iş, kazı ve kapalı alan izinleri kontrol listesi ve onay kilidiyle yönetilir.'],
        ],
    ],

    'madencilik-ve-tas-ocagi' => [
        'baslik' => 'Madencilik ve Taş Ocakları',
        'seo_baslik' => 'Madencilik & Taş Ocağı İSG Yönetimi | Sağlık ve Güvenlik Dokümanı',
        'aciklama' => 'Açık ve yeraltı maden işletmeleri ile taş ocaklarında Sağlık ve Güvenlik Dokümanı, patlatma, iş makineleri, toz/gürültü ölçümü ve sağlık gözetimi takibi.',
        'giris' => 'Ağır iş makineleri, patlatma, şev stabilitesi ve yüksek toz maruziyeti barındıran madencilik sahalarında 6331 ve Maden İşyerleri Yönetmeliğine tam uyumlu dijital takip sunuyoruz.',
        'bolgeler' => ['Türkiye Geneli', 'Marmara', 'Ege', 'İç Anadolu', 'Karadeniz'],
        'tehlike' => 'Çok Tehlikeli',
        'nace' => [
            ['08.11.01', 'Taş ocakçılığı (mermer, granit, kum taşı vb.)', 'Çok Tehlikeli'],
            ['08.12.01', 'Çakıl ve kum ocaklarının faaliyetleri', 'Çok Tehlikeli'],
            ['05.10.01', 'Taş kömürü ve linyit madenciliği', 'Çok Tehlikeli'],
        ],
        'riskler' => [
            ['Şev stabilitesi ve göçük', 'Basamak yüksekliği, şev eğimi, yağış sonrası çatlak denetimleri ve düşen kaya bariyerleri.'],
            ['Ağır iş makineleri', 'Ekskavatör, yükleyici ve kaya kamyonları için G sınıfı/operatörlük belge vadesi, periyodik kontroller ve kör nokta kuralları.'],
            ['Toz (silika) ve gürültü', 'Kişisel maruziyet ölçümleri, sulu bastırma sistemleri, FFP3 solunum koruyucular ve periyodik SFT/odyometri/akciğer grafisi takibi.'],
            ['Patlatma ve ateşleme', 'Ateşleyici yeterlilik belgeleri, patlayıcı madde depolama güvenliği ve patlatma öncesi alan tahliye protokolleri.'],
            ['Acil durum ve kurtarma', 'Sertifikalı ilkyardımcı, tahlisiye/kurtarma ekibi kontenjanları, revir/ecza dolabı stoğu ve yıllık tatbikatlar.'],
        ],
        'sss' => [
            ['Maden işyerlerinde Sağlık ve Güvenlik Dokümanı (SGD) nasıl takip edilir?', 'Maden İşyerlerinde İş Sağlığı ve Güvenliği Yönetmeliği gereği çalışma başlamadan önce hazırlanması zorunlu olan risk değerlendirmesi, acil durum ve ekipman kayıtları Mehse İSG üzerinde sürüm kilidiyle saklanır.'],
            ['İş makinesi operatörlerinin belge geçerlilikleri otomatik hatırlatılır mı?', 'Evet, çalışan kartındaki MYK, SRC, Psikoteknik ve Operatörlük belge geçerlilik tarihleri Yasal Takvim üzerinden otomatik uyarı üretir.'],
        ],
    ],

    'enerji-ve-tersane' => [
        'baslik' => 'Enerji ve Tersane',
        'seo_baslik' => 'Enerji Santralleri & Tersane İSG Yönetimi | LOTO, PTW ve ATEX',
        'aciklama' => 'GES, RES, termik santraller ve tersane sahalarında yüksek gerilim, kapalı alan, sıcak iş izni (PTW), ATEX/PKD ve çoklu yüklenici İSG kontrolü.',
        'giris' => 'Yüksek enerjili elektrik tesisleri, rüzgar/güneş santralleri ve tersane bakım havuzlarında kapalı alan, sıcak iş ve yüksek gerilim risklerini izin ve kanıt disipliniyle yönetiyoruz.',
        'bolgeler' => ['Türkiye Geneli', 'Yalova', 'Tuzla', 'Kocaeli', 'İzmir', 'Bursa'],
        'tehlike' => 'Çok Tehlikeli',
        'nace' => [
            ['35.11.19', 'Elektrik enerjisi üretimi (GES, RES, HES, termik)', 'Çok Tehlikeli'],
            ['30.11.01', 'Gemi ve yüzer yapıların inşası ve onarımı (tersane)', 'Çok Tehlikeli'],
            ['35.12.01', 'Elektrik enerjisinin iletimi ve dağıtımı', 'Çok Tehlikeli'],
        ],
        'riskler' => [
            ['Yüksek gerilim ve LOTO', 'Enerji kesme, kilitleme-etiketleme, gerilim yokluğu kontrolü, topraklama ve ark flaş KKD matrisi.'],
            ['Kapalı alan ve gaz ölçümü', 'Gemi tankları ve menhollerde O₂/LEL/CO/H₂S ölçümü, gözcü ataması ve kurtarma ekipmanı.'],
            ['Sıcak işler ve yangın', 'Kesme/kaynak iş izinleri (PTW), gaz-free kontrolü, yangın gözcüsü ve kıvılcım örtüleri.'],
            ['Patlayıcı ortam (ATEX/PKD)', 'Boya, yakıt ve akü odalarında bölge sınıflandırması ve Patlamadan Korunma Dokümanı sicili.'],
            ['Çoklu alt yüklenici koordinasyonu', 'Sahaya giren onlarca alt yüklenicinin eş zamanlı belge, eğitim ve günlük yoklama kontrolü.'],
        ],
        'sss' => [
            ['Tersane ve enerji tesislerinde kapalı alan / sıcak iş izinleri nasıl yönetilir?', 'İş İzin Formu (PTW) modülünde ön kontrol maddeleri tamamlanmadan ve "uygun değil" kalemler giderilmeden izin onaylanmaz.'],
            ['Yabancı veya dış ziyaretçiler sahaya girmeden İSG brifingi alabilir mi?', 'Evet, Ziyaretçi QR Brifing & Bilgi Testi kilidi ile ziyaretçi testi geçmeden kapı geçiş onayı açılmaz.'],
        ],
    ],

    'gida-ve-uretim' => [
        'baslik' => 'Gıda ve Fabrika Üretimi',
        'seo_baslik' => 'Gıda & Üretim Fabrikaları İSG Yönetimi | Hijyen, Makine ve Revir',
        'aciklama' => 'Gıda üretim ve imalat fabrikalarında makine emniyeti, hijyen eğitimleri, portör/sağlık gözetimi, revir/ecza dolabı ve kimyasal SDS takibi.',
        'giris' => 'Kesintisiz vardiya düzeninde çalışan gıda ve imalat tesislerinde makine emniyeti, hijyen/sağlık gözetimi ve BRC/IFS/ISO 45001 denetimlerine hazır dijital kayıt sistemi kuruyoruz.',
        'bolgeler' => ['Türkiye Geneli', 'Bursa', 'İstanbul', 'İzmir', 'Konya', 'Gaziantep'],
        'tehlike' => 'Tehlikeli',
        'nace' => [
            ['10.89.90', 'Gıda maddelerinin imalatı ve paketlenmesi', 'Tehlikeli'],
            ['10.51.01', 'Süt ve süt ürünleri imalatı', 'Tehlikeli'],
            ['22.22.01', 'Plastik ambalaj malzemeleri imalatı', 'Tehlikeli'],
        ],
        'riskler' => [
            ['Makine ve konveyör güvenliği', 'Sıkışma noktaları, acil durdurma halatları, temizlik/bakım sırasında enerji kesimi.'],
            ['Kaygan zemin ve soğuk depolar', 'Su/yağ kaynaklı kayma riski, soğuk hava deposu termal KKD\'leri ve içeride kilitli kalma alarmları.'],
            ['Temizlik ve dezenfeksiyon kimyasalları', 'Asit/alkali karışım riski, güncel GBF/SDS envanteri, göz ve vücut duşları.'],
            ['Hijyen ve sağlık gözetimi', 'Gıda hijyeni eğitimleri, periyodik sağlık/portör kontrolleri, revir protokol defteri ve aşı takibi.'],
        ],
        'sss' => [
            ['Gıda işletmelerinde hijyen ve İSG eğitimleri tek panelden yönetilebilir mi?', 'Evet, Canlı QR Sınav ve Uzaktan Eğitim modüllerinde Temel İSG yanında 4 saatlik İşyeri ve Gıda Hijyeni sertifikaları üretilir.'],
            ['Revir, ecza dolabı SKT ve aşı takibi mevcut mu?', 'Evet, Sağlık Gözetimi ekranındaki Revir/Protokol Defteri, Ecza Dolabı SKT ve Aşı Takibi sekmelerinden tüm hekim/DSP süreçleri izlenir.'],
        ],
    ],

];
