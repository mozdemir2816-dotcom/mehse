<?php

/*
 * Acil Durum Planı — sektöre özgü metinler. Firma NACE koduna göre öneri
 * yapılır (ilk iki hane), uzman plan ekranında elle değiştirebilir (NACE ile
 * fiili iş her zaman örtüşmez). Yeni sektör = bu dosyaya bir girdi; metinler
 * kullanıcının gerçek referans planlarından alınır.
 *
 * onlemler           : 3. bölümde "Sektöre özgü önlemler" paragrafı
 * senaryo_aciklama   : EK-1'de ilgili senaryonun açıklamasının yerine geçer
 */
return [
    'genel' => [
        'ad' => 'Genel (ortak)',
        'nace' => [],
    ],

    // Kaynak: FOM MAKİNA ADEP (kullanıcının referans planı).
    'metal' => [
        'ad' => 'Metal işleme / makine imalatı',
        'nace' => ['24', '25', '28', '29', '30', '33'],
        'onlemler' => 'Makine koruyucuları ve emniyet kilitleri çalışır tutulur; temizlik ve bakımda enerji izolasyonu yapılır. Elektrik panoları önleri açık tutulur. Yağ / soğutma sıvısı döküntüleri uygun yöntemle temizlenir; kimyasallar etiketli, SDS’leri erişilebilir tutulur. Varsa kaynak / taşlama için sıcak çalışma tedbirleri alınır; gaz tüpleri sabitlenir. Talaş ve yanıcı atıklar düzenli uzaklaştırılır. Metal türüne göre yanıcı metal yangını riski değerlendirilir; uygun olmayan söndürücü kullanılmaz. Varsa kaldırma ekipmanı, forklift ve basınçlı kaplar kontrol edilir.',
        'senaryo_aciklama' => [
            'makine_arizasi' => 'Fiilen bulunan CNC, torna, matkap, freze, kesme, taşlama veya diğer metal işleme makinelerinde sıkışma / ezilmede uygulanır. Gerçek makine listesi Ek-5’e kaydedilir. Koruyucular ve kilitlemeler iptal edilmez; talaş temizliği ve bakımda enerji izolasyonu yapılır.',
        ],
    ],
];
