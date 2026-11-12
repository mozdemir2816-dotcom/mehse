<?php

/*
|--------------------------------------------------------------------------
| Ekipman Kataloğu (13.10.2026)
|--------------------------------------------------------------------------
| Firmaya eklenen iş ekipmanını (is_ekipmanlari.tip — isg.periyodik_kontrol.tipler
| içindeki 'ad') üç modüle bağlar; okuyucu App\Support\EkipmanKatalogu:
|
|  'risk_kategorileri' — tehlike_kategorileri.anahtar. Bu kategorilerin maddeleri
|                        firmanın son risk değerlendirmesinde yoksa uyarı + "Ekipman
|                        Eksikleri" ile eklenir.
|  'kontrol_formu'     — isg.haftalik_ekipman_kontrol.tipler içindeki 'ad'. Firmanın
|                        ekipman kontrol formu PDF'ine bu sayfa girer.
|  'talimat'          — TalimatKutuphanesi::hazirSablonlar içindeki 'baslik'. Ekipman
|                        firmaya eklenince talimat firmaya otomatik eklenir.
|
| Periyodik muayene takibi ekipmanın kategorisinden gelir (kategori 'periyodik' =>
| false ise vize takibi yok). Yeni ekipman = periyodik_kontrol.tipler'e satır + burada
| kayıt; kod değişmez.
*/

return [
    'tipler' => [
        // Üretim & atölye makineleri — risk: MakineRiskKutuphanesiSeeder (RA-MAK-2026-01,
        // CNC-RA-01, TFD-RA-01, EKP-RA-01). Kendi eğitim/acil durum maddelerini içeren dosyalardan
        // gelen tipler ('ortak_riskler' => false) ortak atölye maddelerini tetiklemez.
        'CNC Tezgâhı' => ['risk_kategorileri' => ['atolye_cnc_isleme_merkezi'], 'kontrol_formu' => 'CNC Tezgâhı', 'talimat' => 'CNC MAKİNESİ GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Torna Tezgâhı' => ['risk_kategorileri' => ['atolye_torna_tezgahi'], 'kontrol_formu' => 'Torna Tezgâhı', 'talimat' => 'ÜNİVERSAL TORNA TEZGAHI GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Dikey Freze Tezgâhı' => ['risk_kategorileri' => ['atolye_dikey_freze'], 'kontrol_formu' => 'Dikey Freze Tezgâhı', 'talimat' => 'DİKEY FREZE TEZGAHI GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Sütunlu Matkap' => ['risk_kategorileri' => ['atolye_sutunlu_matkap'], 'kontrol_formu' => 'Sütunlu Matkap', 'talimat' => 'Sütunlu/El Matkap Kullanma Talimatı'],
        'Kesme Tezgâhı (Disk / Testere)' => ['risk_kategorileri' => ['atolye_kesme_tezgahi'], 'kontrol_formu' => 'Kesme Tezgâhı (Disk / Testere)', 'ortak_riskler' => false],
        'Taşlama / Bant Zımpara Makinesi' => ['risk_kategorileri' => ['atolye_bant_zimpara_taslama'], 'kontrol_formu' => 'Taşlama / Bant Zımpara Makinesi', 'talimat' => 'SÜTUNLU ZIMPARA MAKİNESİ (BANT ZIMPARA) GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Ekstrüzyon Hattı' => ['risk_kategorileri' => ['atolye_ekstruzyon_hatti'], 'kontrol_formu' => 'Ekstrüzyon Hattı', 'talimat' => 'PLASTİK EKSTRÜZYON HATTI (EKSTRUDER) GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Plastik Kırıcı / Granülatör' => ['risk_kategorileri' => ['atolye_plastik_kirici_granulator'], 'kontrol_formu' => 'Plastik Kırıcı / Granülatör', 'talimat' => 'PLASTİK KIRMA MAKİNESİ (GRANÜL KRICI) GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Merdaneli Makine' => ['risk_kategorileri' => ['atolye_merdaneli_makineler'], 'kontrol_formu' => 'Merdaneli Makine', 'talimat' => 'PLASTİK ÇEKME / EZME MERDANESİ GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Gaz Tüpü ve Şaloma Seti' => ['risk_kategorileri' => ['atolye_gaz_tupleri_ve_saloma'], 'kontrol_formu' => 'Gaz Tüpü ve Şaloma Seti', 'talimat' => 'Oksijen-Asetilen Kaynak/Kesme Talimatı'],
        'Açık Vals (İki Silindirli)' => ['risk_kategorileri' => ['atolye_acik_vals'], 'kontrol_formu' => 'Açık Vals (İki Silindirli)', 'talimat' => 'AÇIK VALS TEZGÂHI İSG GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Gazaltı Kaynak Makinesi' => ['risk_kategorileri' => ['atolye_gazalti_kaynak'], 'kontrol_formu' => 'Ark Kaynak / Tig / Plazma Kesme Makinesi', 'ortak_riskler' => false, 'talimat' => ['Elektrik Ark Kaynağı (MIG/MAG/TIG) Talimatı', 'Sıcak İşler (Kaynak, Kesme, Taşlama) İzin Talimatı']],
        'Satıh Taşlama Tezgâhı' => ['risk_kategorileri' => ['atolye_satih_taslama'], 'ortak_riskler' => false],
        'Dalma Erozyon (EDM) Tezgâhı' => ['risk_kategorileri' => ['atolye_dalma_erozyon'], 'ortak_riskler' => false],
        'Ayaklı Taş Motoru' => ['risk_kategorileri' => ['atolye_ayakli_tas_motoru'], 'kontrol_formu' => 'Ayaklı Taş Motoru', 'talimat' => 'ÇİFT TAŞLAMA MOTORU (ZIMPARA TAŞ MOTORU) GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        // Havalandırma kanalı ve sac işleme (TLT-SPK/KRD/SMV/PLZ) — kendi risk dosyası yok; ortak atölye maddeleri önerilir.
        'Spiral Hava Kanalı Makinesi' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Spiral Hava Kanalı Makinesi', 'talimat' => 'SPİRAL HAVA KANALI MAKİNESİ (DFC MACHINERY) GÜVENLİ ÇALIŞMA TALİMATI'],
        'Kordon Tezgâhı (Motorlu Pedallı)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Kordon Tezgâhı (Motorlu Pedallı)', 'talimat' => 'MOTORLU PEDALLI KORDON TEZGÂHI GÜVENLİ ÇALIŞMA TALİMATI'],
        'Sac Silindir Büküm Makinesi (Vals)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Sac Silindir Büküm Makinesi (Vals)', 'talimat' => 'MOTORLU SAC SİLİNDİR BÜKÜM MAKİNESİ (VALS) GÜVENLİ ÇALIŞMA TALİMATI'],
        'CNC Plazma Sac Kesim Tezgâhı' => ['risk_kategorileri' => [], 'kontrol_formu' => 'CNC Plazma Sac Kesim Tezgâhı', 'talimat' => 'CNC PLAZMA SAC KESİM TEZGÂHI & ÇAPAK MASASI GÜVENLİ ÇALIŞMA TALİMATI'],
        'Pürmüz ve LPG Tüpü' => ['risk_kategorileri' => ['ekipman_purmuz_lpg'], 'kontrol_formu' => 'Pürmüz ve LPG Tüpü', 'talimat' => 'PÜRMÜZ VE PİKNİK TÜPÜ (LPG TAVLAMA SETİ) GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],

        // Diğer iş ekipmanları & araçlar (periyodik muayene yok) — EKP-RA-01
        'Endüstriyel Zemin Temizleme Otomatı' => ['risk_kategorileri' => ['ekipman_zemin_temizleme']],
        'Seyyar / Çok Amaçlı Merdiven' => ['risk_kategorileri' => ['ekipman_seyyar_merdiven'], 'kontrol_formu' => 'Seyyar El Merdiveni', 'talimat' => 'SEYYAR EL MERDİVENİ GÜVENLİ ÇALIŞMA TALİMATI'],
        'Şirket Aracı' => ['risk_kategorileri' => ['ekipman_sirket_araci']],
        'Endüstriyel Raf' => ['risk_kategorileri' => ['ekipman_endustriyel_raf'], 'talimat' => 'Depo ve Raf Sistemleri Güvenlik Talimatı'],
        // Şantiye iş makineleri — risk: SK-RA-01 (Şantiye ve kamp); kendi eğitim/acil maddeleri var.
        'Kazıcı Yükleyici (Beko Loder)' => ['risk_kategorileri' => ['santiye_kazici_yukleyici'], 'kontrol_formu' => 'Beko Loder (Kazıcı Yükleyici)', 'ortak_riskler' => false, 'talimat' => ['Kazı İşleri Güvenlik Talimatı', 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı']],
        'Damperli Kamyon' => ['risk_kategorileri' => ['santiye_damperli_kamyon'], 'kontrol_formu' => 'Damperli Şantiye Kamyonu', 'ortak_riskler' => false, 'talimat' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı'],

        // Periyodik muayeneli ekipmanlar
        'Trafo & Yüksek Gerilim İşletmesi' => ['risk_kategorileri' => ['ekipman_trafo'], 'kontrol_formu' => 'Trafo (OG/AG)', 'talimat' => 'TRAFO MERKEZİ VE YG TESİSLERİ GÜVENLİ ÇALIŞMA TALİMATI'],
        'Elektrik Ana & Tali Dağıtım Panoları' => ['risk_kategorileri' => ['ekipman_elektrik_panosu'], 'talimat' => ['Elektrik Panosunda Çalışma Talimatı', 'Kilitleme-Etiketleme (LOTO) Talimatı']],
        'Hava Kompresörü ve Hava Tankı' => ['risk_kategorileri' => ['ekipman_kompresor'], 'kontrol_formu' => 'Kompresör', 'talimat' => 'KOMPRESÖR VE HAVA TANKI GÜVENLİ ÇALIŞMA TALİMATI'],
        'Forklift / Akülü & Dizel İstifleyici' => ['risk_kategorileri' => ['ekipman_forklift'], 'kontrol_formu' => 'Forklift', 'talimat' => 'Forklift Kullanma Talimatı'],
        'Transpalet (Motorlu / Manuel)' => ['risk_kategorileri' => ['ekipman_transpalet'], 'talimat' => 'Manuel Transpalet Kullanma Talimatı'],
        'Sütunlu Pergel Vinç' => ['risk_kategorileri' => ['ekipman_pergel_vinc']],
        // KEK (kaldırma ekipmanları/aksesuarları) + KLD (kaldırma işlemleri), 15.10.2026
        'Tavan Vinci / Gezer Köprülü Vinç' => ['risk_kategorileri' => ['ekipman_kaldirma_ekipmanlari', 'kaldirma_islemleri'], 'kontrol_formu' => 'Köprü Vinç'],
        'Portal Vinç' => ['risk_kategorileri' => ['ekipman_kaldirma_ekipmanlari', 'kaldirma_islemleri'], 'kontrol_formu' => 'Portal Vinç'],
        'Mobil Vinç / Hiyap' => ['risk_kategorileri' => ['santiye_mobil_vinc'], 'kontrol_formu' => 'Mobil Vinç', 'ortak_riskler' => false], // MV-RA: mobil vinçle kalıp taşıma
        // KLV dosyası bağlama/sapan/kör kaldırmayı kendi içinde kapsar — KLD ayrıca bağlanmaz (tekrar olmasın).
        'Kule Vinç' => ['risk_kategorileri' => ['ekipman_kule_vinc'], 'kontrol_formu' => 'Kule Vinç', 'talimat' => 'Kule Vinç ile Çalışma ve Sinyalizasyon Talimatı'],
        'Caraskal & Halatlı/Zincirli Çektirme' => ['risk_kategorileri' => ['ekipman_caraskal', 'ekipman_kaldirma_ekipmanlari'], 'kontrol_formu' => 'Caraskal'],
        'Yükseltilebilir Seyyar İş Platformu (Manlift)' => ['risk_kategorileri' => ['ekipman_manlift'], 'kontrol_formu' => 'Makaslı Platform (Manlift)', 'talimat' => 'Makaslı/Eklemli Platform (Manlift) Kullanma Talimatı'],
        'Kaldırma Aksesuarları (sapan, zincir, halat, mapa)' => ['risk_kategorileri' => ['ekipman_kaldirma_ekipmanlari']],
        'Teleskopik Yükleyici (Manitou / Telehandler)' => ['risk_kategorileri' => ['ekipman_teleskopik_yukleyici'], 'kontrol_formu' => 'Telehandler (Teleskobik Bomlu Yükleyici)'],
        // Ekipman Değerlendirme Aracı karşılaştırması (19.10.2026) — risk analizi "Ekipman Risk Analizi Yükle" ile bağlanır.
        'Araç Üstü Yüksek Platform (Sepetli Platform / Cherry Picker)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Araç Üstü Yüksek Platform (Sepetli Platform / Cherry Picker)', 'talimat' => 'Yükseklerde Çalışma Talimatı'],
        'Gondol (Asılı Çalışma Platformu)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Gondol (Asılı Çalışma Platformu)', 'talimat' => 'Yükseklerde Çalışma Talimatı'],
        'Seyyar Yük Kaldırma Lifti (Sepetli Vinç / Mobilya Asansörü)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Seyyar Yük Kaldırma Lifti (Sepetli Vinç / Mobilya Asansörü)'],
        'Düşmeyi Önleyici Ekipman (Yaşam Hattı / Emniyet Kemeri)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Düşmeyi Önleyici Ekipman (Yaşam Hattı / Emniyet Kemeri)', 'talimat' => 'Yükseklerde Çalışma Talimatı'],
        'Traktör' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Traktör'],
        'Greyder' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Greyder', 'talimat' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı'],
        'Transmikser (Beton Mikseri Kamyonu)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Transmikser (Beton Mikseri)', 'talimat' => ['Beton Dökümü ve Kalıp İşleri Talimatı', 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı']],
        'Mobil Beton Pompası' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Mobil Beton Pompası', 'talimat' => 'Beton Dökümü ve Kalıp İşleri Talimatı'],
        'Sabit Beton Pompası' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Sabit Beton Pompası', 'talimat' => 'Beton Dökümü ve Kalıp İşleri Talimatı'],
        'Beton Vibratörü' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Beton Vibratörü', 'talimat' => ['Beton Dökümü ve Kalıp İşleri Talimatı', 'Titreşimli El Aletleriyle Çalışma Talimatı']],
        'Beton Karıştırıcı (Mikser)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Beton Karıştırıcı (Mikser)', 'talimat' => 'Beton Dökümü ve Kalıp İşleri Talimatı'],
        'Fore Kazık / Kazık Çakma / Sondaj Makinesi' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Fore Kazık Makinesi', 'talimat' => 'FORE KAZIK İŞLERİNDE GÜVENLİ ÇALIŞMA TALİMATI'],
        'Konsolidasyon Ekipmanı (Drenaj / Zemin İyileştirme)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Konsolidasyon Ekipmanı (Drenaj / Zemin İyileştirme)', 'talimat' => 'ENJEKSİYON İŞLERİNDE GÜVENLİ ÇALIŞMA TALİMATI'],
        'Asfalt Finişeri / Distribütörü (Yol Kaplama)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Asfalt Finişeri', 'talimat' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı'],
        'Lowbed (Ağır Yük Taşıyıcı Dorse)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Lowbed (Ağır Yük Taşıyıcı Dorse)', 'talimat' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı'],
        'Hidrolik Pres' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Hidrolik Pres', 'talimat' => 'Hidrolik Pres Makinesi Talimatı'],
        'El Matkabı / Darbeli Matkap / Tornavida' => ['risk_kategorileri' => [], 'kontrol_formu' => 'El Matkabı / Darbeli Matkap / Tornavida', 'talimat' => ['Sütunlu/El Matkap Kullanma Talimatı', 'El Aletleri (Çekiç, Tornavida, Pense) Talimatı']],
        'Avuç Taşlama (Spiral)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Avuç Taşlama (Spiral)', 'talimat' => 'Spiral (Avuç İçi) Taşlama Makinesi Talimatı'],
        'Dekupaj / El Tipi Daire Testere' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Dekupaj / El Tipi Daire Testere'],
        'Kırıcı / Delici (Jack Hammer)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Kırıcı / Delici (Jack Hammer)', 'talimat' => ['Elektrikli Kırıcı/Delici Kullanma Talimatı', 'Titreşimli El Aletleriyle Çalışma Talimatı']],
        'Ark Kaynak / Tig / Plazma Kesme Makinesi' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Ark Kaynak / Tig / Plazma Kesme Makinesi', 'talimat' => ['Elektrik Ark Kaynağı (MIG/MAG/TIG) Talimatı', 'Plazma Kesim Talimatı']],
        'Isı Makineleri (Sıcak Hava Tabancası / Havya / PVC Kaynak)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Isı Makineleri (Sıcak Hava Tabancası / Havya / PVC Kaynak)'],
        'Uzatma Kablosu / Kablo Makarası' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Uzatma Kablosu / Kablo Makarası', 'talimat' => 'Kablo Döşeme ve Bağlantı Talimatı'],
        'Seyyar Transformatör ve Vantilatör' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Seyyar Transformatör ve Vantilatör'],

        // 12.11.2026: seçim listesinde olup katalogda olmayan tipler — kütüphanedeki talimat / günlük form bağlandı.
        'İskele (Cephe / Mobil / Asma)' => ['risk_kategorileri' => [], 'talimat' => ['Cephe İskelesinde Çalışma Talimatı', 'İskele Kurulum ve Söküm Talimatı']],
        'Yapı / Cephe İskelesi' => ['risk_kategorileri' => [], 'talimat' => ['Cephe İskelesinde Çalışma Talimatı', 'İskele Kurulum ve Söküm Talimatı']],
        'Loder / Lastikli Yükleyici' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Loder', 'talimat' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı'],
        'Silindir (Toprak / Asfalt)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Toprak Silindiri', 'talimat' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı'],
        'Ekskavatör ve Diğer Yol Yapım Makineleri' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Paletli Ekskavatör', 'talimat' => ['Kazı İşleri Güvenlik Talimatı', 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı']],
        'Yangın Söndürme Cihazları (YSC)' => ['risk_kategorileri' => [], 'talimat' => 'Yangın Güvenliği ve Söndürme Cihazı Kullanma Talimatı'],
        'Yangın Dolapları' => ['risk_kategorileri' => [], 'talimat' => 'Yangın Güvenliği ve Söndürme Cihazı Kullanma Talimatı'],
        'Toz Boya Fırını' => ['risk_kategorileri' => [], 'talimat' => 'Boya ve Cila Uygulama Talimatı'],
    ],

    // Kategoride en az bir ekipman varsa ayrıca beklenen ortak risk kategorileri. Kendi risk
    // dosyası bu konuları zaten kapsayan tip 'ortak_riskler' => false ile tetiklemez.
    'kategori_ortak_riskleri' => [
        'uretim_atolye' => ['atolye_elektrik_ve_ortak_makine_guvenligi'],
    ],

    // Ekipmandan/sektörden bağımsız, HER firmanın risk değerlendirmesinde beklenen evrensel set
    // (kullanıcı kararı 23.10.2026: eğitim + sağlık gözetimi + bina/işyeri genel güvenliği —
    // sektöre özgü eski "atölye eğitimi" setinin yerini aldı, o maddeler Risk Sihirbazı'nda
    // "Genel – Eğitim ve yetkilendirme" sanal grubundan elle de eklenebilir). Yoksa uyarı +
    // "Eksik Maddeler" ile tek tıkla eklenir; değerlendirmede olan / "gerekmiyor" denen gelmez.
    'her_firma_riskleri' => ['egitim_saglik_yeterlilik', 'genel_isyeri'],

    // "her_firma_riskleri"ne ek olarak, firmanın NACE grubuna göre (App\Support\NaceGrubu) seçilen
    // acil durum seti — inşaat/şantiye firmalarında atölye acil durumu yerine şantiyeye uygun genel
    // çalışma/tesis maddeleri önerilir; diğer tüm sektörlerde eski davranış (atölye seti) korunur.
    'her_firma_acil_durum' => [
        'insaat' => ['santiye_genel_calismalar', 'santiye_genel_calismalar_tesisler'],
        'varsayilan' => ['atolye_acil_durumlar'],
    ],
];
