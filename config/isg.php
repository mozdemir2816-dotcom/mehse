<?php

/**
 * İSG mevzuatına bağlı sabitler. Değişince tek yerden güncellenir.
 */
return [

    /*
    | Tehlike sınıfları — İş Sağlığı ve Güvenliğine İlişkin İşyeri Tehlike
    | Sınıfları Tebliği. Risk değerlendirmesi / acil durum planı yenileme
    | periyodu (yıl) buna bağlı.
    */
    'tehlike_siniflari' => [
        'az_tehlikeli' => 'Az Tehlikeli',
        'tehlikeli' => 'Tehlikeli',
        'cok_tehlikeli' => 'Çok Tehlikeli',
    ],

    // Risk değerlendirmesi geçerlilik süresi (yıl) — 6331 sayılı Kanun md.10.
    'risk_gecerlilik_yili' => [
        'az_tehlikeli' => 6,
        'tehlikeli' => 4,
        'cok_tehlikeli' => 2,
    ],

    // Acil durum planı yenileme süresi (yıl) — İşyerlerinde Acil Durumlar Yön.
    'acil_durum_gecerlilik_yili' => [
        'az_tehlikeli' => 6,
        'tehlikeli' => 4,
        'cok_tehlikeli' => 2,
    ],

    // Temel İSG eğitimi yenileme periyodu (yıl) — Çalışanların İSG Eğitimleri Yön.
    'egitim_yenileme_yili' => [
        'az_tehlikeli' => 3,
        'tehlikeli' => 2,
        'cok_tehlikeli' => 1,
    ],

    // İSG profesyoneli unvanları (hesap sahibi uzmanın sınıfı).
    'uzman_unvanlari' => [
        'a_sinifi' => 'A Sınıfı İş Güvenliği Uzmanı',
        'b_sinifi' => 'B Sınıfı İş Güvenliği Uzmanı',
        'c_sinifi' => 'C Sınıfı İş Güvenliği Uzmanı',
        'isyeri_hekimi' => 'İşyeri Hekimi',
        'dsp' => 'Diğer Sağlık Personeli',
    ],

    // Dashboard: "Önemli Risk" eşiği (risk puanı bu değerin üstündeyse).
    'onemli_risk_esigi' => 140,

    // Yaklaşan iş / evrak hatırlatma penceresi (gün).
    'hatirlatma_gun' => 30,

    /*
    |--------------------------------------------------------------------------
    | Risk değerlendirme yöntemleri
    |--------------------------------------------------------------------------
    | Her yöntem: ölçek metinleri + puan → düzey bantları (üstten alta ilk
    | eşleşen). Puan hesabı `App\Support\RiskSkorlama`.
    */
    'risk_yontemleri' => [
        'matris_5x5' => '5x5 Matris (L Tipi)',
        'fine_kinney' => 'Fine-Kinney',
    ],

    'risk_matris_5x5' => [
        'aciklama' => 'Risk = Olasılık × Şiddet (T.C. 5×5 L Tipi Matris).',
        'olasilik' => [
            '1' => 'Çok Küçük (yılda bir)',
            '2' => 'Küçük (üç ayda bir)',
            '3' => 'Orta (ayda bir)',
            '4' => 'Yüksek (haftada bir)',
            '5' => 'Çok Yüksek (her gün)',
        ],
        'siddet' => [
            '1' => 'Çok Hafif (iş saati kaybı yok, ilkyardım)',
            '2' => 'Hafif (iş günü kaybı yok, ilkyardım gerektiren)',
            '3' => 'Orta (hafif yaralanma, tedavi gerekli)',
            '4' => 'Ciddi (uzuv kaybı, meslek hastalığı)',
            '5' => 'Çok Ciddi (ölüm, sürekli iş göremezlik)',
        ],
        // puan >= min → düzey (üstten alta)
        'bantlar' => [
            ['min' => 25, 'ad' => 'Tolerans Gösterilemez Risk', 'renk' => '#7f1d1d', 'eylem' => 'Bu risk kontrol altına alınıncaya kadar çalışma yapılmamalıdır.'],
            ['min' => 15, 'ad' => 'Yüksek Risk', 'renk' => '#dc2626', 'eylem' => 'Bu risk azaltılıncaya kadar iş başlatılmamalı; devam eden işte acil önlem alınmalıdır.'],
            ['min' => 8, 'ad' => 'Orta Düzeyde Risk', 'renk' => '#f59e0b', 'eylem' => 'Belirli bir süre içinde risk azaltıcı önlemler alınmalıdır.'],
            ['min' => 3, 'ad' => 'Katlanılabilir Risk', 'renk' => '#84cc16', 'eylem' => 'Ek kontrol gerekmeyebilir; maliyeti düşük iyileştirmeler düşünülmelidir.'],
            ['min' => 1, 'ad' => 'Önemsiz Risk', 'renk' => '#22c55e', 'eylem' => 'Önlem planlamaya / kayıt tutmaya gerek yoktur.'],
        ],
    ],

    'risk_fine_kinney' => [
        'aciklama' => 'Risk = Olasılık × Frekans (maruz kalma) × Şiddet.',
        // Ondalıklı anahtarlar string olmalı (PHP dizisinde float anahtar int'e kırpılır).
        'olasilik' => [
            '0.2' => 'Pratik olarak imkânsız',
            '0.5' => 'Zayıf olasılık (beklenmez)',
            '1' => 'Küçük olasılık (ama mümkün)',
            '3' => 'Nadir ama olabilir',
            '6' => 'Kuvvetle muhtemel',
            '10' => 'Beklenir, kesin',
        ],
        'frekans' => [
            '0.5' => 'Çok seyrek (yılda birkaç kez)',
            '1' => 'Oldukça seyrek (ayda bir)',
            '2' => 'Ara sıra (haftada bir)',
            '3' => 'Sık (günde bir)',
            '6' => 'Sürekli (saatte bir / gün boyu)',
            '10' => 'Devamlı',
        ],
        'siddet' => [
            '1' => 'Önemsiz (dikkate değmez)',
            '3' => 'Önemli (yaralanma, küçük hasar)',
            '7' => 'Ciddi (kalıcı hasar / uzuv kaybı)',
            '15' => 'Çok ciddi (bir ölüm)',
            '40' => 'Felaket (birden çok ölüm)',
        ],
        'bantlar' => [
            ['min' => 400, 'ad' => 'Tolerans Gösterilemez Risk', 'renk' => '#7f1d1d', 'eylem' => 'Çalışma derhal durdurulmalıdır.'],
            ['min' => 200, 'ad' => 'Esaslı Risk', 'renk' => '#dc2626', 'eylem' => 'Kısa dönemde (birkaç ay) iyileştirme yapılmalıdır.'],
            ['min' => 70, 'ad' => 'Önemli Risk', 'renk' => '#f59e0b', 'eylem' => 'Yıl içinde iyileştirme yapılmalıdır.'],
            ['min' => 20, 'ad' => 'Olası Risk', 'renk' => '#84cc16', 'eylem' => 'Gözetim altında tutulmalı, iyileştirme planlanmalıdır.'],
            ['min' => 0, 'ad' => 'Önemsiz Risk', 'renk' => '#22c55e', 'eylem' => 'Ek önleme gerek yoktur.'],
        ],
    ],

    // Risk maddesi durumları
    'risk_madde_durumlari' => [
        'acik' => 'Açık',
        'devam' => 'Devam Ediyor',
        'kapali' => 'Kapalı (önlem alındı)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kontrol Merkezi (İSG Komuta Merkezi) — isgpratik 135-136.jpg
    |--------------------------------------------------------------------------
    | Portföy genelinde 12 yasal kriterin firma bazlı tamamlanma oranı.
    | `hazir` = mehse'de bu kriteri hesaplayacak modül şu an var mı? (yoksa 0/N,
    | ilgili modül kurulunca `App\Support\PortfoyKarne` gerçek sayıma geçer.)
    */
    'kontrol_merkezi' => [
        'kriterler' => [
            ['anahtar' => 'risk_degerlendirmesi', 'ad' => 'Risk Değerlendirmesi', 'ikon' => 'heroicon-o-sparkles', 'hazir' => true],
            ['anahtar' => 'yillik_calisma_plani', 'ad' => 'Yıllık Çalışma Planı', 'ikon' => 'heroicon-o-calendar-days', 'hazir' => false],
            ['anahtar' => 'acil_durum_plani', 'ad' => 'Acil Durum Planı Belgesi (ADP)', 'ikon' => 'heroicon-o-exclamation-triangle', 'hazir' => false],
            ['anahtar' => 'acil_durum_destek', 'ad' => 'Acil Durum Destek Elemanları', 'ikon' => 'heroicon-o-user-group', 'hazir' => false],
            ['anahtar' => 'acil_durum_tatbikat', 'ad' => 'Acil Durum Tatbikat Tutanağı', 'ikon' => 'heroicon-o-fire', 'hazir' => false],
            ['anahtar' => 'isg_kurulu', 'ad' => 'İSG Kurulu Toplantısı', 'ikon' => 'heroicon-o-users', 'hazir' => false, 'kosul' => 'elli_calisan'],
            ['anahtar' => 'yillik_egitim_plani', 'ad' => 'Yıllık Eğitim Planı', 'ikon' => 'heroicon-o-academic-cap', 'hazir' => false],
            ['anahtar' => 'yillik_degerlendirme', 'ad' => 'Yıllık Değerlendirme Raporu', 'ikon' => 'heroicon-o-document-chart-bar', 'hazir' => false],
            ['anahtar' => 'calisan_temsilcisi', 'ad' => 'Çalışan Temsilcisi Görevlendirmesi', 'ikon' => 'heroicon-o-identification', 'hazir' => false],
            ['anahtar' => 'igu_atamasi', 'ad' => 'İş Güvenliği Uzmanı (İGU) Ataması', 'ikon' => 'heroicon-o-shield-check', 'hazir' => false],
            ['anahtar' => 'hekim_atamasi', 'ad' => 'İşyeri Hekimi Ataması', 'ikon' => 'heroicon-o-heart', 'hazir' => false],
            ['anahtar' => 'tespit_oneri', 'ad' => 'Tespit ve Öneri Defteri Kaydı', 'ikon' => 'heroicon-o-book-open', 'hazir' => false],
        ],

        'uzman_tavsiyeleri' => [
            ['baslik' => 'Yıllık Tatbikat', 'metin' => 'Yılda en az 1 kez acil durum tahliye / yangın tatbikatı yapılıp tutanak sisteme işlenmelidir.'],
            ['baslik' => 'İSG Kurulu', 'metin' => '50 ve üzeri çalışanlı işyerlerinde kurul kararları ve toplantı tutanakları yıl boyu güncel tutulmalıdır.'],
            ['baslik' => 'Çalışan Temsilcisi', 'metin' => 'Seçim veya atama tutanaklarının süresi dolmadan yenilenmesi yasal zorunludur.'],
            ['baslik' => 'Periyodik Muayene', 'metin' => 'Ağır ve tehlikeli işlerde çalışanların periyodik sağlık muayeneleri aksatılmamalıdır.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İSG-KATİP Robot — isgpratik 7-9.jpg
    |--------------------------------------------------------------------------
    | İSG-KATİP portalını otomatikleştiren bir Chrome eklentisi. mehse'de gerçek
    | eklenti + resmî portal erişimi olmadığı için YALNIZ BİLGİ SAYFASI + günlük
    | hak sayacı stub'ı olarak kurulur; "Kurmak için tıklayın" bildirim gösterir.
    */
    'isg_katip' => [
        'gunluk_hak' => 3,

        'gereksinimler' => [
            ['baslik' => 'Google Chrome Tarayıcı', 'ikon' => 'heroicon-o-globe-alt',
                'aciklama' => 'Bot yalnızca Google Chrome veya Chromium tabanlı tarayıcılarda çalışır.'],
            ['baslik' => 'Aktif İSG-KATİP Oturumu', 'ikon' => 'heroicon-o-clock',
                'aciklama' => 'İşlemler için tarayıcınızda İSG-KATİP oturumunuzun açık olması gerekir.'],
            ['baslik' => 'mehse Üyeliği', 'ikon' => 'heroicon-o-user-circle',
                'aciklama' => 'Bot üzerindeki özellikleri kullanmak için aktif bir mehse hesabınız olmalıdır.'],
        ],

        'kurulum_adimlari' => [
            ['baslik' => 'Web Store’dan Yükleyin', 'aciklama' => 'Eklentiyi Chrome Web Store’dan tek tıkla yükleyin. Otomatik güncellenir.'],
            ['baslik' => 'Chrome’a Ekle’yi Onaylayın', 'aciklama' => 'Mağaza sayfasında "Chrome’a Ekle" butonuna tıklayın ve onay penceresinde "Uzantı ekle" seçin.'],
            ['baslik' => 'İSG-KATİP Oturumunuzu Açın', 'aciklama' => 'Ayrı bir sekmede İSG-KATİP sistemine giriş yapın. Eklenti otomatik bağlantı kuracaktır.'],
            ['baslik' => 'Bağlantıyı Kontrol Edin', 'aciklama' => 'Bu sayfadaki "Bağlantıyı Kontrol Et" butonuna tıklayarak bağlantı durumunu doğrulayın.'],
        ],

        // her bot: {ad, aciklama, osgb (OSGB rozeti), yeni}
        'botlar' => [
            ['ad' => 'Çoklu Atama Yap', 'osgb' => true, 'yeni' => false,
                'aciklama' => 'OSGB ve Mesul Müdürler için birden fazla işyerine tek seferde personel ataması.'],
            ['ad' => 'Personel İçe Görevlendirme', 'osgb' => true, 'yeni' => false,
                'aciklama' => 'OSGB ile İGU, işyeri hekimi veya diğer sağlık personeli arasındaki personel görevlendirmesi.'],
            ['ad' => 'Çoklu Sabit Tıbbi Tetkik Ataması', 'osgb' => true, 'yeni' => true,
                'aciklama' => 'Onaylı işyeri hekimi bulunan birden fazla işyerine seçtiğiniz sabit sağlık tetkik ataması.'],
            ['ad' => 'Çoklu Gezici İş Sağlığı Aracı Ataması', 'osgb' => true, 'yeni' => true,
                'aciklama' => 'Kayıtlı gezici sağlık aracını, yetkili tetkikleri ve tarih aralığını seçerek birden fazla işyerine atama.'],
            ['ad' => 'Toplu Atama Sözleşmesi İndir', 'osgb' => false, 'yeni' => false,
                'aciklama' => 'Aktif atama sözleşmelerinizi listeleyip seçtiğiniz ya da tüm sözleşmeleri PDF olarak indirin.'],
            ['ad' => 'Toplu Sözleşme Onayla', 'osgb' => false, 'yeni' => true,
                'aciklama' => 'Personel onayında bekleyen hizmet sözleşmelerini seçerek veya topluca onaylayın.'],
            ['ad' => 'Toplu Sözleşme Sonlandır', 'osgb' => true, 'yeni' => false,
                'aciklama' => 'Aktif ve onaylı hizmet sözleşmelerinizi listeleyip seçtiklerinizi İSG-KATİP üzerinden sonlandırın.'],
            ['ad' => 'Asgari Süreden Fazla Atanan Sözleşmeleri Güncelle', 'osgb' => true, 'yeni' => false,
                'aciklama' => 'Çalışan sayısı ve tehlike sınıfına göre hesaplanan gerekli süreye göre fazla atanan sözleşmeleri düzeltin.'],
            ['ad' => 'Güncellenmesi Gereken Sözleşmeler', 'osgb' => true, 'yeni' => false,
                'aciklama' => 'İSG-KATİP’teki uyumsuz sözleşmeleri bulur ve çalışan sayısı ile tehlike sınıfına göre işaretler.'],
            ['ad' => 'Hizmet Alan İşyerleri İSG Sözleşme Durumu', 'osgb' => false, 'yeni' => false,
                'aciklama' => 'OSGB’nizin hizmet verdiği tüm işyerlerindeki İSG profesyonellerinin sözleşme durumunu listeler.'],
            ['ad' => 'Süre Analizi', 'osgb' => false, 'yeni' => false,
                'aciklama' => 'Personellerin kalan sürelerini ve doluluk oranlarını anlık raporlar.'],
        ],

        'neden' => [
            'İSG-KATİP’te tek tek yapılan yüzlerce atama/sözleşme işlemi saatler alır; bot bunu dakikalara indirir.',
            'İnsan kaynaklı süre/çalışan sayısı hataları (asgari süre ihlali) otomatik tespit edilir.',
            'Tüm işlemler sizin oturumunuzda, sizin adınıza yapılır — bot arka planda hiçbir veri saklamaz.',
        ],

        'guvenlik' => [
            'Eklenti yalnızca açık olan İSG-KATİP sekmesinde, sizin verdiğiniz komutları çalıştırır.',
            'Şifreniz veya e-devlet bilginiz hiçbir şekilde istenmez, iletilmez veya saklanmaz.',
            'Kaynak kod Chrome Web Store incelemesinden geçer; her işlem öncesi onayınız alınır.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Acil Durum Eylem Planı — isgpratik 19-21, 146-154.jpg
    |--------------------------------------------------------------------------
    | 6331 SK kapsamında her işyerinde zorunlu belge. Firma seçilir, konu
    | sayfaları + destek ekipleri + kapak çerçevesi seçilir → PDF üretilir.
    | Ayrıca 7 acil durum afişi (A3/A4 talimat).
    */
    'acil_durum' => [
        'hakkinda' => 'Acil Durum Eylem Planı, işyerinizde meydana gelebilecek yangın, deprem, doğal afetler ve diğer acil durumlara karşı alınacak önlemleri içeren yasal bir belgedir. Bu plan, 6331 sayılı İş Sağlığı ve Güvenliği Kanunu kapsamında her işyerinde bulundurulması zorunlu belgelerden biridir.',

        // İşyerlerinde Acil Durumlar Hakkında Yönetmelik EK-2 — ulusal acil durum
        // hatları (işyerinden bağımsız, sabit). Yerel kurum/kuruluş telefonları
        // (elektrik/su/doğalgaz arıza hattı vb.) plan sahibince elle eklenir.
        'irtibat_telefonlari' => [
            'İtfaiye' => '110 / 112',
            'Ambulans (Acil Sağlık)' => '112',
            'Polis' => '155',
            'Jandarma' => '156',
            'AFAD' => '122',
            'Sahil Güvenlik' => '158',
            'Ulusal Zehir Danışma Merkezi (UZEM)' => '114',
            'Sosyal Yardımlaşma Hattı (SABİM)' => '184',
        ],

        // Plan yenileme periyodu tehlike sınıfına göre (yıl) — Acil Durumlar Yön.
        'gecerlilik_yili' => [
            'az_tehlikeli' => 6,
            'tehlikeli' => 4,
            'cok_tehlikeli' => 2,
        ],

        /*
         | Acil durum konu sayfaları (isgpratik 155.jpg — 19 konu + ek). Yeni planda
         | firmaya göre otomatik seçilir (`App\Support\AcilDurumKonuSecici`):
         |   kosul = null  → her firmada seçili (genel afet + ortak riskler)
         |   kosul = ['nace' => [ön ekler], 'tehlike' => [sınıflar]]  → NACE ön eki
         |     eşleşirse VEYA tehlike sınıfı eşleşirse seçili.
         | Kullanıcı elle ekleyip çıkarabilir; firmaya uymayan konu işaretlenmez.
        */
        'konular' => [
            ['anahtar' => 'yangin', 'ad' => 'Yangın', 'kosul' => null],
            ['anahtar' => 'deprem', 'ad' => 'Deprem', 'kosul' => null],
            ['anahtar' => 'sabotaj', 'ad' => 'Sabotaj', 'kosul' => null],
            ['anahtar' => 'is_kazasi', 'ad' => 'İş Kazası ve Sağlık Olayları', 'kosul' => null],
            ['anahtar' => 'elektrik', 'ad' => 'Elektrik Çarpması', 'kosul' => null],
            ['anahtar' => 'sel', 'ad' => 'Sel ve Su Baskını', 'kosul' => null],
            ['anahtar' => 'yildirim', 'ad' => 'Yıldırım Düşmesi ve Fırtına', 'kosul' => null],

            ['anahtar' => 'kimyasal', 'ad' => 'Kimyasal Dökülme / Sızıntı',
                'kosul' => ['nace' => ['05', '06', '07', '08', '09', '10', '11', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '35', '36', '37', '38', '39', '86'], 'tehlike' => ['cok_tehlikeli']]],
            ['anahtar' => 'zehirlenme', 'ad' => 'Zehirlenme',
                'kosul' => ['nace' => ['10', '11', '19', '20', '21', '86'], 'tehlike' => ['cok_tehlikeli']]],
            ['anahtar' => 'patlama', 'ad' => 'Patlama',
                'kosul' => ['nace' => ['05', '06', '07', '08', '09', '19', '20', '21', '24', '35'], 'tehlike' => ['cok_tehlikeli']]],
            ['anahtar' => 'salgin', 'ad' => 'Salgın Hastalık ve Biyolojik Etki',
                'kosul' => ['nace' => ['10', '11', '55', '56', '85', '86', '87', '88']]],
            ['anahtar' => 'bomba', 'ad' => 'Bomba İhbarı ve Patlayıcı Tehdidi',
                'kosul' => ['nace' => ['35', '51', '64', '65', '66', '84'], 'tehlike' => ['cok_tehlikeli']]],
            ['anahtar' => 'yuksekten_dusme', 'ad' => 'Yüksekten Düşme',
                'kosul' => ['nace' => ['05', '06', '07', '08', '09', '33', '41', '42', '43'], 'tehlike' => ['tehlikeli', 'cok_tehlikeli']]],
            ['anahtar' => 'heyelan', 'ad' => 'Heyelan ve Toprak Kayması',
                'kosul' => ['nace' => ['01', '02', '05', '06', '07', '08', '09', '41', '42', '43']]],
            ['anahtar' => 'radyasyon', 'ad' => 'Radyasyon Sızıntısı',
                'kosul' => ['nace' => ['21', '24', '25', '35', '72', '86']]],
            ['anahtar' => 'trafik', 'ad' => 'İşyeri İçi Trafik ve Araç Kazası',
                'kosul' => ['nace' => ['01', '02', '41', '42', '43', '45', '46', '47', '49', '50', '52', '53']]],
            ['anahtar' => 'bogulma', 'ad' => 'Boğulma ve Su Kazası',
                'kosul' => ['nace' => ['03', '50', '52', '93']]],
            ['anahtar' => 'basincli_kap', 'ad' => 'Basınçlı Kap Patlaması',
                'kosul' => ['nace' => ['10', '11', '19', '20', '21', '22', '23', '24', '25', '35'], 'tehlike' => ['tehlikeli', 'cok_tehlikeli']]],
            ['anahtar' => 'makine_arizasi', 'ad' => 'Makine Arızası ve Sıkışma',
                'kosul' => ['nace' => ['01', '02', '05', '06', '07', '08', '09', '10', '11', '13', '14', '15', '16', '17', '18', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '41', '42', '43'], 'tehlike' => ['tehlikeli', 'cok_tehlikeli']]],
            ['anahtar' => 'gida_zehirlenmesi', 'ad' => 'Toplu Gıda Zehirlenmesi',
                'kosul' => ['nace' => ['10', '11', '55', '56']]],
            ['anahtar' => 'asansor', 'ad' => 'Asansörde Mahsur Kalma',
                'kosul' => ['nace' => []]], // elle seçilir (kat bilgisi yok)
        ],

        'kapak_cerceveleri' => [
            'klasik' => 'Klasik — siyah çift çizgi çerçeve',
            'altin' => 'Altın — ince altın kenarlık',
            'mavi_zarif' => 'Mavi Zarif — köşe süslemeli',
            'minimalist' => 'Minimalist — ince tek çizgi',
            'yesil_doga' => 'Yeşil Doğa — organik kenar',
            'kirmizi_resmi' => 'Kırmızı Resmi — kalın kırmızı bant',
            'golgeli' => 'Gölgeli — hafif gölge çerçeve',
        ],

        'ekipler' => [
            'sondurme' => 'Söndürme Ekibi',
            'kurtarma' => 'Kurtarma Ekibi',
            'koruma' => 'Koruma Ekibi',
            'ilk_yardim' => 'İlk Yardım Ekibi',
        ],

        // 7 acil durum afişi — A3/A4 talimat (numaralı adımlar).
        'afisler' => [
            'yangin' => [
                'ad' => 'Yangınla Mücadele', 'baslik' => 'YANGINLA MÜCADELE VE TAHLİYE TALİMATI',
                'dosya' => 'yangin.pdf',
                'adimlar' => [
                    'İtfaiyeyi arayın (110).',
                    'Sesli olarak etrafındakileri uyarın.',
                    'En yakın yangın alarm butonuna basın.',
                    'Yangın müdahale ekip şefini arayın.',
                    'Eğitimliyseniz yangın söndürücü ile ilk müdahaleyi yapın.',
                    'Yangının niteliğine göre gaz akımı ve diğer enerjileri kesin.',
                    'Söndüremiyorsanız binayı tahliye edin, en yakın çıkışı kullanın.',
                    'Toplanma bölgesine gidin ve sayım yapın.',
                    'İtfaiye gelene kadar kimseyi içeri sokmayın.',
                ],
            ],
            'deprem' => [
                'ad' => 'Deprem Eylem Planı', 'baslik' => 'DEPREM EYLEM PLANI',
                'dosya' => 'deprem.pdf',
                'adimlar' => [
                    'Deprem anında etrafındakileri sesli olarak uyarın.',
                    'Çök – kapan – tutun: sağlam bir masanın altına girin.',
                    'Elektrik ve gazı kesin (mümkünse).',
                    'Sarsıntı bitene kadar en yakın korunma bölgesinde kalın.',
                    'Sarsıntı geçince düşebilecek cisimlerden korunarak binayı terk edin.',
                    'Asansör kullanmayın, merdivenlerden inin.',
                    'Toplanma bölgesine gidin ve sayım yapın.',
                    'Yaralı varsa ilk yardım uygulayın, 112’yi arayın.',
                    'Olay raporu hazırlayın.',
                ],
            ],
            'is_kazasi' => [
                'ad' => 'İş Kazası Eylem Planı', 'baslik' => 'İŞ KAZASI EYLEM PLANI',
                'dosya' => 'is_kazasi.pdf',
                'adimlar' => [
                    'Kaza ve yaralanmayı belirleyin, ortamı güvene alın.',
                    'Makine / ekipman hasarını değerlendirin, enerjiyi kesin.',
                    'Duruma uygun acil eylem planını uygulayın.',
                    'Kaza yerine müdahale edin, ilk yardım uygulayın.',
                    'İş Sağlığı ve Güvenliği birimine haber verin.',
                    'Gerekliyse 112’yi arayın.',
                    'Yasa gereği kazayı 3 iş günü içinde SGK’ya bildirin.',
                    'Olay raporu hazırlayın, kök neden analizi yapın.',
                    'Oluşan aksaklıkların tekrar etmemesi için önlem alın.',
                ],
            ],
            'elektrik' => [
                'ad' => 'Elektrik Çarpması', 'baslik' => 'ELEKTRİK ÇARPMASI ACİL MÜDAHALE TALİMATI',
                'dosya' => 'elektrik.pdf',
                'adimlar' => [
                    'Akımı kesin — ana şalteri kapatın.',
                    'Kazazedeye asla çıplak elle dokunmayın; yalıtkan cisimle ayırın.',
                    'Bilinç ve nefes kontrolü yapın.',
                    'Nefes yoksa kalp masajına başlayın (CPR).',
                    '112 acil servisini arayın.',
                    'Yanık varsa yanık bakımı yapın ve hastaneye sevk edin.',
                ],
            ],
            'kimyasal' => [
                'ad' => 'Kimyasal Dökülme', 'baslik' => 'KİMYASAL DÖKÜLME VE SIZINTI EYLEM PLANI',
                'dosya' => 'kimyasal.pdf',
                'adimlar' => [
                    'Uyarı verin ve ortamı havalandırın; camları açın.',
                    'Kişisel koruyucu ekipman giyin (maske, eldiven, gözlük).',
                    'Dökülme ve sızıntıyı kontrol altına alın.',
                    'Döküntüyü uygun malzemeyle absorbe edin.',
                    'Etkilenen kişiye acil yardım: duş ve göz yıkama.',
                    '112 veya 114 (UZEM) haber verin.',
                    'Olayı kaydedin, tedbirleri gözden geçirin.',
                ],
            ],
            'sel' => [
                'ad' => 'Sel ve Su Baskını', 'baslik' => 'SEL VE SU BASKINI EYLEM PLANI',
                'dosya' => 'sel.pdf',
                'adimlar' => [
                    'Sel / su baskını anında etrafındakileri sesli uyarın.',
                    'Durumu İş Güvenliği birimine bildirin.',
                    'Elektrik ve gazı kesin.',
                    'En yakın yüksek / korunma bölgesine taşının.',
                    'Saha imkânları yeterliyse su baskınını önlemek için müdahale edin.',
                    'Toplanma bölgesine gidin.',
                    'Yaralı varsa ilk yardım uygulayın, 112’yi arayın.',
                    'Oluşan aksaklıklar için önlem alın.',
                ],
            ],
            'sabotaj' => [
                'ad' => 'Sabotaj ve Patlama', 'baslik' => 'SABOTAJ VE PATLAMA EYLEM PLANI',
                // Hazır afiş dosyası varsa `resources/belge/acil-durum-afisleri/<dosya>`
                // kullanılır (kullanıcı ekler). Yoksa aşağıdaki adımlardan PDF üretilir.
                'dosya' => 'sabotaj.pdf',
                'adimlar' => [
                    'Sabotaj şeklini belirleyin (yangın, patlayıcı, mekanik, kimyasal, biyolojik).',
                    'Makine / ekipman hasarını değerlendirin.',
                    'Duruma uygun acil eylem planını uygulayın.',
                    'İş Sağlığı ve Güvenliği birimine haber verin.',
                    'Olay yerine müdahale edin.',
                    'Yaralı varsa ilk müdahaleyi yapın, ambulans çağırın (112).',
                    'Toplanma bölgesine gidin ve sayım yapın.',
                    'Olay raporu hazırlayın, güvenlik önlemlerini artırın.',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Sihirbazı — "Yapay Zeka" (kural tabanlı) risk üretimi
    |--------------------------------------------------------------------------
    | isgpratik 103-115.jpg akışı. Gerçek LLM yerine kural tabanlı motor
    | (`App\Support\RiskUretici`): sektör + alt kategori + soru cevapları →
    | aday risk maddeleri. Cevaplardaki eksiklikler ("Hiçbiri verilmedi" gibi)
    | ilgili mevzuat riskini tetikler. LLM entegrasyonu ileride env ile eklenir.
    |
    | riskler[] madde şekli: {bolum, faaliyet, tehlike, risk, mevcut_onlem,
    |   oneri, mevzuat, olasilik, siddet}  (O/Ş = önerilen varsayılan)
    */
    'risk_ai' => [

        'gunluk_hak' => 5, // yumuşak sayaç (kural tabanlıda engellemez)

        // 1. Aşama — Ana Sektör (isgpratik 105.jpg)
        'sektorler' => [
            'fabrika' => [
                'ad' => 'Fabrika / Üretim',
                'aciklama' => 'Otomotiv, metal, plastik, tekstil, gıda işleme, kimya, mobilya vb.',
                'ikon' => 'heroicon-o-building-office',
                'alt_kategoriler' => ['Metal işleme', 'Plastik / kauçuk', 'Tekstil / konfeksiyon', 'Gıda işleme', 'Kimya / boya', 'Mobilya / ahşap', 'Montaj hattı', 'Ambalaj / paketleme'],
            ],
            'insaat' => [
                'ad' => 'İnşaat / Yapı',
                'aciklama' => 'Konut, altyapı, yıkım, tadilat, yol, köprü, tünel.',
                'ikon' => 'heroicon-o-wrench-screwdriver',
                'alt_kategoriler' => ['Konut inşaatı', 'Ticari yapı', 'Altyapı (yol/köprü)', 'Tünel', 'Yıkım', 'Tadilat / dekorasyon', 'Çatı işleri', 'İskele kurulum', 'Prefabrik', 'Restorasyon'],
            ],
            'saglik' => [
                'ad' => 'Sağlık / Hastane',
                'aciklama' => 'Hastane, klinik, eczane, laboratuvar, ambulans.',
                'ikon' => 'heroicon-o-heart',
                'alt_kategoriler' => ['Hastane / poliklinik', 'Diş / ağız sağlığı', 'Laboratuvar', 'Eczane', 'Ambulans / acil', 'Bakım evi'],
            ],
            'ofis' => [
                'ad' => 'Ofis / Hizmet / Finans',
                'aciklama' => 'Banka, sigorta, muhasebe, hukuk, danışmanlık, çağrı merkezi.',
                'ikon' => 'heroicon-o-briefcase',
                'alt_kategoriler' => ['Banka / finans', 'Çağrı merkezi', 'Muhasebe / hukuk', 'Bilişim / yazılım', 'Danışmanlık'],
            ],
            'gida' => [
                'ad' => 'Gıda / Yemekhane / Catering',
                'aciklama' => 'Restoran, kafe, fırın, kasap, fabrika yemekhanesi, otel mutfak.',
                'ikon' => 'heroicon-o-cake',
                'alt_kategoriler' => ['Restoran / kafe', 'Fırın / pastane', 'Kasap / şarküteri', 'Toplu yemek üretim', 'Otel mutfağı'],
            ],
            'ulasim' => [
                'ad' => 'Ulaşım / Lojistik / Akaryakıt',
                'aciklama' => 'Kamyon, otobüs, taksi, kargo, liman, havalimanı, ADR, akaryakıt / petrol istasyonu.',
                'ikon' => 'heroicon-o-truck',
                'alt_kategoriler' => ['Karayolu taşımacılık', 'Kargo / dağıtım', 'ADR / tehlikeli madde', 'Akaryakıt istasyonu', 'Liman / terminal'],
            ],
            'tarim' => [
                'ad' => 'Tarım / Hayvancılık / Orman',
                'aciklama' => 'Çiftlik, sera, hayvancılık, balıkçılık, ormancılık.',
                'ikon' => 'heroicon-o-sun',
                'alt_kategoriler' => ['Tarla tarımı', 'Sera', 'Büyükbaş / küçükbaş', 'Kanatlı', 'Su ürünleri', 'Ormancılık'],
            ],
            'maden' => [
                'ad' => 'Maden / Taşocağı',
                'aciklama' => 'Yeraltı maden, açık ocak, taşocağı, mermer ocağı.',
                'ikon' => 'heroicon-o-cube',
                'alt_kategoriler' => ['Yeraltı maden', 'Açık ocak', 'Taş / mermer ocağı', 'Kırma-eleme tesisi'],
            ],
            'servis' => [
                'ad' => 'Servis / Bakım / Teknik',
                'aciklama' => 'Elektrik tesisat, doğalgaz, klima, asansör, beyaz eşya servisi.',
                'ikon' => 'heroicon-o-cog-6-tooth',
                'alt_kategoriler' => ['Elektrik tesisat', 'Doğalgaz / tesisat', 'İklimlendirme / soğutma', 'Asansör bakım', 'Beyaz eşya servisi'],
            ],
            'depo' => [
                'ad' => 'Depo / Lojistik / Antrepo',
                'aciklama' => 'Genel depo, soğuk hava, antrepo, e-ticaret fulfillment.',
                'ikon' => 'heroicon-o-archive-box',
                'alt_kategoriler' => ['Genel depo', 'Soğuk hava deposu', 'Antrepo', 'E-ticaret / fulfillment'],
            ],
            'egitim' => [
                'ad' => 'Eğitim / Okul / Kreş',
                'aciklama' => 'İlkokul, ortaokul, lise, anaokulu, üniversite, meslek lisesi.',
                'ikon' => 'heroicon-o-academic-cap',
                'alt_kategoriler' => ['Anaokulu / kreş', 'İlk / ortaokul', 'Lise', 'Meslek lisesi / atölye', 'Üniversite / yurt'],
            ],
            'eglence' => [
                'ad' => 'Eğlence / Otel / Restoran / Spor',
                'aciklama' => 'Otel, bar, AVM, sinema, spor salonu, havuz, lunapark, konser.',
                'ikon' => 'heroicon-o-sparkles',
                'alt_kategoriler' => ['Otel / konaklama', 'AVM / perakende', 'Spor salonu / havuz', 'Sinema / sahne', 'Lunapark / etkinlik'],
            ],
            'diger' => [
                'ad' => 'Diğer / Özel Sektör',
                'aciklama' => 'Berber, temizlik, güvenlik, medya, atık, matbaa, evde bakım, kurye.',
                'ikon' => 'heroicon-o-ellipsis-horizontal-circle',
                'alt_kategoriler' => ['Kişisel bakım / berber', 'Temizlik hizmetleri', 'Özel güvenlik', 'Matbaa / baskı', 'Atık / geri dönüşüm', 'Medya / prodüksiyon'],
            ],
        ],

        /*
         | Sohbet soruları (isgpratik 107-115.jpg). Sırayla sorulur.
         | tip: radyo (tek) | coklu (checkbox)
         | secenekler[]: {deger, etiket, riskler?: [madde,...]}  seçilince eklenir
         | sektorler?: [anahtar,...]  verilirse yalnız o sektörlerde sorulur
        */
        'sorular' => [
            [
                'anahtar' => 'calisan_sayisi',
                'soru' => 'İşyerinizde toplam kaç çalışan görev yapıyor?',
                'ipucu' => 'Çalışan sayısı; 6331 m.6 uyarınca İSG uzmanı, işyeri hekimi ve sağlık personeli bulundurma yükümlülüğünü, çalışan temsilcisi ve İSG kurulu zorunluluğunu belirler.',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'mikro', 'etiket' => '1-9 (mikro)'],
                    ['deger' => 'kucuk', 'etiket' => '10-49 (küçük)'],
                    ['deger' => 'orta', 'etiket' => '50-149 (orta)', 'riskler' => [[
                        'bolum' => 'Organizasyon', 'faaliyet' => 'İSG yönetimi',
                        'tehlike' => 'Çalışan temsilcisi seçilmemiş olması',
                        'risk' => 'Çalışan katılımının sağlanamaması, mevzuata aykırılık',
                        'oneri' => '6331 m.20 uyarınca çalışan temsilcisi seçilir ve eğitilir.',
                        'mevzuat' => '6331 SK m.20', 'olasilik' => 3, 'siddet' => 2,
                    ]]],
                    ['deger' => 'buyuk', 'etiket' => '150-499 (büyük)', 'riskler' => [[
                        'bolum' => 'Organizasyon', 'faaliyet' => 'İSG yönetimi',
                        'tehlike' => '50+ çalışanda İSG kurulunun kurulmamış olması',
                        'risk' => 'Kurul kararlarının alınmaması, denetimde idari yaptırım',
                        'oneri' => 'İSG kurulu kurulur, ayda en az 1 (tehlikeli) toplanır, kararlar kayıt altına alınır.',
                        'mevzuat' => 'İSG Kurulları Hakkında Yönetmelik', 'olasilik' => 3, 'siddet' => 2,
                    ]]],
                    ['deger' => 'cok_buyuk', 'etiket' => '500+ (çok büyük)', 'riskler' => [[
                        'bolum' => 'Organizasyon', 'faaliyet' => 'İSG yönetimi',
                        'tehlike' => 'Büyük ölçekli işyerinde İSG kurulu / tam zamanlı uzman eksikliği',
                        'risk' => 'İSG organizasyonunun yetersiz kalması',
                        'oneri' => 'Tam zamanlı İGU/İH görevlendirilir, İSG kurulu düzenli toplanır.',
                        'mevzuat' => '6331 SK m.6, İSG Kurulları Yön.', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                ],
            ],
            [
                'anahtar' => 'vardiya',
                'soru' => 'İşyerinde vardiya sistemi nasıl uygulanıyor?',
                'ipucu' => 'Vardiyalı çalışma yorgunluk, aydınlatma, dikkat dağılması ve psikososyal yük açısından ek risk doğurur (4857 Kanunu m.69).',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'tek', 'etiket' => 'Tek vardiya (gündüz)'],
                    ['deger' => 'iki', 'etiket' => '2 vardiya (gündüz + akşam)', 'riskler' => [[
                        'bolum' => 'Çalışma düzeni', 'faaliyet' => 'Vardiyalı çalışma',
                        'tehlike' => 'Akşam vardiyasında yetersiz aydınlatma ve yorgunluk',
                        'risk' => 'Dikkat dağılması kaynaklı kaza, kas-iskelet zorlanması',
                        'oneri' => 'Aydınlatma ölçümü (TS EN 12464-1), mola planı, vardiya rotasyonu.',
                        'mevzuat' => '4857 SK m.69, Postalar Hâlinde Çalışma Yön.', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                    ['deger' => 'uc', 'etiket' => '3 vardiya (24 saat)', 'riskler' => [[
                        'bolum' => 'Çalışma düzeni', 'faaliyet' => 'Gece vardiyası',
                        'tehlike' => 'Gece çalışması kaynaklı sirkadiyen bozukluk ve yorgunluk',
                        'risk' => 'Kaza riskinde artış, uzun vadede sağlık bozulması',
                        'oneri' => 'Gece çalışanlarına periyodik muayene (en az yılda 1), 7,5 saat sınırı, ileri rotasyon.',
                        'mevzuat' => '4857 SK m.69, Gece Çalışması Yön.', 'olasilik' => 4, 'siddet' => 3,
                    ]]],
                    ['deger' => 'esnek', 'etiket' => 'Esnek / karma çalışma'],
                    ['deger' => 'yok', 'etiket' => 'Vardiya yok / standart mesai'],
                ],
            ],
            [
                'anahtar' => 'egitimler',
                'soru' => 'Yangın güvenliği, ilk yardım, acil durum tahliye eğitimi verildi mi?',
                'ipucu' => 'Yangın eğitimi Binaların Yangından Korunması Yön. m.129, ilk yardım İlk Yardım Yön. m.16 (çok tehlikeli işyerlerinde her 10 kişide 1 sertifikalı) zorunludur.',
                'tip' => 'coklu',
                'secenekler' => [
                    ['deger' => 'yangin', 'etiket' => 'Yangın güvenliği eğitimi'],
                    ['deger' => 'ilkyardim', 'etiket' => 'İlk yardım eğitimi (sertifikalı)'],
                    ['deger' => 'tahliye', 'etiket' => 'Acil durum / tahliye eğitimi'],
                    ['deger' => 'hicbiri', 'etiket' => 'Hiçbiri verilmedi', 'riskler' => [[
                        'bolum' => 'Acil durum', 'faaliyet' => 'Eğitim',
                        'tehlike' => 'Yangın / ilk yardım / tahliye eğitimi verilmemiş olması',
                        'risk' => 'Acil durumda müdahale edememe, panik, yaralanma ve can kaybı',
                        'oneri' => 'Tüm çalışanlara yangın ve tahliye eğitimi; çok tehlikelide 10 kişide 1 sertifikalı ilk yardımcı.',
                        'mevzuat' => 'Binaların Yangından Korunması Yön. m.129, İlk Yardım Yön. m.16',
                        'olasilik' => 3, 'siddet' => 4,
                    ]]],
                ],
            ],
            [
                'anahtar' => 'tatbikat',
                'soru' => 'Acil durum tahliye tatbikatı son 12 ayda yapıldı mı?',
                'ipucu' => 'Acil Durumlar Yön. m.11 ve Yangın Yön. m.129; yılda en az 1 tatbikat zorunlu, sonuçlar kayıt altına alınır.',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'guncel', 'etiket' => 'Evet, son 12 ayda yapıldı + kayıt var'],
                    ['deger' => 'eski', 'etiket' => 'Evet ama 12 aydan eski', 'riskler' => [[
                        'bolum' => 'Acil durum', 'faaliyet' => 'Tatbikat',
                        'tehlike' => 'Tahliye tatbikatının güncel olmaması (12 aydan eski)',
                        'risk' => 'Tahliye süresinin ve rollerin sınanmamış olması',
                        'oneri' => 'Yılda en az 1 tam tahliye tatbikatı yapılır, süre ölçülür, tutanak tutulur.',
                        'mevzuat' => 'İşyerlerinde Acil Durumlar Yön. m.11', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                    ['deger' => 'teorik', 'etiket' => 'Sadece duyuru / teorik yapıldı', 'riskler' => [[
                        'bolum' => 'Acil durum', 'faaliyet' => 'Tatbikat',
                        'tehlike' => 'Uygulamalı tahliye tatbikatının yapılmamış olması',
                        'risk' => 'Gerçek acil durumda tahliye kaosu',
                        'oneri' => 'Uygulamalı (bina boşaltmalı) tatbikat yapılır, toplanma alanı sayımı yapılır.',
                        'mevzuat' => 'İşyerlerinde Acil Durumlar Yön. m.11', 'olasilik' => 3, 'siddet' => 4,
                    ]]],
                    ['deger' => 'hic', 'etiket' => 'Hayır, hiç tatbikat yapılmadı', 'riskler' => [[
                        'bolum' => 'Acil durum', 'faaliyet' => 'Tatbikat',
                        'tehlike' => 'Hiç tahliye tatbikatı yapılmamış olması',
                        'risk' => 'Acil durumda tahliye yollarının ve rollerin bilinmemesi, panik',
                        'oneri' => 'Acil durum planı hazırlanır, ekipler atanır, ilk tatbikat 60 gün içinde yapılır.',
                        'mevzuat' => 'İşyerlerinde Acil Durumlar Yön. m.11', 'olasilik' => 4, 'siddet' => 4,
                    ]]],
                ],
            ],
            [
                'anahtar' => 'yangin_altyapi',
                'soru' => 'Yangın altyapısı (acil çıkış + söndürücü) durumu nasıl?',
                'ipucu' => 'Yangın Yön. m.32 + TS EN 1838 acil aydınlatma + TS EN ISO 7010 işaretleme + panik bar (TS EN 1125). Yangın Yön. m.99 + TS ISO 11602-2; her 100 m²’de 1 adet 6 kg ABC tüp + yılda 1 dolum/test.',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'tam', 'etiket' => 'Çıkışlar işaretli/açık + 100 m²’de ABC tüp + yıllık kontrol'],
                    ['deger' => 'sondurucu_eksik', 'etiket' => 'Çıkışlar OK ama söndürücü sayı/kontrol eksik', 'riskler' => [[
                        'bolum' => 'Yangın', 'faaliyet' => 'Yangın söndürme',
                        'tehlike' => 'Yetersiz sayıda / kontrolsüz yangın söndürücü',
                        'risk' => 'Başlangıç yangınına müdahale edilememesi, yangının büyümesi',
                        'oneri' => 'Her 100 m²’ye 1 adet 6 kg ABC tüp, yılda 1 dolum/hidrostatik test, aylık gözle kontrol.',
                        'mevzuat' => 'Binaların Yangından Korunması Yön. m.99, TS ISO 11602-2', 'olasilik' => 3, 'siddet' => 4,
                    ]]],
                    ['deger' => 'cikis_eksik', 'etiket' => 'Söndürücü OK ama çıkış kilitli/işaretsiz', 'riskler' => [[
                        'bolum' => 'Yangın', 'faaliyet' => 'Tahliye',
                        'tehlike' => 'Acil çıkışların kilitli / işaretsiz / kapalı olması',
                        'risk' => 'Yangında tahliyenin engellenmesi, toplu can kaybı',
                        'oneri' => 'Panik bar (TS EN 1125), aydınlatmalı yönlendirme (TS EN 1838), çıkış önü daima açık.',
                        'mevzuat' => 'Binaların Yangından Korunması Yön. m.32', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'yok', 'etiket' => 'İkisi de yetersiz / yok', 'riskler' => [[
                        'bolum' => 'Yangın', 'faaliyet' => 'Yangın güvenliği',
                        'tehlike' => 'Yangın söndürme ve tahliye altyapısının bulunmaması',
                        'risk' => 'Yangında müdahale ve tahliye imkânsızlığı, ölüm',
                        'oneri' => 'Söndürücü, yangın dolabı, acil aydınlatma, işaretleme ve panik bar tesis edilir.',
                        'mevzuat' => 'Binaların Yangından Korunması Yön.', 'olasilik' => 4, 'siddet' => 5,
                    ]]],
                ],
            ],
            [
                'anahtar' => 'elektrik',
                'soru' => 'Elektrik güvenlik kontrolleri (30 mA kaçak akım rölesi + yıllık topraklama ölçümü) durumu?',
                'ipucu' => 'Elektrik İç Tesisleri Yön. + TS HD 60364-4-41 (30 mA KAKR zorunlu, aylık test). Topraklama Yön. m.10 (yılda 1 EKAT belgeli ölçüm).',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'tam', 'etiket' => '30 mA KAKR aylık test + son 12 ayda EKAT belgeli topraklama'],
                    ['deger' => 'topraklama_eksik', 'etiket' => 'KAKR var ama topraklama eski/yok', 'riskler' => [[
                        'bolum' => 'Elektrik', 'faaliyet' => 'Elektrik tesisatı',
                        'tehlike' => 'Topraklama ölçümünün güncel olmaması / yapılmaması',
                        'risk' => 'Gövde kaçağında elektrik çarpması, yangın',
                        'oneri' => 'Yılda 1 EKAT belgeli mühendise topraklama + pano ölçümü yaptırılır.',
                        'mevzuat' => 'Elektrik Tesislerinde Topraklamalar Yön. m.10', 'olasilik' => 3, 'siddet' => 4,
                    ]]],
                    ['deger' => 'kakr_eksik', 'etiket' => 'Topraklama güncel ama KAKR eksik/test edilmiyor', 'riskler' => [[
                        'bolum' => 'Elektrik', 'faaliyet' => 'Elektrik tesisatı',
                        'tehlike' => '30 mA kaçak akım rölesi eksik / test edilmiyor',
                        'risk' => 'Dolaylı temasta ölümcül elektrik çarpması',
                        'oneri' => 'Tüm priz hatlarına 30 mA KAKR, aylık test butonu kontrolü kayıt altına alınır.',
                        'mevzuat' => 'Elektrik İç Tesisleri Yön., TS HD 60364-4-41', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'yok', 'etiket' => 'İkisi de eksik / bilinmiyor', 'riskler' => [[
                        'bolum' => 'Elektrik', 'faaliyet' => 'Elektrik tesisatı',
                        'tehlike' => 'Elektrik güvenlik önlemlerinin (KAKR + topraklama) bulunmaması',
                        'risk' => 'Elektrik çarpması kaynaklı ölüm, elektrik yangını',
                        'oneri' => 'Yetkili firmaya tesisat kontrolü yaptırılır; KAKR + topraklama tesis edilir, EKAT raporu alınır.',
                        'mevzuat' => 'Elektrik İç Tesisleri Yön., Topraklamalar Yön.', 'olasilik' => 4, 'siddet' => 5,
                    ]]],
                ],
            ],
            [
                'anahtar' => 'havalandirma',
                'soru' => 'Mekanik havalandırma / iklimlendirme sisteminiz var ve filtreler 3 ayda 1 değişiyor mu?',
                'ipucu' => 'TS EN 16798; kapalı ofis min 6 ACH (hava değişim/saat), CO₂ < 1000 ppm. Legionella için yıllık su analizi.',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'tam', 'etiket' => 'Evet, mekanik sistem + 3 ayda 1 filtre değişim + Legionella analizi'],
                    ['deger' => 'bakimsiz', 'etiket' => 'Var ama bakımı düzensiz', 'riskler' => [[
                        'bolum' => 'Ortam', 'faaliyet' => 'İç ortam havası',
                        'tehlike' => 'İklimlendirme sisteminin bakımsız olması',
                        'risk' => 'Legionella, alerjen ve toz maruziyeti, CO₂ birikimi',
                        'oneri' => '3 ayda 1 filtre değişimi, yıllık kanal temizliği, su sistemlerinde Legionella analizi.',
                        'mevzuat' => 'TS EN 16798, İşyeri Bina ve Eklentileri Yön.', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                    ['deger' => 'dogal', 'etiket' => 'Sadece doğal havalandırma (pencere)'],
                    ['deger' => 'yetersiz', 'etiket' => 'Havalandırma yetersiz / yok', 'riskler' => [[
                        'bolum' => 'Ortam', 'faaliyet' => 'İç ortam havası',
                        'tehlike' => 'Yetersiz havalandırma',
                        'risk' => 'Solunum sıkıntısı, baş ağrısı, kirletici/gaz birikimi, verim düşüşü',
                        'oneri' => 'Mekanik havalandırma tesis edilir; ortam ölçümü (CO₂, VOC, toz) yapılır.',
                        'mevzuat' => 'İşyeri Bina ve Eklentileri Yön., TS EN 16798', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                ],
            ],
            [
                'anahtar' => 'kaza_gecmisi',
                'soru' => 'Son 12 ayda iş kazası veya ramak kala olayı yaşandı mı?',
                'ipucu' => '6331 m.14 ve SGK; iş kazası 3 iş günü içinde bildirilir + nedensellik analizi yapılır.',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'agir', 'etiket' => 'Evet, ağır kaza (yaralanma/ölüm)', 'riskler' => [[
                        'bolum' => 'Organizasyon', 'faaliyet' => 'Kaza araştırma',
                        'tehlike' => 'Son 1 yılda ağır iş kazası yaşanmış olması',
                        'risk' => 'Aynı kök nedenin tekrar ederek yeni ağır kazaya yol açması',
                        'oneri' => 'Kaza kök neden analizi (5N/balık kılçığı), DÖF açılır, benzer işler için ek önlem alınır.',
                        'mevzuat' => '6331 SK m.14', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'hafif', 'etiket' => 'Evet, hafif kaza', 'riskler' => [[
                        'bolum' => 'Organizasyon', 'faaliyet' => 'Kaza araştırma',
                        'tehlike' => 'Tekrarlayan hafif kazalar',
                        'risk' => 'Ağır kazanın habercisi olan koşulların sürmesi',
                        'oneri' => 'Hafif kaza ve ramak kala kayıtları analiz edilir, trend izlenir.',
                        'mevzuat' => '6331 SK m.14', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                    ['deger' => 'ramak', 'etiket' => 'Sadece ramak kala olayları', 'riskler' => [[
                        'bolum' => 'Organizasyon', 'faaliyet' => 'Ramak kala',
                        'tehlike' => 'Ramak kala olaylarının değerlendirilmeden kapatılması',
                        'risk' => 'Önlenebilir kazaların gerçekleşmesi',
                        'oneri' => 'Ramak kala bildirim sistemi kurulur; her olay için DÖF açılır.',
                        'mevzuat' => '6331 SK m.4', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                    ['deger' => 'yok', 'etiket' => 'Hayır, kaza yok'],
                ],
            ],
            [
                'anahtar' => 'psikososyal',
                'soru' => 'İşyerinizde psikososyal risk (stres / mobbing / aşırı iş yükü / şiddet) durumu?',
                'ipucu' => '6331 m.4 işverene tüm riskleri (psikososyal dâhil) değerlendirme yükümlülüğü verir; müşteriyle temas eden işlerde şiddet riski öne çıkar.',
                'tip' => 'radyo',
                'secenekler' => [
                    ['deger' => 'yonetiliyor', 'etiket' => 'Değerlendirildi ve önlem alınıyor'],
                    ['deger' => 'var', 'etiket' => 'Var ama sistematik ele alınmıyor', 'riskler' => [[
                        'bolum' => 'Psikososyal', 'faaliyet' => 'Çalışma ortamı',
                        'tehlike' => 'Aşırı iş yükü / stres / kişilerarası çatışma',
                        'risk' => 'Tükenmişlik, depresyon, dikkat kaybına bağlı kaza, işten ayrılma',
                        'oneri' => 'Psikososyal risk anketi, iş yükü dengelemesi, mobbing prosedürü, EAP desteği.',
                        'mevzuat' => '6331 SK m.4, ISO 45003', 'olasilik' => 3, 'siddet' => 3,
                    ]]],
                    ['deger' => 'siddet', 'etiket' => 'Müşteri/hasta kaynaklı şiddet riski var', 'riskler' => [[
                        'bolum' => 'Psikososyal', 'faaliyet' => 'Müşteri teması',
                        'tehlike' => 'Üçüncü kişi (müşteri/hasta) kaynaklı sözlü/fiziksel şiddet',
                        'risk' => 'Yaralanma, travma sonrası stres',
                        'oneri' => 'Panik butonu, güvenlik kamerası, tek başına çalışma yasağı, şiddet olay bildirimi.',
                        'mevzuat' => '6331 SK m.4', 'olasilik' => 3, 'siddet' => 4,
                    ]]],
                ],
            ],
            // Sektöre özel sorular
            [
                'anahtar' => 'insaat_yapi_turu',
                'soru' => 'Hangi tür yapı işi yapıyorsunuz?',
                'ipucu' => 'Yapı İşlerinde İSG Yön. m.3 — sınıflandırma + Sağlık Güvenlik Planı (SGP) zorunlu.',
                'tip' => 'coklu',
                'sektorler' => ['insaat'],
                'secenekler' => [
                    ['deger' => 'bina', 'etiket' => 'Konut / iş yeri binası'],
                    ['deger' => 'yol', 'etiket' => 'Yol / asfalt', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Yol yapımı',
                        'tehlike' => 'Trafiğe açık kesimde çalışma',
                        'risk' => 'Araç çarpması, sıcak asfalt yanığı',
                        'oneri' => 'Trafik yönetim planı, reflektif kıyafet (EN ISO 20471), hız kesici, sinyalizasyon.',
                        'mevzuat' => 'Yapı İşlerinde İSG Yön. Ek-4', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'kopru', 'etiket' => 'Köprü / viyadük', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Yüksekte yapı işi',
                        'tehlike' => 'Büyük yükseklikte kalıp/donatı işleri',
                        'risk' => 'Yüksekten düşme (ölüm)',
                        'oneri' => 'Kenar koruma, güvenlik ağı, yaşam hattı + tam vücut kemeri, düşüş durdurucu.',
                        'mevzuat' => 'Yapı İşlerinde İSG Yön. Ek-4', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'tunel', 'etiket' => 'Tünel', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Tünel kazısı',
                        'tehlike' => 'Kapalı alanda gaz birikimi, göçük, tozluluk',
                        'risk' => 'Boğulma, göçük altında kalma, silikozis',
                        'oneri' => 'Sürekli gaz ölçümü, zorlanmış havalandırma, tahkimat, toz bastırma, kurtarma ekibi.',
                        'mevzuat' => 'Yapı İşlerinde İSG Yön., Maden Yön.', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'catizolasyon', 'etiket' => 'Çatı / izolasyon', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Çatı işleri',
                        'tehlike' => 'Kırılgan çatı yüzeyi ve kenar boşluğu',
                        'risk' => 'Çatıdan / kenardan düşme',
                        'oneri' => 'Çatı merdiveni, yürüme yolu, kenar koruması, yaşam hattı, hava koşulu sınırı.',
                        'mevzuat' => 'Yapı İşlerinde İSG Yön. Ek-4', 'olasilik' => 4, 'siddet' => 5,
                    ]]],
                    ['deger' => 'cephe', 'etiket' => 'Cephe / mantolama', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Cephe işleri',
                        'tehlike' => 'Asılı iskele / cephe platformu kullanımı',
                        'risk' => 'Platform çökmesi, yüksekten düşme, malzeme düşmesi',
                        'oneri' => 'Asılı platform periyodik kontrolü, ikincil emniyet halatı, alt bölge bariyeri.',
                        'mevzuat' => 'İş Ekipmanları Yön., Yapı İşleri Yön.', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'yikim', 'etiket' => 'Yıkım', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Yıkım',
                        'tehlike' => 'Kontrolsüz çökme, asbest, toz',
                        'risk' => 'Ezilme, asbestoz, göçük',
                        'oneri' => 'Yıkım planı, asbest envanteri ve sökümü, sulama, sıralı yıkım, komşu yapı desteği.',
                        'mevzuat' => 'Yapı İşleri Yön., Asbestle Çalışmalarda İSG Yön.', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                    ['deger' => 'altyapi', 'etiket' => 'Altyapı (kanal/hat)', 'riskler' => [[
                        'bolum' => 'Saha', 'faaliyet' => 'Kazı',
                        'tehlike' => 'İksasız derin kazı, yer altı hatları',
                        'risk' => 'Göçük altında kalma, patlama, elektrik çarpması',
                        'oneri' => 'İksa/şev hesabı, hat sorgusu (AYKOME), kazı kenarı yük yasağı, güvenli iniş.',
                        'mevzuat' => 'Yapı İşlerinde İSG Yön. Ek-4', 'olasilik' => 3, 'siddet' => 5,
                    ]]],
                ],
            ],
        ],
    ],
];
