<?php

/*
 * Acil Durum Planı — sektöre özgü metinler. Firma NACE koduna göre öneri
 * yapılır (ilk iki hane), uzman plan ekranında elle değiştirebilir (NACE ile
 * fiili iş her zaman örtüşmez). Yeni sektör = bu dosyaya bir girdi; metinler
 * kullanıcının gerçek referans planlarından alınır.
 *
 * onlemler           : 3. bölümde "Sektöre özgü önlemler" paragrafı
 * senaryo_aciklama   : EK-1'de ilgili senaryonun açıklamasının yerine geçer
 * senaryo_ek         : EK-1'de ilgili senaryonun sonuna eklenen başlıklı madde blokları
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

    // Kaynak: Beyda Yapı ADEP (31.08.2026) — kullanıcının şantiye referans planı.
    'insaat' => [
        'ad' => 'İnşaat / şantiye',
        'nace' => ['41', '42', '43'],
        'onlemler' => 'Şantiyede acil durum halinde çalışmayı durdurma, tahliye ve toplanma noktasına yönlendirme amaçlı sesli / ışıklı alarm ve ikaz sistemi kurulur; zaman zaman haberli veya habersiz denenir. Şantiye çevresinde anız ve ot yangınlarına karşı ot mücadelesi düzenli sürdürülür. Makine ve donanım sarsıntıda devrilmemesi için sabitlenir. İnşaat alanına giren araçların yapı, direk ve iskelelere çarpmasını önlemek için tampon / bariyer yapılır ve sağlamlıkları düzenli kontrol edilir. Enerji nakil hatlarına güvenli yaklaşma mesafesi korunur; gerekli durumlarda dağıtım şirketine bilgi verilerek hattın yalıtılması veya uzaklaştırılması sağlanır. Yedek besleme sistemleri (jeneratör) bakım ve kontrollerle çalışır durumda tutulur. Yüksekte çalışma, iskele ve kaldırma işleri iş izni ile yapılır; ayrıntılı tedbirler EK-1 “Yüksekten Düşme” bölümündedir.',
        'senaryo_ek' => [
            'yuksekten_dusme' => [
                ['baslik' => 'YÜKSEKTE ÇALIŞMA İÇİN ÖNLEYİCİ TEDBİRLER', 'maddeler' => [
                    'Yüksekte çalışmanın 1. kuralı, zeminde yapılabilecek bütün işlerin yüksekte yapılmaması, zemine indirilerek zeminde yapılmasıdır.',
                    'Yüksekte çalışma yapılması gereken noktalarda toplu koruma önlemleri alınır (ara korkuluğu ve topuk levhası bulunan, yeterli dayanımda en az 1,2 m yüksekliğinde korkuluk, EN 1263 standardında güvenlik ağları vb.).',
                    'Toplu önlemlerin alınamadığı noktalarda kişisel koruyucu önlemler alınır: EN 361 paraşüt tipi emniyet kemeri, EN 354 emniyet halatı ve çift lanyard, EN 355 şok emici, EN 362 çift emniyet kancası ve karabinalar, EN 363 düşmeyi durdurucu sistemler.',
                    'TS EN 795 standardında yatay yaşam halatları, EN 353-1:2014 standardında dikey yaşam halatları kurulur. Yaşam hatlarına birden fazla personel bağlanmaz.',
                    'Yüksekte çalışacak personelin yüksekte çalışmaya uygun olduğunu gösterir sağlık raporu ve yüksekte çalışma eğitimi bulunur.',
                    'Yüksekte çalışmalar iş izin sistemi ile yapılır; onaylar alınmadan ve kişisel koruyucu donanım kullanılmadan çalışmaya başlanmaz.',
                    'Yüksekte yapılacak çalışmalar mümkün olan en az kişi ile yapılır.',
                    'Yüksekte asılı kalma durumu için özel eğitimli kurtarma ekipleri hazır bulunur; yüksekte çalışma ve kurtarma ekiplerinin eğitimleri yetkili ehil kişi tarafından verilir (işyeri dışından hizmet alınabilir).',
                    'Yüksekte çalışma platformları, iskeleler vb. ekipmanların ayakları zemine iyi basar; tekerlek frenleri kapalı tutulur, destek ayakları ayarlanabilir olur ve ekipman çalışma yüzeyine belirli aralıklarla çelik dübelle sabitlenir.',
                    'Platform ve iskelelerde çalışan personel kendisini çalışma yapılan zemine bağlar.',
                    'Balkon, asansör boşluğu, çatı kenarı gibi alanlarda kemer halatı kısa tutulur; kemerler duvarda oluşturulan bağlantı noktalarına bağlanır.',
                    'Çatılardaki ışıklık / perdelikler kırılgandır; bu alanlarda yatay yaşam hattı oluşturulur ve personel kendini bu hatta bağlar.',
                    'Halat boyu; çatı yüksekliği, personel boyu ve emniyet mesafesi dikkate alınarak düşme anında zemine 1 m kalacak şekilde ayarlanır.',
                    'Standarda uymayan, eski, deforme olmuş veya yırtılmış halatlar kullanılmaz ve kullanımdan kaldırılır.',
                    'Manlift tarzı insan kaldırma ekipmanlarında paraşüt tipi emniyet kemeri kullanılır; halat kısa tutulup sepet tabanına bağlanır. Sepet kapısı içeri açılır, korkuluk yüksekliği 120 cm olur; personelin çalışma sırasında sepetten çıkması önlenir ve sepet kaldırma ekipmanına sabitlenir.',
                ]],
                ['baslik' => 'SINIRLAYICI TEDBİRLER', 'maddeler' => [
                    'Yangın söndürme, ilk yardım, kurtarma ve koruma ekipleri oluşturulur ve gerekli eğitimleri verilir / aldırılır; ekip üyeleri ve iletişim bilgileri şantiyede görünür şekilde asılır.',
                    'Yılda en az bir defa acil durum tahliye ve kurtarma tatbikatı yapılır.',
                    'Yüksekte çalışan personelden en az birinin ilk yardımcı belgesi bulunur.',
                    'Yüksekte çalışma esnasında kurtarma ekipleri hazır bulundurulur.',
                    'Öncelikle korkuluk, güvenlik ağı gibi toplu koruma yöntemleri, son olarak paraşüt tipi emniyet kemeri kullanılır.',
                    'Personelin emniyet kemerine bağlı olarak askıda kalmasının önüne geçilir.',
                ]],
                ['baslik' => 'İSKELELERİN KURULMASI, KULLANIMI VE KONTROLÜ', 'maddeler' => [
                    'İskelelerin tasarımı, kurulması, değiştirilmesi ve sökülmesi yetkili kişilerin gözetiminde yapılır; bütün iskeleler güvenli şekilde bağlanır veya desteklenir, hazır iskeleler üreticinin önerdiği şekilde kurulur.',
                    'Platform zeminini oluşturan malzemeler yerinden kaldırılmaz, korkuluklar ve bağlantılar yerinden oynatılmaz; değişiklikler yalnız yetkili kişilerce yapılır.',
                    'Tam kurulmamış platformlarda çalışılmaz; iskeleler kapasitesinin üzerinde yüklenmez; platforma ulaşım için uygun merdiven veya basamak bulunur.',
                    'İskeleler yetkili kişi tarafından 6 ayda bir kontrol edilir ve uygunluk belgesi düzenlenir; ayrıca ilk kullanımdan önce, önemli değişiklikte, sağlamlığı etkileyen olaydan sonra ve haftada bir yetkili kişi tarafından kontrol edilir.',
                    'Taşeron gibi işveren sorumluluğu taşıyanlar, başka alandan temin edilen iskelenin güvenli olduğundan emin olur.',
                ]],
                ['baslik' => 'KORKULUK, TEHLİKELİ BOŞLUKLAR VE PLATFORMLAR', 'maddeler' => [
                    'Düşmeler yeterli yükseklikte sağlam korkuluklarla önlenir; korkulukta trabzan, ara korkuluk ve etek tahtası bulunur.',
                    'Basamakları yapılmamış betonarme merdivenlere geçici ahşap basamaklar, merdiven boşluğu ve döşeme kenarlarına korkuluk yapılır; mümkün değilse döşeme kenarına korkuluklu iskele kurulur.',
                    'Tavan ve döşemelerdeki boşluk ve deliklere, asansör boşluklarına korkuluk yapılır veya üstleri geçici olarak uygun şekilde kapatılır / güvenlik ağı ile örtülür.',
                    'Hareketli platformlarda paraşüt tipi emniyet kemeri kullanılır ve platform zeminine bağlanır; tekerlekler kilitlenir, destek ayakları açılır; korkuluk yüksekliği en az 125 cm olur; platformlar yılda bir yetkili kişilerce kontrol edilir.',
                    'Rüzgâr hızı 45 km/saate ulaştığında yüksekte çalışma yapılmaz.',
                ]],
                ['baslik' => 'ÇATI ÇALIŞMALARI VE YAŞAM HATLARI', 'maddeler' => [
                    'Düz çatılarda çatı kenarından, çalışma alanı kenarından, boşluk ve kırılgan ışıklıklardan; eğimli çatılarda saçaktan, kayarak ve kenar korkuluklarından düşme riski değerlendirilir.',
                    'Kırılgan çatılar güvenli çalışma alanı değildir; toplu koruma (korkuluk, güvenlik ağı) veya kişisel koruyucu önlem alınmadan çalışılmaz.',
                    'Kemer yatay yaşam hattına bağlanır; halat uzunluğu, şok emicinin açılma mesafesi de hesaba katılarak düşmede zemine 1 m kalacak şekilde ayarlanır.',
                    'Yatay yaşam hatları düşme riskinin başladığı kenarlar boyunca, dikey yaşam hatları (EN 353-1:2014) merdiven gibi dikey güzergâhlarda uzman montaj ekibi tarafından kurulur.',
                ]],
            ],
        ],
    ],
];
