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

];
