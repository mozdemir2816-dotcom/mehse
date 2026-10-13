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
        'Taşlama / Bant Zımpara Makinesi' => ['risk_kategorileri' => ['atolye_bant_zimpara_taslama'], 'kontrol_formu' => 'Taşlama / Bant Zımpara Makinesi', 'ortak_riskler' => false],
        'Ekstrüzyon Hattı' => ['risk_kategorileri' => ['atolye_ekstruzyon_hatti'], 'kontrol_formu' => 'Ekstrüzyon Hattı', 'talimat' => 'EKSTRÜZYON MAKİNESİ GÜVENLİ ÇALIŞMA TALİMATI', 'ortak_riskler' => false],
        'Plastik Kırıcı / Granülatör' => ['risk_kategorileri' => ['atolye_plastik_kirici_granulator'], 'kontrol_formu' => 'Plastik Kırıcı / Granülatör', 'ortak_riskler' => false],
        'Merdaneli Makine' => ['risk_kategorileri' => ['atolye_merdaneli_makineler'], 'kontrol_formu' => 'Merdaneli Makine', 'ortak_riskler' => false],
        'Gaz Tüpü ve Şaloma Seti' => ['risk_kategorileri' => ['atolye_gaz_tupleri_ve_saloma'], 'kontrol_formu' => 'Gaz Tüpü ve Şaloma Seti', 'talimat' => 'Oksijen-Asetilen Kaynak/Kesme Talimatı'],
        'Açık Vals (İki Silindirli)' => ['risk_kategorileri' => ['atolye_acik_vals'], 'ortak_riskler' => false],
        'Gazaltı Kaynak Makinesi' => ['risk_kategorileri' => ['atolye_gazalti_kaynak'], 'ortak_riskler' => false],
        'Satıh Taşlama Tezgâhı' => ['risk_kategorileri' => ['atolye_satih_taslama'], 'ortak_riskler' => false],
        'Dalma Erozyon (EDM) Tezgâhı' => ['risk_kategorileri' => ['atolye_dalma_erozyon'], 'ortak_riskler' => false],
        'Ayaklı Taş Motoru' => ['risk_kategorileri' => ['atolye_ayakli_tas_motoru'], 'ortak_riskler' => false],
        'Pürmüz ve LPG Tüpü' => ['risk_kategorileri' => ['ekipman_purmuz_lpg'], 'ortak_riskler' => false],

        // Diğer iş ekipmanları & araçlar (periyodik muayene yok) — EKP-RA-01
        'Endüstriyel Zemin Temizleme Otomatı' => ['risk_kategorileri' => ['ekipman_zemin_temizleme']],
        'Seyyar / Çok Amaçlı Merdiven' => ['risk_kategorileri' => ['ekipman_seyyar_merdiven']],
        'Şirket Aracı' => ['risk_kategorileri' => ['ekipman_sirket_araci']],
        'Endüstriyel Raf' => ['risk_kategorileri' => ['ekipman_endustriyel_raf']],

        // Periyodik muayeneli ekipmanlar
        'Trafo & Yüksek Gerilim İşletmesi' => ['risk_kategorileri' => ['ekipman_trafo'], 'kontrol_formu' => 'Trafo (OG/AG)', 'talimat' => 'TRAFO MERKEZİ VE YG TESİSLERİ GÜVENLİ ÇALIŞMA TALİMATI'],
        'Elektrik Ana & Tali Dağıtım Panoları' => ['risk_kategorileri' => ['ekipman_elektrik_panosu']],
        'Hava Kompresörü ve Hava Tankı' => ['risk_kategorileri' => ['ekipman_kompresor'], 'kontrol_formu' => 'Kompresör', 'talimat' => 'KOMPRESÖR VE HAVA TANKI GÜVENLİ ÇALIŞMA TALİMATI'],
        'Forklift / Akülü & Dizel İstifleyici' => ['risk_kategorileri' => ['ekipman_forklift'], 'kontrol_formu' => 'Forklift', 'talimat' => 'Forklift Kullanma Talimatı'],
        'Transpalet (Motorlu / Manuel)' => ['risk_kategorileri' => ['ekipman_transpalet']],
        'Sütunlu Pergel Vinç' => ['risk_kategorileri' => ['ekipman_pergel_vinc']],
        'Tavan Vinci / Gezer Köprülü Vinç' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Köprü Vinç'],
        'Portal Vinç' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Portal Vinç'],
        'Mobil Vinç / Hiyap' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Mobil Vinç'],
        'Kule Vinç' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Kule Vinç', 'talimat' => 'Kule Vinç ile Çalışma ve Sinyalizasyon Talimatı'],
        'Caraskal & Halatlı/Zincirli Çektirme' => ['risk_kategorileri' => ['ekipman_caraskal'], 'kontrol_formu' => 'Caraskal'],
        'Yükseltilebilir Seyyar İş Platformu (Manlift)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Makaslı Platform (Manlift)'],
    ],

    // Kategoride en az bir ekipman varsa ayrıca beklenen ortak risk kategorileri. Kendi risk
    // dosyası bu konuları zaten kapsayan tip 'ortak_riskler' => false ile tetiklemez.
    'kategori_ortak_riskleri' => [
        'uretim_atolye' => ['atolye_elektrik_ve_ortak_makine_guvenligi', 'atolye_egitim_ve_yetkilendirme'],
    ],
];
