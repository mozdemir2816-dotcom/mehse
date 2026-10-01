<?php

/*
| Yıllık plan STANDART şablonu (tüm firmalar) — kullanıcının VİZYON firması için hazırladığı
| "VIZYON_Egitim_Plani_Yazdirma.xlsx" ve "vizyon 2026 yıllık çalışma planı.xlsx"
| dosyalarından OTOMATİK çıkarıldı; metinler BİREBİR (düzeltmeyin, kaynak Excel'i
| güncelleyip yeniden çıkarın). Vizyon'da P'ler Eylül–Aralık'taydı (sözleşme Eylül'de
| başladı); dört ayın hepsinde P olan madde "her ay" (0–11) sayıldı — firma sözleşme
| başlangıcından önceki aylar YillikPlan::maddeAylarIle ile zaten boş kalır.
| Kullanım: App\Support\YillikPlanSablonu — tüm firmalarda varsayılan; eğitimlerin
| 4. bölümü (ise_ozgu) yalnız inşaat firmalarında yüklenir.
*/

return array (
  'egitimler' => 
  array (
    0 => 
    array (
      'konu' => 'Çalışma mevzuatı ile ilgili bilgiler',
      'kategori' => 'genel',
      'hedef' => 'Çalışma mevzuatı ile ilgili bilgileri hakkında çalışanları bilgilendirmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    1 => 
    array (
      'konu' => 'Çalışanların yasal hak ve sorumlulukları',
      'kategori' => 'genel',
      'hedef' => 'Çalışanların yasal hak ve sorumlulukları hakkında bilgi sahibi olmasını sağlamak',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    2 => 
    array (
      'konu' => 'İşyeri temizliği ve düzeni',
      'kategori' => 'genel',
      'hedef' => 'Çalışanların İşyeri temizliği ve düzenine dikkat etmesini sağlamak',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    3 => 
    array (
      'konu' => 'İş kazası ve meslek hastalığından doğan hukuki sonuçlar',
      'kategori' => 'genel',
      'hedef' => 'Çalışanları iş kazası ve meslek hastalığından doğan hukuki sonuçlar hakkında bilgilendirmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    4 => 
    array (
      'konu' => 'Meslek hastalıklarının sebepleri',
      'kategori' => 'saglik',
      'hedef' => 'Meslek hastalıklarının sebeplerini çalışanlara aktarmak',
      'egitici' => 'İŞYERİ HEKİMİ',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    5 => 
    array (
      'konu' => 'Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması',
      'kategori' => 'saglik',
      'hedef' => 'Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması hakkında bilgilendirme yapmak',
      'egitici' => 'İŞYERİ HEKİMİ',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    6 => 
    array (
      'konu' => 'Biyolojik ve psikososyal risk etmenleri',
      'kategori' => 'saglik',
      'hedef' => 'Çalışanları biyolojik ve psikososyal risk etmenleri hakkında bilgilendirme yapmak',
      'egitici' => 'İŞYERİ HEKİMİ',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    7 => 
    array (
      'konu' => 'İlkyardım',
      'kategori' => 'saglik',
      'hedef' => 'İlkyardım uygulamalarını öğrenmek',
      'egitici' => 'İŞYERİ HEKİMİ',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    8 => 
    array (
      'konu' => 'Tütün ürünlerinin zararları ve pasif etkilenim,',
      'kategori' => 'saglik',
      'hedef' => 'Tütünün zararları konusunda çalışanları bilgilendirmek',
      'egitici' => 'İŞYERİ HEKİMİ',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    9 => 
    array (
      'konu' => 'Kimyasal, fiziksel ve ergonomik risk etmenleri',
      'kategori' => 'teknik',
      'hedef' => 'Kimyasal, fiziksel ve ergonomik risk etmenleri öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    10 => 
    array (
      'konu' => 'Elle kaldırma ve taşıma',
      'kategori' => 'teknik',
      'hedef' => 'Elle kaldırma ve taşıma işlerinde doğru kullanımı öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    11 => 
    array (
      'konu' => 'Parlama, patlama, yangın ve yangından korunma',
      'kategori' => 'teknik',
      'hedef' => 'Parlama, patlama, yangın ve yangından korunma önlemlerini öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    12 => 
    array (
      'konu' => 'İş ekipmanlarının güvenli kullanımı',
      'kategori' => 'teknik',
      'hedef' => 'İş ekipmanlarının güvenli kullanımı hakkında bilgi sahibi olmak',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    13 => 
    array (
      'konu' => 'Ekranlı araçlarla çalışma',
      'kategori' => 'teknik',
      'hedef' => 'Ekranlı araçlarla çalışmayı öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    14 => 
    array (
      'konu' => 'Elektrik, tehlikeleri, riskleri ve önlemleri',
      'kategori' => 'teknik',
      'hedef' => 'Elektrik, tehlikeleri, riskleri ve önlemleri öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    15 => 
    array (
      'konu' => 'İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanması',
      'kategori' => 'teknik',
      'hedef' => 'İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanmasını öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    16 => 
    array (
      'konu' => 'Güvenlik ve sağlık işaretleri',
      'kategori' => 'teknik',
      'hedef' => 'Güvenlik ve sağlık işaretlerini tanımak,öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    17 => 
    array (
      'konu' => 'Kişisel koruyucu donanım kullanımı',
      'kategori' => 'teknik',
      'hedef' => 'Kişisel koruyucu donanımın nasıl ve hangi durumlarda kullanılacağını öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    18 => 
    array (
      'konu' => 'İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürü',
      'kategori' => 'teknik',
      'hedef' => 'İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürünü öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    19 => 
    array (
      'konu' => 'Tahliye ve kurtarma',
      'kategori' => 'teknik',
      'hedef' => 'Tahliye ve kurtarmanın nasıl ve ne şekilde olacağını öğrenmek',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    20 => 
    array (
      'konu' => 'Yüksekte Çalışma',
      'kategori' => 'ise_ozgu',
      'hedef' => 'Kenar ve döşeme boşlukları, iskele, merdiven ve yükseltilebilir platformlar; toplu koruma, uygun bağlantı ve kurtarma düzeni. Güvenli erişimi ve koruma kontrolünü göstermek.',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    21 => 
    array (
      'konu' => 'Kazı ve zemin işleri',
      'kategori' => 'ise_ozgu',
      'hedef' => 'Göçük, su girişi, yeraltı hatları, kazıya erişim ve kenar yükleri; iksa/şev ve saha sınırları. Fore kazık ve zemin iyileştirme varsa ekipman çevresini değerlendirmek.',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    22 => 
    array (
      'konu' => 'Kaldırma ve saha trafiği',
      'kategori' => 'ise_ozgu',
      'hedef' => 'Vinç, sapan, işaretçi, asılı yük alanı; ekskavatör, loder, kamyon ve teleskopik yükleyici. Yaya ayrımı, kör nokta ve manevra iletişimini göstermek.',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    23 => 
    array (
      'konu' => 'Elektrik ve özel işler',
      'kategori' => 'ise_ozgu',
      'hedef' => 'Geçici panolar/kablolar, enerji izolasyonu; kaynak ve sıcak iş izinleri; kapalı alan varsa giriş, ölçüm, gözcü ve kurtarma düzeni. Yetkisiz müdahaleyi önlemek.',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    24 => 
    array (
      'konu' => 'Sahaya özgü acil durum',
      'kategori' => 'ise_ozgu',
      'hedef' => 'Kazı göçüğü, yüksekten düşme ve yangın senaryoları; toplanma yeri ve haberleşme. Silika/toz, gürültü, çimento teması ve kamp alanı risklerini mevcut dokümanlarla işlemek.',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    25 => 
    array (
      'konu' => 'Kalıp, demir ve beton',
      'kategori' => 'ise_ozgu',
      'hedef' => 'Kalıp stabilitesi, demir uçları, pompa hortumu; daire testere, demir kesme/bükme ve etriye makineleri. Koruyucuların ve çalışma bölgesinin kontrolünü uygulamak.',
      'egitici' => 'İŞ GÜVENLİĞİ UZMANI',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    26 => 
    array (
      'konu' => 'Söndürme EkibiEğitimi (4 saat)',
      'kategori' => 'diger',
      'hedef' => 'Yangın sündürme konularında bilgi sahibi olmak',
      'egitici' => 'Yetkili Eğitmen',
      'hafta' => 3,
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
      'aciklama' => 'Söndürme ekibinde görevlendirilen çalışanlara yönelik uygulanacaktır. Yangın türleri, uygun söndürme ekipmanının seçimi ve kullanımı, alarm verme, güvenli müdahale sınırları ve ekip koordinasyonu konuları teorik ve uygulamalı olarak işlenecektir.',
    ),
    27 => 
    array (
      'konu' => 'Kurtarma Ekibi Eğitimi (2 saat)',
      'kategori' => 'diger',
      'hedef' => 'Acil durumlarda kurtarma konularında bilgi sahibi olmak',
      'egitici' => 'Yetkili Eğitmen',
      'hafta' => 3,
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
      'aciklama' => 'Kurtarma ekibinde görevlendirilen çalışanların; acil durumlarda kendi güvenliklerini koruyarak, eğitim ve donanımlarının sınırları içerisinde güvenli arama, tahliye ve kurtarma yöntemlerini uygulayabilmesini ve diğer ekiplerle koordineli hareket etmesini sağlamak',
    ),
    28 => 
    array (
      'konu' => 'İlkyardımcı Ekibi Eğitimi (16 saat)',
      'kategori' => 'diger',
      'hedef' => 'İlkyardım konularında bilgi sahibi olmak',
      'egitici' => 'Yetkili Firma Eğitmeni',
      'hafta' => 3,
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
      'aciklama' => 'İlkyardımcı olarak görevlendirilecek çalışanların; olay yerini güvenli şekilde değerlendirmesini, acil yardım çağrısını yapmasını ve profesyonel sağlık ekibi gelinceye kadar gerekli ilkyardım uygulamalarını doğru şekilde gerçekleştirebilecek bilgi ve beceriyi kazanmasını sağlamak',
    ),
    29 => 
    array (
      'konu' => 'Koruma ekibi',
      'kategori' => 'diger',
      'hedef' => 'Acil durumlarda koruma konularında bilgi sahibi olmak',
      'egitici' => 'Yetkili Firma Eğitmeni',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
      ),
      'aciklama' => 'Koruma ekibinde görevlendirilen çalışanların; acil durum alanına yetkisiz girişleri önlemesini, kargaşayı engellemesini, tahliye ve müdahale yollarının açık tutulmasına yardımcı olmasını ve ekiplerle koordineli çalışmasını sağlamak.',
    ),
    30 => 
    array (
      'konu' => 'Yüksekte Çalışma',
      'kategori' => 'diger',
      'hedef' => 'Yüksekte çalışacak personelin; düşme tehlikelerini tanıması, uygun kişisel düşmeye karşı koruyucu donanımı seçmesi, kullanım öncesi kontrollerini yapması, emniyet kemerini doğru kuşanması, uygun ankraj ve yaşam hattına güvenli bağlantı kurması ve güvenli çalışma yöntemlerini uygulamalı olarak öğrenmesi; kazanılan becerilerin uygulamalı değerlendirme ile doğrulanması.',
      'egitici' => 'Yetkili Firma Eğitmeni',
      'hafta' => 3,
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
      'aciklama' => 'Yüksekte çalışma yapacak personelin; düşme tehlikelerini tanımasını, güvenli erişim ve çalışma yöntemlerini uygulamasını, düşmeye karşı koruyucu donanımların kullanım öncesi kontrollerini yapmasını ve uygun ankraj ile yaşam hattına güvenli bağlantı kurma becerisi kazanmasını sağlamak.',
    ),
    31 => 
    array (
      'konu' => 'Yeni iş başı eğitimi',
      'kategori' => 'diger',
      'hedef' => 'Yapacağı işe, kullanacağı ekipmana ve çalışma ortamına özgü tehlike ve riskleri tanımasını, korunma tedbirlerini öğrenmesini ve güvenli çalışma yöntemlerini uygulayabilecek bilgi ve beceri kazanmasını sağlamak.',
      'egitici' => 'Formen, Ustabaşı',
      'hafta' => 2,
      'varsayilan_aylar' => 
      array (
      ),
      'aciklama' => 'Yeni işe başlayan çalışanın; yapacağı işe, kullanacağı ekipmana ve çalışma ortamına özgü tehlike ve riskleri tanımasını, korunma tedbirlerini öğrenmesini ve güvenli çalışma yöntemlerini uygulayabilmesini sağlayarak iş kazası ve meslek hastalıklarının önlenmesine katkıda bulunmak.',
    ),
    32 => 
    array (
      'konu' => 'Mesleki Yeterlilik Gerektiren Eğitimler (Operatörler Katılım Sağlayacaktır. Forklift, Ekstrüzyon Makinası)',
      'kategori' => 'diger',
      'hedef' => 'Kullanılan İş Ekipmanlarının Kullanımı Hakkında Yetkili Kuruluşutan Eğitim Alınması',
      'egitici' => 'Yetkili Firma Eğitmeni',
      'hafta' => 3,
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
      'aciklama' => 'İş makinesi ve ilgili diğer ekipmanlarda görev yapacak operatörlerin; işin gerektirdiği mesleki bilgi ve uygulama becerisini kazanmasını, ekipmanı güvenli kullanmasını ve görevi için gerekli eğitim veya yeterlilik belgesi şartlarını karşılamasını sağlamak.',
    ),
  ),
  'egitim_bilgileri' => 
  array (
    'katilanlar' => 'TÜM PERSONEL',
    'toplam_sure' => '16 SAAT',
    'bolum_sureleri' => 
    array (
      'genel' => 'GENEL KONULAR: 4SAAT/ 1 SAAT',
      'saglik' => 'SAĞLIK KONULARI: 4 SAAT/ 1 SAAT',
      'teknik' => 'TEKNİK KONULAR: 4 SAAT / 1 SAAT',
      'ise_ozgu' => 'İŞE ÖZGÜ RİSKLER: 4 SAAT/4 SAAT',
    ),
    'bolum_aciklamalari' => 
    array (
      'genel' => 'İlk defa temel İSG eğitimi alacak çalışanlara 4 ders saati, tekrar eğitimi alacak çalışanlara 1 ders saati olarak uygulanacaktır. İlgili eğitim grubundaki tüm çalışanlar katılacaktır.',
      'saglik' => 'İlk defa temel İSG eğitimi alacak çalışanlara 4 ders saati, tekrar eğitimi alacak çalışanlara 2 ders saati olarak uygulanacaktır. İlgili eğitim grubundaki tüm çalışanlar katılacak; içerik yapılan işten kaynaklanan sağlık riskleri ve korunma yöntemlerine göre düzenlenecektir.',
      'teknik' => 'İlk defa temel İSG eğitimi alacak çalışanlara 4 ders saati, tekrar eğitimi alacak çalışanlara 1 ders saati olarak uygulanacaktır. İlgili eğitim grubundaki tüm çalışanlar katılacak; konular kullanılan iş ekipmanları ve çalışma ortamındaki risklerle ilişkilendirilerek anlatılacaktır.',
      'ise_ozgu' => 'İlk defa temel İSG eğitimi alacak çalışanlara ve tekrar eğitimi alacak çalışanlara ayrı ayrı 4 ders saati olarak yüz yüze uygulanacaktır. Çalışanlar; görevleri, kullandıkları ekipmanlar ve maruz kaldıkları risklere göre ilgili eğitim gruplarına katılacaktır. İçerik, işyerinin risk değerlendirmesi, acil durum planı ve varsa patlamadan korunma dokümanı esas alınarak hazırlanacaktır.',
    ),
  ),
  'faaliyetler' => 
  array (
    0 => 
    array (
      'ana_konu' => 'PLANLAMA VE DOKÜMANTASYON',
      'faaliyet' => 'Yıllık çalışma planının hazırlanması / gözden geçirilmesi',
      'frekans' => 'Yılda 1',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu; İş Sağlığı ve Güvenliği Hizmetleri Yönetmeliği; İş Güvenliği Uzmanları Yönetmeliği; İşyeri Hekimi Yönetmeliği',
      'kayit_notu' => 'İSG yönetim sistemi',
      'varsayilan_aylar' => 
      array (
        0 => 11,
      ),
    ),
    1 => 
    array (
      'ana_konu' => 'PLANLAMA VE DOKÜMANTASYON',
      'faaliyet' => 'Yıllık eğitim planının hazırlanması / gözden geçirilmesi',
      'frekans' => 'Yılda 1',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => 'Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik; İşyeri Hekimi Yönetmeliği',
      'kayit_notu' => 'Eğitim planlaması',
      'varsayilan_aylar' => 
      array (
        0 => 11,
      ),
    ),
    2 => 
    array (
      'ana_konu' => 'PLANLAMA VE DOKÜMANTASYON',
      'faaliyet' => 'Yıllık değerlendirme raporunun hazırlanması',
      'frekans' => 'Yılda 1',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => 'İş Sağlığı ve Güvenliği Hizmetleri Yönetmeliği; İşyeri Hekimi Yönetmeliği',
      'kayit_notu' => 'Yıl sonu değerlendirme',
      'varsayilan_aylar' => 
      array (
        0 => 11,
      ),
    ),
    3 => 
    array (
      'ana_konu' => 'RİSK YÖNETİMİ',
      'faaliyet' => 'Risk değerlendirmesinin gözden geçirilmesi; değişiklik, kaza veya ihtiyaç halinde revizyon',
      'frekans' => 'Sürekli / Gerektiğinde',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi / RD Ekibi',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu md.10; İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği',
      'kayit_notu' => 'Risk değerlendirmesi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    4 => 
    array (
      'ana_konu' => 'RİSK YÖNETİMİ',
      'faaliyet' => 'Saha gözetimi, tespit ve önerilerin kayıt altına alınması',
      'frekans' => 'Aylık / Sürekli',
      'sorumlu' => 'İGU / İşyeri Hekimi / Bölüm Sorumluları',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu; İş Güvenliği Uzmanlarının Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik',
      'kayit_notu' => 'Saha gözetimi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    5 => 
    array (
      'ana_konu' => 'RİSK YÖNETİMİ',
      'faaliyet' => 'Tespit edilen uygunsuzluklar için DÖF açılması ve kapanış takibi',
      'frekans' => 'Sürekli',
      'sorumlu' => 'İGU / İşveren / Bölüm Sorumluları',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu; Risk Değerlendirmesi Yönetmeliği',
      'kayit_notu' => 'DÖF takibi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    6 => 
    array (
      'ana_konu' => 'EĞİTİM',
      'faaliyet' => 'Yeni başlayan ve eğitimi yenilenecek çalışanların temel İSG eğitimlerinin planlanması',
      'frekans' => 'Gerektiğinde',
      'sorumlu' => 'İGU / İşyeri Hekimi / Yetkili Eğitici',
      'yasal_gereklilik' => 'İSG eğitimleri',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    7 => 
    array (
      'ana_konu' => 'EĞİTİM',
      'faaliyet' => 'İşe ve riske özgü toolbox / kısa saha eğitimleri',
      'frekans' => 'Aylık',
      'sorumlu' => 'İGU / Bölüm Sorumluları',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu md.17; Çalışanların İSG Eğitimleri Yönetmeliği',
      'kayit_notu' => 'Saha eğitimi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    8 => 
    array (
      'ana_konu' => 'ACİL DURUM',
      'faaliyet' => 'Acil durum planının gözden geçirilmesi ve gerektiğinde güncellenmesi',
      'frekans' => 'Yılda 1 / Gerektiğinde',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
      'kayit_notu' => 'Acil durum',
      'varsayilan_aylar' => 
      array (
        0 => 9,
      ),
    ),
    9 => 
    array (
      'ana_konu' => 'ACİL DURUM',
      'faaliyet' => 'Acil durum ekipleri ve görevlendirmelerin kontrolü / güncellenmesi',
      'frekans' => 'Yılda 1 / Değişiklikte',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
      'kayit_notu' => 'Görevlendirme',
      'varsayilan_aylar' => 
      array (
        0 => 9,
      ),
    ),
    10 => 
    array (
      'ana_konu' => 'ACİL DURUM',
      'faaliyet' => 'Yangın, tahliye ve kurtarma tatbikatının planlanması ve uygulanması',
      'frekans' => 'Yılda en az 1',
      'sorumlu' => 'İşveren / İGU / Acil Durum Ekipleri',
      'yasal_gereklilik' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
      'kayit_notu' => 'Tatbikat',
      'varsayilan_aylar' => 
      array (
        0 => 8,
      ),
    ),
    11 => 
    array (
      'ana_konu' => 'ACİL DURUM',
      'faaliyet' => 'Acil çıkış yolları, kapıları, yönlendirme levhaları ve acil aydınlatmanın kontrolü',
      'frekans' => 'Aylık',
      'sorumlu' => 'İşveren / İGU / Teknik Birim',
      'yasal_gereklilik' => 'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik; Binaların Yangından Korunması Hakkında Yönetmelik',
      'kayit_notu' => 'Acil çıkış',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    12 => 
    array (
      'ana_konu' => 'YANGIN GÜVENLİĞİ',
      'faaliyet' => 'Yangın söndürücüler, hortumlar, hidrant/sprinkler ve yangın ekipmanlarının kontrol takibi',
      'frekans' => 'Periyodik / Üretici ve mevzuata göre',
      'sorumlu' => 'İşveren / Teknik Birim / Yetkili Firma',
      'yasal_gereklilik' => 'Binaların Yangından Korunması Hakkında Yönetmelik; İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
      'kayit_notu' => 'Yangın ekipmanı',
      'varsayilan_aylar' => 
      array (
        0 => 9,
      ),
    ),
    13 => 
    array (
      'ana_konu' => 'YANGIN GÜVENLİĞİ',
      'faaliyet' => 'Yangın algılama, ihbar, duman/ısı sensörü ve alarm sistemlerinin test/kontrol takibi',
      'frekans' => 'Periyodik',
      'sorumlu' => 'İşveren / Teknik Birim / Yetkili Firma',
      'yasal_gereklilik' => 'Binaların Yangından Korunması Hakkında Yönetmelik',
      'kayit_notu' => 'Yangın algılama',
      'varsayilan_aylar' => 
      array (
        0 => 9,
      ),
    ),
    14 => 
    array (
      'ana_konu' => 'İŞ EKİPMANLARI',
      'faaliyet' => 'Kaldırma ve iletme ekipmanlarının periyodik kontrol takibi',
      'frekans' => 'Mevzuat / ekipmana göre',
      'sorumlu' => 'İşveren / Teknik Birim / Yetkili Kişi',
      'yasal_gereklilik' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği',
      'kayit_notu' => 'Vinç, forklift, transpalet vb.',
      'varsayilan_aylar' => 
      array (
        0 => 9,
      ),
    ),
    15 => 
    array (
      'ana_konu' => 'İŞ EKİPMANLARI',
      'faaliyet' => 'Basınçlı kap ve tesisatların periyodik kontrol takibi',
      'frekans' => 'Mevzuat / ekipmana göre',
      'sorumlu' => 'İşveren / Teknik Birim / Yetkili Kişi',
      'yasal_gereklilik' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği',
      'kayit_notu' => 'Kompresör, kazan, hidrofor vb.',
      'varsayilan_aylar' => 
      array (
        0 => 9,
      ),
    ),
    16 => 
    array (
      'ana_konu' => 'ELEKTRİK',
      'faaliyet' => 'Elektrik tesisatı, panolar, prizler, kablolar ve kaçak akım koruma düzenlerinin saha kontrolü',
      'frekans' => '3 Aylık / Gerektiğinde',
      'sorumlu' => 'İşveren / Teknik Birim / İGU',
      'yasal_gereklilik' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; Elektrik Tesislerinde Topraklamalar Yönetmeliği',
      'kayit_notu' => 'Elektrik güvenliği',
      'varsayilan_aylar' => 
      array (
        0 => 9,
        1 => 10,
        2 => 11,
      ),
    ),
    17 => 
    array (
      'ana_konu' => 'ELEKTRİK',
      'faaliyet' => 'Elektrik tesisatı, topraklama ve yıldırımdan korunma tesisatı periyodik kontrol takibi',
      'frekans' => 'Mevzuata göre',
      'sorumlu' => 'İşveren / Yetkili Kişi',
      'yasal_gereklilik' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği; Elektrik Tesislerinde Topraklamalar Yönetmeliği',
      'kayit_notu' => 'Periyodik kontrol',
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
    ),
    18 => 
    array (
      'ana_konu' => 'MAKİNE GÜVENLİĞİ',
      'faaliyet' => 'Makine koruyucuları, acil stoplar, switchler, kullanım talimatları ve güvenli kullanım kontrolleri',
      'frekans' => 'Aylık',
      'sorumlu' => 'İGU / Teknik Birim / Bölüm Sorumluları',
      'yasal_gereklilik' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği',
      'kayit_notu' => 'Makine güvenliği',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    19 => 
    array (
      'ana_konu' => 'KKD',
      'faaliyet' => 'KKD ihtiyaçlarının belirlenmesi, uygun KKD temini ve zimmetle teslimi',
      'frekans' => 'Sürekli',
      'sorumlu' => 'İşveren / İGU / Bölüm Sorumluları',
      'yasal_gereklilik' => 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik',
      'kayit_notu' => 'KKD',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    20 => 
    array (
      'ana_konu' => 'KKD',
      'faaliyet' => 'KKD kullanımının saha denetimi',
      'frekans' => 'Aylık / Sürekli',
      'sorumlu' => 'İGU / Bölüm Sorumluları',
      'yasal_gereklilik' => 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik',
      'kayit_notu' => 'KKD denetimi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    21 => 
    array (
      'ana_konu' => 'İŞYERİ DÜZENİ',
      'faaliyet' => 'Sağlık ve güvenlik işaretleri, korkuluklar, tehlikeli boşluklar, yaya yolları ve genel tertip-düzen kontrolü',
      'frekans' => 'Aylık',
      'sorumlu' => 'İGU / Bölüm Sorumluları',
      'yasal_gereklilik' => 'Sağlık ve Güvenlik İşaretleri Yönetmeliği; İşyeri Bina ve Eklentileri Yönetmeliği',
      'kayit_notu' => 'İşyeri düzeni',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    22 => 
    array (
      'ana_konu' => 'KİMYASAL',
      'faaliyet' => 'Kimyasal envanter, etiketler ve Güvenlik Bilgi Formlarının kontrolü',
      'frekans' => '6 Aylık / Değişiklikte',
      'sorumlu' => 'İGU / İşyeri Hekimi / Bölüm Sorumluları',
      'yasal_gereklilik' => 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik',
      'kayit_notu' => 'Kimyasal güvenlik',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    23 => 
    array (
      'ana_konu' => 'PATLAYICI ORTAM',
      'faaliyet' => 'Patlamadan korunma dokümanı ve patlayıcı ortam risklerinin gözden geçirilmesi (uygulanıyorsa)',
      'frekans' => 'Değişiklikte / Gerektiğinde',
      'sorumlu' => 'İşveren / İGU / Yetkili Uzman',
      'yasal_gereklilik' => 'Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik',
      'kayit_notu' => 'ATEX',
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
    ),
    24 => 
    array (
      'ana_konu' => 'ORTAM ÖLÇÜMLERİ',
      'faaliyet' => 'Gürültü, toz, titreşim, aydınlatma, termal konfor, gaz/VOC vb. ölçüm ihtiyacının değerlendirilmesi ve ölçümlerin planlanması',
      'frekans' => 'Risk değerlendirmesine göre',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => 'İş Hijyeni Ölçüm, Test ve Analizleri Hakkında Yönetmelik; ilgili maruziyet yönetmelikleri',
      'kayit_notu' => 'İş hijyeni',
      'varsayilan_aylar' => 
      array (
        0 => 11,
      ),
    ),
    25 => 
    array (
      'ana_konu' => 'SAĞLIK GÖZETİMİ',
      'faaliyet' => 'İşe giriş sağlık muayeneleri',
      'frekans' => 'İşe girişte',
      'sorumlu' => 'İşveren / İşyeri Hekimi',
      'yasal_gereklilik' => 'Sağlık gözetimi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    26 => 
    array (
      'ana_konu' => 'SAĞLIK GÖZETİMİ',
      'faaliyet' => 'Periyodik sağlık muayeneleri ve işe/risklere özgü tetkiklerin planlanması',
      'frekans' => 'Risk ve mevzuata göre',
      'sorumlu' => 'İşyeri Hekimi / İşveren',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu md.15; İşyeri Hekimi ve Diğer Sağlık Personeli Yönetmeliği',
      'kayit_notu' => 'Sağlık gözetimi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    27 => 
    array (
      'ana_konu' => 'SAĞLIK GÖZETİMİ',
      'faaliyet' => 'İlk yardım dolapları ve sağlık ekipmanlarının kontrolü',
      'frekans' => 'Aylık / Periyodik',
      'sorumlu' => 'İşyeri Hekimi / Görevlendirilen Personel',
      'yasal_gereklilik' => 'İlk yardım',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    28 => 
    array (
      'ana_konu' => 'HİJYEN',
      'faaliyet' => 'İçme-kullanma suyu, yemekhane, tuvalet, soyunma alanları ve genel hijyen kontrolleri',
      'frekans' => 'Periyodik',
      'sorumlu' => 'İşyeri Hekimi / İşveren / İlgili Birim',
      'yasal_gereklilik' => 'İşyeri Bina ve Eklentileri Yönetmeliği; İşyeri Hekimi ve Diğer Sağlık Personeli Yönetmeliği',
      'kayit_notu' => 'Hijyen',
      'varsayilan_aylar' => 
      array (
        0 => 10,
      ),
    ),
    29 => 
    array (
      'ana_konu' => 'KAZA / RAMAK KALA',
      'faaliyet' => 'İş kazaları, ramak kala olayları ve ilk yardım vakalarının kayıt, inceleme ve kök neden takibi',
      'frekans' => 'Olay oldukça',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi',
      'yasal_gereklilik' => 'İlkyardım Yönetmeliği; İşyeri Hekimi ve Diğer Sağlık Personeli Yönetmeliği',
      'kayit_notu' => 'Olay yönetimi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    30 => 
    array (
      'ana_konu' => 'ÇALIŞAN KATILIMI',
      'faaliyet' => 'Çalışan temsilcisi ve destek elemanlarının görevlendirme/atamalarının kontrolü',
      'frekans' => 'Yılda 1 / Değişiklikte',
      'sorumlu' => 'İşveren / İGU',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu md.20',
      'kayit_notu' => 'Görevlendirme',
      'varsayilan_aylar' => 
      array (
        0 => 8,
      ),
    ),
    31 => 
    array (
      'ana_konu' => 'İSG KURULU',
      'faaliyet' => 'İSG Kurulu üyeliklerinin ve toplantı planının kontrolü; toplantıların mevzuat periyodunda yapılması (uygulanıyorsa)',
      'frekans' => 'Mevzuat periyodunda',
      'sorumlu' => 'İşveren / İGU / İşyeri Hekimi / Kurul',
      'yasal_gereklilik' => 'İSG Kurulu',
      'varsayilan_aylar' => 
      array (
        0 => 8,
      ),
    ),
    32 => 
    array (
      'ana_konu' => 'ALT İŞVEREN',
      'faaliyet' => 'Alt işveren / taşeron çalışmalarının İSG evrak, iş izinleri ve saha uygulamaları yönünden kontrolü',
      'frekans' => 'Her çalışma öncesi / Sürekli',
      'sorumlu' => 'İşveren / İGU / Saha Sorumluları',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu; İş Sağlığı ve Güvenliği Hizmetleri Yönetmeliği',
      'kayit_notu' => 'İş izinleri',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    33 => 
    array (
      'ana_konu' => 'BELGELER',
      'faaliyet' => 'Operatörlük, mesleki eğitim, ustalık ve diğer zorunlu yeterlilik belgelerinin kontrolü',
      'frekans' => 'İşe girişte / Sürekli',
      'sorumlu' => 'İşveren / İGU / İK',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu; Mesleki Eğitim Yönetmeliği; İş Ekipmanları Yönetmeliği',
      'kayit_notu' => 'Yetkinlik',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    34 => 
    array (
      'ana_konu' => 'ARAÇ GÜVENLİĞİ',
      'faaliyet' => 'Şirket araçları, iş makineleri ve taşıma araçlarının bakım, kontrol ve sürücü yetkinliklerinin takibi',
      'frekans' => 'Periyodik',
      'sorumlu' => 'İşveren / Filo-Teknik Birim / İGU',
      'yasal_gereklilik' => '6331 sayılı İSG Kanunu; İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği',
      'kayit_notu' => 'Araç güvenliği',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
    35 => 
    array (
      'ana_konu' => 'ERGONOMİ',
      'faaliyet' => 'Elle taşıma, ergonomi ve ekranlı araçlarla çalışma koşullarının gözden geçirilmesi',
      'frekans' => '3 Aylık / Gerektiğinde',
      'sorumlu' => 'İGU / İşyeri Hekimi / Bölüm Sorumluları',
      'yasal_gereklilik' => 'Elle Taşıma İşleri Yönetmeliği; Ekranlı Araçlarla Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik',
      'kayit_notu' => 'Ergonomi',
      'varsayilan_aylar' => 
      array (
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
      ),
    ),
  ),
);
