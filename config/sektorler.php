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
    ],

];
