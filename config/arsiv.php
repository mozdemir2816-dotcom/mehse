<?php

/*
|--------------------------------------------------------------------------
| Arşiv — kategori kuralları (Doküman Yönetimi → Arşiv, 04.10.2026)
|--------------------------------------------------------------------------
| Referans: kullanıcının "FirstİSG" Arşiv ekran görüntüleri (Downloads/Yeni
| klasör). Her kategori bir "kural" taşır; ArsivKurali bu kurala göre
| firma başına beklenen belgeyi, geçerlilik sonunu ve durumu (tamam /
| eksik / yaklaşan / gecikmiş) hesaplar.
|
| kural:
|   suresiz      — bitiş tarihi yok; yenisi yüklenene kadar yürürlükte.
|   yillik_plan  — ait olduğu yılın 31 Aralık'ında biter; bu yılınki beklenir.
|   yillik_rapor — geçen yılın raporu; o yılı izleyen 31 Ocak'a kadar hazırlanır.
|   periyodik    — belge tarihinden "ay" sonra yenilenir ("ay" tehlike
|                  sınıfına göre dizi olabilir).
|   kayit        — beklenen belge yok; olay/kayıt arşivi (isteğe bağlı bitiş).
| yoksa: belge hiç yokken durum ('eksik' | 'gecikmis').
| kosul: 'elli_calisan' — yalnız 50+ çalışanlı firmalarda beklenir.
| alanlar: kisi (etiket), tarih (etiket), yil (etiket), bitis (manuel bitiş tarihi).
| uretici: ArsivUretici'deki sistem üreticisi anahtarı.
*/

return [

    'gruplar' => [
        'sozlesmeler' => 'Sözleşmeler',
        'planlar' => 'Planlar ve Raporlar',
        'kurul' => 'Kurul ve Temsil',
        'risk' => 'Risk ve Acil Durum',
        'olcum' => 'Ölçüm ve Kontrol',
        'egitim' => 'Eğitim ve Sağlık',
        'olay' => 'Olay ve Denetim',
        'diger' => 'Diğer Belgeler',
    ],

    'kategoriler' => [
        'igu_sozlesmesi' => [
            'ad' => 'İSG Uzmanı Sözleşmesi', 'grup' => 'sozlesmeler', 'ikon' => 'heroicon-o-pencil-square',
            'kural' => 'suresiz', 'yoksa' => 'eksik', 'bolum' => 'Yürürlükteki Sözleşmeler',
            'alanlar' => ['kisi' => 'Uzman Adı', 'tarih' => 'Sözleşme Tarihi'],
            'kural_metni' => 'Sözleşmelerin bitiş tarihi yoktur; yenisi yüklenene kadar yürürlükte kalırlar. Bu yüzden bu kategoride yenileme uyarısı çıkmaz.',
        ],
        'hekim_sozlesmesi' => [
            'ad' => 'İşyeri Hekimi Sözleşmesi', 'grup' => 'sozlesmeler', 'ikon' => 'heroicon-o-briefcase',
            'kural' => 'suresiz', 'yoksa' => 'eksik', 'bolum' => 'Yürürlükteki Sözleşmeler',
            'alanlar' => ['kisi' => 'Hekim Adı', 'tarih' => 'Sözleşme Tarihi'],
            'kural_metni' => 'Sözleşmelerin bitiş tarihi yoktur; yenisi yüklenene kadar yürürlükte kalırlar. Bu yüzden bu kategoride yenileme uyarısı çıkmaz.',
        ],
        'sozlesme' => [
            'ad' => 'Diğer Sözleşme / Görevlendirme', 'grup' => 'sozlesmeler', 'ikon' => 'heroicon-o-document-text',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Belge Tarihi', 'bitis' => 'Bitiş Tarihi'],
            'kural_metni' => 'Görevlendirme yazıları ve diğer sözleşmeler arşivlenir; bitiş tarihi girilirse süresi takip edilir.',
        ],

        'yillik_calisma_plani' => [
            'ad' => 'Yıllık Çalışma Planı', 'grup' => 'planlar', 'ikon' => 'heroicon-o-calendar-days',
            'kural' => 'yillik_plan', 'yoksa' => 'eksik', 'uretici' => 'yillik_calisma_plani',
            'alanlar' => ['yil' => 'Plan Yılı', 'tarih' => 'Hazırlama Tarihi'],
            'kural_metni' => "Planlar hangi tarihte hazırlanırsa hazırlansın ait oldukları yılın 31 Aralık'ında sona erer. Gelecek yılın planı şimdiden yüklenebilir; yükleme ekranındaki yıl seçimi belgenin hangi yıla ait olduğunu belirler. Bu yılın planı yoksa eksik görünür.",
        ],
        'yillik_egitim_plani' => [
            'ad' => 'Yıllık Eğitim Planı', 'grup' => 'planlar', 'ikon' => 'heroicon-o-calendar',
            'kural' => 'yillik_plan', 'yoksa' => 'eksik', 'uretici' => 'yillik_egitim_plani',
            'alanlar' => ['yil' => 'Plan Yılı', 'tarih' => 'Hazırlama Tarihi'],
            'kural_metni' => "Planlar hangi tarihte hazırlanırsa hazırlansın ait oldukları yılın 31 Aralık'ında sona erer. Gelecek yılın planı şimdiden yüklenebilir; yükleme ekranındaki yıl seçimi belgenin hangi yıla ait olduğunu belirler. Bu yılın planı yoksa eksik görünür.",
        ],
        'yillik_degerlendirme' => [
            'ad' => 'Yıllık Değerlendirme Raporu', 'grup' => 'planlar', 'ikon' => 'heroicon-o-document-chart-bar',
            'kural' => 'yillik_rapor', 'yoksa' => 'gecikmis', 'uretici' => 'yillik_degerlendirme',
            'alanlar' => ['yil' => 'Rapor Yılı', 'tarih' => 'Hazırlama Tarihi'],
            'kural_metni' => "Rapor geçen yılın faaliyetlerini anlatır ve 31 Ocak'a kadar hazırlanır. Hazırlandıktan sonra süresi dolmaz; her yıl için ayrı bir rapor beklenir.",
        ],

        'calisan_temsilcisi' => [
            'ad' => 'Çalışan Temsilcisi Kayıtları', 'grup' => 'kurul', 'ikon' => 'heroicon-o-identification',
            'kural' => 'suresiz', 'yoksa' => 'eksik', 'bolum' => 'Yürürlükteki Kayıtlar',
            'alanlar' => ['kisi' => 'Temsilci Adı', 'tarih' => 'Seçim / Atama Tarihi'],
            'kural_metni' => 'Temsilci seçim tutanağı ve atama yazısı, yeni seçim yapılana kadar yürürlükte kalır.',
        ],
        'kurul_tutanagi' => [
            'ad' => 'İSG Kurul Toplantı Tutanakları', 'grup' => 'kurul', 'ikon' => 'heroicon-o-clipboard-document-list',
            'kural' => 'periyodik', 'ay' => ['cok_tehlikeli' => 1, 'tehlikeli' => 2, 'az_tehlikeli' => 3], 'yoksa' => 'eksik',
            'kosul' => 'elli_calisan', 'uretici' => 'kurul_tutanagi',
            'alanlar' => ['tarih' => 'Toplantı Tarihi'],
            'kural_metni' => 'Elli ve daha fazla çalışanı olan işyerlerinde kurul; çok tehlikelide ayda bir, tehlikelide iki ayda bir, az tehlikelide üç ayda bir toplanır. Son tutanağın tarihinden itibaren bir sonraki toplantı beklenir.',
        ],

        'risk_degerlendirmesi' => [
            'ad' => 'Risk Değerlendirmesi', 'grup' => 'risk', 'ikon' => 'heroicon-o-shield-exclamation',
            'kural' => 'periyodik', 'ay' => ['cok_tehlikeli' => 24, 'tehlikeli' => 48, 'az_tehlikeli' => 72], 'yoksa' => 'eksik',
            'uretici' => 'risk_degerlendirmesi',
            'alanlar' => ['tarih' => 'Hazırlama / Revizyon Tarihi'],
            'kural_metni' => 'Risk değerlendirmesi çok tehlikelide 2, tehlikelide 4, az tehlikelide 6 yılda bir yenilenir; işyerinde değişiklik olduğunda daha önce de yenilenmelidir.',
        ],
        'acil_durum_plani' => [
            'ad' => 'Acil Durum Planı', 'grup' => 'risk', 'ikon' => 'heroicon-o-bell-alert',
            'kural' => 'periyodik', 'ay' => ['cok_tehlikeli' => 24, 'tehlikeli' => 48, 'az_tehlikeli' => 72], 'yoksa' => 'eksik',
            'uretici' => 'acil_durum_plani',
            'alanlar' => ['tarih' => 'Hazırlama / Revizyon Tarihi'],
            'kural_metni' => 'Acil durum planı risk değerlendirmesiyle aynı periyotta (2 / 4 / 6 yıl) yenilenir.',
        ],
        'acil_durum_ekipleri' => [
            'ad' => 'Acil Durum Ekipleri', 'grup' => 'risk', 'ikon' => 'heroicon-o-fire',
            'kural' => 'suresiz', 'yoksa' => 'eksik', 'bolum' => 'Yürürlükteki Kayıtlar', 'uretici' => 'acil_durum_ekipleri',
            'alanlar' => ['tarih' => 'Görevlendirme Tarihi'],
            'kural_metni' => 'Ekip görevlendirmeleri, ekipte değişiklik olana kadar yürürlükte kalır.',
        ],
        'tatbikat' => [
            'ad' => 'Acil Durum Tatbikatları', 'grup' => 'risk', 'ikon' => 'heroicon-o-arrow-right-on-rectangle',
            'kural' => 'periyodik', 'ay' => 12, 'yoksa' => 'gecikmis', 'uretici' => 'tatbikat',
            'alanlar' => ['tarih' => 'Tatbikat Tarihi'],
            'kural_metni' => 'Tatbikat en az yılda bir yapılır; son tatbikatın tarihinden bir yıl sonra yenisi beklenir.',
        ],

        'is_hijyeni' => [
            'ad' => 'İş Hijyeni Ölçümleri', 'grup' => 'olcum', 'ikon' => 'heroicon-o-beaker',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Ölçüm Tarihi', 'bitis' => 'Sonraki Ölçüm Tarihi'],
            'kural_metni' => 'Ölçüm raporları arşivlenir; sonraki ölçüm tarihi girilirse yaklaşınca ve geçince uyarı çıkar.',
        ],
        'periyodik_kontrol' => [
            'ad' => 'Periyodik Kontroller', 'grup' => 'olcum', 'ikon' => 'heroicon-o-wrench-screwdriver',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Kontrol Tarihi', 'bitis' => 'Sonraki Kontrol Tarihi'],
            'kural_metni' => 'Kontrol raporları arşivlenir; sonraki kontrol tarihi girilirse yaklaşınca ve geçince uyarı çıkar.',
        ],

        'isg_egitimleri' => [
            'ad' => 'İSG Eğitimleri', 'grup' => 'egitim', 'ikon' => 'heroicon-o-academic-cap',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Eğitim Tarihi', 'bitis' => 'Geçerlilik Sonu'],
            'kural_metni' => 'Katılım formları ve sertifikalar arşivlenir; geçerlilik sonu girilirse yenileme takip edilir.',
        ],
        'saglik_raporlari' => [
            'ad' => 'Sağlık Raporları', 'grup' => 'egitim', 'ikon' => 'heroicon-o-heart',
            'kural' => 'kayit', 'alanlar' => ['kisi' => 'Çalışan Adı', 'tarih' => 'Muayene Tarihi', 'bitis' => 'Sonraki Muayene Tarihi'],
            'kural_metni' => 'İşe giriş ve periyodik muayene raporları arşivlenir; sonraki muayene tarihi girilirse takip edilir.',
        ],

        'is_kazasi' => [
            'ad' => 'İş Kazası Kayıtları', 'grup' => 'olay', 'ikon' => 'heroicon-o-exclamation-triangle',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Olay Tarihi'],
            'kural_metni' => 'İş kazası bildirimleri, tutanaklar ve inceleme raporları süresiz arşivlenir.',
        ],
        'tespit_oneri' => [
            'ad' => 'Tespit ve Öneri Defteri Kayıtları', 'grup' => 'olay', 'ikon' => 'heroicon-o-book-open',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Kayıt Tarihi'],
            'kural_metni' => 'Onaylı defter sayfalarının fotoğrafları süresiz arşivlenir.',
        ],

        'talimat' => [
            'ad' => 'Talimat / Prosedür', 'grup' => 'diger', 'ikon' => 'heroicon-o-list-bullet',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Belge Tarihi', 'bitis' => 'Geçerlilik Sonu'],
            'kural_metni' => 'Talimat ve prosedürler arşivlenir.',
        ],
        'kimyasal' => [
            'ad' => 'Kimyasal / SDS / PKD', 'grup' => 'diger', 'ikon' => 'heroicon-o-beaker',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Belge Tarihi', 'bitis' => 'Geçerlilik Sonu'],
            'kural_metni' => 'Güvenlik bilgi formları ve patlamadan korunma dokümanları arşivlenir.',
        ],
        'resmi_yazi' => [
            'ad' => 'Resmî Yazışma', 'grup' => 'diger', 'ikon' => 'heroicon-o-envelope',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Yazı Tarihi'],
            'kural_metni' => 'Resmî yazışmalar süresiz arşivlenir.',
        ],
        'mevzuat' => [
            'ad' => 'Mevzuat', 'grup' => 'diger', 'ikon' => 'heroicon-o-scale',
            'kural' => 'kayit', 'alanlar' => [],
            'kural_metni' => 'Mevzuat metinleri başvuru için arşivlenir.',
        ],
        'diger' => [
            'ad' => 'Diğer', 'grup' => 'diger', 'ikon' => 'heroicon-o-document',
            'kural' => 'kayit', 'alanlar' => ['tarih' => 'Belge Tarihi', 'bitis' => 'Geçerlilik Sonu'],
            'kural_metni' => 'Diğer belgeler arşivlenir; geçerlilik sonu girilirse süresi takip edilir.',
        ],
    ],

    // Doküman Yönetimi'nin eski kategori anahtarları → yeni anahtarlar (migration).
    'eski_kategoriler' => [
        'acil_durum' => 'acil_durum_plani',
        'yillik_plan' => 'yillik_calisma_plani',
        'egitim' => 'isg_egitimleri',
        'ortam_olcumu' => 'is_hijyeni',
        'olay' => 'is_kazasi',
    ],

    // Varsayılan "yaklaşan" eşiği (Hatırlatma Ayarları ile kullanıcı değiştirir).
    'yaklasan_gun' => 30,

    // Kullanıcı şablonlarında {{...}} yer tutucusuyla kullanılabilen sistem alanları.
    'sistem_alanlari' => [
        'isyeri.unvan' => 'İşyeri Unvanı',
        'isyeri.adres' => 'İşyeri Adresi',
        'isyeri.telefon' => 'İşyeri Telefonu',
        'isyeri.eposta' => 'İşyeri E-postası',
        'isyeri.sgk_sicil' => 'SGK Sicil No',
        'isyeri.tehlike_sinifi' => 'Tehlike Sınıfı',
        'isyeri.nace' => 'NACE Kodu',
        'isyeri.iskolu' => 'Faaliyet (NACE açıklaması)',
        'isyeri.calisan_sayisi' => 'Çalışan Sayısı',
        'isyeri.katip_no' => 'İSG-KATİP No',
        'erkek_sayisi' => 'Erkek Çalışan Sayısı',
        'kadin_sayisi' => 'Kadın Çalışan Sayısı',
        'yetkili' => 'İşveren / İşveren Vekili',
        'uzman.ad' => 'İSG Uzmanı',
        'uzman.sertifika_no' => 'Uzman Sertifika No',
        'hekim.ad' => 'İşyeri Hekimi',
        'hekim.sertifika_no' => 'Hekim Sertifika No',
        'osgb.unvan' => 'OSGB / Hizmet Veren',
        'kayit.yil' => 'Belge Yılı',
        'kayit.plan_yili' => 'Plan Yılı',
        'kayit.rapor_yili' => 'Rapor Yılı',
        'kayit.tarih' => 'Belge Tarihi',
        'bugun' => 'Bugünün Tarihi',
    ],
];
