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

    /*
    |--------------------------------------------------------------------------
    | Eğitim Katılım Formu — isgpratik 45-59, 116-132.jpg
    |--------------------------------------------------------------------------
    | "İş Sağlığı ve Güvenliği" (varsayılan) 4 sabit blok gösterir: Genel +
    | Sağlık + Teknik konular (hepsi sabit) + İşyerine Özgü Riskler (sektöre
    | göre). Diğer tüm başlıklar (ekip eğitimleri vb.) TEK bloklu, kendi sabit
    | konu listesini gösteren "özel" eğitimlerdir.
    */
    'egitim' => [
        // Tehlike sınıfına göre yıllık periyodik eğitim süresi (Çalışanların İSG
        // Eğitimlerinin Usul ve Esasları Hak. Yön. Ek-1: 8/12/16 saat). İşe özgü
        // risk ve dinlenme dakikaları kullanıcı tarafından doğrulandı.
        'sureler' => [
            'az_tehlikeli' => ['saat' => 8, 'ise_ozgu_dk' => 90, 'dinlenme_dk' => 120],
            'tehlikeli' => ['saat' => 12, 'ise_ozgu_dk' => 135, 'dinlenme_dk' => 180],
            'cok_tehlikeli' => ['saat' => 16, 'ise_ozgu_dk' => 180, 'dinlenme_dk' => 240],
        ],

        // "İş Sağlığı ve Güvenliği" başlığının sabit 3 bloğu (dakikalar toplamı
        // genel=80, sağlık=80, teknik=120 — isgpratik ekranlarıyla birebir).
        'genel_konular' => [
            ['madde' => 'Çalışma mevzuatı ile ilgili bilgiler', 'dakika' => 20],
            ['madde' => 'Çalışanların yasal hak ve sorumlulukları', 'dakika' => 20],
            ['madde' => 'İşyeri temizliği ve düzeni', 'dakika' => 20],
            ['madde' => 'İş kazası ve meslek hastalığından doğan hukuki sonuçlar', 'dakika' => 20],
        ],
        'saglik_konulari' => [
            ['madde' => 'Meslek hastalıklarının sebepleri', 'dakika' => 20],
            ['madde' => 'Hastalıktan korunma prensipleri ve korunma teknikleri', 'dakika' => 20],
            ['madde' => 'Biyolojik ve psikososyal risk etmenleri', 'dakika' => 20],
            ['madde' => 'İlkyardım', 'dakika' => 10],
            ['madde' => 'Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı', 'dakika' => 10],
        ],
        'teknik_konular' => [
            ['madde' => 'Kimyasal, fiziksel ve ergonomik risk etmenleri', 'dakika' => 10],
            ['madde' => 'Elle kaldırma ve taşıma', 'dakika' => 10],
            ['madde' => 'Parlama, patlama', 'dakika' => 10],
            ['madde' => 'Yangın ve yangından korunma', 'dakika' => 10],
            ['madde' => 'İş ekipmanlarının güvenli kullanımı', 'dakika' => 10],
            ['madde' => 'Ekranlı araçlarla çalışma', 'dakika' => 10],
            ['madde' => 'Elektrik, tehlikeleri, riskleri ve önlemleri', 'dakika' => 10],
            ['madde' => 'İş kazalarının sebepleri ve korunma prensipleri ile teknikleri', 'dakika' => 10],
            ['madde' => 'Sağlık ve güvenlik işaretleri', 'dakika' => 10],
            ['madde' => 'Kişisel koruyucu donanım kullanımı', 'dakika' => 10],
            ['madde' => 'İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürü', 'dakika' => 10],
            ['madde' => 'Acil durumlar, tahliye ve kurtarma', 'dakika' => 10],
        ],

        // "İşyerine Özgü Riskler" bloğu — sektör seçilince 5 madde otomatik gelir;
        // ekran görüntüsünde doğrulanan sektörler bunlar. Listede olmayan bir
        // sektör için kullanıcı elle madde ekler (arayüz bunu zaten destekliyor).
        'isyerine_ozgu_sektorler' => [
            'insaat' => ['ad' => 'İnşaat', 'maddeler' => [
                'İskele ve kalıp güvenliği', 'Yüksekte çalışma önlemleri', 'Kazı işlerinde güvenlik',
                'Ağır iş makinesi kullanımı', 'Düşen nesne tehlikesi',
            ]],
            'maden' => ['ad' => 'Maden', 'maddeler' => [
                'Yeraltı madenciliği güvenliği', 'Gaz ve toz patlaması önlemleri', 'Göçük riski ve destek sistemleri',
                'Havalandırma sistemleri', 'Kurtarma odası kullanımı',
            ]],
            'tekstil' => ['ad' => 'Tekstil', 'maddeler' => [
                'İplik ve dokuma makine güvenliği', 'Toz ve lif maruziyeti', 'Boya ve kimyasal madde güvenliği',
                'Gürültüden korunma', 'Ergonomik riskler ve tekrarlayıcı hareketler',
            ]],
            'enerji_elektrik' => ['ad' => 'Enerji / Elektrik', 'maddeler' => [
                'Elektrik çarpması önlemleri', 'Enerji nakil hattı güvenliği', 'LOTO (kilitle-etiketle) sistemi',
                'Ark flash tehlikeleri', 'Trafo ve pano güvenliği',
            ]],
            'cimento_beton' => ['ad' => 'Çimento / Beton', 'maddeler' => [
                'Çimento tozu maruziyeti', 'Döner fırın güvenliği', 'Bant konveyörlerinde güvenlik',
                'Kapalı alan çalışma', 'Patlama ve parlama riskleri',
            ]],
            'nakliye_tasima' => ['ad' => 'Nakliye / Taşıma', 'maddeler' => [
                'Ticari araç sürüş güvenliği', 'Yük sabitleme ve bağlama', 'Tehlikeli madde taşımacılığı (ADR)',
                'Uzun sürüş ve yorgunluk yönetimi', 'Yük indirme-bindirme güvenliği',
            ]],
        ],

        // "Özel" (tek bloklu) eğitim başlıkları — her biri kendi sabit konu
        // listesiyle gelir; genel/sağlık/teknik/işyerine özgü ayrımı yoktur.
        'ozel_basliklar' => [
            'fiziksel_risk' => ['ad' => 'Fiziksel Risk Etmenleri', 'maddeler' => [
                'Tozlu ortamlarda çalışmalarda iş güvenliği',
                'Gürültülü ortamlarda çalışmalarda iş güvenliği',
                'Titreşimli ortamlarda çalışmalarda iş güvenliği',
                'Termal konfor',
                'Ergonomi',
                'Toz, gürültü, titreşim risk etmenleri ve maruziyet sınırları',
            ]],
            'yuksekte_calisma' => ['ad' => 'Yükseklerde Çalışma', 'maddeler' => [
                'Yüksekliğin tanımı & yüksekte çalışma ortamları',
                'Kaza istatistikleri ve yüksekten düşme şeklindeki kazaların oranı',
                'Yüksekte çalışırken dikkat edilecek hususlar',
                'Temel güvenlik kuralları',
                'İşe uygun merdiven iskele seçimi — merdiven ve iskelelerin kurulması ve sabitlenmesi',
                'Toplu koruma yöntemleri ve önemi (korkuluk, platform, güvenlik ağı, barikatlama, işaretleme vs.)',
                'Kişisel koruyucu donanımlar (standart personel koruyucu ekipmanlar)',
                'Temel emniyet ipi ile enerji tutucu sistemlerin kullanımı ve özellikleri',
                'Yüksekte çalışma sırasında olabilecek kazaların önlenmesi ve iş güvenliği performansının iyileştirilmesi',
                'Çatılarda çalışma alınacak önlemler',
                'Yüksek iş makinelerinde alınacak önlemler',
                'Yükseklikte yapılacak çalışmalarda tehlike ve risklerin önceden belirlenmesi ve önlenmesi',
                'Düşme faktörü kavramı ve önlemler',
                'Düşmeden korunmanın teorisi ve uygulaması',
                'İş planı ve alanın organizasyonu',
            ]],
            'kapali_alan' => ['ad' => 'Kapalı Alanlarda Çalışma Eğitimi', 'maddeler' => [
                'Kapalı/sınırlı alan tanımı, türleri ve sınıflandırması (I, II, III sınıf)',
                'Yasal çerçeve: 6331 sayılı İSG kanunu, ilgili yönetmelikler ve uluslararası standartlar (OSHA 1910.146)',
                'Kapalı alan kaza istatistikleri, önemi ve risk değerlendirmesi',
                'Atmosferik tehlikeler: oksijen yetersizliği, parlayıcı/patlayıcı ortam ve zehirli ortam',
                'Oksijen dengesi: güvenli aralık, yetersizlik nedenleri ve oksijence zengin ortamda artan yanma riski',
                'Boğucu ve zehirli gazlar: H2S, karbon monoksit, CO2 ve tehlikenin duyularla algılanamaması',
                'Parlayıcı/patlayıcı atmosfer: alt ve üst patlama sınırları (LEL/UEL) ve ATEX önlemleri',
                'Fiziksel ve diğer tehlikeler: gömülme/boğulma, sıkışma, termal stres, gürültü ve biyolojik etkenler',
                'Atmosfer ölçümü ve gaz dedektörü kullanımı: ölçüm sırası, katmanlı ölçüm ve sürekli izleme',
                'Gaz ölçüm cihazlarının kalibrasyonu, bump test ve alarm seviyeleri',
                'Kapalı alana güvenli giriş izni sistemi (permit-to-work), izin formu ve girişin sonlandırılması',
                'Enerji izolasyonu, kilitleme-etiketleme (LOTO), körleme, purge ve inertleme',
                'Zorlamalı (cebri) mekanik havalandırma ve ex-proof ekipman kullanımı',
                'Görev ve sorumluluklar: giren personel, bekçi/gözetmen, giriş sorumlusu ve gaz ölçüm yetkilisi',
                'İletişim ve haberleşme sistemleri: sesli, telsiz, halat işareti ve sürekli haberleşme kuralı',
                'Kişisel koruyucu donanım seçimi ve kullanımı: SCBA, hava hattı/kaçış cihazı, tam vücut kemeri ve halat',
                'Acil durum, kurtarma ve tahliye planı: kurtarma hiyerarşisi, ekipmanlar (tripod/vinç/halat) ve tatbikat',
                'Yanlış kurtarmanın ölümcül tehlikesi (çok kurbanlı ikinci ölüm), ilk yardım ve temel yaşam desteği',
            ]],
            'is_kazasi_sonrasi' => ['ad' => 'İş Kazası Sonrası İşe Dönüş Eğitimi', 'maddeler' => [
                'Yasal mevzuat: 6331 sayılı İSG kanunu md.17/3 (ilave eğitim) ve çalışanların iş eğitimlerinin usul ve esasları hakkında yönetmelik md.8 (RG 15.05.2013/28648)',
                'Yaşanan iş kazasının değerlendirilmesi: kazanın oluş şekli, sebepleri ve katkıda bulunan faktörler',
                'Kaza kök neden analizi: tehlikeli durum ve tehlikeli hareket (davranış) ayrımı',
                'Benzer kazaların önlenmesi: alınan düzeltici faaliyetler (DÖF) ve kazadan çıkarılan dersler',
                'Koruma yolları: toplu koruma önlemleri, mühendislik kontrolleri ve kişisel koruyucu donanım kullanımı',
                'Güvenli çalışma yöntemleri: işe özgü güvenli çalışma talimatları ve prosedürleri',
                'Risk değerlendirmesi: kaza sonrası güncellenen riskler ve yeni kontrol tedbirleri',
                'Makine ve iş ekipmanı güvenliği: koruyucular, acil durdurma ve güvenlik tertibatlarının kontrolü',
                'Ramak kala ve tehlikeli durum bildirimi: raporlama kültürü ve kazalardan öğrenme',
                'İşe dönüş sağlık gözetimi: işe dönüş muayenesi ve işyeri hekimi uygunluk değerlendirmesi',
                'Kaza sonrası psikososyal destek: travma sonrası iş uyum ve kaygı yönetimi',
                'Çalışanın hak ve yükümlülükleri: 6331 md.19 (çalışanların yükümlülükleri) ve md.13 (çalışmaktan kaçınma hakkı)',
                'Güvenlik kültürü: örnek olay paylaşımı, davranış odaklı güvenlik ve sürekli iyileştirme',
            ]],
            'calisan_temsilcisi' => ['ad' => 'Çalışan Temsilcisi Eğitimi', 'maddeler' => [
                'Yasal mevzuat: 6331 sayılı İSG kanunu ve çalışan temsilcisi yönetmeliği',
                'Görev ve sorumluluklar: temsilcinin yetkileri ve yükümlülükleri',
                'İletişim teknikleri: çalışanların görüşlerini alma ve işverene raporlama yöntemleri',
                'İSG kuruluna katılım: kurul toplantılarında temsil ve oy hakkı',
                'Tehlike kaynaklarını izleme: işyerindeki riskli durumları gözlemleme ve durdurma yetkisi (ciddi ve yakın tehlike)',
            ]],
            'risk_degerlendirme_ekibi' => ['ad' => 'Risk Değerlendirme Ekibi Eğitimi', 'maddeler' => [
                'Tehlike ve risk kavramları: arasındaki farklar ve tanımlama yöntemleri',
                'Risk değerlendirmesi metodolojisi: kullanılan yöntem (matris, Fine-Kinney, L-tipi vb.) hakkında teknik bilgi',
                'Adım adım risk analizi: tehlikelerin belirlenmesi, risklerin puanlanması ve kontrol tedbirlerinin kararlaştırılması',
                'Kontrol hiyerarşisi: riskle mücadelede öncelik sıralaması (eliminasyon, ikame, mühendislik önlemleri, KKD)',
                'Dokümantasyon ve takip: risk analizinin güncellenme süreleri ve aksiyon takibi',
            ]],
            'isg_kurulu' => ['ad' => 'İSG Kurulu Eğitimi', 'maddeler' => [
                'Yasal mevzuat: 6331 sayılı İSG kanunu ve İş Sağlığı ve Güvenliği Kurulları Hakkında Yönetmelik',
                'Kurulun oluşumu: üyeler, roller ve görev tanımları (başkan, İGU, hekim, İK, sivil savunma, usta, çalışan temsilcisi)',
                'Kurul kurma zorunluluğu: 50+ çalışan, sanayiden sayılan ve 6 aydan fazla süreklilik kriterleri',
                'Toplantı periyodu: tehlike sınıfına göre ayda 1 / 2 ayda 1 / 3 ayda 1 toplantı zorunluluğu',
                'Görevleri: iç yönerge hazırlama, risk değerlendirme sonuçlarını değerlendirme, iş kazası inceleme',
                'Yıllık plan hazırlama: yıllık çalışma planı, eğitim planı ve değerlendirme raporu',
                'Acil durum planlarının gözden geçirilmesi ve tatbikat değerlendirmesi',
                'Karar alma ve oy çokluğu: kurul kararlarının kayıt altına alınması ve işveren onayı',
                'Tutanak ve dokümantasyon: toplantı tutanakları, karar defteri ve denetim hazırlık',
                'Koordinasyon: alt işveren ilişkisi ve ortak çalışma alanlarında kurul iş birliği',
            ]],
            'acil_durum_koordinatoru' => ['ad' => 'Acil Durum Koordinatörü Eğitimi', 'maddeler' => [
                'Yasal mevzuat: 6331 sayılı İSG kanunu ve İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11',
                'Acil durum planı: hazırlama, güncelleme ve yasal zorunluluklar',
                'Ekip yönetimi: söndürme, kurtarma, koruma, ilkyardım ekiplerinin koordinasyonu',
                'Tatbikat planlaması: yıllık tatbikat zorunluluğu, senaryo hazırlama ve değerlendirme',
                'Tahliye planı: toplanma yerleri, kaçış yolları, kat planları ve işaretleme',
                'Kriz iletişimi: dış kurumlarla (112, itfaiye, AFAD, kolluk) koordinasyon',
                'Yangın güvenliği: yangın söndürme ekipmanları, dedektörler ve periyodik kontrol',
                'Risk değerlendirme entegrasyonu: sonuçların acil durum planına yansıtılması',
                'Kriz yönetimi ve liderlik: panik kontrolü, karar alma ve ekip yönetimi',
                'Olay sonrası: inceleme, raporlama ve düzeltici faaliyet takibi',
                'Bilgilendirme: çalışan eğitimi, ilan panosu ve tahliye krokisi güncelleme',
            ]],
            'sondurme_ekibi' => ['ad' => 'Söndürme Ekibi Eğitimi', 'maddeler' => [
                'Yasal mevzuat: İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 — söndürme ekibi tanımı ve tehlike sınıfına göre sayısal zorunluluk (1/30, 1/40, 1/50)',
                'Yangının kimyası: yanma üçgeni, yanma türleri ve yayılma yolları',
                'Yangın sınıfları ve özellikleri: A (katı), B (sıvı), C (gaz), D (metal), F (bitkisel/hayvansal yağ)',
                'Yangın söndürücü tipleri: kuru kimyevi toz (ABC), köpük, CO2, hangi sınıfta hangi söndürücü kullanılacağı',
                'Taşınabilir söndürücü kullanım tekniği (PASS): pimi çek, nozzle\'ı yangına yönelt, sık, süpürür gibi hareket et',
                'Yangın hortumu ve dolaplı sistemler (fire hose cabinet): kurulum, bağlantı ve basınçlı kullanım',
                'Yangın algılama ve uyarı sistemleri: duman/ısı dedektörleri, butonlar ve siren',
                'Güvenli müdahale prensipleri: sırt dönük müdahale etmeme, kaçış yolu bırakma, ekip halinde hareket',
                'Kişisel koruyucu donanım: yangıncı başlığı, eldiven, bot, nefes koruyucu',
                'Tatbikat ve periyodik kontrol yükümlülükleri',
            ]],
            'kurtarma_ekibi' => ['ad' => 'Kurtarma Ekibi Eğitimi', 'maddeler' => [
                'Yasal mevzuat: İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 — kurtarma ekibi tanımı ve tehlike sınıfına göre sayısal zorunluluk',
                'Kurtarma ekibinin görevleri ve ekip organizasyonu (ekip başı, görev dağılımı)',
                'Bina içi kat planları ve güvenli rota seçimi',
                'Mahsur kalan personele ulaşma yöntemleri: sistematik arama, oda işaretleme, kat tarama',
                'Personel sayımı ve toplanma alanı kontrolü',
                'Kurtarma ekipmanları: halat, sedye, taşıma battaniyesi, kesici aletler',
                'Riskli alana güvenli giriş prosedürleri: hava kontrolü, ışık, geri çekilme planı',
                'Yaralı taşıma teknikleri: sırtta taşıma, koltuk taşıma, sürüklemeyle tahliye',
                'Kapalı alan (confined space) giriş kuralları ve gözetim',
                'Koordinasyon: söndürme ve koruma ekipleriyle iş birliği, 112 ile iletişim',
            ]],
            'koruma_ekibi' => ['ad' => 'Koruma Ekibi Eğitimi', 'maddeler' => [
                'Yasal mevzuat: İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 — koruma ekibi tanımı (2021 değişikliğiyle eklenen ayrı ekip)',
                'Koruma ekibinin görevleri ve söndürme/kurtarma ekibinden ayrımı',
                'Kaçış yollarının açık tutulması ve periyodik denetimi',
                'Tahliye edilen bina/alanın güvenliğinin sağlanması: yetkisiz girişlerin engellenmesi',
                'Çalışanların güvenli alana sevki ve toplanma alanı disiplini',
                'Mülkiyet koruması: değerli evrak, bilgisayar, makine ve cihaz emniyeti',
                'Trafik ve araç yönetimi: itfaiye/ambulans araçları için yol açma',
                'Bilgilendirme ve panik kontrolü: çalışan ve ziyaretçileri yönlendirme',
                'Dış kurumlarla koordinasyon: polis, itfaiye, sağlık ekipleriyle iletişim',
                'Olay sonrası bölge güvenliğinin korunması ve olay yeri delil muhafazası',
            ]],
            'ilkyardim_ekibi' => ['ad' => 'İlk Yardım Ekibi Eğitimi (İlkyardım Yön. md.19)', 'maddeler' => [
                'Yasal mevzuat: İlkyardım Yönetmeliği md.19 — tehlike sınıfına göre sayısal zorunluluk (1/10, 1/15, 1/20) ve Sağlık Bakanlığı sertifikası şartı',
                'İlkyardımın temel ilkeleri ve ilkyardımcının sorumluluğu',
                'Olay yerinin değerlendirilmesi ve güvenlik önceliği',
                'Temel yaşam desteği (TYD): yetişkin, çocuk ve bebeklerde CPR uygulaması',
                'Otomatik eksternal defibrilatör (OED/AED) kullanımı',
                'Bilinç değerlendirme ve hava yolu açıklığı (head-tilt-chin-lift, Heimlich manevrası)',
                'Kanama kontrolü ve şok yönetimi',
                'Yara çeşitleri, yanıklar ve elektrik çarpması müdahale',
                'Kırık, çıkık, burkulma ve stabilizasyon teknikleri',
                'Zehirlenmeler, boğulma, ısı çarpması ve hipotermi müdahale',
                'Hasta taşıma teknikleri ve sedye kullanımı',
                'İlkyardım çantası içeriği, sertifika güncelleme ve yıllık tatbikat',
            ]],
            'destek_elemanlari' => ['ad' => 'Destek Elemanları (Toplu Eğitim)', 'maddeler' => [
                'Yasal mevzuat: 6331 sayılı İSG kanunu md.11/c (2021 değişikliği — söndürme, kurtarma, koruma ayrımı)',
                'Acil durum planı: işyerindeki tahliye planı, toplanma yerleri ve kaçış yolları',
                'Söndürme ekibi görevleri: yangın sınıfları, söndürücü tipleri ve müdahale teknikleri',
                'Kurtarma ekibi görevleri: bina içi kat planları, personel sayımı ve mahsur kalanlara güvenli ulaşım',
                'Koruma ekibi görevleri: kaçış yollarının açık tutulması, tahliye edilen alanın güvenliği ve mülkiyet korunması',
                'İlkyardım (Sağlık Bakanlığı sertifikalı personel için): temel yaşam desteği, kanama, yaralanmaya acil müdahale',
                'Haberleşme ve koordinasyon: acil durumlarda ekipler arası iletişim ve dış kurumlarla irtibat',
                'Tatbikat ve değerlendirme: yıllık tatbikat zorunluluğu, kayıtlar ve eksikliklerin giderilmesi',
            ]],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İSG Profesyonelleri — İGU / İşyeri Hekimi / DSP kayıtları
    |--------------------------------------------------------------------------
    | Uzmanın portföyünde çalıştığı sağlık/güvenlik profesyonelleri; her firmaya
    | bir İGU + bir İşyeri Hekimi + bir DSP atanabilir (Firma.igu_id vb.).
    | Kaşe/imza görselleri atandığı firmanın belgelerinde (Atama Yazıları vb.)
    | otomatik basılır.
    */
    'isg_profesyonelleri' => [
        'tipler' => [
            'igu' => 'İş Güvenliği Uzmanı',
            'isyeri_hekimi' => 'İşyeri Hekimi',
            'dsp' => 'Diğer Sağlık Personeli (DSP)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atama Yazıları — isgpratik 38-44.jpg
    |--------------------------------------------------------------------------
    | 10 görev tipi; 'tekli' tek bir kişiyle doldurulur (Görev Başlangıç/Bitiş
    | tarihli), 'ekip' çoklu üye seçimiyle (bir üye "baş üye" işaretlenebilir).
    | Anahtarlar, örtüştükleri yerde isg.egitim.ozel_basliklar ile birebir aynı.
    */
    'atama' => [
        'roller' => [
            'calisan_temsilcisi' => [
                'ad' => 'Çalışan Temsilcisi', 'tip' => 'tekli', 'ikon' => 'heroicon-o-user',
                'aciklama' => '6331 Sayılı İSG Kanunu md.20 uyarınca görevlendirilir.',
            ],
            'isveren_vekili' => [
                'ad' => 'İşveren Vekili', 'tip' => 'tekli', 'ikon' => 'heroicon-o-briefcase',
                'aciklama' => '6331 Sayılı İSG Kanunu md.3 (1/e ve 2. fıkra) ve 4857 Sayılı İş Kanunu md.2 uyarınca, '.
                    'işveren adına hareket eden ve iş/işyerinin yönetiminde görev alan kişi olarak görevlendirilir. '.
                    'Kanunda işveren için öngörülen sorumluluk ve zorunluluklar, devredilen yetki alanı ile sınırlı '.
                    'olarak işveren vekili hakkında da uygulanır.',
            ],
            'risk_degerlendirme_ekibi' => [
                'ad' => 'Risk Değerlendirme Ekibi', 'tip' => 'tekli', 'ikon' => 'heroicon-o-shield-exclamation',
                'aciklama' => 'İSG Risk Değerlendirmesi Yönetmeliği uyarınca oluşturulur.',
            ],
            'bilgi_sahibi' => [
                'ad' => 'Bilgi Sahibi Çalışan', 'tip' => 'tekli', 'ikon' => 'heroicon-o-information-circle',
                'aciklama' => 'İSG Risk Değerlendirmesi Yönetmeliği md.6/1-d uyarınca, işyerindeki bütün birimleri '.
                    'temsil edecek şekilde belirlenen ve iş/tehlike/riskler hakkında bilgi sahibi çalışan, risk '.
                    'değerlendirme ekibine görevlendirilir.',
            ],
            'sondurme_ekibi' => [
                'ad' => 'Söndürme Ekibi', 'tip' => 'ekip', 'ikon' => 'heroicon-o-fire',
                'aciklama' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 (2021 değişikliği) uyarınca ayrı '.
                    'ayrı görevlendirilir. Tehlike sınıfına göre her 30 / 40 / 50 çalışan için en az 1 destek '.
                    'elemanı, 10\'dan az çalışanı olan işyerlerinde tek özel eğitimli çalışan yeterlidir.',
            ],
            'kurtarma_ekibi' => [
                'ad' => 'Kurtarma Ekibi', 'tip' => 'ekip', 'ikon' => 'heroicon-o-hand-raised',
                'aciklama' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 (2021 değişikliği) uyarınca ayrı '.
                    'ayrı görevlendirilir. Tehlike sınıfına göre her 30 / 40 / 50 çalışan için en az 1 destek '.
                    'elemanı, 10\'dan az çalışanı olan işyerlerinde tek özel eğitimli çalışan yeterlidir.',
            ],
            'koruma_ekibi' => [
                'ad' => 'Koruma Ekibi', 'tip' => 'ekip', 'ikon' => 'heroicon-o-shield-check',
                'aciklama' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 (2021 değişikliği) uyarınca ayrı '.
                    'ayrı görevlendirilir. Tehlike sınıfına göre her 30 / 40 / 50 çalışan için en az 1 destek '.
                    'elemanı, 10\'dan az çalışanı olan işyerlerinde tek özel eğitimli çalışan yeterlidir.',
            ],
            'ilkyardim_ekibi' => [
                'ad' => 'İlk Yardım Ekibi', 'tip' => 'ekip', 'ikon' => 'heroicon-o-heart',
                'aciklama' => 'İlkyardım Yönetmeliği md.19 uyarınca tehlike sınıfına göre 10 / 15 / 20 çalışan için '.
                    'en az 1 Sağlık Bakanlığı sertifikalı ilkyardımcı bulundurulur.',
            ],
            'isg_kurulu' => [
                'ad' => 'İSG Kurulu', 'tip' => 'ekip', 'ikon' => 'heroicon-o-user-group',
                'aciklama' => '6331 sayılı Kanun ve İSG Kurulları Hakkında Yönetmelik kapsamında, sanayiden sayılan '.
                    've 50 ve üzeri çalışanı olan, 6 aydan fazla sürecek işyerleri için zorunludur. Üyeler: Başkan '.
                    '(işveren/vekili), İGU, İşyeri Hekimi, İnsan Kaynakları Sorumlusu, Sivil Savunma Uzmanı (varsa), '.
                    'Usta/Formen (varsa), Çalışan Temsilcisi.',
            ],
            'acil_durum_koordinatoru' => [
                'ad' => 'Acil Durum Koordinatörü', 'tip' => 'tekli', 'ikon' => 'heroicon-o-exclamation-triangle',
                'aciklama' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik md.11 kapsamında, söndürme/kurtarma/'.
                    'koruma/ilkyardım ekiplerini koordine eden ve dış kurumlarla (itfaiye, 112, AFAD) iletişimi '.
                    'sağlayan tek kişi olarak işveren tarafından görevlendirilir.',
            ],
        ],
        // İSG Kurulu ekip üyesi seçilince, her üye için firmadaki genel "gorev" alanı
        // yerine kurul içindeki görev tanımı atanabilir (İSG Kurulları Hk. Yönetmelik
        // md.6 — kurul üyeleri).
        'kurul_gorevleri' => [
            'baskan' => 'Başkan (İşveren / İşveren Vekili)',
            'igu' => 'İş Güvenliği Uzmanı',
            'isyeri_hekimi' => 'İşyeri Hekimi',
            'insan_kaynaklari' => 'İnsan Kaynakları Sorumlusu',
            'sivil_savunma' => 'Sivil Savunma Uzmanı',
            'usta_formen' => 'Usta / Formen',
            'calisan_temsilcisi' => 'Çalışan Temsilcisi',
            'dsp' => 'Diğer Sağlık Personeli (DSP)',
            'diger' => 'Diğer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İSG Kurulu Toplantısı — isgpratik yardım/kurul-toplantisi rehberi
    |--------------------------------------------------------------------------
    | Gündem ekranında "Hazır Gündem Maddeleri" kategorilere ayrılmış sabit
    | listeden tek tıkla eklenir (AI önerisi ayrı, ileride Gemini ile).
    */
    'kurul_toplantisi' => [
        'katilimci_gorevleri' => [
            'İşveren / Vekili', 'İş Güvenliği Uzmanı', 'İşyeri Hekimi', 'İnsan Kaynakları Sorumlusu',
            'Çalışan Temsilcisi (Asıl)', 'Çalışan Temsilcisi (Yedek)', 'Formen / Ustabaşı', 'Destek Elemanı', 'Diğer',
        ],
        'hazir_gundem_maddeleri' => [
            'Genel' => [
                'Bir önceki toplantı kararlarının gözden geçirilmesi',
                'İş kazası ve meslek hastalığı istatistiklerinin değerlendirilmesi',
                'Risk değerlendirmesi sonuçlarının değerlendirilmesi',
                'Yıllık çalışma planı ve eğitim planının gözden geçirilmesi',
                'Ramak kala olayların ve tespit/öneri defteri kayıtlarının değerlendirilmesi',
            ],
            'Acil Durum' => [
                'Acil durum planının ve tatbikat sonuçlarının değerlendirilmesi',
                'Yangın söndürme ve algılama sistemlerinin kontrol durumu',
                'İlk yardım, söndürme, kurtarma ve koruma ekiplerinin yeterliliği',
            ],
            'Sağlık Gözetimi' => [
                'Periyodik sağlık muayenelerinin takibi',
                'İşe giriş/periyodik muayene sonuçlarının değerlendirilmesi',
            ],
            'Eğitim' => [
                'Çalışan eğitimlerinin planlanan sürede tamamlanma durumu',
                'Yeni işe başlayan çalışanların işbaşı eğitimlerinin kontrolü',
            ],
            'Denetim' => [
                'Saha denetimlerinde tespit edilen uygunsuzlukların (DÖF) takibi',
                'Kişisel koruyucu donanım kullanım durumunun değerlendirilmesi',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eğitim Soruları — isgpratik 69-70.jpg
    |--------------------------------------------------------------------------
    | Sektör listesi risk_ai.sektorler ile ortak (aynı 13 sektör + "Genel").
    */
    'egitim_sorulari' => [
        'zorluklar' => [
            'kolay' => 'Kolay',
            'orta' => 'Orta',
            'zor' => 'Zor',
            'karisik' => 'Karışık',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DÖF Oluştur (Çoklu DÖF) — isgpratik 158.jpg
    |--------------------------------------------------------------------------
    | Saha gözetimi/denetimi sonucu birden çok Düzeltici Önleyici Faaliyet
    | maddesini tek raporda toplar. "AI ile Öneri Al" GeminiOneriDanismani'yı
    | (Tespit Öneri Defteri ile aynı) reuse eder.
    */
    'dof' => [
        'oncelikler' => [
            'dusuk' => 'Düşük',
            'orta' => 'Orta',
            'yuksek' => 'Yüksek',
            'kritik' => 'Kritik',
        ],
        'durumlar' => [
            'acik' => 'Açık',
            'devam_ediyor' => 'Devam Ediyor',
            'tamamlandi' => 'Tamamlandı',
            'ertelendi' => 'Ertelendi',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Saha Analizi — isgpratik AI SAHA ANALİZİ/1-6.jpg
    |--------------------------------------------------------------------------
    | Saha fotoğrafı Gemini vision'a gönderilir; her fotoğraftaki uygunsuzluk
    | için bulgu (tespit + öneriler + yasal gerekçe + risk derecesi) üretilir.
    | Çıktı PDF'i isgpratik'in "İSG Saha Gözetim Raporu" ile birebir aynı
    | kolon düzenini kullanır (bkz. örnek PDF: Coklu-DOF-...pdf).
    */
    'saha_analiz' => [
        'risk_dereceleri' => [
            1 => 'Çok Yüksek',
            2 => 'Yüksek',
            3 => 'Orta',
            4 => 'Düşük',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Saha Denetimi — isgpratik SAHA DENETİMİ/1-15.jpg + gerçek örnek PDF
    |--------------------------------------------------------------------------
    | "Şantiye Denetim ve Değerlendirme v1" kontrol listesi — 9 kategori,
    | 41 madde, isgpratik'ten birebir (kritik/uygulanamaz-izni bayrakları
    | gerçek PDF çıktısındaki "KRİTİK" etiketleriyle doğrulandı). {FIRMA}
    | placeholder'ı ifade metninde firma unvanıyla değiştirilir.
    | isgpratik'teki sürükle-bırak Şablon Editörü (kategori/madde
    | ekleme-çıkarma, kritik/uygulanamaz/foto bayrağı düzenleme) kapsam
    | dışı — kontrol listesi burada sabit config, ileride istenirse ayrı iş.
    */
    'saha_denetimi' => [
        'sablon_adi' => 'Şantiye Denetim ve Değerlendirme',
        'dokuman_kodu' => 'Şantiye Denetimi',
        'sonuc_secenekleri' => [
            'uygun' => 'Uygun',
            'uygun_degil' => 'Uygun Değil',
            'uygulanamaz' => 'Uygulanamaz',
        ],
        'kategoriler' => [
            'yangin' => [
                'ad' => 'Yangın',
                'maddeler' => [
                    ['kod' => '1.1', 'ifade' => 'Yangın söndürme tüpünün muayene etiketi mevcut, basıncı yeterli ve ibresi yeşil alanda mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '1.2', 'ifade' => 'Yangın söndürme cihazı {FIRMA} etiketiyle tanımlanmış mı?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '1.3', 'ifade' => 'Ekipte yangınla mücadele eğitimi almış personel bulunuyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '1.4', 'ifade' => 'Çalışan ekipte yangın battaniyesi bulunuyor mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '1.5', 'ifade' => 'Çalışma alanı yanıcı dağınıklık ve kirlilikten arındırılmış mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                ],
            ],
            'genel_konular' => [
                'ad' => 'Genel Konular',
                'maddeler' => [
                    ['kod' => '2.1', 'ifade' => 'Şantiye panosu hazırlanmış ve ekip bilgileri panoya yazılmış mı?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '2.2', 'ifade' => 'Çalışma alanı emniyet şeridiyle ayrılarak güvenli hale getirilmiş mi?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '2.3', 'ifade' => 'Risk değerlendirme formu hazırlanmış mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '2.4', 'ifade' => 'Şantiye alanı temiz ve düzenli mi?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '2.5', 'ifade' => 'Şantiye zemini kuru ve yağdan arındırılmış mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '2.6', 'ifade' => 'Geçiş yolları kablo, kanal ve hortum gibi engellerden arındırılmış mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '2.7', 'ifade' => 'Taşlama işlerinde spiral taş muhafazaları ve taş ölçüleri uygun mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                ],
            ],
            'santiye_ekipleri' => [
                'ad' => 'Şantiye Ekipleri',
                'maddeler' => [
                    ['kod' => '3.1', 'ifade' => 'Çalışma alanındaki tüm personelin bildirimi yapılmış mı?', 'kritik' => true, 'uygulanamaz_izni' => false],
                ],
            ],
            'kkd' => [
                'ad' => 'Kişisel Koruyucu Donanım',
                'maddeler' => [
                    ['kod' => '4.1', 'ifade' => '{FIRMA} logolu iş elbiseleri kullanılıyor mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '4.2', 'ifade' => 'Kişisel koruyucu donanımlar kullanılabilir durumda mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '4.3', 'ifade' => 'Göz solüsyonu mevcut ve ekibin erişiminde mi?', 'kritik' => false, 'uygulanamaz_izni' => true],
                ],
            ],
            'elektrik' => [
                'ad' => 'Elektrikli Cihazlar',
                'maddeler' => [
                    ['kod' => '5.1', 'ifade' => 'Elektrik kabloları tehlikeli ek, ezilme ve deformasyon içermiyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '5.2', 'ifade' => 'Elektrik kabloları ve cihazlar dış etkilerden korunmuş mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '5.3', 'ifade' => 'Elektrik bağlantı prizleri sağlam mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '5.4', 'ifade' => 'Elektrik bağlantısı uygun akımdaki prizden alınıyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                ],
            ],
            'kaynak' => [
                'ad' => 'Kaynak İşleri',
                'maddeler' => [
                    ['kod' => '6.1', 'ifade' => 'Basınçlı tüpler dik pozisyonda ve sabitlenmiş mi?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '6.2', 'ifade' => 'Basınçlı tüpler için taşıma arabası mevcut mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '6.3', 'ifade' => 'Basınçlı tüplerin manometreleri çalışır durumda mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '6.4', 'ifade' => 'Basınçlı tüplerin koruyucu başlıkları mevcut mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '6.5', 'ifade' => 'Sıcak çalışma izni alınıyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '6.6', 'ifade' => 'Kaynak şase uçları sağlam mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                ],
            ],
            'yukseklik' => [
                'ad' => 'Yüksekte Çalışma',
                'maddeler' => [
                    ['kod' => '7.1', 'ifade' => 'Merdiven güvenli kullanıma uygun mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '7.2', 'ifade' => 'Rampalar ve çalışma platformlarında korkuluk bulunuyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '7.3', 'ifade' => 'Paraşüt tipi emniyet kemeri kullanılıyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '7.4', 'ifade' => 'Emniyet kemeri halatı güvenli ankraja bağlı mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '7.5', 'ifade' => 'Manlift üzerinde {FIRMA} tanımlaması mevcut mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '7.6', 'ifade' => 'Manlift periyodik muayenesi güncel mi?', 'kritik' => true, 'uygulanamaz_izni' => true],
                ],
            ],
            'santiye_duzeni' => [
                'ad' => 'Şantiye Düzeni',
                'maddeler' => [
                    ['kod' => '8.1', 'ifade' => 'Şantiye çalışma arabası ekibin yanında mı?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '8.2', 'ifade' => 'Uygun aydınlatma sağlanmış mı?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '8.3', 'ifade' => 'Çalışma ortamı hareket engellerinden arındırılmış mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '8.4', 'ifade' => 'İskele ve manlift yolları güvenli mi?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '8.5', 'ifade' => 'Elektrikli el aletleri güvenli depolanıyor mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '8.6', 'ifade' => 'Zararlı hava, partikül, buhar ve gaz riski kontrol altında mı?', 'kritik' => true, 'uygulanamaz_izni' => true],
                ],
            ],
            'calisan_davranislari' => [
                'ad' => 'Çalışan Davranışları',
                'maddeler' => [
                    ['kod' => '9.1', 'ifade' => 'Yalnız belirlenmiş alanlarda sigara içiliyor mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                    ['kod' => '9.2', 'ifade' => 'Onaylanmamış materyal veya tehlikeli alet kullanılmıyor mu?', 'kritik' => true, 'uygulanamaz_izni' => true],
                    ['kod' => '9.3', 'ifade' => 'Dinlenme yalnız belirlenmiş alanlarda yapılıyor mu?', 'kritik' => false, 'uygulanamaz_izni' => true],
                ],
            ],
        ],
        'guvenlik_uyarilari' => [
            'Kişisel koruyucu donanımlar sürekli kullanılmalıdır.',
            'Sigara yalnız belirlenmiş alanlarda içilmelidir.',
            'Makine koruyucuları yalnız enerji kesildikten sonra çıkarılmalıdır.',
            'Yetki ve izin bulunmayan bölümlerde çalışma yapılmamalıdır.',
            'Sıcak çalışma öncesinde ortam kontrolü ve çalışma izni alınmalıdır.',
            'Yükseklte çalışma öncesinde uygun KKD ve izin hazır olmalıdır.',
            'Dinlenme, yeme ve içme yalnız belirlenmiş alanlarda yapılmalıdır.',
            'Taşıma ekipmanlarını yalnız yetkili kişiler kullanmalıdır.',
            'Çalışmalar firma İSG kurallarına uygun yürütülmelidir.',
        ],
        'kkd_secenekleri' => [
            'Baret', 'Koruyucu gözlük', 'İş elbisesi', 'İş ayakkabısı',
            'Kulak koruyucu', 'Koruyucu maske', 'Koruyucu eldiven', 'Reflektif yelek',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tespit ve Öneri Defteri — isgpratik 65-66.jpg
    |--------------------------------------------------------------------------
    | Hazır öneri kataloğu, isgpratik'teki "171 madde" kataloğunun bir alt
    | kümesi — kategorilere ayrılmış, öncelik etiketli, mevzuat dayanaklı.
    | Zamanla genişletilebilir.
    */
    'tespit_oneri' => [
        'katalog' => [
            'Yönetim Sistemi ve Dokümantasyon' => [
                ['oncelik' => 'yuksek', 'tespit' => 'İşyerinde güncel ve onaylı İSG Risk Değerlendirmesi raporunun bulunmadığı/süresinin dolduğu tespit edilmiştir.', 'oneri' => 'Tehlike sınıfına uygun süre içinde (çok tehlikeli 2, tehlikeli 4, az tehlikeli 6 yıl) işyerine özgü risk değerlendirmesi yapılmalı/yenilenmeli ve uygulamaya konulmalıdır.', 'dayanak' => '6331 s.K. m.10; İSG Risk Değerlendirmesi Yönetmeliği'],
                ['oncelik' => 'yuksek', 'tespit' => 'İş kazalarının yasal süresinde SGK\'ya bildirilmediği/kayıt altına alınmadığı tespit edilmiştir.', 'oneri' => 'Tüm iş kazaları kazadan sonraki 3 iş günü içinde SGK\'ya bildirilmeli ve kaza inceleme raporu düzenlenmelidir.', 'dayanak' => '6331 s.K. m.14'],
                ['oncelik' => 'orta', 'tespit' => 'İşyeri hekimi ve iş güvenliği uzmanınca hazırlanması gereken yıllık çalışma planının bulunmadığı görülmüştür.', 'oneri' => 'İSG profesyonellerince yıllık çalışma planı hazırlanmalı, eğitim/ölçüm/muayene takvimi belirlenmeli ve işverene sunulmalıdır.', 'dayanak' => 'İSG Hizmetleri Yönetmeliği'],
                ['oncelik' => 'orta', 'tespit' => 'İşyerinde çalışan sayısı ve tehlike sınıfı gereği bulunması gereken İSG Kurulunun kurulmadığı/toplantıların yapılmadığı görülmüştür.', 'oneri' => '50 ve üzeri çalışanı olan ve 6 aydan fazla süren işlerde İSG Kurulu oluşturulmalı, toplantılar düzenli yapılarak karar defterine işlenmelidir.', 'dayanak' => '6331 s.K. m.22; İSG Kurulları Hakkında Yönetmelik'],
            ],
            'Kişisel Koruyucu Donanım' => [
                ['oncelik' => 'yuksek', 'tespit' => 'Çalışanların işin gerektirdiği kişisel koruyucu donanımı (baret, iş ayakkabısı, eldiven vb.) kullanmadığı tespit edilmiştir.', 'oneri' => 'Risk değerlendirmesinde belirlenen KKD\'ler ücretsiz temin edilmeli, kullanımı denetlenmeli ve KKD tutanağı ile teslim edilmelidir.', 'dayanak' => 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik'],
                ['oncelik' => 'orta', 'tespit' => 'KKD teslim tutanaklarının imzalı/eksiksiz olmadığı görülmüştür.', 'oneri' => 'Her çalışana teslim edilen KKD için imzalı teslim tutanağı düzenlenmeli ve özlük dosyasında saklanmalıdır.', 'dayanak' => 'KKD Yönetmeliği m.6'],
            ],
            'Elektrik ve Yangın Güvenliği' => [
                ['oncelik' => 'yuksek', 'tespit' => 'Yangın söndürme cihazlarının periyodik bakım/dolum kontrolünün süresi geçmiş etiketli olduğu tespit edilmiştir.', 'oneri' => 'Yangın söndürme cihazları yıllık periyodik bakıma tabi tutulmalı, muayene etiketleri güncel tutulmalıdır.', 'dayanak' => 'Binaların Yangından Korunması Hakkında Yönetmelik'],
                ['oncelik' => 'yuksek', 'tespit' => 'Elektrik pano ve tesisatının periyodik topraklama/izolasyon ölçümünün yapılmadığı tespit edilmiştir.', 'oneri' => 'Elektrik tesisatı yılda en az 1 kez yetkili kişi/kuruluşa periyodik kontrol yaptırılmalı, ölçüm raporları saklanmalıdır.', 'dayanak' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği Ek-3'],
            ],
            'Makine ve Ekipman Güvenliği' => [
                ['oncelik' => 'yuksek', 'tespit' => 'Kaldırma ve iletme ekipmanlarının (vinç, forklift vb.) periyodik kontrolünün yapılmadığı/belgesinin bulunmadığı tespit edilmiştir.', 'oneri' => 'İş ekipmanları yönetmeliği Ek-3 kapsamında periyodik kontroller yaptırılmalı, kontrol raporları işyerinde bulundurulmalıdır.', 'dayanak' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği'],
                ['oncelik' => 'orta', 'tespit' => 'Makinelerin hareketli aksamlarında koruyucu (siper) bulunmadığı görülmüştür.', 'oneri' => 'Kesme/sıkışma riski taşıyan hareketli aksamlara uygun koruyucu/siper takılmalı, acil durdurma butonu erişilebilir olmalıdır.', 'dayanak' => 'Makina Emniyeti Yönetmeliği'],
            ],
            'Sağlık Gözetimi ve Eğitim' => [
                ['oncelik' => 'orta', 'tespit' => 'Çalışanların periyodik sağlık muayenelerinin tehlike sınıfına göre belirlenen sürelerde yapılmadığı tespit edilmiştir.', 'oneri' => 'İşe giriş ve periyodik muayeneler işyeri hekimince tehlike sınıfına uygun aralıklarla (çok tehlikeli yılda 1, tehlikeli 3 yılda 1, az tehlikeli 5 yılda 1) tekrarlanmalıdır.', 'dayanak' => 'İşyeri Hekimi ve Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik'],
                ['oncelik' => 'orta', 'tespit' => 'Yeni işe başlayan çalışanlara işbaşı/oryantasyon eğitimi verilmediği/tutanağa bağlanmadığı görülmüştür.', 'oneri' => 'İşe yeni başlayan her çalışan için işbaşı eğitimi verilmeli ve tek sayfalık tutanakla belgelendirilmelidir.', 'dayanak' => 'Çalışanların İSG Eğitimlerinin Usul ve Esasları Hak. Yönetmelik'],
            ],
            'Acil Durum' => [
                ['oncelik' => 'yuksek', 'tespit' => 'İşyerinde yürürlükte bir acil durum planının bulunmadığı/güncel olmadığı tespit edilmiştir.', 'oneri' => 'İşyerine özgü acil durum planı hazırlanmalı, tahliye planı ve toplanma yeri işaretlenmelidir.', 'dayanak' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik'],
                ['oncelik' => 'orta', 'tespit' => 'Yıllık tatbikat yapılmadığı/tatbikat tutanağının bulunmadığı görülmüştür.', 'oneri' => 'Acil durum planı doğrultusunda yılda en az 1 tatbikat yapılmalı ve tutanakla belgelendirilmelidir.', 'dayanak' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik m.13'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KKD Zimmet Formu — isgpratik 71-75.jpg
    |--------------------------------------------------------------------------
    | 6 kategori, isgpratik ekranlarından birebir (madde adı + TS EN standardı).
    */
    'kkd' => [
        'kategoriler' => [
            'bas_yuz' => ['ad' => 'Baş ve Yüz Koruyucular', 'maddeler' => [
                ['ad' => 'Endüstriyel Emniyet Bareti', 'standart' => 'TS EN 397'],
                ['ad' => 'Endüstriyel Darbe Bağlıklı Kep Tipi', 'standart' => 'TS EN 812'],
                ['ad' => 'Çene Kayışlı Yüksekte Çalışma Bareti', 'standart' => 'TS EN 397 / TS EN 12492'],
                ['ad' => 'Elektriksel Yalıtımlı Baret', 'standart' => 'TS EN 50365'],
                ['ad' => 'Göz Koruyucu (Koruyucu Gözlük)', 'standart' => 'TS EN 166'],
                ['ad' => 'Kimyasal Sıçrama Gözlüğü', 'standart' => 'TS EN 166'],
                ['ad' => 'Yüz Siperi', 'standart' => 'TS EN 166'],
                ['ad' => 'Kaynak Maskesi / Yüz Siperi', 'standart' => 'TS EN 175'],
                ['ad' => 'Kaynak Gözlüğü / Filtreli Kaynak Camı', 'standart' => 'TS EN 169 / TS EN 379'],
                ['ad' => 'Isı ve Erimiş Metal Sıçramasına Karşı Yüz Siperi', 'standart' => 'TS EN 166'],
                ['ad' => 'Lazer Koruyucu Gözlük', 'standart' => 'TS EN 207'],
                ['ad' => 'Radyasyon Koruyucu Kurşun Gözlük', 'standart' => 'TS EN 61331'],
                ['ad' => 'Kumlama Başlığı / Vizörü', 'standart' => 'TS EN 166 / TS EN 14594'],
            ]],
            'isitme' => ['ad' => 'İşitme Koruyucuları', 'maddeler' => [
                ['ad' => 'Kulak Tıkacı', 'standart' => 'TS EN 352-2'],
                ['ad' => 'Kulaklık (Mansonlu)', 'standart' => 'TS EN 352-1'],
                ['ad' => 'Barete Takılabilir Kulaklık', 'standart' => 'TS EN 352-3'],
                ['ad' => 'Seviye Bağımlı Elektronik Kulaklık', 'standart' => 'TS EN 352-4'],
                ['ad' => 'İletişimli Gürültü Önleyici Kulaklık', 'standart' => 'TS EN 352-6'],
            ]],
            'solunum' => ['ad' => 'Solunum Koruyucuları', 'maddeler' => [
                ['ad' => 'Toz Maskesi (FFP1/FFP2/FFP3)', 'standart' => 'TS EN 149'],
                ['ad' => 'FFP3 Partikül Respiratörü', 'standart' => 'TS EN 149'],
                ['ad' => 'Partikül Filtresi (P1/P2/P3)', 'standart' => 'TS EN 143'],
                ['ad' => 'Gaz Filtresi / Kombine Filtre', 'standart' => 'TS EN 14387'],
                ['ad' => 'Kaynak Dumanı Respiratörü', 'standart' => 'TS EN 149 / TS EN 143'],
                ['ad' => 'Motorlu Hava Temizleyici Solunum Cihazı (PAPR)', 'standart' => 'TS EN 12941 / TS EN 12942'],
                ['ad' => 'Temiz Hava Tüplü Solunum Cihazı (SCBA)', 'standart' => 'TS EN 137'],
                ['ad' => 'Hava Beslemeli Solunum Cihazı', 'standart' => 'TS EN 14594'],
                ['ad' => 'Kumlama İçin Hava Beslemeli Solunum Başlığı', 'standart' => 'TS EN 14594'],
                ['ad' => 'Tıbbi / Cerrahi Maske', 'standart' => 'TS EN 14683'],
            ]],
            'el_kol' => ['ad' => 'El ve Kol Koruyucuları', 'maddeler' => [
                ['ad' => 'Mekanik Risklere Karşı Eldiven', 'standart' => 'TS EN 388'],
                ['ad' => 'Kesilmeye Dirençli Eldiven', 'standart' => 'TS EN 388'],
                ['ad' => 'Kimyasal Maddelere Karşı Eldiven', 'standart' => 'TS EN ISO 374'],
                ['ad' => 'Asit ve Alkaliye Dayanıklı Eldiven', 'standart' => 'TS EN ISO 374'],
                ['ad' => 'Tek Kullanımlık Nitril Eldiven', 'standart' => 'TS EN ISO 374'],
                ['ad' => 'Gıda Temasına Uygun Eldiven', 'standart' => 'TS EN 1186 / TS EN 388'],
                ['ad' => 'Isı Risklere Karşı Eldiven', 'standart' => 'TS EN 407'],
                ['ad' => 'Soğuğa Karşı Eldiven', 'standart' => 'TS EN 511'],
                ['ad' => 'Kriyojenik Sıvılara Karşı Eldiven', 'standart' => 'TS EN 511 / TS EN 388'],
                ['ad' => 'Kaynakçı Eldiveni', 'standart' => 'TS EN 12477'],
                ['ad' => 'Elektriksel Yalıtımlı Eldiven', 'standart' => 'TS EN 60903'],
                ['ad' => 'Antistatik / ESD Eldiven', 'standart' => 'TS EN 16350'],
                ['ad' => 'Titreşim Azaltıcı Eldiven', 'standart' => 'TS EN ISO 10819'],
                ['ad' => 'Zincir Örgü Kesilme Eldiveni', 'standart' => 'TS EN 1082'],
                ['ad' => 'Radyasyon Koruyucu Eldiven', 'standart' => 'TS EN 421'],
                ['ad' => 'Kesilmeye Dirençli Kolluk', 'standart' => 'TS EN 388'],
                ['ad' => 'Kaynak ve Isı Risklere Karşı Deri Kolluk', 'standart' => 'TS EN ISO 1611'],
            ]],
            'ayak_bacak' => ['ad' => 'Ayak ve Bacak Koruyucuları', 'maddeler' => [
                ['ad' => 'Emniyet Ayakkabısı (Çelik/Kompozit Burunlu)', 'standart' => 'TS EN ISO 20345'],
                ['ad' => 'İş Ayakkabısı (Burun Korumasız)', 'standart' => 'TS EN ISO 20347'],
                ['ad' => 'Elektriksel Yalıtımlı Ayakkabı', 'standart' => 'TS EN 50321'],
                ['ad' => 'Yanmaz Tabanlı Bot', 'standart' => 'TS EN ISO 20349'],
                ['ad' => 'Kimyasala Dayanıklı Çizme', 'standart' => 'TS EN ISO 20345 / TS EN 13832'],
                ['ad' => 'Kaymaz Tabanlı İş Ayakkabısı', 'standart' => 'TS EN ISO 20347'],
                ['ad' => 'Soğuk Depo Botu', 'standart' => 'TS EN ISO 20345 / TS EN 511'],
                ['ad' => 'Antistatik / ESD Ayakkabı', 'standart' => 'TS EN ISO 20345 / TS EN 61340-5-1'],
                ['ad' => 'Metatars Koruyuculu Emniyet Ayakkabısı', 'standart' => 'TS EN ISO 20345'],
                ['ad' => 'Zincir Testere Koruyucu Bot', 'standart' => 'TS EN 17249'],
                ['ad' => 'Su Geçirmez İş Çizmesi', 'standart' => 'TS EN ISO 20345'],
                ['ad' => 'Dizlik / Diz Koruyucu', 'standart' => 'TS EN 14404'],
                ['ad' => 'Kaynakçı Tozluğu / Bacak Koruyucu', 'standart' => 'TS EN ISO 1611'],
            ]],
            'govde_yukseklik' => ['ad' => 'Gövde ve Yükseklerde Çalışma Ekipmanları', 'maddeler' => [
                ['ad' => 'Tam Vücut Kuşak Sistemi (Paraşüt Tipi)', 'standart' => 'TS EN 361'],
                ['ad' => 'Lanyard (Bağlantı Halatı)', 'standart' => 'TS EN 354'],
                ['ad' => 'Enerji Absorbe Edici (Şok Emici)', 'standart' => 'TS EN 355'],
                ['ad' => 'Geri Sarımlı Düşmeyi Durdurucu', 'standart' => 'TS EN 360'],
                ['ad' => 'Yatay / Dikey Yaşam Hattı', 'standart' => 'TS EN 795 / TS EN 353'],
                ['ad' => 'Ankraj Noktası / Ankraj Sapanı', 'standart' => 'TS EN 795'],
                ['ad' => 'Konumlandırma Kemeri ve Halatı', 'standart' => 'TS EN 358'],
                ['ad' => 'Kapalı Alan Kurtarma Tripodu', 'standart' => 'TS EN 795'],
                ['ad' => 'Kurtarma Vinci / Kaldırma Cihazı', 'standart' => 'TS EN 1496'],
                ['ad' => 'Yüksek Görünürlüklü İkaz Yeleği', 'standart' => 'TS EN 20471'],
                ['ad' => 'Yüksek Görünürlüklü Pantolon / Parka', 'standart' => 'TS EN 20471'],
                ['ad' => 'Isıya Dayanıklı Elbise', 'standart' => 'TS EN 11612'],
                ['ad' => 'Alev Geciktirici Koruyucu Elbise', 'standart' => 'TS EN 11612 / TS EN 14116'],
                ['ad' => 'Kaynakçı Deri Önlüğü / Ceketi', 'standart' => 'TS EN 11611'],
                ['ad' => 'Elektrik Arkına Karşı Koruyucu Elbise', 'standart' => 'IEC 61482 / TS EN 11612'],
                ['ad' => 'Kimyasal Koruyucu Tulum', 'standart' => 'TS EN 14605 / TS EN 13034'],
                ['ad' => 'Tek Kullanımlık Tip 5/6 Koruyucu Tulum', 'standart' => 'TS EN ISO 13982-1 / TS EN 13034'],
                ['ad' => 'Biyolojik Risklere Karşı Koruyucu Tulum', 'standart' => 'TS EN 14126'],
                ['ad' => 'Sıvı Geçirmez Önlük', 'standart' => 'TS EN 14605 / TS EN 13034'],
                ['ad' => 'Laboratuvar Önlüğü', 'standart' => 'TS EN 13034 / TS EN 14126'],
                ['ad' => 'Antistatik Koruyucu Elbise', 'standart' => 'TS EN 1149-5'],
                ['ad' => 'ESD Önlük / Temiz Oda Önlüğü', 'standart' => 'TS EN 61340-5-1'],
                ['ad' => 'Soğuk Depo Montu / Tulumu', 'standart' => 'TS EN 342'],
                ['ad' => 'Yağmur ve Dış Ortam Koruyucu Elbisesi', 'standart' => 'TS EN 343'],
                ['ad' => 'Can Yeleği / Yüzdürme Yardımcısı', 'standart' => 'TS EN 12402'],
                ['ad' => 'Radyasyon Koruyucu Kurşun Önlük', 'standart' => 'TS EN 61331'],
                ['ad' => 'Zincir Testere Koruyucu Pantolonu', 'standart' => 'TS EN 11393'],
                ['ad' => 'Yangın Müdahale Koruyucu Elbisesi', 'standart' => 'TS EN 469'],
            ]],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İş İzin Formu (Permit to Work) — isgpratik 76-78.jpg
    |--------------------------------------------------------------------------
    | Güvenlik önlemi maddelerinin 'tur' alanı null ise her izinde gösterilir,
    | doluysa yalnız o izin türü seçiliyken gösterilir.
    */
    'is_izin' => [
        'turler' => [
            'sicak_is' => 'Sıcak İş Çalışması',
            'yukseklik' => 'Yüksekte Çalışma',
            'kapali_alan' => 'Kapalı Alan',
            'elektrik' => 'Elektrik Çalışması',
        ],
        'guvenlik_onlemleri' => [
            ['madde' => 'Çalışma alanı sınırlandırıldı / Uyarı levhaları asıldı', 'tur' => null],
            ['madde' => 'Acil durum planı ve iletişim hazır', 'tur' => null],
            ['madde' => 'İlk yardım malzemeleri kontrol edildi', 'tur' => null],
            ['madde' => 'Çalışanlar iş güvenliği eğitimi aldı', 'tur' => null],
            ['madde' => 'Risk değerlendirmesi yapıldı ve paylaşıldı', 'tur' => null],
            ['madde' => 'Yangın söndürme tüpü hazır', 'tur' => 'sicak_is'],
            ['madde' => 'Yanıcı malzemeler uzaklaştırıldı', 'tur' => 'sicak_is'],
            ['madde' => 'Yangın gözetlemecisi görevlendirildi', 'tur' => 'sicak_is'],
            ['madde' => 'Yaşam hattı / Emniyet kemeri kontrol edildi', 'tur' => 'yukseklik'],
            ['madde' => 'İskele ve platformlar kontrol edildi', 'tur' => 'yukseklik'],
            ['madde' => 'Düşme önleme sistemleri kuruldu', 'tur' => 'yukseklik'],
            ['madde' => 'Gaz ölçümü yapıldı ve uygun', 'tur' => 'kapali_alan'],
            ['madde' => 'Havalandırma sistemi çalışıyor', 'tur' => 'kapali_alan'],
            ['madde' => 'Kurtarma ekipmanları hazır', 'tur' => 'kapali_alan'],
            ['madde' => 'Enerji kesildi ve kilitlendi (LOTO)', 'tur' => 'elektrik'],
            ['madde' => 'Topraklama kontrolü yapıldı', 'tur' => 'elektrik'],
        ],
        'kkd_secenekleri' => [
            'Baret', 'İş Ayakkabısı', 'Reflektörlü Yelek', 'Koruyucu Gözlük',
            'Kaynak Maskesi', 'Emniyet Kemeri', 'Gaz Maskesi', 'Kulaklık',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İSG Ceza ve Tebliğ Tutanağı — isgpratik 79-80.jpg
    |--------------------------------------------------------------------------
    */
    'ceza_teblig' => [
        'ihlal_kategorileri' => [
            'Kişisel Koruyucu Donanım' => [
                ['madde' => 'KKD kullanmama', 'dayanak' => '6331 s.K. m.19/2-b; KKD Yönetmeliği m.10'],
                ['madde' => 'Emniyet kemeri takmama (yüksekte çalışma)', 'dayanak' => '6331 s.K. m.19/2-b; Yapı İşlerinde İSG Yönetmeliği'],
                ['madde' => "KKD'yi amacına uygun kullanmama / koruma", 'dayanak' => '6331 s.K. m.19/2-b; 4857 s.K. m.25-II/(ı)'],
            ],
            'Makine ve Ekipman' => [
                ['madde' => 'Güvenlik tertibatını devre dışı bırakma', 'dayanak' => '6331 s.K. m.19/2-a,c; 4857 s.K. m.25-II/(ı)'],
                ['madde' => 'Yetkisiz makine/ekipman kullanımı', 'dayanak' => '6331 s.K. m.19/2-ç; İş Ekipmanları Yönetmeliği'],
                ['madde' => 'Arızalı ekipmanla çalışmaya devam etme', 'dayanak' => '6331 s.K. m.19/2-e'],
                ['madde' => 'Ekipmanı talimata aykırı kullanma', 'dayanak' => '6331 s.K. m.19/2-ç'],
            ],
        ],
        'yaptirimlar' => [
            'sozlu_uyari' => ['ad' => 'Sözlü Uyarı', 'aciklama' => 'İlk/hafif ihlalde sözlü ikaz; tutanakla kayıt altına alınır.'],
            'yazili_ihtar' => ['ad' => 'Yazılı İhtar', 'aciklama' => 'Tekrarında fesih sürecine dayanak olacak yazılı uyarı.'],
            'ucret_kesme' => ['ad' => 'Ücret Kesme Cezası', 'aciklama' => '4857 md.38 — ayda en fazla iki gündelik kesilebilir.'],
            'yazili_savunma' => ['ad' => 'Yazılı Savunma Talebi', 'aciklama' => 'Çalışandan süre verilerek yazılı savunma istenir.'],
        ],

        // İşverene İPC (İdari Para Cezası) Tebliği — 6331 s.K. m.26 kapsamındaki
        // ihlal başlıkları. Ceza tutarları her yıl yeniden değerleme oranıyla
        // güncellendiğinden burada SABİT TL tutarı YOK — resmi tebligattaki
        // tutar kullanıcı tarafından girilir.
        'ipc_maddeleri' => [
            ['baslik' => 'Risk Değerlendirmesi Yaptırmama', 'aciklama' => '6331 s.K. m.10 uyarınca risk değerlendirmesinin yapılmaması/yaptırılmaması.'],
            ['baslik' => 'İSG Hizmeti Sağlamama', 'aciklama' => '6331 s.K. m.6, m.8 uyarınca İGU/işyeri hekimi görevlendirilmemesi veya OSGB hizmeti alınmaması.'],
            ['baslik' => 'Acil Durum Planı Eksikliği', 'aciklama' => '6331 s.K. m.11, m.12 uyarınca acil durum planının hazırlanmaması/tatbikat yapılmaması.'],
            ['baslik' => 'İş Kazası/Meslek Hastalığı Bildirim İhlali', 'aciklama' => '6331 s.K. m.14 uyarınca iş kazası/meslek hastalığının süresinde bildirilmemesi.'],
            ['baslik' => 'Sağlık Gözetimi Yaptırmama', 'aciklama' => '6331 s.K. m.15 uyarınca işe giriş/periyodik sağlık muayenelerinin yaptırılmaması.'],
            ['baslik' => 'Eğitim / Bilgilendirme Eksikliği', 'aciklama' => '6331 s.K. m.16, m.17 uyarınca çalışanlara İSG eğitimi verilmemesi/bilgilendirme yapılmaması.'],
            ['baslik' => 'Çalışan Temsilcisi / Kurul Eksikliği', 'aciklama' => '6331 s.K. m.18, m.22 uyarınca çalışan temsilcisi görevlendirilmemesi veya İSG kurulu oluşturulmaması.'],
            ['baslik' => 'Koruyucu / Önleyici Tedbir Eksikliği', 'aciklama' => '6331 s.K. m.4, m.5 uyarınca tespit edilen risklere karşı gerekli önlemlerin alınmaması.'],
            ['baslik' => 'Durdurma Kararına Uymama', 'aciklama' => '6331 s.K. m.25 uyarınca işin durdurulması kararına rağmen faaliyetin sürdürülmesi.'],
            ['baslik' => 'Diğer', 'aciklama' => 'Yukarıdaki kategorilere girmeyen, tebligatta belirtilen diğer ihlal.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İş Kazası Raporu — isgpratik'te ilgili ekran görüntüsü yok (planNotu'ndaki
    | 16.jpg mevcut değil); 6331 s.K. ve standart kaza inceleme raporu formatına
    | (5N1K + kök neden analizi) göre kuruldu.
    |--------------------------------------------------------------------------
    */
    'is_kazasi' => [
        'kaza_turleri' => [
            'dusme' => 'Düşme (aynı seviye / yükseklikten)',
            'carpma' => 'Çarpma / Bir Cisme Çarpma',
            'sikisma' => 'Sıkışma / Ezilme',
            'kesilme' => 'Kesilme / Delinme',
            'yanik' => 'Yanık (termal/kimyasal)',
            'elektrik' => 'Elektrik Çarpması',
            'kimyasal' => 'Kimyasal Maruziyet',
            'trafik' => 'İş Trafiği Kazası',
            'diger' => 'Diğer',
        ],
        'agirlik_dereceleri' => [
            'hafif' => 'Hafif (ilkyardım, işe devam)',
            'yarali' => 'Yaralanmalı (iş göremezlik / kayıp gün var)',
            'agir' => 'Ağır Yaralanma / Uzuv Kaybı',
            'olumlu' => 'Ölümlü',
        ],
        // Kök neden analizi — birden çok seçilebilir (balık kılçığı/5N1K yöntemine göre).
        'kok_neden_kategorileri' => [
            'insan_faktoru' => 'İnsan Faktörü (dikkatsizlik, tedbirsizlik)',
            'ekipman_arizasi' => 'Ekipman / Makine Arızası',
            'kkd_kullanilmamasi' => 'KKD Kullanılmaması / Uygunsuzluğu',
            'egitim_eksikligi' => 'Eğitim Eksikliği',
            'talimat_eksikligi' => 'Prosedür / Talimat Eksikliği',
            'calisma_ortami' => 'Çalışma Ortamı Koşulları',
            'yonetim_sistemi' => 'Yönetim Sistemi / Denetim Eksikliği',
            'diger' => 'Diğer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sertifika Oluştur — isgpratik 66-68.jpg
    |--------------------------------------------------------------------------
    | 4 sertifika tipi. İSG Sertifikası çoklu eğitici (İGU + İşyeri Hekimi,
    | isg.egitim 'genel' içeriği); diğer 3 tip tek eğitici + sabit özel başlık
    | içeriği (isg.egitim.ozel_basliklar'dan reuse). Eğitici kaşesi firmaya
    | atanmış İSG Profesyoneli'nden (isg_profesyonelleri) otomatik gelir.
    */
    'sertifika' => [
        'tipler' => [
            'isg' => [
                'ad' => 'İSG Sertifikası', 'baslik' => 'İş Sağlığı ve Güvenliği Temel Eğitim Sertifikası',
                'coklu_egitici' => true, 'icerik_anahtari' => null,
            ],
            'yukseklik' => [
                'ad' => 'Yüksekte Çalışma Sertifikası', 'baslik' => 'Yüksekte Çalışma Eğitimi Sertifikası',
                'coklu_egitici' => false, 'icerik_anahtari' => 'yuksekte_calisma',
            ],
            'kapali_alan' => [
                'ad' => 'Kapalı Alanlarda Çalışma Sertifikası', 'baslik' => 'Kapalı Alanda Çalışma Eğitimi Sertifikası',
                'coklu_egitici' => false, 'icerik_anahtari' => 'kapali_alan',
            ],
            'yangin' => [
                'ad' => 'Yangın Eğitimi Sertifikası', 'baslik' => 'Yangın Eğitimi ve Söndürme Sertifikası',
                'coklu_egitici' => false, 'icerik_anahtari' => 'sondurme_ekibi',
            ],
        ],
        // Çalışanların İSG Eğitimlerinin Usul ve Esasları Hak. Yön. — periyodik
        // eğitim tekrar süresi: az tehlikeli 3, tehlikeli 2, çok tehlikeli 1 yıl.
        'gecerlilik_yili' => [
            'az_tehlikeli' => 3,
            'tehlikeli' => 2,
            'cok_tehlikeli' => 1,
        ],
        'sureler' => ['4 saat', '8 saat', '8 Ders saati', '16 saat'],
        // isgpratik'in gerçek sertifika çıktılarında görülen 4 çerçeve seçeneği.
        'cerceveler' => [
            'sade' => 'Sade — çerçevesiz',
            'mavi_kose' => 'Mavi Köşeli Çerçeve',
            'altin_susleme' => 'Altın Süsleme Çerçeve',
            'gri_cizgi' => 'Gri Çift Çizgi Çerçeve',
        ],
        'turler' => [
            'ilk_defa' => 'İlk Defa Eğitim',
            'tekrar' => 'Tekrar Eğitim',
        ],
        'sekiller' => [
            'yuz_yuze' => 'Yüz Yüze',
            'uzaktan' => 'Uzaktan',
            'karma' => 'Karma',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Çalışma Talimatları — isgpratik 82-83.jpg
    |--------------------------------------------------------------------------
    | Hazır şablon kütüphanesi (isgpratik'in 36 şablonluk kütüphanesinden 30'u
    | ekran görüntülerinden çıkarıldı — başlık/kategori/açıklama/KKD listesi
    | birebir; madde metinleri (adım adım talimat) AI (Gemini) ile üretilir.
    */
    'talimat' => [
        'kategoriler' => [
            'is_makineleri' => 'İş Makineleri', 'el_aletleri' => 'El Aletleri', 'kimyasal_madde' => 'Kimyasal Madde',
            'elektrik_isleri' => 'Elektrik İşleri', 'yukseklerde_calisma' => 'Yükseklerde Çalışma', 'kapali_alan' => 'Kapalı Alan',
            'kaynak_kesme' => 'Kaynak ve Kesme', 'tasima_depolama' => 'Taşıma & Depolama', 'mutfak_yemekhane' => 'Mutfak & Yemekhane',
            'ofis_buro' => 'Ofis & Büro', 'insaat_saha' => 'İnşaat & Saha', 'genel_isg' => 'Genel İSG',
        ],
        'sablonlar' => [
            ['baslik' => 'Forklift Kullanma Talimatı', 'kategori' => 'is_makineleri', 'aciklama' => 'Forklift operatörlerinin uyması gereken güvenli kullanım esasları.', 'kkdler' => ['Baret', 'Çelik burunlu iş ayakkabısı', 'Reflektörlü yelek']],
            ['baslik' => 'Torna Tezgahı Kullanma Talimatı', 'kategori' => 'is_makineleri', 'aciklama' => 'Torna tezgahında güvenli talaşlı imalat çalışmaları için temel kurallar.', 'kkdler' => ['Koruyucu gözlük', 'Çelik burunlu iş ayakkabısı', 'Vücuda oturan tulum']],
            ['baslik' => 'Hidrolik Pres Makinesi Talimatı', 'kategori' => 'is_makineleri', 'aciklama' => 'Hidrolik/eksantrik pres makinelerinde kalıp ve baskı işlemleri.', 'kkdler' => ['Koruyucu gözlük', 'Çelik burunlu iş ayakkabısı', 'Anti-vibrasyon eldiveni']],
            ['baslik' => 'Spiral (Avuç İçi) Taşlama Makinesi Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Spiral taşlama makinesinin güvenli kullanımı için kurallar.', 'kkdler' => ['Yüz siperliği', 'Koruyucu gözlük', 'Anti-vibrasyon eldiveni']],
            ['baslik' => 'Sütunlu/El Matkap Kullanma Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Sütunlu ve el matkabı ile güvenli delik delme operasyonu.', 'kkdler' => ['Koruyucu gözlük', 'Çelik burunlu iş ayakkabısı', 'Vücuda oturan tulum']],
            ['baslik' => 'Elektrikli Kırıcı/Delici Kullanma Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Kırıcı tip elektrikli delici makinelerin güvenli kullanımı.', 'kkdler' => ['Anti-vibrasyon eldiveni', 'Koruyucu gözlük', 'FFP3 toz maskesi']],
            ['baslik' => 'Kimyasal Madde Depolama ve Kullanma Talimatı', 'kategori' => 'kimyasal_madde', 'aciklama' => 'Tehlikeli kimyasalların depolanması ve kullanılması.', 'kkdler' => ['Kimyasala dayanıklı eldiven', 'Kimyasal gaz maskesi', 'Yarım/tam yüz maske + uygun filtre']],
            ['baslik' => 'Boya ve Cila Uygulama Talimatı', 'kategori' => 'kimyasal_madde', 'aciklama' => 'Endüstriyel boya, vernik ve cila uygulamalarında güvenli çalışma.', 'kkdler' => ['Yarım/tam yüz organik gaz maskesi', 'Kimyasal dirençli eldiven', 'Koruyucu gözlük']],
            ['baslik' => 'Asbest ile Çalışma Talimatı', 'kategori' => 'kimyasal_madde', 'aciklama' => 'Asbest içeren malzemelerin sökümü ve bertarafında güvenli çalışma.', 'kkdler' => ['Tam yüz maskesi + P3 filtre', 'Tek kullanımlık tulum', 'Çelik burunlu iş ayakkabısı']],
            ['baslik' => 'Elektrik Panosunda Çalışma Talimatı', 'kategori' => 'elektrik_isleri', 'aciklama' => 'Elektrik panolarında bakım/onarım yapacak yetkili personel için talimat.', 'kkdler' => ['Elektrik yalıtımlı eldiven', 'Yüz siperliği', 'Yalıtkan bot']],
            ['baslik' => 'Jeneratör Kullanma Talimatı', 'kategori' => 'elektrik_isleri', 'aciklama' => 'Dizel/benzinli jeneratörlerin güvenli çalıştırılması ve bakımı.', 'kkdler' => ['Kulak tıkacı/kulaklık', 'Yalıtkan eldiven', 'Çelik burunlu iş ayakkabısı']],
            ['baslik' => 'Kablo Döşeme ve Bağlantı Talimatı', 'kategori' => 'elektrik_isleri', 'aciklama' => 'Kablo çekimi, döşeme ve bağlantı işlerinde güvenli çalışma.', 'kkdler' => ['Yalıtkan eldiven', 'Koruyucu gözlük', 'Yalıtkan ayakkabı']],
            ['baslik' => 'Yükseklerde Çalışma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => '2 metre ve üzeri yükseklikte yapılacak çalışmalar için temel güvenlik kuralları.', 'kkdler' => ['Tam vücut emniyet kemeri', 'Çift kancalı şok emici lanyard', 'Çene bağlantılı baret']],
            ['baslik' => 'Çatı Üzerinde Çalışma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => 'Çatı bakım, onarım ve izolasyon işlerinde güvenli çalışma.', 'kkdler' => ['Tam vücut emniyet kemeri', 'Çift kancalı lanyard', 'Çene bağlantılı baret']],
            ['baslik' => 'Portatif Merdiven Kullanma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => 'A tipi, uzatma ve tek ayaklı portatif merdivenlerin güvenli kullanımı.', 'kkdler' => ['Kaymaz iş ayakkabısı', 'Baret', 'Mekanik eldiven']],
            ['baslik' => 'Kapalı Alanda Çalışma Talimatı', 'kategori' => 'kapali_alan', 'aciklama' => 'Tank, depo, kazan gibi kapalı alanlarda güvenli giriş ve çalışma.', 'kkdler' => ['Tam vücut emniyet kemeri + kurtarma halatı', 'Solunum cihazı', 'Çoklu gaz dedektörü']],
            ['baslik' => 'Silo ve Tank İçi Temizlik Talimatı', 'kategori' => 'kapali_alan', 'aciklama' => 'Silo, tank ve depo içi temizlik çalışmalarında güvenli giriş ve çalışma.', 'kkdler' => ['Tam vücut emniyet kemeri + kurtarma halatı', 'Solunum koruma', 'Çoklu gaz dedektörü']],
            ['baslik' => 'Oksijen-Asetilen Kaynak/Kesme Talimatı', 'kategori' => 'kaynak_kesme', 'aciklama' => 'Gazaltı kaynak ve oksi-asetilen kesme işleri için güvenli çalışma kuralları.', 'kkdler' => ['Kaynak maskesi', 'Kaynakçı eldiveni', 'Krom deri önlük ve tozluk']],
            ['baslik' => 'Elektrik Ark Kaynağı (MIG/MAG/TIG) Talimatı', 'kategori' => 'kaynak_kesme', 'aciklama' => 'Elektrik ark kaynağı (MIG/MAG/TIG) ile güvenli çalışma kuralları.', 'kkdler' => ['Otomatik kararmalı kaynak maskesi', 'Kaynakçı eldiveni', 'Alev geciktirici tulum']],
            ['baslik' => 'Manuel Transpalet Kullanma Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Manuel transpalet ile yük taşıma ve istifleme kuralları.', 'kkdler' => ['Çelik burunlu iş ayakkabısı', 'Mekanik dirençli eldiven', 'Reflektörlü yelek']],
            ['baslik' => 'İstif Makinesi (Reach Truck) Kullanma Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Depo içi dar koridor istif makinesi ile güvenli yük taşıma ve istifleme.', 'kkdler' => ['Baret', 'Çelik burunlu iş ayakkabısı', 'Reflektörlü yelek']],
            ['baslik' => 'Depo ve Raf Sistemleri Güvenlik Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Depo raf sistemlerinin güvenli kullanımı, istifleme ve bakım kuralları.', 'kkdler' => ['Baret', 'Çelik burunlu iş ayakkabısı', 'Mekanik eldiven']],
            ['baslik' => 'Mutfak Bıçak ve Kesim Aletleri Talimatı', 'kategori' => 'mutfak_yemekhane', 'aciklama' => 'Mutfak personeli için bıçak, kıyma makinesi ve kesim aletlerinin güvenli kullanımı.', 'kkdler' => ['Kesilmeye dirençli eldiven', 'Kaymaz iş ayakkabısı', 'Mutfak önlüğü']],
            ['baslik' => 'Endüstriyel Fırın ve Ocak Kullanma Talimatı', 'kategori' => 'mutfak_yemekhane', 'aciklama' => 'Yemekhane ve endüstriyel fırın, ocak ve buharlı cihazların güvenli kullanımı.', 'kkdler' => ['Sıcak iş eldiveni', 'Kaymaz iş ayakkabısı', 'Mutfak önlüğü']],
            ['baslik' => 'Ofis ve Ekran Önü Çalışma Talimatı', 'kategori' => 'ofis_buro', 'aciklama' => 'Ekran karşısında uzun süre çalışan ofis personeli için ergonomi kuralları.', 'kkdler' => ['Bilgisayar gözlüğü', 'Bilek desteği', 'Bel/sırt desteği']],
            ['baslik' => 'Yazıcı ve Fotokopi Makinesi Güvenlik Talimatı', 'kategori' => 'ofis_buro', 'aciklama' => 'Lazer yazıcı, fotokopi makinesi ve tarayıcıların güvenli kullanımı.', 'kkdler' => ['Toner değişiminde nitril eldiven', 'FFP2 maske', 'Koruyucu gözlük']],
            ['baslik' => 'Kazı İşleri Güvenlik Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Hendek, temel ve altyapı kazı işlerinde güvenli çalışma kuralları.', 'kkdler' => ['Baret', 'Su geçirmez çizme', 'Reflektörlü yelek']],
            ['baslik' => 'Beton Dökümü ve Kalıp İşleri Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Beton dökümü, kalıp kurulum/söküm ve vibratör kullanımında güvenlik.', 'kkdler' => ['Baret', 'Alkali dirençli eldiven', 'Çelik burunlu iş ayakkabısı']],
            ['baslik' => 'Cephe İskelesinde Çalışma Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Cephe iskelesinin kurulumu, kullanımı ve söküm sırasında güvenli çalışma.', 'kkdler' => ['Tam vücut emniyet kemeri', 'Çift kancalı şok emici lanyard', 'Çene bağlamalı baret']],
            ['baslik' => 'İşyeri Temizlik ve Hijyen Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'İşyeri genel temizliği, hijyen kuralları ve dezenfeksiyon prosedürleri.', 'kkdler' => ['Kimyasal eldiven', 'Kaymaz iş ayakkabısı', 'Koruyucu gözlük']],
            ['baslik' => 'Genel İşyeri İSG Kuralları Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Tüm çalışanların uyması gereken işyeri genel iş sağlığı ve güvenliği kuralları.', 'kkdler' => ['Çalışma alanına özel KKD\'leri tak', 'Genel: baret']],
            ['baslik' => 'El Aletleri (Çekiç, Tornavida, Pense) Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Genel el aletlerinin güvenli kullanımı ve bakımı.', 'kkdler' => ['Mekanik dirençli eldiven', 'Koruyucu gözlük', 'Çelik burunlu iş ayakkabısı']],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Yıllık Planlar — isgpratik 86-87.jpg
    |--------------------------------------------------------------------------
    | Yeni bir yıllık plan başlatılınca bu 14 faaliyet varsayılan olarak
    | yüklenir (ekran görüntüsündeki liste birebir); kullanıcı ekleyip
    | çıkarabilir. Her faaliyetin 12 aylık durumu ayrı tutulur.
    */
    'yillik_plan' => [
        'ay_durumlari' => ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'],
        'varsayilan_faaliyetler' => [
            ['faaliyet' => 'Risk Değerlendirmesi Revizyonu', 'sorumlu' => 'İSG Uzmanı', 'aciklama' => '6331 sayılı kanun gereği risk değerlendirmesinin gözden geçirilmesi ve güncellenmesi'],
            ['faaliyet' => 'Tehlike Kaynak Analizi', 'sorumlu' => 'İSG Uzmanı', 'aciklama' => 'İşyerindeki tehlike kaynaklarının tespit edilerek analiz edilmesi'],
            ['faaliyet' => 'Asansör Periyodik Kontrolü', 'sorumlu' => 'Yetkili Firma', 'aciklama' => 'Asansör Bakım ve İşletme Yönetmeliği kapsamında yıllık periyodik kontrol'],
            ['faaliyet' => 'Basınçlı Kap Periyodik Kontrolü', 'sorumlu' => 'Yetkili Firma', 'aciklama' => 'Basınçlı ekipmanların yıllık periyodik muayene ve hidrostatik testleri'],
            ['faaliyet' => 'Elektrik Tesisatı Kontrolü', 'sorumlu' => 'Yetkili Firma', 'aciklama' => 'Elektrik iç tesisat ve topraklama kontrollerinin periyodik olarak yaptırılması'],
            ['faaliyet' => 'Topraklama Ölçümü', 'sorumlu' => 'Yetkili Firma', 'aciklama' => 'Elektrik tesisatı topraklama dirençlerinin ölçülmesi ve uygunluğunun kontrolü'],
            ['faaliyet' => 'Yangın Söndürme Cihazı Kontrolü', 'sorumlu' => 'Yetkili Firma', 'aciklama' => 'Yangın söndürme tüplerinin dolum, basınç ve genel durumlarının kontrolü'],
            ['faaliyet' => 'Gürültü Ölçümü', 'sorumlu' => 'İSG Laboratuvarı', 'aciklama' => 'Çalışma ortamındaki gürültü düzeyinin ölçülerek maruziyet sınırlarının değerlendirilmesi'],
            ['faaliyet' => 'Toz Ölçümü', 'sorumlu' => 'İSG Laboratuvarı', 'aciklama' => 'Çalışma ortamındaki toz konsantrasyonunun ölçülmesi ve sınır değerlerle karşılaştırılması'],
            ['faaliyet' => 'Aydınlatma Ölçümü', 'sorumlu' => 'İSG Laboratuvarı', 'aciklama' => 'Çalışma alanlarındaki aydınlatma seviyelerinin ölçülerek yeterliliğinin değerlendirilmesi'],
            ['faaliyet' => 'Yangın Tatbikatı', 'sorumlu' => 'İSG Uzmanı', 'aciklama' => 'Acil durum eylem planı kapsamında yılda en az 1 kez yangın tahliye tatbikatı yapılması'],
            ['faaliyet' => 'Deprem Tatbikatı', 'sorumlu' => 'İSG Uzmanı', 'aciklama' => 'Acil durum eylem planı kapsamında deprem tatbikatı ve tahliye uygulaması'],
            ['faaliyet' => 'İSG Kurul Toplantısı', 'sorumlu' => 'İSG Uzmanı', 'aciklama' => '6331 sayılı kanun gereği İSG kurulunun 2 ayda bir toplanarak kararlar alması'],
            ['faaliyet' => 'Periyodik Sağlık Muayenesi', 'sorumlu' => 'İşyeri Hekimi', 'aciklama' => 'Çalışanların periyodik sağlık muayenelerinin işyeri hekimi tarafından yapılması'],
        ],

        // "Yıllık Eğitim Planı" sekmesi — isgpratik 88-89.jpg. Aynı ay durum
        // matrisini kullanır; süre saat cinsinden, eğitici İSG Uzmanı/İşyeri Hekimi.
        'varsayilan_egitimler' => [
            ['konu' => 'Çalışma Mevzuatı ile İlgili Bilgiler', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar 4857 ve 5510 sayılı kanunlar hakkında bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Çalışanların Yasal Hak ve Sorumlulukları', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar, 4857, 6331 sayılı kanunlarda belirtilen çalışan hak ve sorumlulukları konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Kimyasal, Fiziksel ve Ergonomik Risk Etmenleri', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar işyerinde mevcut olan riskler ve çalışma ortamında uyulması gereken davranışlar hakkında bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Elle Kaldırma ve Taşıma', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar işyerinde mevcut olan elle kaldırma ve taşıma konularında bilgi sahibi olurlar.', 'hedef_kitle' => 'Üretim Çalışanları'],
            ['konu' => 'Parlama, Patlama, Yangın ve Yangından Korunma', 'sure_saat' => 2, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar, parlama, patlama, yangın ve yangından korunma yöntemlerini bilir. Acil durumda uyulması gerekli davranışlar konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'İş Ekipmanlarının Güvenli Kullanımı', 'sure_saat' => 2, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar, işyerinde mevcut olan iş ekipmanları riskleri ve güvenli kullanımı konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Operatörler'],
            ['konu' => 'Ekranlı Araçlarla Çalışma', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar, ekranlı araçlarla çalışma ve dikkat edilmesi gereken davranışlar konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Ofis Çalışanları'],
            ['konu' => 'Elektrik Tehlikeleri, Riskleri ve Önlemleri', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar elektrik tehlike, risk ve önlemleri konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'İş Kazalarının Sebepleri ve Korunma Prensipleri', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'İşyerinde olabilecek iş kazaları sebepleri ve önlemleri konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Güvenlik ve Sağlık İşaretleri', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar, güvenlik ve sağlık işaretleri yönetmeliğinde mevcut olan işaret ve renkleri hakkında bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Kişisel Koruyucu Donanım Kullanımı', 'sure_saat' => 2, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar kişisel koruyucu donanımlar hakkında ve hangi alanda kullanılacakları konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Üretim Çalışanları'],
            ['konu' => 'İş Sağlığı ve Güvenliği Genel Kuralları ve Güvenlik Kültürü', 'sure_saat' => 2, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar İSG Genel Kuralları ve Güvenlik Kültürü hakkında bilgi sahibi olurlar, güvenlik kültürünün işyerinde geliştirilmesi için örneklemelerde bulunurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Tahliye ve Kurtarma', 'sure_saat' => 1, 'egitici' => 'İSG Uzmanı', 'hedef' => 'Çalışanlar Acil durumlarda yapılması gerekli davranışlar hakkında bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Meslek Hastalıklarının Sebepleri', 'sure_saat' => 1, 'egitici' => 'İşyeri Hekimi', 'hedef' => 'Çalışanlar, işin niteliğinden dolayı maruz oldukları mesleki riskleri ve sebepleri konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'Biyolojik ve Psikososyal Risk Etmenleri', 'sure_saat' => 1, 'egitici' => 'İşyeri Hekimi', 'hedef' => 'Çalışanlar, işin niteliğinden dolayı oluşan biyolojik ve psikososyal riskler konusunda bilgi sahibi olurlar.', 'hedef_kitle' => 'Tüm Çalışanlar'],
            ['konu' => 'İlkyardım', 'sure_saat' => 4, 'egitici' => 'İşyeri Hekimi', 'hedef' => 'Çalışanlar, Acil durum halinde uygulanması gerekli temel ilkyardım konuları hakkında bilgi sahibi olurlar.', 'hedef_kitle' => 'İlkyardımcılar'],
        ],

        // "Yıllık Değerlendirme Raporu" sekmesi — isgpratik 90.jpg. Ay matrisi
        // yok; satır bazlı serbest metin (tarih/tekrar sayısı kullanıcı girer,
        // yapan kişi/yöntem/sonuç önerilen varsayılan değerle gelir).
        'varsayilan_degerlendirmeler' => [
            ['calisma' => 'Risk değerlendirmesi', 'yapan_kisi' => 'Risk Değerlendirme Ekibi', 'yontem' => 'Fine Kinney', 'sonuc' => 'Riskler belirlendi, önlemler alındı'],
            ['calisma' => 'Ortam ölçümleri', 'yapan_kisi' => 'İSG Laboratuvarı', 'yontem' => 'Ölçüm Cihazları', 'sonuc' => 'Ölçüm sonuçları uygun'],
            ['calisma' => 'İşe giriş muayeneleri', 'yapan_kisi' => 'İşyeri Hekimi', 'yontem' => 'Tıbbi Muayene', 'sonuc' => 'Çalışanlar işe uygun bulundu'],
            ['calisma' => 'Periyodik muayeneler', 'yapan_kisi' => 'İşyeri Hekimi', 'yontem' => 'Tıbbi Muayene', 'sonuc' => 'Çalışanlar çalışmaya uygun bulundu'],
            ['calisma' => 'Radyolojik analizler', 'yapan_kisi' => 'İşyeri Hekimi', 'yontem' => 'Röntgen/Akciğer Grafisi', 'sonuc' => 'Bulgular normal sınırlarda'],
            ['calisma' => 'Biyolojik analizler', 'yapan_kisi' => 'İşyeri Hekimi', 'yontem' => 'Kan/İdrar Tahlili', 'sonuc' => 'Sonuçlar normal sınırlarda'],
            ['calisma' => 'Toksikolojik analizler', 'yapan_kisi' => 'İşyeri Hekimi', 'yontem' => 'Laboratuvar Analizi', 'sonuc' => 'Maruziyet sınırları içinde'],
            ['calisma' => 'Fizyolojik testler', 'yapan_kisi' => 'İşyeri Hekimi', 'yontem' => 'Fonksiyon Testleri', 'sonuc' => 'Test sonuçları normal'],
            ['calisma' => 'Psikolojik testler', 'yapan_kisi' => 'Psikolog', 'yontem' => 'Psikolojik Değerlendirme', 'sonuc' => 'Değerlendirme sonuçları olumlu'],
            ['calisma' => 'Eğitim çalışmaları', 'yapan_kisi' => 'İSG Uzmanı', 'yontem' => 'Sunum/Uygulama', 'sonuc' => 'Eğitimler tamamlandı'],
            ['calisma' => 'Diğer çalışmalar', 'yapan_kisi' => 'İsim ve Unvan', 'yontem' => 'Yöntem', 'sonuc' => 'Sonuç ve yorum'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İşbaşı / Oryantasyon Eğitim Tutanağı — isgpratik 60.jpg
    |--------------------------------------------------------------------------
    | İşe yeni başlayan her çalışan için ayrı, tek sayfalık tutanak. Kontrol
    | listesi kategorilere ayrılmıştır; ekranda kesilen kısım nedeniyle
    | yalnız görülebilen maddeler config'e alındı — kullanıcı "Madde Ekle" ile
    | genişletebilir.
    */
    'isbasi_egitim' => [
        'konu_kategorileri' => [
            'İşyeri Tanıtımı' => [
                'İşyeri ve organizasyonun tanıtımı',
                'Çalışma saatleri, molalar ve vardiya düzeni',
                'İşyeri kuralları, disiplin ve davranış kuralları',
                'Sosyal alanların tanıtımı (yemekhane, soyunma odası, WC, dinlenme)',
                'Amir ve çalışma arkadaşları ile tanıştırma',
            ],
            'Görev ve Ekipman Tanıtımı' => [
                'Görev tanımının aktarılması ve iş ile ilgili beklentiler',
                'Kullanılacak iş ekipmanlarının tanıtımı ve güvenli kullanımı',
                'İlgili çalışma talimatlarının okutulması ve imzalatılması',
            ],
            'İşe / İşyerine Özgü İSG' => [
                'İşyerine özgü tehlikeler, riskler ve alınan önlemler',
            ],
        ],
        'egitim_yontemleri' => ['Uygulamalı', 'Teorik', 'Uygulamalı + Teorik'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tatbikat Tutanağı — isgpratik 61-65.jpg
    |--------------------------------------------------------------------------
    */
    'tatbikat' => [
        'senaryolar' => [
            'yangin' => ['ad' => 'Yangın Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında işyerinde yangın alarmı verilerek çalışanların en yakın ve güvenli çıkış yollarını kullanarak belirlenen toplanma noktasına tahliyesi test edilmiştir.'],
            'deprem' => ['ad' => 'Deprem Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında deprem senaryosu uygulanmış; çalışanların "Çök-Kapan-Tutun" davranışını sergilemesi ve sarsıntı sonrası güvenli tahliyesi test edilmiştir.'],
            'genel_tahliye' => ['ad' => 'Genel Tahliye Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'İşyerinin genel tahliye planı doğrultusunda tüm çalışanların belirlenen toplanma alanına yönlendirilmesi ve sayımının yapılması test edilmiştir.'],
            'kimyasal_dokulme' => ['ad' => 'Kimyasal Dökülme/Sızıntı Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında kimyasal madde dökülme/sızıntı senaryosu uygulanmış; etkilenen alanın izolasyonu, müdahale ekibinin devreye girmesi ve tahliye süreci test edilmiştir.'],
            'ilkyardim_is_kazasi' => ['ad' => 'İlk Yardım / İş Kazası Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında bir iş kazası senaryosu canlandırılmış; ilkyardım ekibinin müdahalesi, 112 ile iletişim ve olay yeri güvenliğinin sağlanması test edilmiştir.'],
            'dogalgaz_lpg' => ['ad' => 'Doğalgaz/LPG Kaçağı Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında doğalgaz/LPG kaçağı senaryosu uygulanmış; gaz vanalarının kapatılması, elektrik/ateş kaynaklarının izole edilmesi ve tahliye süreci test edilmiştir.'],
            'sel_su_baskini' => ['ad' => 'Sel/Su Baskını Tatbikatı', 'sure_dk' => '45-60', 'metin' => 'Tatbikat kapsamında sel/su baskını senaryosu uygulanmış; alçak kotlardaki alanların tahliyesi ve elektrik kesintisi prosedürleri test edilmiştir.'],
            'elektrik_yangini' => ['ad' => 'Elektrik Yangını Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında elektrik kaynaklı yangın senaryosu uygulanmış; ana elektrik kesme prosedürü ve uygun söndürücü (CO2) kullanımı test edilmiştir.'],
            'sabotaj_guvenlik' => ['ad' => 'Sabotaj/Güvenlik Tehdidi Tatbikatı', 'sure_dk' => '30-45', 'metin' => 'Tatbikat kapsamında güvenlik tehdidi/şüpheli paket senaryosu uygulanmış; alanın güvenli bir şekilde boşaltılması ve yetkili mercilerle iletişim test edilmiştir.'],
            'gida_zehirlenmesi' => ['ad' => 'Gıda Zehirlenmesi Tatbikatı', 'sure_dk' => '45-60', 'metin' => 'Tatbikat kapsamında toplu gıda zehirlenmesi şüphesi senaryosu uygulanmış; etkilenen çalışanların tespiti, ilkyardım müdahalesi ve sağlık kuruluşuyla iletişim test edilmiştir.'],
        ],
        'degerlendirme_sorulari' => [
            'Alarm/anons tüm çalışma alanlarında duyuldu mu?',
            'Tahliye planlanan sürede tamamlandı mı?',
            'Kaçış yolları ve acil çıkışlar açık ve engelsiz miydi?',
            'Acil durum yönlendirme levhaları yeterli ve görünür müydü?',
            'Toplanma noktasında sayım doğru ve eksiksiz yapıldı mı?',
        ],
        'degerlendirme_secenekleri' => ['evet' => 'Evet', 'hayir' => 'Hayır', 'kismen' => 'Kısmen'],
        'ekip_secenekleri' => ['Söndürme Ekibi', 'Kurtarma Ekibi', 'Koruma Ekibi', 'İlk Yardım Ekibi'],
    ],

    /*
    |--------------------------------------------------------------------
    | Ücretsiz E-Reçetem — bilgi sayfası (gerçek MEDULA/e-Reçete
    | entegrasyonu yok; işyeri hekiminin periyodik muayenelerde
    | çalışanlara ücretsiz e-reçete düzenleyebildiği hizmetin anlatımı).
    |--------------------------------------------------------------------
    */
    'e_recetem' => [
        'nedir' => 'Aile hekimliği/işyeri hekimliği mevzuatı gereği, işyeri hekiminiz periyodik muayene sırasında çalışanlarınıza Sağlık Bakanlığı e-Reçete sistemi üzerinden ücretsiz ilaç reçetesi düzenleyebilir. Bu, çalışanın ayrıca aile hekimine gitmesine gerek kalmadan, iş yerinde tespit edilen basit rahatsızlıklar için hızlı çözüm sağlar.',

        'kimler_faydalanabilir' => [
            ['baslik' => 'Periyodik Muayeneden Geçen Çalışanlar', 'ikon' => 'heroicon-o-user-group',
                'aciklama' => 'İşyeri hekiminin fiziki muayenesi sırasında tespit edilen basit/akut şikayetler için.'],
            ['baslik' => 'İşe Giriş Muayenesi Yapılanlar', 'ikon' => 'heroicon-o-clipboard-document-check',
                'aciklama' => 'İşe giriş muayenesinde ihtiyaç görülmesi halinde.'],
            ['baslik' => 'İş Kazası/Meslek Hastalığı Sonrası', 'ikon' => 'heroicon-o-heart',
                'aciklama' => 'Hafif iş kazası sonrası ilk müdahale niteliğindeki ilaç ihtiyaçlarında.'],
        ],

        'nasil_calisir' => [
            ['baslik' => 'Muayene Yapılır', 'aciklama' => 'İşyeri hekimi çalışanı iş yerinde veya sözleşmeli sağlık biriminde muayene eder.'],
            ['baslik' => 'e-Reçete Sisteme Girilir', 'aciklama' => 'Hekim, kendi doktor e-imzası ve SGK hekim şifresiyle Sağlık Bakanlığı e-Reçete portalına reçeteyi işler.'],
            ['baslik' => 'Barkod/Kod Çalışana İletilir', 'aciklama' => 'Çalışan, SMS ile gelen reçete kodunu veya T.C. kimlik numarasını herhangi bir eczaneye ibraz eder.'],
            ['baslik' => 'Eczaneden Ücretsiz Temin Edilir', 'aciklama' => 'SGK\'lı çalışan, katılım payı dışında ek ücret ödemeden ilacını alır.'],
        ],

        'kapsam_disi' => [
            'mehse üzerinden doğrudan e-Reçete/MEDULA sistemine reçete yazılamaz — bu işlem, işyeri hekiminin kendi mevzuat gereği sahip olduğu doktor e-imzası ve SGK yetkilendirmesiyle, resmi Sağlık Bakanlığı sistemleri üzerinden yapılır.',
            'mehse, bu süreci yalnızca bilgilendirme ve işyeri hekimi atama takibi (İSG Profesyonelleri modülü) ile destekler.',
        ],

        'sss' => [
            ['soru' => 'İşyeri hekimim yoksa bu hizmetten faydalanabilir miyiz?', 'cevap' => 'Hayır, e-reçete yalnızca firmanıza atanmış ve SGK\'ya kayıtlı bir işyeri hekimi tarafından düzenlenebilir. Firma kaydında işyeri hekimi atamasını kontrol edin.'],
            ['soru' => 'Reçete bedeli tamamen ücretsiz mi?', 'cevap' => 'SGK\'lı çalışanlar için, ilacın SGK katılım payı dışında ek bir ücret alınmaz; muayene ve reçete için ayrıca ücret talep edilemez.'],
            ['soru' => 'Kronik hastalıklar için de reçete yazılabilir mi?', 'cevap' => 'İşyeri hekimliği kapsamı iş sağlığına yönelik akut/basit durumları kapsar; kronik hastalık takibi için çalışanın kendi aile hekimine yönlendirilmesi önerilir.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------
    | Araçlar — İSG uzmanına yönelik bağımsız hesaplayıcılar.
    | Gürültü eylem sınırları: Çalışanların Gürültü ile İlgili
    | Risklerden Korunmalarına Dair Yönetmelik'teki eşik değerleri.
    |--------------------------------------------------------------------
    */
    'araclar' => [
        'gurultu_sinirlari' => [
            ['esik' => 87, 'etiket' => 'Maruziyet Sınır Değeri Aşıldı', 'renk' => 'danger',
                'aciklama' => 'KKD etkisi dahil edilse bile bu değer aşılamaz — acil teknik/organizasyonel önlem gerekir.'],
            ['esik' => 85, 'etiket' => 'Üst Eylem Değeri Aşıldı', 'renk' => 'danger',
                'aciklama' => 'KKD kullanımı zorunludur; alan işaretlenmeli ve erişim sınırlandırılmalıdır.'],
            ['esik' => 80, 'etiket' => 'Alt Eylem Değeri Aşıldı', 'renk' => 'warning',
                'aciklama' => 'KKD bulundurulmalı ve çalışanlara gönüllü kullanım imkânı sağlanmalıdır.'],
        ],
        'gurultu_guvenli_mesaj' => 'Ölçülen günlük maruziyet düzeyi eylem sınırlarının altındadır.',
    ],

    /*
    |--------------------------------------------------------------------
    | Muayene Formu (EK-2) — İşyeri Hekimi ve Diğer Sağlık Personelinin
    | Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik'in
    | EK-2 "İşe Giriş / Periyodik Muayene Formu"na göre kuruldu.
    |--------------------------------------------------------------------
    */
    'muayene' => [
        'muayene_turleri' => [
            'ise_giris' => 'İşe Giriş Muayenesi',
            'periyodik' => 'Periyodik Muayene',
            'araya_giren' => 'Aralıklı / Ara Kontrol Muayenesi',
            'ise_donus' => 'İşe Dönüş Muayenesi',
        ],

        'sistemik_muayene_basliklari' => [
            'Genel Durum',
            'Baş - Boyun / KBB',
            'Göz',
            'Solunum Sistemi',
            'Kardiyovasküler Sistem',
            'Gastrointestinal Sistem',
            'Nörolojik Sistem',
            'Kas - İskelet Sistemi',
            'Deri',
            'Psikiyatrik Değerlendirme',
        ],

        'tetkikler' => [
            'odyometri' => 'Odyometri (İşitme Testi)',
            'sft' => 'Solunum Fonksiyon Testi (SFT/Spirometri)',
            'akciger_grafisi' => 'Akciğer Grafisi',
            'goz_muayenesi' => 'Göz Muayenesi (Görme Keskinliği / Renk Körlüğü)',
            'tam_kan' => 'Tam Kan Sayımı',
            'biyokimya' => 'Biyokimya (Açlık Kan Şekeri, Karaciğer/Böbrek Fonksiyonu)',
            'idrar_tetkiki' => 'İdrar Tetkiki',
            'ekg' => 'EKG',
        ],

        'sonuc_kanaatleri' => [
            'uygun' => 'İşe Uygundur',
            'sartli_uygun' => 'Şartlı Uygundur',
            'uygun_degil' => 'Uygun Değildir',
            'is_degisikligi' => 'İş Değişikliği Önerilir',
        ],

        // Periyodik muayene yenileme süresi (yıl) — tehlike sınıfına göre.
        'periyot_yili' => [
            'az_tehlikeli' => 5,
            'tehlikeli' => 3,
            'cok_tehlikeli' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------
    | Ziyaret Programı — isgpratik'te ekran görüntüsü yok; kullanıcı
    | onayıyla BASİTLEŞTİRİLMİŞ liste (12 aylık satır) olarak kuruldu,
    | tam takvim/sürükle-bırak arayüzü kapsam dışı bırakıldı.
    |--------------------------------------------------------------------
    */
    'ziyaret_programi' => [
        'amac_kategorileri' => [
            'Genel Saha Gözetimi',
            'Risk Değerlendirmesi Güncelleme',
            'Eğitim',
            'Tetkik / Ölçüm Takibi',
            'DÖF Takibi',
            'Kaza / Olay İncelemesi',
            'Kurul Toplantısı',
            'Diğer',
        ],
    ],
];
