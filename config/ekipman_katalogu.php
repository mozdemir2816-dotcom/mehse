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
|
| Periyodik muayene takibi ekipmanın kategorisinden gelir (kategori 'periyodik' =>
| false ise vize takibi yok). Yeni ekipman = periyodik_kontrol.tipler'e satır + burada
| kayıt; kod değişmez.
*/

return [
    'tipler' => [
        // Üretim & atölye makineleri — risk: RA-MAK-2026-01 (MakineRiskKutuphanesiSeeder)
        // CNC-RA-01 kendi elektrik/eğitim/acil durum maddelerini içerir → ortak atölye maddeleri bu tipten tetiklenmez.
        'CNC Tezgâhı' => ['risk_kategorileri' => ['atolye_cnc_isleme_merkezi'], 'kontrol_formu' => 'CNC Tezgâhı', 'ortak_riskler' => false],
        'Torna Tezgâhı' => ['risk_kategorileri' => ['atolye_torna_tezgahi'], 'kontrol_formu' => 'Torna Tezgâhı'],
        'Sütunlu Matkap' => ['risk_kategorileri' => ['atolye_sutunlu_matkap'], 'kontrol_formu' => 'Sütunlu Matkap'],
        'Kesme Tezgâhı (Disk / Testere)' => ['risk_kategorileri' => ['atolye_kesme_tezgahi'], 'kontrol_formu' => 'Kesme Tezgâhı (Disk / Testere)'],
        'Taşlama / Bant Zımpara Makinesi' => ['risk_kategorileri' => ['atolye_bant_zimpara_taslama'], 'kontrol_formu' => 'Taşlama / Bant Zımpara Makinesi'],
        'Ekstrüzyon Hattı' => ['risk_kategorileri' => ['atolye_ekstruzyon_hatti'], 'kontrol_formu' => 'Ekstrüzyon Hattı'],
        'Plastik Kırıcı / Granülatör' => ['risk_kategorileri' => ['atolye_plastik_kirici_granulator'], 'kontrol_formu' => 'Plastik Kırıcı / Granülatör'],
        'Merdaneli Makine' => ['risk_kategorileri' => ['atolye_merdaneli_makineler'], 'kontrol_formu' => 'Merdaneli Makine'],
        'Gaz Tüpü ve Şaloma Seti' => ['risk_kategorileri' => ['atolye_gaz_tupleri_ve_saloma'], 'kontrol_formu' => 'Gaz Tüpü ve Şaloma Seti'],

        // Periyodik muayeneli ekipmanlar — risk analizleri kullanıcıdan geldikçe doldurulacak.
        'Hava Kompresörü ve Hava Tankı' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Kompresör'],
        'Forklift / Akülü & Dizel İstifleyici' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Forklift'],
        'Tavan Vinci / Gezer Köprülü Vinç' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Köprü Vinç'],
        'Portal Vinç' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Portal Vinç'],
        'Mobil Vinç / Hiyap' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Mobil Vinç'],
        'Kule Vinç' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Kule Vinç'],
        'Caraskal & Halatlı/Zincirli Çektirme' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Caraskal'],
        'Yükseltilebilir Seyyar İş Platformu (Manlift)' => ['risk_kategorileri' => [], 'kontrol_formu' => 'Makaslı Platform (Manlift)'],
    ],

    // Kategoride en az bir ekipman varsa ayrıca beklenen ortak risk kategorileri. Kendi risk
    // dosyası bu konuları zaten kapsayan tip 'ortak_riskler' => false ile tetiklemez.
    'kategori_ortak_riskleri' => [
        'uretim_atolye' => ['atolye_elektrik_ve_ortak_makine_guvenligi', 'atolye_egitim_ve_yetkilendirme'],
    ],
];
