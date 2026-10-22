<?php

use App\Models\AcilDurumPlani;
use App\Models\AtamaYazisi;
use App\Models\CezaTebligTutanagi;
use App\Models\DofRaporu;
use App\Models\EgitimKatilim;
use App\Models\EgitimSinavi;
use App\Models\FirmaJsa;
use App\Models\IpcTebligi;
use App\Models\IsbasiEgitimTutanagi;
use App\Models\IseDonusBelgesi;
use App\Models\IsIzinFormu;
use App\Models\IsKazasiRaporu;
use App\Models\KazaIstatistigi;
use App\Models\KimyasalRiskDegerlendirmesi;
use App\Models\KkdMatrisi;
use App\Models\KkdZimmetFormu;
use App\Models\KurulToplantisi;
use App\Models\MeslekHastaligiBildirimi;
use App\Models\MuayeneFormu;
use App\Models\OlayKaydi;
use App\Models\OrtamOlcumu;
use App\Models\PeriyodikKontrol;
use App\Models\RiskDegerlendirmesi;
use App\Models\SaglikGozetimi;
use App\Models\SahaAnalizi;
use App\Models\SahaDenetimi;
use App\Models\Sertifika;
use App\Models\Talimat;
use App\Models\TatbikatTutanagi;
use App\Models\TespitOneriDefteri;
use App\Models\YanginGuvenligiDegerlendirmesi;
use App\Models\YillikPlan;
use App\Models\ZiyaretProgrami;
use App\Support\AcilDurumPlaniUretici;
use App\Support\AcilDurumWordUretici;
use App\Support\AtamaYazisiUretici;
use App\Support\AtamaYazisiWordUretici;
use App\Support\CezaTebligTutanagiUretici;
use App\Support\DofRaporuUretici;
use App\Support\EgitimKatilimUretici;
use App\Support\EgitimSinaviUretici;
use App\Support\FirmaJsaUretici;
use App\Support\IpcTebligiUretici;
use App\Support\IsbasiEgitimTutanagiUretici;
use App\Support\IseDonusBelgesiUretici;
use App\Support\IsIzinFormuUretici;
use App\Support\IsKazasiRaporuUretici;
use App\Support\KazaIstatistigiUretici;
use App\Support\KimyasalRiskUretici;
use App\Support\KkdMatrisiUretici;
use App\Support\KkdZimmetFormuUretici;
use App\Support\KurulToplantisiUretici;
use App\Support\MeslekHastaligiBildirimiUretici;
use App\Support\MuayeneFormuUretici;
use App\Support\OlayKaydiUretici;
use App\Support\OrtamOlcumuUretici;
use App\Support\PeriyodikKontrolUretici;
use App\Support\RiskDegerlendirmesiUretici;
use App\Support\SaglikGozetimiUretici;
use App\Support\SahaAnaliziUretici;
use App\Support\SahaDenetimiUretici;
use App\Support\SertifikaUretici;
use App\Support\SertifikaYildizGrupUretici;
use App\Support\TalimatUretici;
use App\Support\TatbikatTutanagiUretici;
use App\Support\TespitOneriDefteriUretici;
use App\Support\YanginGuvenligiUretici;
use App\Support\YillikPlanUretici;
use App\Support\ZiyaretProgramiUretici;

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

    // Periyodik sağlık muayenesi aralığı (yıl) — İşyeri Hekimi ve Diğer Sağlık
    // Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik.
    'saglik_periyodik_yili' => [
        'az_tehlikeli' => 5,
        'tehlikeli' => 3,
        'cok_tehlikeli' => 1,
    ],

    // İş güvenliği uzmanının çalışan başına aylık asgari çalışma süresi (dk) —
    // İş Güvenliği Uzmanlarının Görev, Yetki, Sorumluluk ve Eğitimleri Hk. Yön.
    // Panel "Saha Takvimi" eksik süre hesabında kullanılır.
    'igu_aylik_dk' => [
        'az_tehlikeli' => 10,
        'tehlikeli' => 20,
        'cok_tehlikeli' => 40,
    ],

    // İşyeri hekimi / diğer sağlık personeli aylık asgari süresi (dk / çalışan) —
    // İSG Hizmetleri Yönetmeliği; DSP yalnız çok tehlikeli sınıfta.
    'hekim_aylik_dk' => ['az_tehlikeli' => 5, 'tehlikeli' => 10, 'cok_tehlikeli' => 15],
    'dsp_aylik_dk' => ['az_tehlikeli' => 0, 'tehlikeli' => 0, 'cok_tehlikeli' => 10],

    // Uzman Rapor Merkezi — süreç kategorisi → yasal dayanak. `url` yalnız
    // doğrulanmış resmî bağlantılar (mevzuat.gov.tr / resmigazete.gov.tr);
    // olmayanlar mehse Mevzuat sayfasına yönlenir.
    'uzman_raporu' => [
        'acik_risk_duzeyleri' => ['Yüksek Risk', 'Çok Yüksek Risk', 'Önemli Risk', 'Esaslı Risk', 'Tolerans Gösterilemez Risk'],
        'dayanaklar' => [
            'Eğitim' => ['ad' => 'Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026)', 'url' => 'https://www.resmigazete.gov.tr/eskiler/2026/04/20260402-2.htm'],
            'KKD' => ['ad' => 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik', 'url' => 'https://www.resmigazete.gov.tr/eskiler/2013/07/20130702-2.htm'],
            'Periyodik kontrol' => ['ad' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=18318&MevzuatTur=7&MevzuatTertip=5'],
            'Risk' => ['ad' => '6331 sayılı Kanun Md.10 — risk değerlendirmesi', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5'],
            'Acil durum' => ['ad' => '6331 sayılı Kanun Md.11 — acil durum planları', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5'],
            'Tatbikat' => ['ad' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik — tatbikat', 'url' => null],
            'İSG Kurulu' => ['ad' => '6331 sayılı Kanun Md.22 — iş sağlığı ve güvenliği kurulu', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5'],
            'Olay / SGK bildirimi' => ['ad' => '6331 sayılı Kanun Md.14 — iş kazası kayıt ve bildirimi', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5'],
            'DÖF' => ['ad' => '6331 sayılı Kanun Md.4 — işverenin genel yükümlülüğü', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5'],
            'Ortam ölçümü' => ['ad' => 'İSG Risk Değerlendirmesi Yönetmeliği — ölçüm ve analiz', 'url' => null],
            'SDS' => ['ad' => 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'url' => null],
            'PKD' => ['ad' => 'Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik', 'url' => null],
            'Sözleşme' => ['ad' => 'İş Sağlığı ve Güvenliği Hizmetleri Yönetmeliği — görevlendirme', 'url' => null],
            'Süreç' => ['ad' => '6331 sayılı İş Sağlığı ve Güvenliği Kanunu', 'url' => 'https://www.mevzuat.gov.tr/mevzuat?MevzuatNo=6331&MevzuatTur=1&MevzuatTertip=5'],
        ],
    ],

    // Doküman Yönetimi (isgsuite) — arsiv_dosyalari.kategori anahtarları.
    'dokuman' => [
        'yaklasan_gun' => 30,
        'kategoriler' => [
            'risk_degerlendirmesi' => 'Risk Değerlendirmesi',
            'acil_durum' => 'Acil Durum Planı / Kroki',
            'yillik_plan' => 'Yıllık Plan',
            'egitim' => 'Eğitim',
            'periyodik_kontrol' => 'Periyodik Kontrol',
            'ortam_olcumu' => 'Ortam Ölçümü',
            'talimat' => 'Talimat / Prosedür',
            'sozlesme' => 'Sözleşme / Görevlendirme',
            'kimyasal' => 'Kimyasal / SDS / PKD',
            'olay' => 'İş Kazası / Olay',
            'resmi_yazi' => 'Resmî Yazışma',
            'mevzuat' => 'Mevzuat',
            'diger' => 'Diğer',
        ],
    ],

    // Ana Sayfa — görev durumu (isgsuite "Ana sayfa — görev durumum").
    'ana_sayfa' => [
        // "Yaklaşan" sütunu: termini bu kadar gün içinde olan görevler.
        'yaklasan_gun' => 14,
        // Aylık görevlendirme kapasitesi (saat) — haftalık 45 saat normal çalışma.
        'aylik_kapasite_saat' => 195,
        // İGU belge sınıfının görev yapabileceği tehlike sınıfları
        // (İSG Hizmetleri Yönetmeliği: C → az tehlikeli, B → tehlikeli dahil, A → tümü).
        'sinif_tehlike' => [
            'a_sinifi' => ['az_tehlikeli', 'tehlikeli', 'cok_tehlikeli'],
            'b_sinifi' => ['az_tehlikeli', 'tehlikeli'],
            'c_sinifi' => ['az_tehlikeli'],
        ],
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
        'hazop' => 'HAZOP',
        'fmea' => 'FMEA',
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
            ['min' => 25, 'ad' => 'Çok Yüksek Risk', 'kisa' => 'ÇY', 'renk' => '#7f1d1d', 'eylem' => 'Bu risk kontrol altına alınıncaya kadar çalışma yapılmamalıdır.'],
            ['min' => 15, 'ad' => 'Yüksek Risk', 'kisa' => 'YK', 'renk' => '#dc2626', 'eylem' => 'Bu risk azaltılıncaya kadar iş başlatılmamalı; devam eden işte acil önlem alınmalıdır.'],
            ['min' => 8, 'ad' => 'Orta Düzeyde Risk', 'kisa' => 'OD', 'renk' => '#f59e0b', 'eylem' => 'Belirli bir süre içinde risk azaltıcı önlemler alınmalıdır.'],
            ['min' => 3, 'ad' => 'Katlanılabilir Risk', 'kisa' => 'KT', 'renk' => '#84cc16', 'eylem' => 'Ek kontrol gerekmeyebilir; maliyeti düşük iyileştirmeler düşünülmelidir.'],
            ['min' => 1, 'ad' => 'Önemsiz Risk', 'kisa' => 'ÖS', 'renk' => '#22c55e', 'eylem' => 'Önlem planlamaya / kayıt tutmaya gerek yoktur.'],
        ],
    ],

    'risk_fine_kinney' => [
        'aciklama' => 'Risk = Olasılık × Frekans (maruz kalma) × Şiddet.',
        // Ondalıklı anahtarlar string olmalı (PHP dizisinde float anahtar int'e kırpılır).
        'olasilik' => [
            '0.1' => 'Hemen hemen imkânsız (yalnız teoride)',
            '0.2' => 'Pratik olarak imkânsız',
            '0.5' => 'Zayıf olasılık (beklenmez)',
            '1' => 'Küçük olasılık (ama mümkün)',
            '3' => 'Nadir ama olabilir',
            '6' => 'Kuvvetle muhtemel',
            '10' => 'Beklenir, kesin',
        ],
        // Frekans ve Şiddet açıklamaları kullanıcının SK-RA-01 "Yöntem ve Kaynaklar"
        // ölçekleriyle; Şiddet'te 100 yok — ölüm 30, çoklu ölüm 40 (kullanıcı kararı 14.10.2026).
        // Eski 100'ler 40'a, SK-RA-01 kaynaklı 40 (ölüm) 30'a çevrildi.
        'frekans' => [
            '0.5' => 'Yılda bir veya daha az',
            '1' => 'Yılda birkaç',
            '2' => 'Ayda bir',
            '3' => 'Haftada bir',
            '6' => 'Günlük',
            '10' => 'Sürekli / çok sık',
        ],
        'siddet' => [
            '1' => 'Hafif',
            '3' => 'Küçük yaralanma',
            '7' => 'Ciddi yaralanma',
            '15' => 'Kalıcı hasar',
            '30' => 'Ölüm',
            '40' => 'Çoklu ölüm / felaket',
        ],
        // Etiketler kullanıcının inşaat Fine-Kinney master analiziyle hizalı
        // (200-400 = "Yüksek Risk", <20 = "Kabul Edilebilir Risk").
        'bantlar' => [
            ['min' => 400, 'ad' => 'Çok Yüksek Risk', 'kisa' => 'ÇY', 'renk' => '#7f1d1d', 'eylem' => 'Tolere edilemez risk! Çalışma derhal durdurulmalıdır.'],
            ['min' => 200, 'ad' => 'Yüksek Risk', 'kisa' => 'YK', 'renk' => '#dc2626', 'eylem' => 'Acil tedbir gerektirir; kısa dönemde (birkaç ay) iyileştirme yapılmalıdır.'],
            ['min' => 70, 'ad' => 'Önemli Risk', 'kisa' => 'ÖN', 'renk' => '#f59e0b', 'eylem' => 'DÖF planlanmalı; yıl içinde iyileştirme yapılmalıdır.'],
            ['min' => 20, 'ad' => 'Olası Risk', 'kisa' => 'OL', 'renk' => '#84cc16', 'eylem' => 'Gözetim altında tutulmalı, iyileştirme planlanmalıdır.'],
            ['min' => 0, 'ad' => 'Kabul Edilebilir Risk', 'kisa' => 'KE', 'renk' => '#22c55e', 'eylem' => 'Mevcut kontrol tedbirleri yeterlidir; sürekli izlenir.'],
        ],
    ],

    // HAZOP: yapısal olarak 5x5 Matris ile aynı (Olasılık × Şiddet, iki eksen) —
    // yalnız ölçek metinleri kılavuz-kelime/proses sapması bağlamına uyarlanmış.
    'risk_hazop' => [
        'aciklama' => 'Risk = Olasılık × Şiddet (HAZOP — kılavuz kelime ile sapma analizi sonrası nitel değerlendirme).',
        'olasilik' => [
            '1' => 'Çok Düşük (sapma pratik olarak beklenmez)',
            '2' => 'Düşük (sapma nadiren olabilir)',
            '3' => 'Orta (sapma olabilir)',
            '4' => 'Yüksek (sapma muhtemel)',
            '5' => 'Çok Yüksek (sapma sık beklenir)',
        ],
        'siddet' => [
            '1' => 'Önemsiz (işletme etkisi yok)',
            '2' => 'Hafif (küçük duruş / hasar)',
            '3' => 'Orta (yaralanma, çevresel etki, kısa duruş)',
            '4' => 'Ciddi (ağır yaralanma, önemli çevresel/maddi hasar)',
            '5' => 'Felaket (ölüm, büyük patlama/yangın, ağır çevresel etki)',
        ],
        'bantlar' => [
            ['min' => 20, 'ad' => 'Çok Yüksek Risk', 'kisa' => 'ÇY', 'renk' => '#7f1d1d', 'eylem' => 'Kabul edilemez — proses/ekipman devreye alınmadan tasarım değişikliği veya ek koruma zorunlu.'],
            ['min' => 12, 'ad' => 'Yüksek Risk', 'kisa' => 'YK', 'renk' => '#dc2626', 'eylem' => 'Acil ek koruma/alarm/kilitleme gerekir; kısa sürede kapatılmalıdır.'],
            ['min' => 6, 'ad' => 'Orta Düzeyde Risk', 'kisa' => 'OD', 'renk' => '#f59e0b', 'eylem' => 'Belirli bir süre içinde iyileştirme planlanmalıdır.'],
            ['min' => 3, 'ad' => 'Katlanılabilir Risk', 'kisa' => 'KT', 'renk' => '#84cc16', 'eylem' => 'Mevcut korumalar izlenerek sürdürülebilir; ek iyileştirme düşünülebilir.'],
            ['min' => 1, 'ad' => 'Önemsiz Risk', 'kisa' => 'ÖS', 'renk' => '#22c55e', 'eylem' => 'Ek tedbir gerekmez, kayıt yeterlidir.'],
        ],
    ],

    // FMEA: üç eksenli (RiskSkorlama::UC_EKSENLI_YONTEMLER) — RPN = Şiddet(S) ×
    // Oluşma(O) × Saptanabilirlik(D). Veritabanı sütunları yeniden kullanılıyor:
    // 'olasilik' = Oluşma (O), 'frekans' = Saptanabilirlik (D), 'siddet' = Şiddet (S)
    // (bkz. RiskSkorlama::eksenEtiketleri — ekranlarda doğru başlıkla gösterilir).
    'risk_fmea' => [
        'aciklama' => 'RPN (Risk Öncelik Sayısı) = Şiddet (S) × Oluşma Olasılığı (O) × Saptanabilirlik (D).',
        'olasilik' => [
            '1' => 'Neredeyse hiç (yılda 1\'den az)',
            '2' => 'Çok düşük (yılda birkaç kez)',
            '3' => 'Düşük (ayda bir)',
            '4' => 'Düşük-orta (ayda birkaç kez)',
            '5' => 'Orta (haftada bir)',
            '6' => 'Orta-yüksek (haftada birkaç kez)',
            '7' => 'Yüksek (günde bir)',
            '8' => 'Çok yüksek (günde birkaç kez)',
            '9' => 'Aşırı yüksek (vardiyada birkaç kez)',
            '10' => 'Neredeyse kesin (sürekli)',
        ],
        'frekans' => [
            '1' => 'Kesin saptanır (otomatik kilitleme/durdurma)',
            '2' => 'Çok yüksek saptanabilirlik (otomatik alarm)',
            '3' => 'Yüksek saptanabilirlik (rutin kontrol her zaman yakalar)',
            '4' => 'Orta-yüksek saptanabilirlik',
            '5' => 'Orta saptanabilirlik (rutin kontrol genelde yakalar)',
            '6' => 'Orta-düşük saptanabilirlik',
            '7' => 'Düşük saptanabilirlik (rastgele/örnekleme kontrol)',
            '8' => 'Çok düşük saptanabilirlik',
            '9' => 'Neredeyse imkânsız (görsel/deneyime dayalı, güvenilmez)',
            '10' => 'Saptama yok (hiçbir kontrol yok)',
        ],
        'siddet' => [
            '1' => 'Önemsiz (fark edilmez etki)',
            '2' => 'Çok hafif',
            '3' => 'Hafif (küçük rahatsızlık/duruş)',
            '4' => 'Küçük (küçük yaralanma, kısa duruş)',
            '5' => 'Orta (tedavi gerektiren yaralanma)',
            '6' => 'Orta-ciddi (kısmi iş göremezlik)',
            '7' => 'Ciddi (ağır yaralanma, uzun duruş)',
            '8' => 'Çok ciddi (kalıcı sakatlık, büyük hasar)',
            '9' => 'Aşırı ciddi (tek ölüm riski)',
            '10' => 'Felaket (çoklu ölüm/kalıcı çevre felaketi)',
        ],
        // RPN teorik aralığı 1-1000; pratikte çoğu değer 1-343 (7'nin küpü) bandında yoğunlaşır.
        'bantlar' => [
            ['min' => 200, 'ad' => 'Çok Yüksek Risk (RPN)', 'kisa' => 'ÇY', 'renk' => '#7f1d1d', 'eylem' => 'Öncelikli, acil önleyici faaliyet zorunlu.'],
            ['min' => 100, 'ad' => 'Yüksek Risk (RPN)', 'kisa' => 'YK', 'renk' => '#dc2626', 'eylem' => 'Kısa sürede önleyici faaliyet planlanmalıdır.'],
            ['min' => 50, 'ad' => 'Orta Risk (RPN)', 'kisa' => 'OR', 'renk' => '#f59e0b', 'eylem' => 'Belirli bir süre içinde iyileştirme değerlendirilmelidir.'],
            ['min' => 20, 'ad' => 'Düşük Risk (RPN)', 'kisa' => 'DŞ', 'renk' => '#84cc16', 'eylem' => 'İzlenmeye devam edilmeli, düşük öncelikli iyileştirme düşünülebilir.'],
            ['min' => 0, 'ad' => 'Önemsiz Risk (RPN)', 'kisa' => 'ÖS', 'renk' => '#22c55e', 'eylem' => 'Ek faaliyet gerekmez.'],
        ],
    ],

    // Risk maddesi durumları
    'risk_madde_durumlari' => [
        'acik' => 'Açık',
        'devam' => 'Devam Ediyor',
        'kapali' => 'Kapalı (önlem alındı)',
    ],

    // Saha tehlike kategorileri (75) — isgsuite "İncelenecek tehlikeler".
    // Hızlı Saha Bulgusu kategori listesi + AI Saha Analizi odak seçimi.
    'saha_tehlike_kategorileri' => [
        'Genel işyeri düzeni ve temizlik', 'Kayma, takılma ve düşme', 'Yüksekte çalışma', 'Merdivenler', 'İskeleler',
        'Korkuluklar ve kenar koruma', 'Açıklıklar ve döşeme boşlukları', 'Çatı çalışmaları', 'İnşaat ve şantiye güvenliği', 'Kazı ve kanal çalışmaları',
        'Yıkım çalışmaları', 'Kalıp ve beton işleri', 'Donatı ve demir işleri', 'Kaldırma ve taşıma işleri', 'Vinçler ve kaldırma ekipmanları',
        'Sapan, halat ve kaldırma aksesuarları', 'Forklift ve iş makineleri', 'Araç-yaya trafiği', 'Makine ve ekipman güvenliği', 'Makine koruyucuları',
        'Kilitleme/etiketleme ve enerji izolasyonu', 'Elektrik güvenliği', 'Elektrik panoları ve kablolar', 'Topraklama ve kaçak akım', 'Yangın güvenliği',
        'Acil çıkışlar ve kaçış yolları', 'Yangın tüpleri ve yangın ekipmanları', 'Patlama ve parlama riski', 'Patlayıcı ortamlar', 'Sıcak işler',
        'Kaynak, kesme ve taşlama', 'Kimyasal maddeler', 'Kimyasal etiketleme ve SDS', 'Kimyasal depolama', 'Sızıntı ve dökülmeler',
        'Basınçlı kaplar', 'Tüpler ve basınçlı gazlar', 'Kapalı alanlar', 'Havalandırma', 'Toz ve silis maruziyeti',
        'Asbest ihtimali', 'Gürültü', 'Titreşim', 'Aydınlatma', 'Isı, soğuk ve termal ortam',
        'Radyasyon', 'Ergonomi', 'Manuel taşıma', 'Tekrarlı işler', 'Kişisel koruyucu donanımlar',
        'İş kıyafeti ve görünürlük', 'Acil durum ve tahliye', 'İlk yardım', 'Sağlık hizmetleri ve hastane riskleri', 'Biyolojik riskler',
        'Kesici-delici tıbbi atıklar', 'Gıda ve hijyen', 'Tarım ve hayvancılık', 'Orman ve peyzaj işleri', 'Maden ve agrega',
        'Akü, pil ve enerji depolama', 'Şarj alanları', 'Isıl kaçak ve batarya yangını', 'Depo ve raf sistemleri', 'Yükleme-boşaltma rampaları',
        'Atık yönetimi', 'Kanalizasyon ve atık su', 'Enerji ve elektrik üretim tesisleri', 'Su baskını ve doğal afetler', 'Psikososyal riskler',
        'Çalışma izinleri ve talimatlar', 'Eğitim ve yetkinlik eksiklikleri', 'İşaretleme ve uyarı levhaları', 'İşyerinin bina ve eklentileri', 'Diğer görülebilir tehlikeler',
    ],

    // Hızlı Saha Bulgusu — 5×5 risk ölçeği etiketleri.
    'saha_bulgu' => [
        'olasilik' => [1 => 'Çok düşük', 2 => 'Düşük', 3 => 'Orta', 4 => 'Yüksek', 5 => 'Çok yüksek'],
        'siddet' => [1 => 'Çok hafif', 2 => 'Hafif', 3 => 'Orta', 4 => 'Ciddi', 5 => 'Çok ciddi'],
        'durumlar' => ['acik' => 'Açık', 'devam_ediyor' => 'Devam ediyor', 'ertelendi' => 'Ertelendi', 'kapandi' => 'Kapandı'],
        'max_foto' => 5,
    ],

    // Patlamadan Korunma Dokümanı (PKD) Sicili — isgsuite "PKD Sicili".
    // Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hk. Yön.
    'pkd' => [
        'ortam_turleri' => [
            'gaz_buhar_sis' => 'Gaz / buhar / sis',
            'toz' => 'Yanıcı toz',
            'hibrit' => 'Hibrit (gaz + toz)',
        ],
        'zonelar' => [
            'zone_0' => 'Zone 0',
            'zone_1' => 'Zone 1',
            'zone_2' => 'Zone 2',
            'zone_20' => 'Zone 20',
            'zone_21' => 'Zone 21',
            'zone_22' => 'Zone 22',
        ],
        'tutusturucular' => [
            'sicak_calisma' => 'Sıcak çalışma / açık alev',
            'elektriksel' => 'Elektriksel ekipman',
            'statik' => 'Statik elektrik',
            'mekanik' => 'Mekanik kıvılcım / sıcak yüzey',
            'arac' => 'Araç ve hareketli ekipman',
            'yildirim' => 'Yıldırım / atmosferik etki',
        ],
        'onlemler' => [
            'havalandirma' => 'Yeterli havalandırma',
            'ex_ekipman' => 'Uygun Ex ekipman seçimi',
            'topraklama' => 'Topraklama ve eşpotansiyel bağlantı',
            'sicak_calisma_izni' => 'Sıcak çalışma izin sistemi',
            'algilama' => 'Gaz / toz algılama ve alarm',
            'toz_temizlik' => 'Toz birikimi ve temizlik kontrolü',
            'bakim' => 'Periyodik bakım ve muayene',
            'egitim' => 'Çalışan eğitimi ve bilgilendirme',
        ],
        'durumlar' => [
            'taslak' => 'Taslak',
            'aktif' => 'Aktif',
            'revizyon' => 'Revizyon gerekiyor',
            'arsiv' => 'Arşiv',
        ],
        'yaklasan_gun' => 30,
    ],

    // Kimyasal Ürün Sicili — SDS / GHS-CLP.
    'kimyasal' => [
        'fiziksel_hal' => [
            'sivi' => 'Sıvı',
            'kati' => 'Katı',
            'gaz' => 'Gaz',
            'aerosol' => 'Aerosol',
            'toz' => 'Toz / Granül',
        ],
        // GHS/CLP tehlike sınıfları (piktogramlar).
        'ghs' => [
            'ghs01' => 'GHS01 — Patlayıcı',
            'ghs02' => 'GHS02 — Alevlenir',
            'ghs03' => 'GHS03 — Oksitleyici',
            'ghs04' => 'GHS04 — Basınç altında gaz',
            'ghs05' => 'GHS05 — Aşındırıcı / Korozif',
            'ghs06' => 'GHS06 — Akut toksik (kurukafa)',
            'ghs07' => 'GHS07 — Tahriş edici / Zararlı',
            'ghs08' => 'GHS08 — Sağlığa uzun vadeli tehdit (KMR)',
            'ghs09' => 'GHS09 — Çevreye zararlı (sucul)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kimyasal Risk Değerlendirmesi — kontrol bantlama (COSHH Essentials)
    |--------------------------------------------------------------------------
    | Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yön.
    | Tehlike grubu (A-E, S) × kullanım miktarı × uçuculuk/tozlaşma → kontrol
    | yaklaşımı (1: genel havalandırma · 2: lokal havalandırma · 3: kapalı sistem
    | · 4: özel — uzman görüşü). CMR maddelerde en az yaklaşım 3.
    */
    'kimyasal_risk' => [
        'tehlike_gruplari' => [
            'A' => 'A — Düşük tehlike (tahriş etmeyen)',
            'B' => 'B — Zararlı (H302/H312/H332, cilt/göz tahrişi)',
            'C' => 'C — Ciddi zarar (H301/H311/H331, H314 aşındırıcı)',
            'D' => 'D — Yüksek tehlike (H300/H310/H330, duyarlılaştırıcı)',
            'E' => 'E — Çok yüksek / CMR (H340/H350/H360, H334)',
            'S' => 'S — Deri / göz teması yolu (ek önlem)',
        ],
        'miktarlar' => [
            'az' => 'Az (< 100 g veya ml / gün)',
            'orta' => 'Orta (100 g–kg / ml–L / gün)',
            'cok' => 'Çok (> kg veya L / gün)',
        ],
        'ucuculuk' => [
            'dusuk' => 'Düşük uçuculuk / az tozlaşan (pelet, pasta)',
            'orta' => 'Orta (sıvı, kristal toz)',
            'yuksek' => 'Yüksek uçuculuk / ince toz (kaynama <50°C, uçuşan toz)',
        ],
        'maruziyet_yollari' => [
            'soluma' => 'Soluma',
            'deri' => 'Deri teması / emilim',
            'yutma' => 'Yutma',
            'goz' => 'Göz sıçraması',
        ],
        'yaklasimlar' => [
            1 => '1 — Genel havalandırma / iyi çalışma uygulaması',
            2 => '2 — Lokal cebri havalandırma (LEV)',
            3 => '3 — Kapalı sistem / muhafaza',
            4 => '4 — Özel önlem — uzman görüşü gerekli',
        ],
    ],

    // İSG Afiş / Pano kütüphanesi.
    'afis' => [
        'kategoriler' => [
            'genel' => 'Genel İSG',
            'kimyasal' => 'Kimyasal Güvenliği / GHS',
            'acil_durum' => 'Acil Durum',
            'yangin' => 'Yangın',
            'kkd' => 'KKD Kullanımı',
            'elektrik' => 'Elektrik Güvenliği',
            'yuksekte_calisma' => 'Yüksekte Çalışma',
            'is_makinesi' => 'İş Makinesi / Trafik',
            'ilk_yardim' => 'İlk Yardım',
            'uyari_ikaz' => 'Uyarı / İkaz Levhaları',
        ],
    ],

    // Uzaktan Eğitim (Video LMS) — İSG uzmanı paket oluşturur, çalışana atar; çalışan
    // portaldan (panel: portal, /egitim) izler, final sınavına girer, belge alır.
    'uzaktan_egitim' => [
        'durumlar' => [
            'atandi' => 'Atandı',
            'devam' => 'Devam Ediyor',
            'basarisiz' => 'Sınav Başarısız',
            'tamamlandi' => 'Tamamlandı',
        ],
        // İşe başlama eğitimi (Yön. 02.04.2026 Md.7) uygulamalı ve yüz yüze
        // verildiğinden uzaktan atanamaz; eski "isbasi" kayıtları yalnız etiketle gösterilir.
        'egitim_turleri' => [
            'ilk_defa' => 'İlk Defa',
            'yenileme' => 'Yenileme',
        ],
        'eski_egitim_turleri' => [
            'isbasi' => 'İşbaşı',
        ],

        // ---- Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026) ----
        // Md.16/3: 100 üzerinden en az 60 başarılı; ilk sınav + en fazla 2 tekrar,
        // üçünde de başarısız olan temel eğitime yeniden katılır.
        'gecme_puani_alt_sinir' => 60,
        'sinav_hakki' => 3,
        // Md.12/5-c: aktif katılım — videonun her N dakikasında açılır pencere + kısa soru.
        'yoklama_dk' => 5,
        // Md.12/3: dördüncü konu başlığı bu sınıflarda yüz yüze verilir; uzaktan
        // eğitim tek başına temel eğitimi tamamlamış saymaz.
        'yuz_yuze_dorduncu_konu' => ['tehlikeli', 'cok_tehlikeli'],
        // Md.13: dördüncü konu başlığına ayrılan süre (ders saati).
        'dorduncu_konu_saat' => ['az_tehlikeli' => 2, 'tehlikeli' => 3, 'cok_tehlikeli' => 4],
        // Paket süresi kontrolünde 1 ders saatinin video karşılığı (dk).
        'ders_saati_dk' => 45,
        // Md.8: temel eğitim işe başlamadan itibaren en geç 3 ay içinde tamamlanır.
        'temel_egitim_son_ay' => 3,
        // Ek-1 Eğitim Konuları Tablosu (RG 02.04.2026, birebir) — Ek-2 belgenin arka yüzü.
        'ek1_konulari' => [
            '1. Genel konular' => [
                'a) Çalışma mevzuatı ile ilgili bilgiler',
                'b) Çalışanların yasal hak ve sorumlulukları',
                'c) İşyeri temizliği ve düzeni',
                'ç) İş kazası ve meslek hastalığından doğan hukuki sonuçlar',
            ],
            '2. Sağlık konuları' => [
                'a) Meslek hastalıklarının sebepleri',
                'b) Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması',
                'c) Biyolojik ve psikososyal risk etmenleri',
                'ç) İlk yardım',
                'd) Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı',
            ],
            '3. Teknik konular' => [
                'a) Kimyasal, fiziksel ve ergonomik risk etmenleri',
                'b) Elle kaldırma ve taşıma',
                'c) Parlama, patlama',
                'ç) Yangın ve yangından korunma',
                'd) İş ekipmanlarının güvenli kullanımı',
                'e) Ekranlı araçlarla çalışma',
                'f) Elektrik, tehlikeleri, riskleri ve önlemleri',
                'g) İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanması',
                'ğ) Sağlık ve güvenlik işaretleri',
                'h) Kişisel koruyucu donanım kullanımı',
                'ı) İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürü',
                'i) Acil durumlar, tahliye ve kurtarma',
            ],
        ],
        'ek1_dorduncu_baslik' => '4. İşe ve işyerine özgü riskler ve risk değerlendirmesine dayalı konular (Tehlikeli veya Çok Tehlikeli Sınıf) * / Faaliyetin Genel Tehlike ve Riskleri (Az Tehlikeli Sınıf) *',
        'ek1_dorduncu_a' => 'a) (Tehlikeli veya Çok Tehlikeli Sınıf) İşyerinin acil durum planı, risk değerlendirmesi dokümanı, bulunması halinde patlamadan korunma dokümanı ve iş sağlığı ve güvenliği mevzuatı kapsamında hazırlanan diğer dokümanlarda belirlenmiş olan hususlar ile işyerine ve işe özgü hususları içeren yüksekte çalışma, kapalı ortamda çalışma, yangın, radyasyon riskinin bulunduğu ortamlarda çalışma, kaynakla çalışma, özel risk taşıyan ekipman ile çalışma, kanserojen veya mutajen maddelerle, kimyasal veya biyolojik etkenlerle çalışma ve benzeri konular',
        'ek1_dorduncu_b' => 'b) (Az Tehlikeli Sınıf) Faaliyetin genel tehlike ve riskleri (yüksekte çalışma, yüksekten düşme, kapalı ortamda çalışma, yangın, özel risk taşıyan ekipmanla çalışma gibi.)',
        // Temel İSG eğitimi yenileme periyodu (yıl) — Yenileme Takibi bunu kullanır.
        'yenileme_yili' => [
            'az_tehlikeli' => 3,
            'tehlikeli' => 2,
            'cok_tehlikeli' => 1,
        ],
    ],

    // Onaylı Defter Nüshaları — firma + tür başına sıralı numara verilir.
    'onayli_defter' => [
        'turleri' => [
            'tespit_oneri' => 'İş Güvenliği Uzmanı ve İşyeri Hekimi Tespit ve Öneri Defteri',
            'isyeri_hekimi' => 'İşyeri Hekimi Defteri',
            'diger' => 'Diğer Onaylı Defter',
        ],
        // En son onaylı nüsha yüklemesinden itibaren bu kadar ay içinde yeni
        // nüsha yüklenmezse "süresi dolan" sayılır (kullanıcı talimatı: 3 ayda bir).
        'periyot_ay' => 3,
        // Vade tarihine bu kadar gün kala "yaklaşan" uyarısı gösterilir.
        'yaklasan_gun' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | İş Ekipmanları Periyodik Kontrol — İş Ekipmanlarının Kullanımında Sağlık ve
    | Güvenlik Şartları Yönetmeliği EK-3. `periyot_ay` = ilgili standart/üretici
    | daha kısa bir süre öngörmüyorsa azami periyot. Kapasite raporundan belirlenen
    | ekipmanlar bu katalogdan seçilir (ya da serbest eklenir); her ekipman için
    | son kontrol tarihi + sonuç + sonraki tarih girilir.
    */
    /*
    | Ekipman & Periyodik Kontrol Motoru — İş Ekipmanlarının Kullanımında Sağlık
    | ve Güvenlik Şartları Yönetmeliği EK-III. Her ekipman kategori + tip ile
    | tanımlanır; tip seçilince yasal standart, deney/test ve önerilen periyot
    | otomatik gelir (elle değiştirilir). "Vize" = periyodik muayene geçerliliği.
    */
    'periyodik_kontrol' => [
        'sonuclar' => [
            'bekliyor' => 'Muayene Bekliyor',
            'uygun' => 'Uygun (kullanılabilir)',
            'sartli' => 'Şartlı / Kısmi Uygun',
            'uygun_degil' => 'Uygun Değil (kullanım dışı)',
        ],

        'periyot_secenekleri' => [
            3 => '3 Ay',
            6 => '6 Ay',
            12 => '12 Ay (Standart 1 Yıl)',
            24 => '24 Ay (2 Yıl)',
            36 => '36 Ay (3 Yıl)',
            60 => '60 Ay (5 Yıl)',
        ],

        // Vize durumu: "yaklaşan" eşiği (gün).
        'vize_yaklasan_gun' => 30,

        'kategoriler' => [
            'kaldirma_iletme' => ['ad' => 'Kaldırma & İletme Araçları', 'mevzuat' => 'Yönetmelik Ek-III Madde 2.2'],
            'basincli_kap' => ['ad' => 'Basınçlı Kap ve Tesisatlar', 'mevzuat' => 'Yönetmelik Ek-III Madde 2.1'],
            'elektrik_topraklama' => ['ad' => 'Elektrik & Topraklama', 'mevzuat' => 'Yönetmelik Ek-III Madde 2.3'],
            'tesisat_yangin' => ['ad' => 'Tesisat & Yangın', 'mevzuat' => 'Yönetmelik Ek-III Madde 2.4'],
            'is_hijyeni' => ['ad' => 'İş Hijyeni & Ortam Ölçümleri', 'mevzuat' => 'İş Hijyeni Ölçüm, Test ve Analizi Yapan Laboratuvarlar Hakkında Yönetmelik'],
            // Periyodik muayene (vize) takibi YOK — 'periyodik' => false. Firmaya eklenince
            // risk değerlendirmesi ve kontrol formu bağlantısı çalışır (config/ekipman_katalogu.php);
            // vize durumu "Muayene gerekmiyor" görünür, Kontrol Merkezi periyodik rapor beklemez.
            'uretim_atolye' => ['ad' => 'Üretim & Atölye Makineleri', 'mevzuat' => 'İş Ekipmanları Yön. — kullanım öncesi / düzenli kullanıcı kontrolü', 'periyodik' => false],
            'diger_ekipman' => ['ad' => 'Diğer İş Ekipmanları & Araçlar', 'mevzuat' => 'İş Ekipmanları Yön. — kullanım öncesi kontrol (Ek-III periyodik muayene kapsamı dışı)', 'periyodik' => false],
        ],

        'tipler' => [
            'kaldirma_iletme' => [
                ['ad' => 'Tavan Vinci / Gezer Köprülü Vinç', 'periyot_ay' => 12, 'standart' => 'TS ISO 9927-1', 'deney' => 'Statik 1.25 kat, Dinamik 1.1 kat yük testi'],
                ['ad' => 'Portal Vinç', 'periyot_ay' => 12, 'standart' => 'TS ISO 9927-1', 'deney' => 'Statik 1.25 kat, Dinamik 1.1 kat yük testi'],
                ['ad' => 'Mobil Vinç / Hiyap', 'periyot_ay' => 12, 'standart' => 'TS ISO 9927-1 / FEM', 'deney' => 'Yük testi + yapısal muayene'],
                ['ad' => 'Kule Vinç', 'periyot_ay' => 12, 'standart' => 'TS ISO 9927-1 / TS EN 14439', 'deney' => 'Statik/dinamik yük testi, kurulum muayenesi'],
                ['ad' => 'Forklift / Akülü & Dizel İstifleyici', 'periyot_ay' => 12, 'standart' => 'TS 10689 / TS ISO 3691', 'deney' => 'Nominal yükte kaldırma/eğim testi, fren-direksiyon'],
                ['ad' => 'Transpalet (Motorlu / Manuel)', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 3691-5', 'deney' => 'Yük testi ve fonksiyon kontrolü'],
                ['ad' => 'Araç Kaldırma Lifti', 'periyot_ay' => 12, 'standart' => 'TS EN 1493', 'deney' => 'Nominal yük + %10 aşırı yük testi'],
                ['ad' => 'Caraskal & Halatlı/Zincirli Çektirme', 'periyot_ay' => 12, 'standart' => 'TS EN 13157', 'deney' => 'Statik 1.25 kat yük testi'],
                ['ad' => 'Sütunlu Pergel Vinç', 'periyot_ay' => 12, 'standart' => 'TS EN 14985', 'deney' => 'Statik 1.25 kat, Dinamik 1.1 kat yük testi'],
                ['ad' => 'Teleskopik Yükleyici (Manitou / Telehandler)', 'periyot_ay' => 12, 'standart' => 'TS EN 1459-1', 'deney' => 'Yük diyagramına göre kaldırma ve stabilite kontrolü; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Yükseltilebilir Seyyar İş Platformu (Manlift)', 'periyot_ay' => 6, 'standart' => 'TS EN 280', 'deney' => 'Stabilite + yük testi, acil indirme'],
                ['ad' => 'Asansör (İnsan / Yük)', 'periyot_ay' => 12, 'standart' => 'TS EN 81-20 / Asansör Yön.', 'deney' => 'A tipi muayene kuruluşu yıllık kontrolü'],
                ['ad' => 'Yapı / Cephe İskelesi', 'periyot_ay' => 6, 'standart' => 'TS EN 12811 / Yapı İşleri Yön.', 'deney' => 'Kurulum sonrası ve periyodik statik muayene'],
                ['ad' => 'Kaldırma Aksesuarları (sapan, zincir, halat, mapa)', 'periyot_ay' => 6, 'standart' => 'TS EN 13414 / TS EN 818', 'deney' => 'Gözle muayene + deformasyon/aşınma kontrolü'],
                // Ekipman Değerlendirme Aracı karşılaştırması (19.10.2026)
                ['ad' => 'Araç Üstü Yüksek Platform (Sepetli Platform / Cherry Picker)', 'periyot_ay' => 6, 'standart' => 'TS EN 280', 'deney' => 'Stabilite + yük testi, acil indirme'],
                ['ad' => 'Gondol (Asılı Çalışma Platformu)', 'periyot_ay' => 6, 'standart' => 'TS EN 1808', 'deney' => 'Yük testi, düşme önleyici ve halat muayenesi'],
                ['ad' => 'Seyyar Yük Kaldırma Lifti (Sepetli Vinç / Mobilya Asansörü)', 'periyot_ay' => 12, 'standart' => 'TS ISO 9927-1', 'deney' => 'Statik/dinamik yük testi, sınırlayıcı ve fren kontrolü'],
            ],
            'basincli_kap' => [
                ['ad' => 'Hava Kompresörü ve Hava Tankı', 'periyot_ay' => 12, 'standart' => 'TS 1203 EN 286-1', 'deney' => 'Hidrostatik test (1.5 × işletme basıncı), emniyet valfi'],
                ['ad' => 'Hidrofor & Genleşme Deposu', 'periyot_ay' => 12, 'standart' => 'TS EN 13831', 'deney' => 'Basınç testi + membran/valf kontrolü'],
                ['ad' => 'Buhar Kazanı', 'periyot_ay' => 12, 'standart' => 'TS 2025 / TS EN 12953', 'deney' => 'Hidrostatik test + iç/dış muayene, emniyet donanımı'],
                ['ad' => 'Kalorifer Kazanı / Sıcak Su Kazanı', 'periyot_ay' => 12, 'standart' => 'TS EN 12953 / TS 4041', 'deney' => 'Hidrostatik test + emniyet valfi kontrolü'],
                ['ad' => 'Kızgın Yağ Kazanı', 'periyot_ay' => 12, 'standart' => 'TS EN 12952', 'deney' => 'İç/dış muayene + basınç testi'],
                ['ad' => 'Boyler / Sıcak Su Akümülasyon Tankı', 'periyot_ay' => 12, 'standart' => 'TS EN 12897', 'deney' => 'Hidrostatik test + katodik koruma kontrolü'],
                ['ad' => 'LPG / Kriyojenik Sıvı Gaz Deposu', 'periyot_ay' => 12, 'standart' => 'TS EN 12817 / TS EN 13458', 'deney' => 'İç/dış muayene, hidrostatik test, valf-armatür'],
            ],
            'elektrik_topraklama' => [
                ['ad' => 'Topraklama Tesisatı ve Çevrim Empedansı', 'periyot_ay' => 12, 'standart' => 'TS HD 60364-6', 'deney' => 'Toprak direnci (Ohm) ve süreklilik ölçümü, çevrim empedansı'],
                ['ad' => 'Paratoner & Yıldırımdan Korunma Tesisatı', 'periyot_ay' => 12, 'standart' => 'TS EN 62305', 'deney' => 'Yakalama ucu, iniş iletkeni ve toprak direnci ölçümü'],
                ['ad' => 'Elektrik Ana & Tali Dağıtım Panoları', 'periyot_ay' => 12, 'standart' => 'TS HD 60364 / Elektrik İç Tesisleri Yön.', 'deney' => 'Yalıtım direnci, koruma iletkeni sürekliliği, termografi'],
                ['ad' => 'Kaçak Akım Koruma Rölesi (RCD) Testi', 'periyot_ay' => 12, 'standart' => 'TS HD 60364-6', 'deney' => 'Açma akımı ve açma süresi ölçümü'],
                ['ad' => 'Pano Termal Kamera İncelemesi', 'periyot_ay' => 12, 'standart' => 'TS EN 60204-1', 'deney' => 'Termografik sıcaklık haritalama'],
                ['ad' => 'Trafo & Yüksek Gerilim İşletmesi', 'periyot_ay' => 12, 'standart' => 'Elektrik Kuvvetli Akım Tesisleri Yön.', 'deney' => 'Yağ analizi, izolasyon direnci, topraklama'],
            ],
            'tesisat_yangin' => [
                ['ad' => 'Yangın Tesisatı, Motopomplar & Hidrantlar', 'periyot_ay' => 12, 'standart' => 'TS EN 12845 / BYKHY', 'deney' => 'Debi-basınç testi, pompa performansı, hidrant kontrolü'],
                ['ad' => 'Yangın Söndürme Cihazları (YSC)', 'periyot_ay' => 12, 'standart' => 'TS ISO 11602-2 / TS 862-7 EN 3-7', 'deney' => 'Yıllık kontrol; her 4 yılda tekrar dolum, 10 yılda hidrostatik'],
                ['ad' => 'Yangın Dolapları', 'periyot_ay' => 12, 'standart' => 'TS EN 671-3', 'deney' => 'Hortum basınç testi, lüle ve vana kontrolü'],
                ['ad' => 'Yangın Algılama ve Alarm Sistemi', 'periyot_ay' => 12, 'standart' => 'TS EN 54 / BYKHY', 'deney' => 'Dedektör, buton, siren ve panel fonksiyon testi'],
                ['ad' => 'Otomatik Sprinkler Sistemi', 'periyot_ay' => 12, 'standart' => 'TS EN 12845', 'deney' => 'Alarm vanası, test-drenaj, pompa otomatik devreye girme testi'],
                ['ad' => 'Acil Aydınlatma ve Yönlendirme', 'periyot_ay' => 12, 'standart' => 'TS EN 50172 / TS EN 1838', 'deney' => 'Şebeke kesintisinde süre ve aydınlık düzeyi testi'],
                ['ad' => 'Havalandırma ve İklimlendirme Tesisatı', 'periyot_ay' => 12, 'standart' => 'TS EN 12599', 'deney' => 'Hava debisi, filtre ve duman tahliye fonksiyon testi'],
                ['ad' => 'Doğalgaz & LPG Boru Hattı Tesisatı', 'periyot_ay' => 12, 'standart' => 'TS EN 1775 / Gaz Yön.', 'deney' => 'Sızdırmazlık (basınç düşme) testi, gaz dedektörü kontrolü'],
            ],
            'is_hijyeni' => [
                ['ad' => 'İş Hijyeni Kişisel Gürültü Maruziyeti', 'periyot_ay' => 24, 'standart' => 'TS ISO 1999', 'deney' => 'LEX,8h (dBA) ve Ppeak (dBC) dozimetre ölçümü'],
                ['ad' => 'Solunabilir ve Toplam Toz Ölçümü', 'periyot_ay' => 24, 'standart' => 'TS EN 481 / NIOSH 0500-0600', 'deney' => 'Gravimetrik toz örnekleme, kristal silika analizi'],
                ['ad' => 'Aydınlatma Düzeyi Ölçümü', 'periyot_ay' => 24, 'standart' => 'TS EN 12464-1', 'deney' => 'Lüksmetre ile bölge bazlı ölçüm'],
                ['ad' => 'Termal Konfor Ölçümü (PMV/PPD/WBGT)', 'periyot_ay' => 24, 'standart' => 'TS EN ISO 7730 / TS EN ISO 7243', 'deney' => 'Sıcaklık, nem, hava akış hızı, radyant sıcaklık'],
                ['ad' => 'El-Kol / Tüm Vücut Titreşim Maruziyeti', 'periyot_ay' => 24, 'standart' => 'TS EN ISO 5349 / TS EN ISO 2631', 'deney' => 'A(8) ivme ölçümü'],
            ],
            'uretim_atolye' => [
                ['ad' => 'CNC Tezgâhı', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 23125 / TS EN ISO 16090-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Torna Tezgâhı', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 23125', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Dikey Freze Tezgâhı', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 16090-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Sütunlu Matkap', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 16090-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Kesme Tezgâhı (Disk / Testere)', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 16093 / TS EN ISO 16089', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Taşlama / Bant Zımpara Makinesi', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 16089', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Ekstrüzyon Hattı', 'periyot_ay' => 12, 'standart' => 'TS EN 1114-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Plastik Kırıcı / Granülatör', 'periyot_ay' => 12, 'standart' => 'TS EN 12012-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Merdaneli Makine', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Gaz Tüpü ve Şaloma Seti', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 5172 / TS EN ISO 5175-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                // EKP-RA-01 (13.10.2026) ile eklenenler
                ['ad' => 'Açık Vals (İki Silindirli)', 'periyot_ay' => 12, 'standart' => 'TS EN 1417', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Gazaltı Kaynak Makinesi', 'periyot_ay' => 12, 'standart' => 'TS EN IEC 60974-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Satıh Taşlama Tezgâhı', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 16089', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Dalma Erozyon (EDM) Tezgâhı', 'periyot_ay' => 12, 'standart' => 'TS EN 12957', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Ayaklı Taş Motoru', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 16089', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Pürmüz ve LPG Tüpü', 'periyot_ay' => 12, 'standart' => 'Üretici talimatı', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol'],
                // Havalandırma kanalı ve sac işleme atölyesi (14.10.2026, TLT-SPK/KRD/SMV/PLZ) — kullanıcının talimat + kontrol formu.
                ['ad' => 'Spiral Hava Kanalı Makinesi', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Kordon Tezgâhı (Motorlu Pedallı)', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Sac Silindir Büküm Makinesi (Vals)', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'CNC Plazma Sac Kesim Tezgâhı', 'periyot_ay' => 12, 'standart' => 'TS EN 60974-1 / TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Parça Taşıma Konveyörü', 'periyot_ay' => 12, 'standart' => 'TS EN 619 / TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Toz Boya Fırını', 'periyot_ay' => 12, 'standart' => 'TS EN 1539 / TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                // Ekipman Değerlendirme Aracı karşılaştırması (19.10.2026)
                ['ad' => 'Hidrolik Pres', 'periyot_ay' => 12, 'standart' => 'TS EN 693 / TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'El Matkabı / Darbeli Matkap / Tornavida', 'periyot_ay' => 12, 'standart' => 'TS EN 62841-1 / TS EN 60745-1', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Avuç Taşlama (Spiral)', 'periyot_ay' => 12, 'standart' => 'TS EN 62841-2-3 / TS EN 12413', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Dekupaj / El Tipi Daire Testere', 'periyot_ay' => 12, 'standart' => 'TS EN 62841-2-5 / TS EN 62841-2-11', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Kırıcı / Delici (Jack Hammer)', 'periyot_ay' => 12, 'standart' => 'TS EN 62841-2-6 / TS EN ISO 11148-4', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Ark Kaynak / Tig / Plazma Kesme Makinesi', 'periyot_ay' => 12, 'standart' => 'TS EN 60974-1 / TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Isı Makineleri (Sıcak Hava Tabancası / Havya / PVC Kaynak)', 'periyot_ay' => 12, 'standart' => 'TS EN 60335-2-45 / TS EN 62841-2-6', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Uzatma Kablosu / Kablo Makarası', 'periyot_ay' => 12, 'standart' => 'TS EN 61316 / TS HD 60364', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Seyyar Transformatör ve Vantilatör', 'periyot_ay' => 12, 'standart' => 'TS EN 61558-1 / TS EN 60335-2-80', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
            ],
            'diger_ekipman' => [
                ['ad' => 'Endüstriyel Zemin Temizleme Otomatı', 'periyot_ay' => 12, 'standart' => 'TS EN 60335-2-72', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol'],
                ['ad' => 'Seyyar / Çok Amaçlı Merdiven', 'periyot_ay' => 12, 'standart' => 'TS EN 131', 'deney' => 'Periyodik muayene yok — kullanım öncesi gözle kontrol'],
                ['ad' => 'Şirket Aracı', 'periyot_ay' => 12, 'standart' => 'Karayolları Trafik Yön. (araç muayenesi ayrıca)', 'deney' => 'İSG periyodik muayenesi yok — araç muayene ve bakım takibi'],
                ['ad' => 'Endüstriyel Raf', 'periyot_ay' => 12, 'standart' => 'TS EN 15635', 'deney' => 'Ek-III kapsamı dışı — raf sorumlusu kontrolü ve uzman muayenesi önerilir'],
                // Şantiye iş makineleri (14.10.2026, SK-RA-01) — üretici talimatına göre bakım + kullanım öncesi kontrol formu.
                ['ad' => 'Kazıcı Yükleyici (Beko Loder)', 'periyot_ay' => 12, 'standart' => 'TS EN 474-1 / TS EN 474-4', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Damperli Kamyon', 'periyot_ay' => 12, 'standart' => 'Karayolları Trafik Yön. (araç muayenesi ayrıca)', 'deney' => 'Araç muayene ve bakım takibi; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Loder / Lastikli Yükleyici', 'periyot_ay' => 12, 'standart' => 'TS EN 474-1 / TS EN 474-3', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Silindir (Toprak / Asfalt)', 'periyot_ay' => 12, 'standart' => 'ISO 20500-1 / ISO 20500-4 (EN 500 serisi)', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Ekskavatör ve Diğer Yol Yapım Makineleri', 'periyot_ay' => 12, 'standart' => 'TS EN 474-1 / TS EN 474-5', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                // Ekipman Değerlendirme Aracı karşılaştırması (19.10.2026)
                ['ad' => 'Düşmeyi Önleyici Ekipman (Yaşam Hattı / Emniyet Kemeri)', 'periyot_ay' => 12, 'standart' => 'TS EN 361 / TS EN 795 / TS EN 365', 'deney' => 'Periyodik muayene yok — üretici talimatına göre yıllık inceleme + kullanım öncesi kontrol formu'],
                ['ad' => 'Traktör', 'periyot_ay' => 12, 'standart' => 'TS EN ISO 4254-1', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Greyder', 'periyot_ay' => 12, 'standart' => 'TS EN 474-1 / TS EN 474-5', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Transmikser (Beton Mikseri Kamyonu)', 'periyot_ay' => 12, 'standart' => 'Karayolları Trafik Yön. (araç muayenesi ayrıca); TS EN 12001', 'deney' => 'Araç muayene ve bakım takibi; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Mobil Beton Pompası', 'periyot_ay' => 12, 'standart' => 'TS EN 12001', 'deney' => 'Üretici talimatına göre bakım; ayak, bom ve boru muayenesi; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Sabit Beton Pompası', 'periyot_ay' => 12, 'standart' => 'TS EN 12001', 'deney' => 'Üretici talimatına göre bakım; boru hattı ve emniyet muayenesi; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Beton Vibratörü', 'periyot_ay' => 12, 'standart' => 'TS EN 60745-1 / TS EN 12649', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Beton Karıştırıcı (Mikser)', 'periyot_ay' => 12, 'standart' => 'TS EN 12151 / TS EN ISO 12100', 'deney' => 'Periyodik muayene yok — kullanım öncesi kontrol formu'],
                ['ad' => 'Fore Kazık / Kazık Çakma / Sondaj Makinesi', 'periyot_ay' => 12, 'standart' => 'TS EN 16228 / TS EN 474-1', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Konsolidasyon Ekipmanı (Drenaj / Zemin İyileştirme)', 'periyot_ay' => 12, 'standart' => 'TS EN 16228 / TS EN 474-1', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Asfalt Finişeri / Distribütörü (Yol Kaplama)', 'periyot_ay' => 12, 'standart' => 'TS EN 500-1 / TS EN 500-6', 'deney' => 'Üretici talimatına göre bakım; kullanım öncesi günlük kontrol formu'],
                ['ad' => 'Lowbed (Ağır Yük Taşıyıcı Dorse)', 'periyot_ay' => 12, 'standart' => 'Karayolları Trafik Yön. (araç muayenesi ayrıca)', 'deney' => 'Araç muayene ve bakım takibi; kullanım öncesi günlük kontrol formu'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İş Hijyeni / Ortam Ölçümleri Takibi
    |--------------------------------------------------------------------------
    | İşyerinde yapılması gereken ortam ölçümleri (gürültü, toz, aydınlatma,
    | termal konfor, kimyasal maruziyet vb.), ölçüm tarihi, sınır değerle
    | karşılaştırılmış sonuç ve bir sonraki ölçüm tarihi. Periyot risk
    | değerlendirmesinde belirtilir; varsayılan 24 ay (elle değiştirilir).
    */
    'ortam_olcum' => [
        'sonuclar' => [
            'bekliyor' => 'Ölçüm Bekliyor',
            'uygun' => 'Uygun (sınır değer altında)',
            'sinir' => 'Sınır Değere Yakın',
            'asim' => 'Sınır Değer Aşımı',
        ],

        // Termin durumu (sonraki ölçüm tarihine göre) — isgsuite "Durum" sütunu.
        'termin_durumlari' => [
            'guncel' => 'Güncel',
            'yaklasan' => 'Yaklaşıyor',
            'gecikmis' => 'Gecikmiş',
            'olculmedi' => 'Ölçülmedi',
        ],
        'yaklasan_gun' => 60,
        // Ölçülen değer sınırın bu oranına ulaşınca otomatik "Sınır Değere Yakın".
        'sinir_yakin_orani' => 0.8,

        'katalog' => [
            'Fiziksel Etkenler' => [
                ['ad' => 'Gürültü Maruziyeti (LEX,8h)', 'periyot_ay' => 24, 'birim' => 'dB(A)', 'sinir_deger' => '85'],
                ['ad' => 'Aydınlatma Şiddeti', 'periyot_ay' => 24, 'birim' => 'lüks', 'sinir_deger' => '—'],
                ['ad' => 'Termal Konfor (sıcaklık / nem / hava akış hızı)', 'periyot_ay' => 24, 'birim' => '°C / % / m/s', 'sinir_deger' => '—'],
                ['ad' => 'El-Kol Titreşimi (A(8))', 'periyot_ay' => 24, 'birim' => 'm/s²', 'sinir_deger' => '5'],
                ['ad' => 'Tüm Vücut Titreşimi (A(8))', 'periyot_ay' => 24, 'birim' => 'm/s²', 'sinir_deger' => '1.15'],
                ['ad' => 'Elektromanyetik Alan', 'periyot_ay' => 24, 'birim' => 'µT / V/m', 'sinir_deger' => '—'],
                ['ad' => 'İyonlaştırıcı Radyasyon', 'periyot_ay' => 12, 'birim' => 'mSv', 'sinir_deger' => '20'],
            ],
            'Toz Maruziyeti' => [
                ['ad' => 'Toplam Toz', 'periyot_ay' => 24, 'birim' => 'mg/m³', 'sinir_deger' => '—'],
                ['ad' => 'Solunabilir Toz', 'periyot_ay' => 24, 'birim' => 'mg/m³', 'sinir_deger' => '—'],
                ['ad' => 'Kristal Yapıda Silika (Kuvars)', 'periyot_ay' => 12, 'birim' => 'mg/m³', 'sinir_deger' => '0.1'],
                ['ad' => 'Ahşap Tozu', 'periyot_ay' => 24, 'birim' => 'mg/m³', 'sinir_deger' => '5'],
                ['ad' => 'Un / Tahıl Tozu', 'periyot_ay' => 24, 'birim' => 'mg/m³', 'sinir_deger' => '—'],
            ],
            'Kimyasal Maruziyet' => [
                ['ad' => 'Uçucu Organik Bileşikler (VOC)', 'periyot_ay' => 24, 'birim' => 'ppm / mg/m³', 'sinir_deger' => '—'],
                ['ad' => 'Kaynak Dumanı ve Metal Buharı', 'periyot_ay' => 24, 'birim' => 'mg/m³', 'sinir_deger' => '5'],
                ['ad' => 'Ağır Metaller (Pb, Cr, Ni, Mn ...)', 'periyot_ay' => 12, 'birim' => 'mg/m³', 'sinir_deger' => '—'],
                ['ad' => 'Formaldehit', 'periyot_ay' => 24, 'birim' => 'ppm', 'sinir_deger' => '0.3'],
                ['ad' => 'Amonyak / Asit Buharları', 'periyot_ay' => 24, 'birim' => 'ppm', 'sinir_deger' => '20'],
                ['ad' => 'Karbon Monoksit (CO)', 'periyot_ay' => 24, 'birim' => 'ppm', 'sinir_deger' => '20'],
                ['ad' => 'Egzoz Emisyonu (dizel partikül)', 'periyot_ay' => 24, 'birim' => 'mg/m³', 'sinir_deger' => '—'],
            ],
            'Biyolojik ve Diğer' => [
                ['ad' => 'Biyolojik Etkenler (bakteri / mantar / endotoksin)', 'periyot_ay' => 24, 'birim' => 'kob/m³', 'sinir_deger' => '—'],
                ['ad' => 'İç Ortam Hava Kalitesi (CO₂)', 'periyot_ay' => 24, 'birim' => 'ppm', 'sinir_deger' => '1000'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kaza İstatistikleri — Sıklık ve Ağırlık Oranı
    |--------------------------------------------------------------------------
    | Firma × yıl bazlı; İş Kazası Raporları + Olay Kayıtları (iş kazası tipi) +
    | elle eklenen harici kazalardan hesaba dahil kaza sayısı, kayıp gün ve
    | aylık çalışma saatinden Sıklık / Ağırlık oranı hesaplanır. Standart:
    |  - turkiye_1m  : oran × 1.000.000 (SGK / ÇSGB yıllık istatistik yöntemi)
    |  - osha_200k   : oran × 200.000 (100 tam zamanlı çalışan yılı — OSHA)
    */
    'kaza_istatistik' => [
        'standartlar' => [
            'turkiye_1m' => ['ad' => 'Türkiye (× 1.000.000)', 'carpan' => 1000000],
            'osha_200k' => ['ad' => 'OSHA (× 200.000)', 'carpan' => 200000],
        ],
        // Bir çalışanın bir ayda ortalama fiili çalışma saati (varsayılan öneri;
        // aylık tabloda elle değiştirilebilir).
        'aylik_kisi_saat' => 175,
        'aylar' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sağlık Gözetimi Takibi — çalışan × sağlık tetkiki
    |--------------------------------------------------------------------------
    | 6331 s.K. Md.15 + İşyeri Hekimi Yönetmeliği. Her tetkik türünün varsayılan
    | periyodu; `periyot_ay = null` olanlar tehlike sınıfına göre yıl bazlı
    | (egitim/sağlık yenileme: az_tehlikeli 5 · tehlikeli 3 · çok tehlikeli 1 yıl).
    */
    'saglik_tetkik' => [
        'sonuclar' => [
            'bekliyor' => 'Bekliyor',
            'uygun' => 'Çalışabilir (uygun)',
            'sartli' => 'Şartlı / Kısıtlı Uygun',
            'uygun_degil' => 'Uygun Değil',
        ],

        'turleri' => [
            'ise_giris' => ['ad' => 'İşe Giriş Muayenesi (EK-2)', 'periyot_ay' => null, 'aciklama' => 'İşe başlamadan önce; iş değişikliğinde tekrar'],
            'periyodik' => ['ad' => 'Periyodik Muayene (EK-2)', 'periyot_ay' => null, 'aciklama' => 'Tehlike sınıfına göre 1 / 3 / 5 yılda bir'],
            'ise_donus' => ['ad' => 'İşe Dönüş Muayenesi', 'periyot_ay' => null, 'aciklama' => '6 aydan uzun rapor / iş kazası sonrası'],
            'goz' => ['ad' => 'Göz Muayenesi', 'periyot_ay' => 12, 'aciklama' => 'Ekranlı araç / hassas görsel işler'],
            'odyometri' => ['ad' => 'İşitme Testi (Odyometri)', 'periyot_ay' => 12, 'aciklama' => 'Gürültülü ortam (>85 dB(A))'],
            'sft' => ['ad' => 'Solunum Fonksiyon Testi (SFT)', 'periyot_ay' => 12, 'aciklama' => 'Toz / gaz / kimyasal maruziyeti'],
            'akciger_grafisi' => ['ad' => 'Akciğer Grafisi (PA)', 'periyot_ay' => 12, 'aciklama' => 'Tozlu işler — pnömokonyoz taraması'],
            'kan_tahlili' => ['ad' => 'Hemogram / Biyokimya', 'periyot_ay' => 12, 'aciklama' => 'Kurşun, benzen, çözücü vb. maruziyette'],
            'idrar_tahlili' => ['ad' => 'Tam İdrar Tahlili', 'periyot_ay' => 12, 'aciklama' => 'Ağır metal / çözücü maruziyeti'],
            'portor' => ['ad' => 'Portör Muayenesi', 'periyot_ay' => 3, 'aciklama' => 'Gıda ile temaslı çalışanlar (Hijyen Yön.)'],
            'psikoteknik' => ['ad' => 'Psikoteknik Değerlendirme', 'periyot_ay' => 60, 'aciklama' => 'Araç sürücüleri / vinç-forklift operatörleri'],
            'agir_tehlikeli' => ['ad' => 'Ağır ve Tehlikeli İşler Sağlık Raporu', 'periyot_ay' => null, 'aciklama' => 'İşin niteliğine göre'],
            'tetanoz' => ['ad' => 'Tetanoz Aşısı', 'periyot_ay' => 120, 'aciklama' => 'Yaralanma riski yüksek işler'],
            'hepatit' => ['ad' => 'Hepatit B Aşısı / Tarama', 'periyot_ay' => null, 'aciklama' => 'Sağlık / atık / kan teması riski'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | KKD Seçim Matrisi — iş kalemi × KKD türü
    |--------------------------------------------------------------------------
    | Hangi işte hangi KKD'nin gerektiği (KKD'lerin İşyerlerinde Kullanılması
    | Hakkında Yönetmelik + risk değerlendirmesi). Sütunlar sabit; hücre metni
    | "✔", "Gerekirse", standart no ya da serbest metin olabilir.
    */
    'kkd_matris' => [
        'sutunlar' => [
            'baret' => 'Baret (EN 397)',
            'gozluk' => 'Koruyucu Gözlük / Siperlik (EN 166)',
            'kulaklik' => 'Kulak Koruyucu (EN 352)',
            'maske' => 'Solunum Koruyucu (EN 149 / EN 14387)',
            'eldiven' => 'Koruyucu Eldiven (EN 388 / EN 374 / EN 60903)',
            'ayakkabi' => 'İş Ayakkabısı (EN ISO 20345 S3)',
            'yuksek_ayakkabi' => 'Yüksek Tip İş Ayakkabısı / Çizme',
            'yelek' => 'Reflektif Yelek (EN ISO 20471)',
            'kemer' => 'Düşme Durdurma Sistemi (EN 361 + EN 355)',
            'diger' => 'Diğer KKD',
        ],

        'is_kalemleri' => [
            // Kaynak: "İnşaat İş Adımlarına Göre KKD Matrisi" (kullanıcı referansı) — Z/Ö
            // notasyonu ✔/"Gerekirse" olarak birebir aktarıldı, parantez notları korundu.
            //
            // 'anahtar': firma bazlı iş kalemi seçiminde ve risk/talimat/eğitim
            // eşlemesinde stabil referans (ad metni değişirse anahtar değişmez).
            // 'talimat_basliklari': isg.talimat.kutuphane içindeki gerçek 'baslik'
            // değerleriyle birebir eşleşmeli. 'tehlike_kategorileri': dev DB'de
            // seed edilmiş TehlikeKategorisi.ad değerleriyle birebir eşleşmeli
            // (bkz. RiskKutuphanesi::kategoriler()).
            'İnşaat / Şantiye' => [
                [
                    'anahtar' => 'genel_saha', 'ad' => 'Genel Şantiye Saha Gezisi',
                    'v' => ['baret' => '✔', 'ayakkabi' => '✔'],
                    'talimat_basliklari' => ['Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı', 'Genel İşyeri İSG Kuralları Talimatı', 'Kişisel Koruyucu Donanım (KKD) Genel Kullanım Talimatı'],
                    'tehlike_kategorileri' => ['ŞANTİYE GENEL ÇALIŞMALAR', 'Şantiye Genel Çalışmalar & Tesisler', 'Genel İşyeri'],
                    'egitim_konusu' => 'Şantiye genel giriş-çıkış kuralları, KKD kullanım zorunluluğu, saha trafiği ve yaya güvenliği, acil durum toplanma noktaları.',
                ],
                [
                    'anahtar' => 'hafriyat_kazi', 'ad' => 'Hafriyat ve Kazı İşleri',
                    'v' => ['baret' => '✔', 'gozluk' => 'Gerekirse', 'kulaklik' => 'Gerekirse', 'maske' => 'Gerekirse (toz)', 'eldiven' => '✔', 'ayakkabi' => '✔', 'yuksek_ayakkabi' => 'Gerekirse'],
                    'talimat_basliklari' => ['Kazı İşleri Güvenlik Talimatı'],
                    'tehlike_kategorileri' => ['Hafriyat ve Kazı İşleri'],
                    'egitim_konusu' => 'Kazı kenarı şevlendirme/iksa, yeraltı tesisatı kontrolü, kazı içi çalışma ve güvenli çıkış, ekskavatör/kepçe yakınında çalışma kuralları.',
                ],
                [
                    'anahtar' => 'kalip_iskele_demir', 'ad' => 'Kalıp, İskele ve Demir İşleri',
                    'v' => ['baret' => '✔', 'gozluk' => '✔', 'eldiven' => '✔ mekanik', 'ayakkabi' => '✔ çelik taban/burun', 'kemer' => '✔ iskelede'],
                    'talimat_basliklari' => ['Cephe İskelesinde Çalışma Talimatı', 'İskele Kurulum ve Söküm Talimatı', 'Demir/Donatı Bağlama İşleri Talimatı'],
                    'tehlike_kategorileri' => ['Kaba Yapı - Kalıp & İskele', 'Kaba Yapı - Demir Donatı', 'KALIP İMALATI', 'DEMİR İŞLERİ'],
                    'egitim_konusu' => 'İskele kurulum/söküm kontrolü, kalıp montaj-demontaj güvenliği, demir bağlama/kesme sırasında kesilme ve göz yaralanması riskleri.',
                ],
                [
                    'anahtar' => 'beton_sap', 'ad' => 'Beton Dökümü ve Şap İşleri',
                    'v' => ['baret' => '✔', 'gozluk' => '✔', 'maske' => 'Gerekirse', 'eldiven' => '✔ kimyasal/kauçuk', 'yuksek_ayakkabi' => '✔ çizme'],
                    'talimat_basliklari' => ['Beton Dökümü ve Kalıp İşleri Talimatı'],
                    'tehlike_kategorileri' => ['Betonarme ve Beton Dökümü'],
                    'egitim_konusu' => 'Beton pompası/mikser yakınında çalışma, vibratör kullanımı, çimento ile cilt teması ve kimyasal yanık riskleri.',
                ],
                [
                    'anahtar' => 'kaynak_kesim', 'ad' => 'Kaynak ve Kesim İşleri',
                    'v' => ['baret' => '✔', 'gozluk' => '✔ kaynak maskesi', 'kulaklik' => 'Gerekirse', 'maske' => '✔ gaz/duman filtreli', 'eldiven' => '✔ ısı/deri', 'ayakkabi' => '✔', 'kemer' => 'Gerekirse'],
                    'talimat_basliklari' => ['Oksijen-Asetilen Kaynak/Kesme Talimatı', 'Elektrik Ark Kaynağı (MIG/MAG/TIG) Talimatı', 'Plazma Kesim Talimatı', 'Sıcak İşler (Kaynak, Kesme, Taşlama) İzin Talimatı'],
                    'tehlike_kategorileri' => ['KAYNAKLA ÇALIŞMA'],
                    'egitim_konusu' => 'Kaynak dumanına maruziyet, yangın/patlama riski, sıcak iş izni prosedürü, kaynak maskesi ve UV/IR koruması.',
                ],
                [
                    'anahtar' => 'siva_boya', 'ad' => 'Sıva, Boya ve Yüzey Kaplama',
                    'v' => ['baret' => '✔', 'gozluk' => '✔', 'maske' => '✔ kimyasal/toz filtreli', 'eldiven' => '✔', 'ayakkabi' => '✔', 'kemer' => 'Gerekirse'],
                    'talimat_basliklari' => ['Boya ve Cila Uygulama Talimatı'],
                    'tehlike_kategorileri' => ['Duvar, Sıva & Yalıtım İşleri', 'Boya İşleri', 'İnce Yapı & Kaplama / Montaj', 'SU İZOLASYONU'],
                    'egitim_konusu' => 'Boya/tiner buharına maruziyet ve havalandırma, sıva makinesi kullanımı, yüksekte sıva/boya yaparken düşme riskleri.',
                ],
                [
                    'anahtar' => 'elektrik_tesisat', 'ad' => 'Elektrik ve Tesisat İşleri',
                    'v' => ['baret' => '✔ yalıtkan', 'gozluk' => '✔', 'eldiven' => '✔ yalıtkan', 'ayakkabi' => '✔ yalıtkan', 'kemer' => 'Gerekirse'],
                    'talimat_basliklari' => ['Elektrik Panosunda Çalışma Talimatı', 'Kablo Döşeme ve Bağlantı Talimatı', 'Jeneratör Kullanma Talimatı'],
                    'tehlike_kategorileri' => ['Elektrik Tesisatı & Altyapı', 'Mekanik Tesisat & Borulama', 'PİS SU TESİSATI', 'TEMİZ SU TESİSATI', 'YANGIN TESİSATI', 'AYDINLATMA ARMATÜRLERİ', 'ÇEVRE AYDINLATMASI', 'İNTERKOM TESİSATI', 'HAVALANDIRMA TESİSAT VE EKİPMANLARIN MONTAJI', 'KAZAN DAİRESİ'],
                    'egitim_konusu' => 'Elektrik çarpması riski, topraklama kontrolü, gerilim altında çalışma yasağı, yalıtkan KKD kullanımı.',
                ],
                [
                    'anahtar' => 'yukseklik', 'ad' => 'Yüksekte Çalışma (Çatı, Dış Cephe)',
                    'v' => ['baret' => '✔ çene bağlı', 'gozluk' => 'Gerekirse', 'eldiven' => '✔', 'ayakkabi' => '✔', 'kemer' => '✔ tam vücut tipi'],
                    'talimat_basliklari' => ['Yükseklerde Çalışma Talimatı', 'Çatı Üzerinde Çalışma Talimatı', 'Portatif Merdiven Kullanma Talimatı', 'Makaslı/Eklemli Platform (Manlift) Kullanma Talimatı'],
                    'tehlike_kategorileri' => ['Çatı & Çelik Konstrüksiyon', 'Dış Cephe İşleri'],
                    'egitim_konusu' => 'Düşmeye karşı korunma, tam vücut kemeri/yaşam hattı kullanımı, çatı kenarı korkuluk zorunluluğu, manlift/iskele güvenli kullanımı.',
                ],
                [
                    'anahtar' => 'kirma_yikim', 'ad' => 'Kırma, Delme ve Yıkım İşleri',
                    'v' => ['baret' => '✔', 'gozluk' => '✔', 'kulaklik' => '✔', 'maske' => '✔ toz filtreli', 'eldiven' => '✔', 'ayakkabi' => '✔', 'kemer' => 'Gerekirse'],
                    'talimat_basliklari' => ['Yıkım İşleri Güvenlik Talimatı', 'Elektrikli Kırıcı/Delici Kullanma Talimatı'],
                    'tehlike_kategorileri' => ['Kaldırma Araçları & İş Makineleri', 'İnşaat / Yapı'],
                    'egitim_konusu' => 'Yıkım öncesi statik değerlendirme, toz/gürültü maruziyeti, kırıcı/delici ekipman kullanımı, çevre güvenlik şeridi.',
                ],
            ],
            'İmalat / Fabrika' => [
                ['ad' => 'Kaynak İşleri', 'v' => ['baret' => 'Gerekirse', 'gozluk' => 'Kaynak maskesi + gözlük', 'maske' => 'Kaynak dumanı maskesi', 'eldiven' => 'EN 388 + EN 407', 'ayakkabi' => '✔ S3', 'diger' => 'Kaynakçı önlüğü / tozluk']],
                ['ad' => 'Talaşlı İmalat (CNC / Torna / Freze)', 'v' => ['gozluk' => '✔', 'kulaklik' => '✔', 'eldiven' => 'Dönen parçada TAKILMAZ', 'ayakkabi' => '✔ S3', 'diger' => 'Yüz siperi']],
                ['ad' => 'Pres / Büküm / Giyotin', 'v' => ['gozluk' => '✔', 'kulaklik' => '✔', 'eldiven' => 'EN 388 kesilmeye dayanıklı', 'ayakkabi' => '✔ S3']],
                ['ad' => 'Boyahane / Yüzey İşlem', 'v' => ['gozluk' => '✔', 'maske' => 'EN 14387 A2P3 / tam yüz', 'eldiven' => 'EN 374 kimyasal', 'ayakkabi' => '✔ S3', 'diger' => 'Solunum koruyuculu tulum']],
                ['ad' => 'Depo / Forklift Operasyonu', 'v' => ['baret' => 'Gerekirse', 'ayakkabi' => '✔ S3', 'yelek' => '✔']],
                ['ad' => 'Bakım / Onarım', 'v' => ['baret' => 'Gerekirse', 'gozluk' => '✔', 'kulaklik' => 'Gerekirse', 'eldiven' => 'EN 388', 'ayakkabi' => '✔ S3', 'kemer' => 'Yüksekte ise', 'diger' => 'LOTO kilit-etiket']],
                ['ad' => 'Kimyasal Depolama / Transfer', 'v' => ['gozluk' => '✔ / yüz siperi', 'maske' => 'GBF\'ye göre filtre', 'eldiven' => 'EN 374', 'ayakkabi' => '✔ S3', 'diger' => 'Kimyasal önlük / göz duşu erişimi']],
            ],
            'Genel / Hizmet' => [
                ['ad' => 'Ofis Çalışması', 'v' => ['diger' => 'Ekranlı araç için ergonomi — KKD gerekmez']],
                ['ad' => 'Temizlik İşleri', 'v' => ['gozluk' => 'Kimyasal kullanımında', 'maske' => 'Gerekirse', 'eldiven' => 'EN 374', 'ayakkabi' => 'Kaymaz taban']],
                ['ad' => 'Şoför / Sevkiyat', 'v' => ['ayakkabi' => 'S1P', 'yelek' => '✔ (araç dışı)', 'eldiven' => 'Yük elleçlemede']],
                ['ad' => 'Arşiv / Depo Görevlisi', 'v' => ['ayakkabi' => 'S3', 'eldiven' => 'Yük elleçlemede', 'diger' => 'Merdiven kullanımı eğitimi']],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Haftalık Ekipman Kontrol Formları — kullanıcının kendi referans belgesi
    | ("günlük kontrol.docx", 41 makine türü × 12 soru) birebir esas alındı.
    | Periyodik Kontrol'den (RESMİ muayene) bağımsız: operatörün her hafta
    | doldurduğu BOŞ şablon — Saha Denetimi sayfasından, firmada bulunan
    | ekipman tipleri seçilip toplu PDF olarak üretilip firmaya teslim edilir.
    */
    'haftalik_ekipman_kontrol' => [
        'tipler' => [
            ['ad' => 'Paletli Ekskavatör', 'sorular' => [
                'Motor, soğutma ve hidrolik sıvı seviyeleri uygun ve sistemler kaçaksız mı?',
                'Hortumlar, borular, rakorlar ve silindirler sağlam ve kaçaksız mı?',
                'Palet, pabuç, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Yürüyüş kumandaları ve park konumu düzgün çalışıyor mu?',
                'Bom, kol, kova, dişler ve bağlantı pimleri sağlam ve sıkı mı?',
                'Hızlı bağlantı kilidi ve emniyeti üretici yöntemine göre doğrulandı mı?',
                'Dönüş grubu/karşı ağırlık sağlam, ses ve boşluklar normal mi?',
                'Yürüyüş/dönüş frenleri ve hidrolik kumanda emniyet kilidi çalışıyor mu?',
                'Far, korna, hareket alarmı, görüş araçları ve göstergeler çalışıyor mu?',
                'Kabin, kemer, basamak ve tutamaklar sağlam, söndürücü hazır mı?',
                'Kazı kenarı, yeraltı/üst hatlar ve dönüş alanı güvenli, yaya girişi önlenmiş mi?',
                'Yük kaldırmada onaylı nokta, yük tablosu ve gerekli emniyet donanımı uygun mu?',
            ]],
            ['ad' => 'Tekerlekli Ekskavatör', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Denge ayakları, kilitleri ve altlıklar uygun, makine sağlam zeminde terazide mi?',
                'Bom, kol, kova ve dişler sağlam, pim emniyetleri yerinde mi?',
                'Ataşman hızlı bağlantısının kilitlendiği üretici yöntemine göre doğrulandı mı?',
                'Dönüş grubu ve karşı ağırlık bağlantıları sağlam, boşluklar normal mi?',
                'Hidrolik kumanda kilidi ve seyir emniyetleri çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Loder', 'sorular' => [
                'Motor, soğutma, şanzıman ve hidrolik sıvıları uygun ve kaçaksız mı?',
                'Hidrolik hortumlar, borular ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, basınçlar uygun mu?',
                'Servis freni, park freni ve direksiyon düzgün çalışıyor mu?',
                'Belden kırma mafsalı sağlam ve anormal boşluk/sesten arınmış mı?',
                'Kepçe gövdesi, kesici ağız ve dişler sağlam ve sıkı mı?',
                'Kaldırma kolları, pimler, burçlar ve emniyet elemanları uygun mu?',
                'Hızlı bağlantının kilitlendiğini üretici yöntemine göre doğruladınız mı?',
                'Kumandalar, göstergeler ve geri hareket sistemi düzgün çalışıyor mu?',
                'Farlar, sinyaller, korna, geri alarmı ve tepe lambası çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar sağlam mı?',
                'Söndürücü hazır, yükleme alanı, kenar mesafesi ve yaya ayrımı uygun mu?',
            ]],
            ['ad' => 'Paletli Loder', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Palet, zincir, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Yürüyüş, yönlendirme ve park freni düzgün çalışıyor mu?',
                'Kaldırma kolları, pimler ve burçlar sağlam ve emniyetli mi?',
                'Kepçe, kesici ağız ve dişler sağlam, bağlantılar sıkı mı?',
                'Ataşman bağlantı pimleri ve kilitleri emniyetli mi?',
                'Kumandalar ve göstergeler normal, mevcut emniyet kilitleri çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Yangın söndürücü erişilebilir ve hazır, yanıcı birikimleri temizlenmiş mi?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Beko Loder (Kazıcı Yükleyici)', 'sorular' => [
                'Motor, soğutma ve hidrolik sıvı seviyeleri uygun ve sistem kaçaksız mı?',
                'Hidrolik hortumlar, rakorlar ve silindirler hasarsız ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, basınçlar uygun mu?',
                'Servis freni, park freni ve direksiyon düzgün çalışıyor mu?',
                'Ön kepçe, kaldırma kolları ve pimler sağlam ve emniyetli mi?',
                'Arka bom, kol, kova ve dişler sağlam, pim emniyetleri yerinde mi?',
                'Hızlı bağlantının kilitlendiğini üretici yöntemine göre doğruladınız mı?',
                'Denge ayakları ve pabuçları sağlam, altındaki zemin uygun mu?',
                'Kazıcı grubun seyir kilidi ve ataşman taşıma konumu uygun mu?',
                'Farlar, sinyaller, korna, geri alarmı ve tepe lambası çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Söndürücü hazır, kazı kenarı, hatlar ve yaya çalışma alanı güvenli mi?',
            ]],
            ['ad' => 'Dozer (Buldozer)', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Palet, zincir, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Yönlendirme, yürüyüş ve park freni düzgün çalışıyor mu?',
                'Bıçak, kesici ağız ve köşe uçları sağlam ve sıkı mı?',
                'İtme kolları, mafsallar ve pim emniyetleri sağlam mı?',
                'Riper gövdesi, dişleri ve bağlantıları varsa sağlam mı?',
                'Kumandalar ve göstergeler normal, mevcut emniyet kilitleri çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Yangın söndürücü erişilebilir ve hazır, yanıcı birikimleri temizlenmiş mi?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Greyder', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Bıçak, kesici ağız ve bağlantı cıvataları sağlam mı?',
                'Bıçak dairesi, dişli ve çekme çerçevesinin boşlukları normal mi?',
                'Mafsal ve ön aks bağlantıları sağlam, seyir kilidi uygun mu?',
                'Bıçak kaldırma/yan kaydırma ve eğim kumandaları çalışıyor mu?',
                'Varsa riper dişleri ve bağlantıları sağlam mı?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Skreyper', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Kazan gövdesi, kesici ağız ve bağlantılar sağlam mı?',
                'Ön kapak ve kaldırma mekanizması düzgün çalışıyor mu?',
                'Boşaltma iticisi ve kızakları sağlam, hareket alanı temiz mi?',
                'Çeki mafsalı, bağlantı pimleri ve emniyetleri sağlam mı?',
                'Kumandalar ve göstergeler normal, mevcut emniyet kilitleri çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Mini Yükleyici (Bobcat)', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Yürüyüş, dönüş ve park freni düzgün çalışıyor mu?',
                'Kaldırma kolları, pimler ve burçlar sağlam mı?',
                'Kepçe/ataşman bağlantı kilitleri tam olarak kapalı mı?',
                'Kemer, emniyet barı ve operatör varlık kilidi çalışıyor mu?',
                'Kabin koruyucusu, kapı ve basamaklar sağlam mı?',
                'Bakım için kol dayaması mevcut ve sağlam mı (günlük kontrolde altına girilmez)?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Yangın söndürücü erişilebilir ve hazır, yanıcı birikimleri temizlenmiş mi?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Kule Vinç', 'sorular' => [
                'Erişilebilir kule bağlantıları ve pimlerde görünür gevşeklik/hasar yok mu?',
                'Temel/ankraj bölgesi ve varsa bina bağlantılarında görünür bozulma yok mu?',
                'Bom/karşı bom ve karşı ağırlıklarda görünür hasar veya yer değiştirme yok mu?',
                'Halat ve uç bağlantıları görünür tel kopması, ezilme ve hasardan arınmış mı?',
                'Tambur, makaralar, halat sarımı ve bağlantılar uygun mu?',
                'Kanca, mandal ve blok sağlam, sapanlar tanımlı ve yüke uygun mu?',
                'Mevcut yük sınırlayıcılar, göstergeler ve hareket limitleri çalışıyor mu?',
                'Kaldırma/yürütme frenleri ve kumandalar güvenli işlev kontrolünde çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Rüzgâr göstergesi çalışıyor, çalışma ve park koşulları talimata uygun mu?',
                'Kablo, fiş ve pano sağlam, elektrik koruma düzenekleri kullanıma hazır mı?',
                'Yük kapasiteye uygun, kaldırma alanı ayrılmış ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Mobil Vinç', 'sorular' => [
                'Motor/hidrolik sıvıları uygun, hortum ve silindirler kaçaksız mı?',
                'Lastik, jant ve bijonlar sağlam, frenler ve direksiyon çalışır mı?',
                'Bom bölümleri, uzatma bağlantıları ve pim emniyetleri sağlam mı?',
                'Halat görünür tel kopması, ezilme, kıvrılma ve korozyondan arınmış mı?',
                'Halat sarımı, tambur, makaralar ve uç bağlantıları uygun mu?',
                'Kanca/mandal ve blok sağlam, sapanlar tanımlı ve kapasiteye uygun mu?',
                'Denge ayakları ve altlıklar uygun, vinç sağlam zeminde terazide mi?',
                'Karşı ağırlık ve ayak açıklığı yük tablosuna uygun, bağlantılar emniyetli mi?',
                'Yük moment sınırlayıcı, göstergeler ve üst kanca limiti çalışıyor mu?',
                'Kaldırma/dönüş frenleri, kumandalar ve acil durdurma çalışıyor mu?',
                'Aydınlatma, alarmlar, görüş araçları, kemer ve söndürücü uygun mu?',
                'Yük/radyüs, rüzgâr, hat mesafesi ve işaretçi iletişimi güvenli mi?',
            ]],
            ['ad' => 'Paletli Mobil Vinç', 'sorular' => [
                'Motor/hidrolik sıvıları uygun, hortum ve silindirler kaçaksız mı?',
                'Palet pabuçları, zincirler, makaralar ve cer dişlileri sağlam mı?',
                'Palet açıklığı/kilitleri, gerginlik, zemin ve makine terazisi uygun mu?',
                'Bom, ek bölümler, pimler ve emniyetleri sağlam mı?',
                'Bom askı halatları/çubukları ve bağlantıları sağlam ve emniyetli mi?',
                'Halat, tambur, makaralar ve uç bağlantıları görünür hasardan arınmış mı?',
                'Kanca, mandal, blok ve sapanlar sağlam ve kapasiteye uygun mu?',
                'Karşı ağırlıklar ve bağlantıları bom/yük tablosuyla uyumlu mu?',
                'Yük moment sınırlayıcı, bom/kanca limitleri ve göstergeler çalışıyor mu?',
                'Yürüyüş/dönüş/kaldırma frenleri ve acil durdurma çalışıyor mu?',
                'Farlar, korna, alarmlar, görüş araçları, kemer ve söndürücü uygun mu?',
                'Yük/radyüs, rüzgâr, hat mesafesi ve varsa yüklü yürüyüş koşulları uygun mu?',
            ]],
            ['ad' => 'Makaslı Platform (Manlift)', 'sorular' => [
                'Akü/yakıt sistemi ve kablolar sağlam, seviyeler uygun ve kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Korkuluk, ara kayıt, topuk levhası ve giriş kapısı sağlam ve kapalı mı?',
                'Kaldırma yapısı, pimler ve bağlantılarda görünür hasar yok mu?',
                'Üst/alt kumandalar ve mevcut etkinleştirme anahtarı çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Acil indirme sistemi talimata göre çalışıyor, kurtarma düzeni hazır mı?',
                'Eğim ve mevcut yük uyarıları ile emniyet kilitleri çalışıyor mu?',
                'Yürüyüş frenleri ve mevcut çukur koruma sistemi çalışıyor mu?',
                'Platform yükü, kişi sayısı, rüzgâr ve yükseklik koşulları uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Eklemli Platform (Manlift)', 'sorular' => [
                'Akü/yakıt sistemi ve kablolar sağlam, seviyeler uygun ve kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Korkuluk, ara kayıt, topuk levhası ve giriş kapısı sağlam ve kapalı mı?',
                'Bom, mafsallar ve pim emniyetleri sağlam mı?',
                'Üst/alt kumandalar ve mevcut etkinleştirme anahtarı çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Acil indirme sistemi talimata göre çalışıyor, kurtarma düzeni hazır mı?',
                'Eğim ve mevcut yük uyarıları ile emniyet kilitleri çalışıyor mu?',
                'Üreticinin öngördüğü düşmeye karşı sistem ve ankraj noktası uygun mu?',
                'Platform yükü, kişi sayısı, rüzgâr ve yükseklik koşulları uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Örümcek Platform', 'sorular' => [
                'Akü/yakıt sistemi ve kablolar sağlam, seviyeler uygun ve kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Palet, zincir, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Korkuluk, ara kayıt, topuk levhası ve giriş kapısı sağlam ve kapalı mı?',
                'Bom, mafsallar ve pim emniyetleri sağlam mı?',
                'Üst/alt kumandalar ve mevcut etkinleştirme anahtarı çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Acil indirme sistemi talimata göre çalışıyor, kurtarma düzeni hazır mı?',
                'Denge ayakları, kilitleri ve altlıklar uygun, makine sağlam zeminde terazide mi?',
                'Üreticinin öngördüğü düşmeye karşı sistem ve ankraj noktası uygun mu?',
                'Yük, rüzgâr, terazi ve denge ayağı emniyetleri çalışma koşullarına uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Forklift', 'sorular' => [
                'Lastikler, tekerlekler ve bijonlar sağlam, hava dolgulu tipte basınç uygun mu?',
                'Servis freni, park freni ve direksiyon düzgün çalışıyor mu?',
                'Çatallar sağlam ve belirgin aşınmadan arınmış, kilit pimleri yerinde mi?',
                'Mast, taşıyıcı tabla ve makaralar sağlam ve takılmadan hareket ediyor mu?',
                'Kaldırma zincirleri ve ankrajları sağlam ve simetrik mi?',
                'Hidrolik hortum/silindirler sağlam ve kaçaksız, yağ seviyesi uygun mu?',
                'Kaldırma, indirme, tilt ve varsa yana kaydırma kumandaları çalışıyor mu?',
                'Üst koruyucu, yük sırtlığı, kemer ve operatör varlık sistemi uygun mu?',
                'Kapasite levhası okunaklı, yük merkezi/ataşmana uygun kapasite biliniyor mu?',
                'Far, korna, geri alarmı, uyarı lambaları ve görüş araçları çalışıyor mu?',
                'Akü/kablolar veya yakıt/LPG bağlantıları sağlam ve kaçaksız mı?',
                'Söndürücü hazır, güzergâh, rampalar, yaya ayrımı ve havalandırma uygun mu?',
            ]],
            ['ad' => 'Telehandler (Teleskobik Bomlu Yükleyici)', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Bom bölümleri, kayma pabuçları ve pimler sağlam mı?',
                'Çatal/ataşman sağlam, hızlı bağlantı kilidi doğrulanmış mı?',
                'Yük çizelgesi ataşmana uygun, yük ve uzanma kapasite içinde mi?',
                'Yük moment göstergesi ve mevcut emniyet sınırlayıcı çalışıyor mu?',
                'Denge ayakları, kilitleri ve altlıklar uygun, makine sağlam zeminde terazide mi?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Portal Vinç', 'sorular' => [
                'Kiriş, ayak ve bağlantılarda görünür çatlak/gevşeklik yok mu?',
                'Ray, yürüyüş tekerlekleri ve tamponlar görünür olarak sağlam mı?',
                'Halat ve uç bağlantıları görünür tel kopması, ezilme ve hasardan arınmış mı?',
                'Tambur, makaralar, halat sarımı ve bağlantılar uygun mu?',
                'Kanca, mandal ve blok sağlam, sapanlar tanımlı ve yüke uygun mu?',
                'Kaldırma/yürütme frenleri ve kumandalar güvenli işlev kontrolünde çalışıyor mu?',
                'Mevcut yük sınırlayıcılar, göstergeler ve hareket limitleri çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Kablo, fiş ve pano sağlam, elektrik koruma düzenekleri kullanıma hazır mı?',
                'Yürüyüş alarmı ve mevcut ikaz lambaları çalışıyor mu?',
                'Kablo taşıma ve enerji besleme düzeni takılmadan hareket ediyor mu?',
                'Yük kapasiteye uygun, kaldırma alanı ayrılmış ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Köprü Vinç', 'sorular' => [
                'Köprü kirişi, uç arabalar ve bağlantılarda görünür hasar yok mu?',
                'Ray, yürüyüş tekerlekleri ve tamponlar görünür olarak sağlam mı?',
                'Halat ve uç bağlantıları görünür tel kopması, ezilme ve hasardan arınmış mı?',
                'Tambur, makaralar, halat sarımı ve bağlantılar uygun mu?',
                'Kanca, mandal ve blok sağlam, sapanlar tanımlı ve yüke uygun mu?',
                'Kaldırma/yürütme frenleri ve kumandalar güvenli işlev kontrolünde çalışıyor mu?',
                'Mevcut yük sınırlayıcılar, göstergeler ve hareket limitleri çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Kablo, fiş ve pano sağlam, elektrik koruma düzenekleri kullanıma hazır mı?',
                'Yürüyüş alarmı ve mevcut ikaz lambaları çalışıyor mu?',
                'Kablo taşıma ve enerji besleme düzeni takılmadan hareket ediyor mu?',
                'Yük kapasiteye uygun, kaldırma alanı ayrılmış ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Gantry Vinç (Bacaklı Vinç)', 'sorular' => [
                'Kiriş, ayak ve bağlantılarda görünür çatlak/gevşeklik yok mu?',
                'Ayaklar, tekerlekler ve mevcut sabitleme/park kilitleri uygun mu?',
                'Halat ve uç bağlantıları görünür tel kopması, ezilme ve hasardan arınmış mı?',
                'Tambur, makaralar, halat sarımı ve bağlantılar uygun mu?',
                'Kanca, mandal ve blok sağlam, sapanlar tanımlı ve yüke uygun mu?',
                'Kaldırma/yürütme frenleri ve kumandalar güvenli işlev kontrolünde çalışıyor mu?',
                'Mevcut yük sınırlayıcılar, göstergeler ve hareket limitleri çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Kablo, fiş ve pano sağlam, elektrik koruma düzenekleri kullanıma hazır mı?',
                'Yürüyüş alarmı ve mevcut ikaz lambaları çalışıyor mu?',
                'Kablo taşıma ve enerji besleme düzeni takılmadan hareket ediyor mu?',
                'Yük kapasiteye uygun, kaldırma alanı ayrılmış ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Caraskal', 'sorular' => [
                'Üst askı noktası ve taşıyıcı eleman yük kapasitesine uygun mu?',
                'Üst kanca ve mandal sağlam ve tam oturmuş mu?',
                'Alt kanca ve mandal sağlam, yük merkezden bağlanmış mı?',
                'Yük zinciri bükülme, çatlak ve belirgin aşınmadan arınmış mı?',
                'El zinciri ve zincir kılavuzu düzgün ve takılmadan hareket ediyor mu?',
                'Zincir dişlisi ve gövde görünür hasardan arınmış mı?',
                'Yük freni üreticiye uygun güvenli kontrolde yükü tutuyor mu?',
                'Gövde, kapak ve bağlantılar sağlam mı?',
                'Kapasite levhası okunaklı ve planlanan yük kapasite içinde mi?',
                'Sapan ve bağlantı aksesuarları tanımlı, sağlam ve uygun mu?',
                'Çekiş düşey, yük yolu boş ve yan çekme önlenmiş mi?',
                'Yük kapasiteye uygun, kaldırma alanı ayrılmış ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Damperli Şantiye Kamyonu', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Şasi, süspansiyon ve kasa alt bağlantıları sağlam mı?',
                'Damper silindiri ve hidrolik hatlar sağlam ve kaçaksız mı?',
                'Kasa menteşeleri ve pim emniyetleri sağlam mı?',
                'Arka kapak ve kilitler düzgün çalışıyor mu?',
                'Seyir öncesi kasa tamamen indirilmiş ve uygun konumda mı?',
                'Yük dağılımı, kapasite ve dökülmeye karşı önlemler uygun mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Kaya Kamyonu', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Retarder/yavaşlatıcı ve uyarı göstergeleri normal çalışıyor mu?',
                'Şasi ve süspansiyon silindirlerinde görünür hasar/kaçak yok mu?',
                'Damper silindirleri ve hidrolik bağlantılar sağlam ve kaçaksız mı?',
                'Kasa, menteşeler ve pimler sağlam, kasa indirme uyarısı normal mi?',
                'Yük dağılımı ve toplam yük kapasiteye uygun mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Yangın söndürücü erişilebilir ve hazır, yanıcı birikimleri temizlenmiş mi?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Belden Kırmalı Kaya Kamyonu', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Belden kırma/dönme mafsalı, pimler ve bağlantılar sağlam mı?',
                'Mafsal bakım kilidi mevcut ve sağlam mı (kontrolde araya girilmez)?',
                'Kasa, menteşeler ve damper silindirleri sağlam mı?',
                'Kasa seyir için indirilmiş, yük dağılımı ve kapasite uygun mu?',
                'Kumandalar ve göstergeler normal, mevcut emniyet kilitleri çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Tır ve Çekici', 'sorular' => [
                'Motor yağı/soğutma sıvısı uygun, yakıt ve sıvı sistemleri kaçaksız mı?',
                'Lastik diş/yanakları, jant ve bijonlar sağlam, basınçlar uygun mu?',
                'Frenler, hava basıncı ve düşük basınç uyarısı düzgün çalışıyor mu?',
                'Direksiyon, süspansiyon ve şasi hasardan/anormal boşluktan arınmış mı?',
                'Far, stop, sinyal, reflektör, korna ve varsa geri alarmı uygun mu?',
                'Cam, silecek, görüş araçları, kemer ve basamaklar uygun mu?',
                'Çekici–römork kilidi ve kingpin/beşinci teker emniyeti doğrulandı mı?',
                'Römork hava/elektrik bağlantıları sağlam, destek ayakları kaldırılmış mı?',
                'Yük dağılımı, kapasite, bağlamalar, kasa kapakları ve örtü uygun mu?',
                'Damper hidrolikleri/kilitleri sağlam, kasa seyir için indirilmiş mi?',
                'Göstergeler normal, söndürücü, reflektör ve takozlar hazır mı?',
                'Güzergâh ve boşaltma zemini güvenli, damper için üst açıklık yeterli mi?',
            ]],
            ['ad' => 'Lowbed (Ağır Yük Taşıyıcı Dorse)', 'sorular' => [
                'Dorse şasisi ve yük platformu sağlam, çatlak/gevşeklikten arınmış mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Fren hava hatları, bağlantılar ve fren işlevi uygun mu?',
                'Kingpin/beşinci teker kilidi ve emniyeti doğrulandı mı?',
                'Destek ayakları ve varsa hidrolik boyun bağlantıları sağlam mı?',
                'Rampalar, menteşeler ve kapasite işaretleri uygun mu?',
                'Rampalar seyirde kilitli, kaldırma hidrolikleri sağlam ve kaçaksız mı?',
                'Bağlama halkaları, zincirler ve gerdiriciler sağlam ve yüke uygun mu?',
                'Yük dağılımı, ağırlık merkezi ve dingil yükleri uygun mu?',
                'Stop, sinyal, yan lambalar ve reflektörler uygun mu?',
                'Yükleme zemini sağlam, araç sabitlenmiş ve takozlanmış mı?',
                'Yükseklik/genişlik açıklıkları ve taşıma güzergâhı uygun mu?',
            ]],
            ['ad' => 'Su Tankeri', 'sorular' => [
                'Motor yağı/soğutma sıvısı uygun, yakıt ve hidrolik sistemleri kaçaksız mı?',
                'Lastikler, jantlar, bijonlar ve süspansiyon sağlam mı?',
                'Servis/park freni, fren hava sistemi ve direksiyon düzgün çalışıyor mu?',
                'Tank gövdesi, şasi ve sabitlemeler sağlam ve kaçaksız mı?',
                'Dolum kapağı, contalar ve havalandırma sağlam ve tıkanıklıktan arınmış mı?',
                'Su pompası ve PTO koruyucuları sağlam, çalışma sesi/titreşimi normal mi?',
                'Hortumlar sabitlenmiş, rakorlar ve vanalar sağlam ve kaçaksız mı?',
                'Püskürtme barı/nozullar ve açma-kapama kumandaları çalışıyor mu?',
                'Dolum seviyesi/yük uygun, taşma ve kontrolsüz boşalma önlenmiş mi?',
                'Far, stop, sinyal, korna, geri alarmı ve tepe lambası çalışıyor mu?',
                'Kemer, görüş araçları ve merdiven/platform sağlam, söndürücü hazır mı?',
                'Güzergâh ve püskürtme; görüş, yol tutuşu ve yayalar açısından güvenli mi?',
            ]],
            ['ad' => 'Akaryakıt Tankeri', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Lastikler, jantlar ve bijonlar sağlam, varsa hava basıncı uygun mu?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Tank gövdesi ve sabitlemeler sağlam, yakıt sızıntısı ve koku belirtisi yok mu?',
                'Kapak, conta ve havalandırma düzeni sağlam ve kapalı/uygun konumda mı?',
                'Tahliye vanaları, kapakları ve acil kapatma sistemi uygun mu?',
                'Yakıt hortumu ve bağlantıları ürüne uygun, sağlam ve kaçaksız mı?',
                'Aktarım için gereken eşpotansiyel/topraklama bağlantısı hazır ve uygun mu?',
                'Uygun söndürücü ve döküntü müdahale malzemeleri hazır mı?',
                'Taşınan ürüne uygun tehlike işaretleri ve etiketler okunaklı mı?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Aktarım alanı ayrılmış, ateşleme kaynakları uzak ve araç sabitlenmiş mi?',
            ]],
            ['ad' => 'Transmikser (Beton Mikseri)', 'sorular' => [
                'Motor, soğutma ve hidrolik sıvı seviyeleri uygun ve sistemler kaçaksız mı?',
                'Lastikler, jantlar, bijonlar ve süspansiyon sağlam mı?',
                'Servis/park freni, fren hava sistemi ve direksiyon çalışıyor mu?',
                'Kazan gövdesi ve taşıyıcı bağlantıları sağlam ve sıkı mı?',
                'Kazan bileziği, destek makaraları ve yatakların sesi/boşlukları normal mi?',
                'Kazan tahriki, redüktör ve hidrolik hatlar sağlam ve kaçaksız mı?',
                'Kazan dönüş/yön kumandaları ve acil durdurma çalışıyor mu?',
                'Dolum hunisi, boşaltma oluğu, uzatmalar ve seyir kilitleri sağlam mı?',
                'Koruyucular yerinde, hareket alanları tehlikeli beton birikiminden arınmış mı?',
                'Su tankı, vanalar ve basınçlı tipte gösterge/emniyet donanımı uygun mu?',
                'Aydınlatma, sinyal, korna, geri alarmı, kemer ve görüş araçları uygun mu?',
                'Merdiven/platform sağlam ve temiz, söndürücü hazır, boşaltma alanı güvenli mi?',
            ]],
            ['ad' => 'Toprak Silindiri', 'sorular' => [
                'Motor, soğutma ve hidrolik sıvı seviyeleri uygun ve sistemler kaçaksız mı?',
                'Hidrolik hortum, bağlantı ve yürüyüş motorları sağlam ve kaçaksız mı?',
                'Tambur, sıyırıcılar ve bağlantıları sağlam ve sıkışmış malzemeden arınmış mı?',
                'Lastikli tipte lastikler/bijonlar sağlam ve basınçlar uygun mu?',
                'Servis/park freni, ileri–geri kumandası ve direksiyon çalışıyor mu?',
                'Mafsal ve direksiyon silindirleri sağlam, boşlukları normal mi?',
                'Uygun zemindeki kontrolde titreşim sistemi ve çalışma sesi normal mi?',
                'Tambur titreşim takozları ve bağlantıları sağlam mı?',
                'Su tankı, pompa, püskürtme nozulları ve sıyırıcı ayarları uygun mu?',
                'Far, korna, geri alarmı, tepe lambası ve göstergeler çalışıyor mu?',
                'Devrilme koruyucusu, kemer, emniyet sistemleri ve söndürücü uygun mu?',
                'Eğim üretici sınırında, şev/kenar mesafesi, zemin ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Asfalt Silindiri', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Ön/arka tambur ve bağlantıları sağlam, yüzeyler temiz mi?',
                'Sıyırıcılar sağlam, ayarları uygun ve malzeme birikimi temizlenmiş mi?',
                'Servis/park frenleri ve direksiyon güvenli işlev kontrolünde çalışıyor mu?',
                'Belden kırma mafsalı ve yönlendirme silindirleri sağlam mı?',
                'Titreşim sistemi talimata uygun zeminde normal çalışıyor mu?',
                'Titreşim takozları ve tambur bağlantıları sağlam mı?',
                'Su tankı, pompa ve nozullar çalışıyor, tamburlar yeterli ıslanıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kabin koruyucusu, kemer, görüş araçları ve basamaklar uygun mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Asfalt Finişeri', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Palet, zincir, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Yürüyüş, yönlendirme ve park freni düzgün çalışıyor mu?',
                'Hazne kanatları ve bağlantıları sağlam, sıkışma alanı boş mu?',
                'Konveyör ve zincirler sağlam, koruyucular yerinde mi?',
                'Helezonlar sağlam, dönme alanına erişim önlenmiş mi?',
                'Tabla, uzatmalar ve kilitler sağlam, ayarlar uygun mu?',
                'Tabla ısıtma sistemi ve kablo/yakıt hatları hasarsız ve kaçaksız mı?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Kamyon yaklaşımı güvenli, yaya ayrımı ve sıcak yüzey önlemleri uygun mu?',
            ]],
            ['ad' => 'Asfalt Freze Makinesi', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Palet, zincir, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Yürüyüş ve yönlendirme kumandaları ile frenler çalışıyor mu?',
                'Güvenli duruşta görülebilen tambur ve kesici tutucular sağlam mı?',
                'Erişilebilir kesici uçlar sağlam ve emniyetli mi (enerji kesilmişken)?',
                'Tambur muhafazası, etekler ve erişim kilitleri sağlam mı?',
                'Konveyör bantları, makaralar ve koruyucular uygun mu?',
                'Su püskürtme/toz bastırma sistemi düzgün çalışıyor mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Kompaktör', 'sorular' => [
                'Motor yağı ve yakıt sistemi uygun, görünür kaçak yok mu?',
                'Taban plakası sağlam, altında sıkışmış malzeme yok mu?',
                'Titreşim mekanizması ve bağlantıları sağlam, anormal ses yok mu?',
                'Kayış/kasnak koruyucusu yerinde ve sağlam mı?',
                'Titreşim takozları ve bağlantılar sağlam mı?',
                'Tutamak, katlama kilidi ve bağlantıları sağlam mı?',
                'Gaz, yön ve mevcut çalıştırma emniyetleri çalışıyor mu?',
                'Motor durdurma düğmesi düzgün çalışıyor mu?',
                'Taşıma/kaldırma noktası sağlam ve tanımlı mı?',
                'Havalandırma yeterli, egzoz çıkışı insanlardan ve hava emişlerinden uzak mı?',
                'İşe uygun işitme, göz ve ayak koruması hazır mı?',
                'Zemin, kenar mesafesi, hat açıklıkları ve yaya ayrımı güvenli mi?',
            ]],
            ['ad' => 'Mobil Beton Pompası', 'sorular' => [
                'Motor/hidrolik sıvıları uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Lastik/bijonlar, direksiyon ve frenler uygun, takozlar hazır mı?',
                'Denge ayakları, kilit ve altlıklar uygun, zemin sağlam, makine terazide mi?',
                'Bom bölümleri, mafsallar, pimler ve emniyetler sağlam ve sıkı mı?',
                'Beton boruları/dirsekler görünür aşınma, darbe ve kaçaktan arınmış mı?',
                'Boru kelepçeleri, contalar ve emniyet pimleri tam ve bağlantılar sağlam mı?',
                'Uç hortumu sağlam, tipi/boyu ve uç donanımı üretici şartlarına uygun mu?',
                'Hazne ızgarası ve açılınca hareketi kesen emniyet sistemi çalışıyor mu?',
                'Uzaktan/yerel kumandalar, acil durdurmalar ve haberleşme çalışıyor mu?',
                'Basınç göstergeleri ve bom/ayak emniyetleri normal çalışıyor mu?',
                'Aydınlatma, sinyal, korna, geri alarmı, görüş araçları ve söndürücü uygun mu?',
                'Bom/hat mesafesi güvenli, hortum çevresi boş ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Sabit Beton Pompası', 'sorular' => [
                'Motor/elektrik beslemesi uygun, kablo ve yakıt hatları sağlam mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Şasi/destekler sağlam, pompa sabitlenmiş ve varsa tekerlekler takozlu mu?',
                'Hazne ızgarası ve erişim emniyet kilidi çalışıyor mu?',
                'Hazne karıştırıcısına hareket halindeyken erişim önlenmiş mi?',
                'Boru, dirsek ve destekler sağlam, görünür kaçak yok mu?',
                'Kelepçe, conta ve emniyet pimleri tam ve sağlam mı?',
                'Uç hortumu sağlam ve üreticiye uygun, çevresi ayrılmış mı?',
                'Basınç göstergesi normal, emniyet donanımı sağlam ve devrede mi?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Kumanda ve hat sonundaki ekiple haberleşme çalışıyor mu?',
                'Hat güzergâhı korunmuş, temizlik/basınç boşaltma düzeni hazır mı?',
            ]],
            ['ad' => 'Beton Santrali', 'sorular' => [
                'Bunkerler ve boşaltma kapaklarında görünür hasar yok mu?',
                'Bant, makara ve koruyucular sağlam, bant güzergâhı temiz mi?',
                'Konveyör acil durdurma ipleri ve düğmeleri talimata göre çalışıyor mu?',
                'Mikser dış gövdesi ve bağlantıları sağlam, anormal ses/kaçak yok mu?',
                'Mikser erişim kapaklarının emniyet kilitleri çalışıyor mu?',
                'Silo filtresi ve basınç emniyet donanımı normal durumda mı?',
                'Dolum hortumu, bağlantı ve taşma/üst seviye uyarıları uygun mu?',
                'Tartım göstergeleri ve dozaj kumandaları normal mi?',
                'Hava/su hatları, vanalar ve göstergeler sağlam ve kaçaksız mı?',
                'Kablo, fiş ve pano sağlam, elektrik koruma düzenekleri kullanıma hazır mı?',
                'Merdiven, platform ve korkuluklar sağlam ve temiz mi?',
                'Mikser/yükleyici trafiği, yaya ayrımı ve toz kontrolü uygun mu?',
            ]],
            ['ad' => 'Grout Enjeksiyon Makinesi', 'sorular' => [
                'Motor/elektrik beslemesi ve bağlantıları sağlam mı?',
                'Karıştırma tankı ve kapak/ızgara sağlam mı?',
                'Karıştırıcı koruyucusu ve mevcut kapak emniyeti çalışıyor mu?',
                'Pompa ve tahrik bağlantıları sağlam, görünür kaçak yok mu?',
                'Hortumlar basınca uygun, hasarsız ve kaçaksız, bağlantılar emniyetli mi?',
                'Basınç göstergesi normal, emniyet donanımı sağlam ve devrede mi?',
                'Basınç tahliye/geri dönüş hattı ve vanası uygun durumda mı?',
                'Paker, enjeksiyon bağlantıları ve sabitlemeleri sağlam mı?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Bağlantılarda grout sızıntısı yok, döküntü kontrolü hazır mı?',
                'Çimento/kimyasal için uygun göz, cilt ve solunum koruması hazır mı?',
                'Hortum güzergâhı güvenli, alan ayrılmış ve ekip iletişimi hazır mı?',
            ]],
            ['ad' => 'Perdah Makinesi (Helikopter)', 'sorular' => [
                'Yağ/yakıt seviyeleri uygun, sistem kaçaksız mı?',
                'Enerji kesilmişken bıçaklar/pan sağlam ve bağlantıları sıkı mı?',
                'Koruma çemberi ve bağlantıları sağlam mı?',
                'Kayış ve tahrik koruyucuları yerinde mi?',
                'Tutamak ve ayar/kilit mekanizması sağlam mı?',
                'Tutamak bırakılınca gücü kesen emniyet kumandası çalışıyor mu?',
                'Gaz ve normal durdurma kumandaları çalışıyor mu?',
                'Bıçak açı ayarı ve bağlantıları düzgün çalışıyor mu?',
                'Taşıma noktası ve bağlantıları sağlam mı?',
                'Havalandırma yeterli, egzoz çıkışı insanlardan ve hava emişlerinden uzak mı?',
                'İşe uygun göz, işitme ve ayak koruması hazır mı?',
                'Beton yüzeyi ve kenarlar güvenli, çevredeki kişiler uzaklaştırılmış mı?',
            ]],
            ['ad' => 'Beton Vibratörü', 'sorular' => [
                'Besleme ve elektrik koruma düzeni ekipmana ve ortama uygun mu?',
                'Kablo, fiş ve bağlantılar sağlam, ekler güvenli mi?',
                'Motor gövdesi ve tutamak sağlam, havalandırma açıklıkları temiz mi?',
                'Açma-kapama anahtarı düzgün çalışıyor mu?',
                'Esnek mil/kılıf sağlam, aşırı kıvrılma veya ezilme yok mu?',
                'Şişe ve uç bağlantıları sağlam ve sıkı mı?',
                'Mil-motor kaplini ve emniyeti sağlam mı?',
                'Üreticiye uygun kısa işlev kontrolünde titreşim ve ses normal mi?',
                'Kablo su birikimi, ezilme ve keskin kenarlardan korunmuş mu?',
                'İşe uygun el, göz, işitme ve ayak koruması hazır mı?',
                'Kalıp/çalışma platformu ve erişim yolu güvenli mi?',
                'Gövde ve tutma alanları temiz, enerji kesilerek temizlik düzeni hazır mı?',
            ]],
            ['ad' => 'Fore Kazık Makinesi', 'sorular' => [
                'Motor yağı ve soğutma sıvısı uygun, yakıt ve yağ sistemleri kaçaksız mı?',
                'Hidrolik seviye uygun, hortum ve silindirler sağlam ve kaçaksız mı?',
                'Palet, zincir, makara ve cer dişlileri sağlam, gerginlik uygun mu?',
                'Mast, mafsallar ve pim emniyetleri sağlam mı?',
                'Burgu/kova, kelly bar ve kilit bağlantıları sağlam mı?',
                'Döndürme kafası ve kızaklar sağlam, hareketleri normal mi?',
                'Kanca, mandal ve kaldırma bağlantıları sağlam mı?',
                'Varsa halat/zincir ve besleme sistemi görünür hasardan arınmış mı?',
                'Dönen takıma erişimi önleyen koruyucu/emniyetler uygun mu?',
                'Acil durdurma ve yeniden çalıştırma emniyeti talimata göre çalışıyor mu?',
                'Aydınlatma, korna ve mevcut hareket/geri vites alarmları çalışıyor mu?',
                'Zemin, hatlar, delgi çevresi ve ekip iletişimi güvenli mi?',
            ]],

            // Kullanıcının kendi formu (CNC_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx, İSG-FR-CNC-01) — BİREBİR;
            // gruplu, U/UD/Y/X kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'CNC Tezgâhı',
                'baslik' => 'CNC MAKİNESİ GÜNLÜK KONTROL VE BAKIM FORMU',
                'form_no' => 'İSG-FR-CNC-01',
                'onaylayan' => 'Bölüm Sorumlusu / İSG Uzmanı',
                'kriterler' => [
                    'U' => 'Uygun',
                    'UD' => 'Uygun Değil',
                    'Y' => 'Yapıldı / Dolduruldu',
                    'X' => 'Uygulanamaz',
                ],
                'gruplar' => [
                    [
                        'baslik' => '1. GÜVENLİK & ACİL DURUM EKİPMANLARI',
                        'sorular' => [
                            'Acil durdurma (E-Stop) butonları çalışır ve serbest mi?',
                            'Kabin kapı emniyet kilidi (interlock switch) işlevsel mi?',
                            'Tezgah içi aydınlatma armatürü çalışıyor ve yeterli mi?',
                            'Gövde ve zemin çevresinde kayma/düşme tehlikesi (sıvı, talaş) var mı?',
                        ],
                    ],
                    [
                        'baslik' => '2. PNÖMATİK & HİDROLİK SİSTEMLER',
                        'sorular' => [
                            'Hava giriş basıncı nominal aralıkta mı (min. 5.5 - 6 bar)?',
                            'Hidrolik ünite basıncı ve yağ seviyesi göstergesi uygun mu?',
                            'Hortum, rekor ve valf bağlantılarında hava/yağ sızıntısı var mı?',
                        ],
                    ],
                    [
                        'baslik' => '3. YAĞLAMA & SOĞUTMA SIVISI (BOR YAĞI)',
                        'sorular' => [
                            'Merkezi otomatik kızak yağlama deposu seviyesi yeterli mi?',
                            'Soğutma sıvısı (bor yağı) seviyesi ve refraktometre oranı uygun mu?',
                            'Soğutma nozulları temiz, yönlendirilmiş ve debisi yeterli mi?',
                        ],
                    ],
                    [
                        'baslik' => '4. MEKANİK & TAKIM BAĞLAMA DONANIMLARI',
                        'sorular' => [
                            'Ayna / mengene sıkma pabuçlarında çatlak veya aşınma var mı?',
                            'Kesici takımlar ve tutucularda hasar, gevşeklik, salgı var mı?',
                            'Magazin ve otomatik takım değiştirici (ATC) sorunsuz dönüyor mu?',
                            'X, Y, Z eksen körük muhafazaları yırtıksız ve sağlam mı?',
                        ],
                    ],
                    [
                        'baslik' => '5. TEMİZLİK & ÇEVRE DÜZENİ',
                        'sorular' => [
                            'Talaş konveyörü çalışır durumda ve talaş teknesi boşaltıldı mı?',
                            'Elektrik pano filtreleri ve soğutma fanları temiz mi?',
                        ],
                    ],
                ],
            ],
            // Kullanıcının kendi formu (Universal_Torna_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx, İSG-FR-TRN-01) — BİREBİR;
            // gruplu, U/UD/Y/X kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Torna Tezgâhı',
                'baslik' => 'ÜNİVERSAL TORNA GÜNLÜK KONTROL VE BAKIM FORMU',
                'form_no' => 'İSG-FR-TRN-01',
                'onaylayan' => 'Atölye Sorumlusu / İSG Uzmanı',
                'kriterler' => [
                    'U' => 'Uygun',
                    'UD' => 'Uygun Değil',
                    'Y' => 'Yapıldı / Dolduruldu',
                    'X' => 'Uygulanamaz',
                ],
                'gruplar' => [
                    [
                        'baslik' => '1. GÜVENLİK, KORUYUCULAR & ACİL DURUM SİSTEMLERİ',
                        'sorular' => [
                            'Acil Durdurma (E-Stop) butonu ve Acil Ayak Fren Pedalı çalışıyor mu?',
                            'Ayna koruyucu muhafazası (siperlik) sağlam ve switchi aktif mi?',
                            'Araba üzerindeki şeffaf talaş koruma siperliği temiz ve sağlam mı?',
                            'Ayna anahtarı yaylı mekanizmaya sahip mi ve anahtar ayna dışında mı?',
                            'Fener mili arkasından taşan çubuklar için koruma borusu takılı mı?',
                        ],
                    ],
                    [
                        'baslik' => '2. MEKANİK DONANIMLAR, AYNA & PUNTA KONTROLÜ',
                        'sorular' => [
                            'Ayna çeneleri ve cıvataları aşınmasız, sıkı ve temiz mi?',
                            'Gezer punta gövdesi, koniği ve kilitleme kolu sağlam kilitliyor mu?',
                            'Kalemlik (kater tutucu) cıvataları sağlam ve sıkma torku yeterli mi?',
                            'Ana mil (vidalı mil) ve talaş mili koruma kılıfları/yayları sağlam mı?',
                            'Devir ve ilerleme kumanda kolları boşluksuz ve kilitli geçiyor mu?',
                        ],
                    ],
                    [
                        'baslik' => '3. YAĞLAMA & SOĞUTMA SİSTEMLERİ',
                        'sorular' => [
                            'Fener mili dişli kutusu yağ seviye göstergesi normal aralıkta mı?',
                            'Kızak ve araba el pompası / merkezi yağlama deposu dolu mu?',
                            'Soğutma sıvısı (bor yağı) tank seviyesi, hortumu ve debisi yeterli mi?',
                            'Gövdede, kızaklarda veya zemin üzerinde yağ/sıvı kaçağı var mı?',
                        ],
                    ],
                    [
                        'baslik' => '4. ELEKTRİK, AYDINLATMA & ÇEVRE DÜZENİ',
                        'sorular' => [
                            'Tezgah aydınlatma lambası (alçak gerilim 24V) çalışır durumda mı?',
                            'Elektrik kablolarında, spirallerde yıpranma veya ezilme var mı?',
                            'Talaş tavası boşaltılmış ve çalışma tabanı kaymaz ızgara/paspaslı mı?',
                            'Talaş kancası ve temizleme fırçası tezgah başında hazır mı?',
                        ],
                    ],
                ],
            ],

            // Kullanıcının kendi formu (Dikey_Freze_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx, İSG-FR-FRZ-01) — BİREBİR;
            // gruplu, U/UD/Y/X kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Dikey Freze Tezgâhı',
                'baslik' => 'DİKEY FREZE GÜNLÜK KONTROL VE BAKIM FORMU',
                'form_no' => 'İSG-FR-FRZ-01',
                'onaylayan' => 'Atölye Sorumlusu / İSG Uzmanı',
                'kriterler' => [
                    'U' => 'Uygun',
                    'UD' => 'Uygun Değil',
                    'Y' => 'Yapıldı / Kontrol Edildi',
                    'X' => 'Uygulanamaz',
                ],
                'gruplar' => [
                    [
                        'baslik' => '1. GÜVENLİK DONANIMLARI & ACİL DURUM SİSTEMLERİ',
                        'sorular' => [
                            'Acil Durdurma (E-Stop) butonu basıldığında mili anında durduruyor mu?',
                            'Fener mili şeffaf çapak siperliği sağlam ve emniyet switchi aktif mi?',
                            'Tezgah aydınlatma lambası (24V alçak gerilim) sağlam ve temiz mi?',
                            'Zeminde kaymaya sebep olacak bor yağı, yağ veya talaş birikintisi var mı?',
                        ],
                    ],
                    [
                        'baslik' => '2. MEKANİK MEKANİZMALAR, TABLA & TAKIM BAĞLAMA',
                        'sorular' => [
                            'Mengene, T-cıvataları ve bağlama pabuçları çatlaksız ve sağlam mı?',
                            'Freze çakısı, malafa mili ve pens sıkma somunu rijit ve hasarsız mı?',
                            'X, Y, Z eksen hareket milleri ve boşluk ayar kamaları normal mi?',
                            'Eksen limit sviçleri (dayamalar) çalışıyor ve doğru ayarlanmış mı?',
                            'Eksen körük muhafazaları yırtıksız ve kılavuz kızakları koruyor mu?',
                        ],
                    ],
                    [
                        'baslik' => '3. YAĞLAMA & SOĞUTMA SİSTEMLERİ',
                        'sorular' => [
                            'Merkezi kızak yağlama el pompası deposunda yağ seviyesi yeterli mi?',
                            'Fener mili dişli kutusu yağ seviye gösterge camı kontrol edildi mi?',
                            'Bor yağı soğutma sıvısı tank seviyesi, sirkülasyonu ve debisi uygun mu?',
                            'Hortumlarda, kızaklarda veya pompa bağlantılarında sıvı sızıntısı var mı?',
                        ],
                    ],
                    [
                        'baslik' => '4. TEMİZLİK, EL ALETLERİ & ÇEVRE DÜZENİ',
                        'sorular' => [
                            'Talaş temizleme fırçası ve kancası tezgah başında hazır mı?',
                            'Mengene ve sıkma anahtarları kendi askılık/yuvasında mı?',
                            'Tezgah talaş tavası boşaltılmış ve atık talaş kovasına aktarılmış mı?',
                            'Operatörün üzerinde eldiven, takı veya sarkan kıyafet OLMADIĞI teyit edildi mi?',
                        ],
                    ],
                ],
            ],
            // Kullanıcının kendi formu (Trafo_Merkezi_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx, İSG-FR-TRF-01) — BİREBİR;
            // gruplu, U/UD/Y/X kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Trafo (OG/AG)',
                'baslik' => 'TRAFO MERKEZİ GÜNLÜK / HAFTALIK KONTROL FORMU',
                'form_no' => 'İSG-FR-TRF-01',
                'onaylayan' => 'YG İşletme Sorumlusu / İSG Uzmanı',
                'kriterler' => [
                    'U' => 'Uygun',
                    'UD' => 'Uygun Değil',
                    'Y' => 'Yapıldı / Kontrol Edildi',
                    'X' => 'Uygulanamaz',
                ],
                'gruplar' => [
                    [
                        'baslik' => '1. GİRİŞ GÜVENLİĞİ, FİZİKİ ŞARTLAR & YANGIN EMNİYETİ',
                        'sorular' => [
                            'Trafo merkezi giriş kapısı kilitli ve \'ÖLÜM TEHLİKESİ\' levhaları tam mı?',
                            'Dış havalandırma panjurları temiz ve tel kafesleri haşere/kuş girişine kapalı mı?',
                            'Kablo galerilerinde, tavanda veya zeminde su sızıntısı / nem var mı?',
                            'Trafo kapısında 6 kg CO2 veya KKT yangın söndürme tüpü dolu ve güncel mi?',
                            'Acil durum aydınlatması ve oda normal aydınlatması çalışıyor mu?',
                        ],
                    ],
                    [
                        'baslik' => '2. İSG & YALITKAN GÜVENLİK DONANIMLARI',
                        'sorular' => [
                            'Pano ve hücre önlerindeki yalıtkan paspaslar (dielektrik halı) tam ve serili mi?',
                            'Yüksek gerilim yalıtkan manevra ıstankası (stank) askısında ve hasarsız mı?',
                            'Yüksek gerilim kontrol dedektörü (stank ucu) pilli ve test edilmiş durumda mı?',
                            'Dielektrik yalıtkan eldiven ve yalıtkan çizme dolabında hazır ve deliksiz mi?',
                        ],
                    ],
                    [
                        'baslik' => '3. TRANSFORMATÖR & ELEKTROMEKANİK DURUM GÖZLEMİ',
                        'sorular' => [
                            'Trafo yağ sıcaklığı termometresi izin verilen işletme sınırında mı (<85°C)?',
                            'Trafo yağ seviye göstergesi normal seviye aralığında mı?',
                            'Silikajel (nem alıcı) kristalleri pembeleşmemiş (mavi/kuru) durumda mı?',
                            'Trafodan olağan dışı aşırı vızıltı, ark/cızırtı sesi veya titreşim geliyor mu?',
                            'Trafo gövdesinde, vana veya buşing diplerinde yağ kaçağı/sızıntısı var mı?',
                        ],
                    ],
                    [
                        'baslik' => '4. KORUMA RÖLELERİ, PANO & TOPRAKLAMA SİSTEMİ',
                        'sorular' => [
                            'Buchholz rölesi, termik röle veya aşırı akım rölesinde arıza/ihbar var mı?',
                            'Gövde ve nötr işletme topraklama irtibat baraları sağlam ve oksitsiz mi?',
                            'Hücre kapakları, kilit mekanizmaları ve mekanik göstergeler tam mı?',
                            'Trafo merkezi içinde ilgisiz malzeme, çöp veya hurda OLMADIĞI teyit edildi mi?',
                        ],
                    ],
                ],
            ],
            // Kullanıcının kendi formu (Acik_Vals_Tezgahi_Guvenli_Calisma_Talimati_ve_Kontrol_Formu.docx, TLT-VALS-01) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Açık Vals (İki Silindirli)',
                'baslik' => 'AÇIK VALS TEZGÂHI GÜNLÜK KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-VALS-01',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. ACİL DURDURMA VE EMNİYET DONANIMLARI',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Göğüs / Diz Emniyet Barı',
                        'kabul' => 'Operatör yaslandığında tezgâhı ve merdane hareketini anında kilitliyor mu?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Acil Stop Butonları',
                        'kabul' => 'Mekanik olarak sağlam, basıldığında anında duruş sağlıyor mu?',
                    ],
                    [
                        'no' => '1.3',
                        'soru' => 'Emniyet Teli / Çekme Halatı',
                        'kabul' => 'Halat gergin, deformasyonsuz ve çekildiğinde sistemi durduruyor mu?',
                    ],
                    [
                        'no' => '1.4',
                        'soru' => 'Hızlı Ters Dönüş (Reverse) / Açma',
                        'kabul' => 'Acil duruş tetiklendiğinde merdaneleri geriye açma sistemi devrede mi?',
                    ],
                    [
                        'no' => '1.5',
                        'soru' => 'Ani Frenleme Mesafesi',
                        'kabul' => 'Merdaneler azami çeyrek tur içinde tam duruşa geçiyor mu?',
                    ],
                    [
                        'grup' => '2. MEKANİK & TAHRİK AKSAMI',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Kayış - Kasnak & Dişli Muhafazası',
                        'kabul' => 'Tahrik koruma sacları ve kapakları tam kapalı, sabitlenmiş ve kilitli mi?',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Silindir Aralık (Nip) Ayarı',
                        'kabul' => 'Merdane aralık ayar mekanizması boşluksuz ve düzgün çalışıyor mu?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Yatak Yağlaması & Gres Durumu',
                        'kabul' => 'Rulman ve yataklarda yeterli yağlama mevcut mu, aşırı ısınma/ses var mı?',
                    ],
                    [
                        'no' => '2.4',
                        'soru' => 'Silindir Yüzey Durumu',
                        'kabul' => 'Merdanelerde çatlak, derin çizik, korozyon veya yabancı cisim var mı?',
                    ],
                    [
                        'grup' => '3. ELEKTRİK & ÇEVRESEL İSG ŞARTLARI',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Kumanda Panosu & İzolasyon',
                        'kabul' => 'Pano kapağı kilitli, açıkta veya zedelenmiş kablo bağlantısı yok mu?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Gövde Topraklaması',
                        'kabul' => 'Tezgâh gövdesinde koruma topraklama hattı sağlam ve bağlı mı?',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Kaymaz Zemin & Çalışma Alanı',
                        'kabul' => 'Zemin kaygan yağdan arındırılmış, kaymaz ızgara/ped sağlam ve temiz mi?',
                    ],
                    [
                        'no' => '3.4',
                        'soru' => 'Operatör Kıyafet & Eldiven Kontrolü',
                        'kabul' => 'Operatörün eldivensiz olduğu, manşetlerinin dar ve takısız olduğu teyit edildi mi?',
                    ],
                ],
                'karar' => [
                    'Tezgâh Güvenli (Çalışabilir)',
                    'EMNİYET EKSİKLİĞİ VAR (ÇALIŞTIRILAMAZ - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Operatör (Kontrolü Yapan)',
                    'Vardiya Amiri / Bakım Sorumlusu',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-MER-01) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Seyyar El Merdiveni',
                'baslik' => 'SEYYAR EL MERDİVENİ — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-MER-01',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. MEKANİK YAPI VE KAUÇUK PABUÇLAR',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Kaymaz Taban Pabuçları',
                        'kabul' => 'Kauçuk pabuçlar aşınmamış, yerinde ve yırtıksız mı?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Basamaklar ve Kaynaklar',
                        'kabul' => 'Basamaklarda çatlak, gevşek perçin veya eğrilme yok mu?',
                    ],
                    [
                        'no' => '1.3',
                        'soru' => 'Yan Kiriş Profilleri',
                        'kabul' => 'Ana taşıyıcı kollarda burkulma veya kalıcı deformasyon yok mu?',
                    ],
                    [
                        'grup' => '2. GÜVENLİK AKSAMI VE MONTAJ KİLİTLERİ',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'A-Tipi Gergi Makası / Emniyet İpi',
                        'kabul' => 'Mafsallar kilitleniyor, emniyet gergi teli/makası sağlam mı?',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Dayama Güvenliği / Üst Nokta',
                        'kabul' => 'Dayanılan yüzeyden en az 1 metre yukarıya taşıyor mu?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Elektrik Yalıtım Uyumu',
                        'kabul' => 'Elektrik panosu yanında iletken olmayan merdiven mi seçilmiş?',
                    ],
                    [
                        'grup' => '3. KULLANIM ORTAMI VE OPERATÖR UYUMU',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Zemin Dengesi & Temizlik',
                        'kabul' => 'Zemin düz, yağsız, kuru ve kaymaz özellikte mi?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => '3 Nokta Temas & Son 3 Basamak',
                        'kabul' => 'Operatör kurallara uygun ve son basamaklara çıkmadan mı çalışıyor?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-PUR-05) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Pürmüz ve LPG Tüpü',
                'baslik' => 'PÜRMÜZ VE PİKNİK TÜPÜ (LPG TAVLAMA SETİ) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-PUR-05',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. TÜP, VANA VE BAĞLANTI SIZDIRMAZLIĞI',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Tüp Gövdesi & Vana Durumu',
                        'kabul' => 'Tüpte darbe, korozyon yok, vana rahat açılıp kapanıyor mu?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Sızdırmazlık & Kaçak Kontrolü',
                        'kabul' => 'Vana ve bağlantılarda köpükle test yapılmış, gaz kaçağı yok mu?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Hortum Sağlamlığı',
                        'kabul' => 'Hortumda ezilme, çatlak, yanık yok ve LPG standardına uygun mu?',
                    ],
                    [
                        'grup' => '2. HORTUM VE KELEPÇE GÜVENLİĞİ',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Kelepçe Bağlantıları',
                        'kabul' => 'Hortum her iki uçta standart vidalı kelepçe ile sabitlenmiş mi?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Tüpün Sabitliği & Duruşu',
                        'kabul' => 'Tüp dik konumda, devrilmeye karşı emniyette mi?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Pürmüz Bek ve Tetik Emniyeti',
                        'kabul' => 'Bek ucu tıkalı değil, alev ayar vanası tam sızdırmaz mı?',
                    ],
                    [
                        'grup' => '3. ŞALOMA, ATEŞLEME VE YANGIN EMNİYETİ',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Yangın Tüpü (YSC) Erişimi',
                        'kabul' => 'Çalışma alanında 6 kg KKT yangın tüpü hazır ve dolu mu?',
                    ],
                    [
                        'no' => '3.4',
                        'soru' => 'Operatör KKD ve Çevre Yanıcıları',
                        'kabul' => 'Çevrede tiner/yağ yok, operatör deri eldiven ve gözlük takıyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-TAS-07) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Ayaklı Taş Motoru',
                'baslik' => 'ÇİFT TAŞLAMA MOTORU (ZIMPARA TAŞ MOTORU) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-TAS-07',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. TAŞ MUHAFAZALARI VE PARÇA DAYAMA MESAFESİ',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Parça Dayama Mesafesi (Max 3 mm)',
                        'kabul' => 'Dayama sehpası ile taş arası 3 mm\'den az ve cıvatası sıkı mı?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Üst Kıvılcım Siper Mesafesi (Max 6 mm)',
                        'kabul' => 'Üst ayar sacı ile taş arası 6 mm\'den az mı?',
                    ],
                    [
                        'no' => '1.3',
                        'soru' => 'Çelik Taş Muhafazası',
                        'kabul' => 'Taşın çevresindeki koruyucu sac sağlam ve hasarsız mı?',
                    ],
                    [
                        'grup' => '2. ŞEFFAF ÇAPAK SİPERLERİ VE TAŞ SAĞLAMLIĞI',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Şeffaf Pleksi Çapak Siperi',
                        'kabul' => 'Göz hizasındaki pleksiglas siperlik çatlak değil ve temiz mi?',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Taş Yüzey Durumu & Çatlak Kontrolü',
                        'kabul' => 'Taşta kırık, çatlak, oluklaşma veya yan yüzey aşınması yok mu?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Salgı ve Titreşim',
                        'kabul' => 'Taş dönerken salgı (yalpalama) veya aşırı sarsıntı yapıyor mu?',
                    ],
                    [
                        'grup' => '3. ELEKTRİK, TİTREŞİM VE OPERATÖR GÜVENLİĞİ',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Açma/Kapama & Acil Stop',
                        'kabul' => 'Açma-kapama anahtarı sağlam, gövde topraklaması bağlı mı?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Operatör Koruyucu Gözlük Kullanımı',
                        'kabul' => 'Operatörün gözlük/siperlik taktığı, eldivensiz olduğu teyit edildi mi?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx, TLT-SPK-01) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Spiral Hava Kanalı Makinesi',
                'baslik' => 'SPİRAL HAVA KANALI MAKİNESİ (DFC MACHINERY) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-SPK-01',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. EMNİYET VE ACİL DURDURMA DONANIMLARI',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Acil Stop (E-Stop) Butonu',
                        'kabul' => 'Panel altındaki acil stop basıldığında motor ve hareket derhal duruyor mu?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Dokunmatik Ekran & Butonlar',
                        'kabul' => 'HMI ekran, \'Açı İleri/Geri\' ve \'İleri/Geri\' kontrol butonları sağlam mı?',
                    ],
                    [
                        'no' => '1.3',
                        'soru' => 'Kesici Bıçak/Testere Muhafazası',
                        'kabul' => 'Boru boy kesme ünitesi koruma kapağı yerinde ve kilitli mi?',
                    ],
                    [
                        'grup' => '2. RULO BESLEME, MAKARA VE KESİCİ KAFA',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Giriş Baskı Merdaneleri',
                        'kabul' => 'Rulo sac giriş kılavuz merdanelerinde boşluksuzluk ve paralellik tam mı?',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Kenetleme Makaraları Durumu',
                        'kabul' => 'Kenet ezme makaralarında çatlak, aşırı aşınma veya çapak birikmesi yok mu?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Çıkış Sehpası ve Destek Ruloları',
                        'kabul' => 'Üretilen boruyu taşıyan boru sehpası sağlam ve ekseninde mi?',
                    ],
                    [
                        'grup' => '3. ELEKTRİK, PANO VE ÇEVRE GÜVENLİĞİ',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Kablo Hatları ve Kumanda Panosu',
                        'kabul' => 'Pano kapağı kilitli, açıkta ezilmiş/yıpranmış kablo yok mu?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Operatör Kesilmez Eldiven & KKD',
                        'kabul' => 'Operatör sac taşırken EN 388 Seviye 4/5 eldiven ve kulaklık takıyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx, TLT-KRD-02) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Kordon Tezgâhı (Motorlu Pedallı)',
                'baslik' => 'MOTORLU PEDALLI KORDON TEZGÂHI — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-KRD-02',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. DİŞLİ VE KAYIŞ-KASNAK MUHAFAZALARI',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Açık Dişli Muhafaza Kapağı',
                        'kabul' => 'Arka büyük dişli grubu üzerindeki koruma sacı takılı ve kapalı mı? (Kritik DÖF)',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'V-Kayış ve Kasnak Koruması',
                        'kabul' => 'Alt tahrik kasnağı ve kayış grubu tamamen kapalı muhafaza içinde mi?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Ayak Pedalı & Yay Fonksiyonu',
                        'kabul' => 'Pedala basıldığında dönüyor, ayak bırakıldığında mil derhal duruyor mu?',
                    ],
                    [
                        'grup' => '2. AYAK PEDALI VE KORDON MAKARALARI',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Kordon Topları / Makaralar',
                        'kabul' => 'Makaralarda çatlak, kırık, salgı veya aşırı aşınma yok mu?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Merdane Baskı / Ayar Kolu',
                        'kabul' => 'Üst mil sıkma kolu ve ayar cıvatası boşluksuz sıkıyor mu?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Gövde / Şalter Sağlamlığı',
                        'kabul' => 'Açma/kapama pako şalteri sağlam, şase topraklaması bağlı mı?',
                    ],
                    [
                        'grup' => '3. ŞALTER, GÖVDE VE OPERATÖR EMNİYETİ',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Zemin Sabitlemesi & Yağ Kaçağı',
                        'kabul' => 'Gövde flanşı zemine sabit, redüktörde yağ sızıntısı yok mu?',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Operatör Kıyafet & Eldiven Uyumu',
                        'kabul' => 'Operatörün döner makara başında kumaş eldivensiz ve takısız olduğu teyit edildi mi?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx, TLT-SMV-03) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Sac Silindir Büküm Makinesi (Vals)',
                'baslik' => 'MOTORLU SAC SİLİNDİR BÜKÜM MAKİNESİ (VALS) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-SMV-03',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. AÇIK DİŞLİ MUHAFAZALARI VE EMNİYET TELİ',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Yan Tahrik Dişli Muhafazası',
                        'kabul' => 'Sağdaki açık dişli grubu sac koruyucu kapakla kapatılmış mı? (Kritik DÖF)',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Acil Durdurma Halatı / Butonu',
                        'kabul' => 'Merdane boyu uzanan acil stop teli çekildiğinde dönüş derhal duruyor mu?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Açılır Üst Mil Yatak Kilidi',
                        'kabul' => 'Boru tahliyesinde açılan üst mil mafsalı sıkıca kilitleniyor mu?',
                    ],
                    [
                        'grup' => '2. SİLİNDİR TOPLARI VE AÇILIR MİL KİLİDİ',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Merdane Yüzeyleri & Paralellik',
                        'kabul' => '3 silindir yüzeyinde çatlak, eğrilik yok ve miller birbirine paralel mi?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Top Ayar Milleri / Boşluk',
                        'kabul' => 'Baskı silindiri ayar kolları rahat dönüyor ve ayarı koruyor mu?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Motor & V-Kayış Muhafazası',
                        'kabul' => 'Sol taraftaki motor/kayış koruma kapağı sağlam ve monteli mi?',
                    ],
                    [
                        'grup' => '3. MOTOR, REDÜKTÖR VE ÇEVRESEL İSG',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Topraklama & Kaçak Akım',
                        'kabul' => 'Makine şase topraklaması bağlı ve gevşeklik yok mu?',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Operatör Güvenlik Mesafesi',
                        'kabul' => 'Operatör sac sürerken ellerini merdane ağzından uzakta tutuyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // Kullanıcının kendi formu (Havalandirma_Kanal_ve_Sac_Isleme_Makineleri_ISG_Talimat_ve_Kontrol_Formu.docx, TLT-PLZ-04) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'CNC Plazma Sac Kesim Tezgâhı',
                'baslik' => 'CNC PLAZMA SAC KESİM TEZGÂHI & ÇAPAK MASASI — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-PLZ-04',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. IZGARA TABLASI, DÜZEN VE ÇAPAK MASASI',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Izgara Tablası Sağlamlığı',
                        'kabul' => 'Sac taşıyıcı lama ızgaralar düzgün, yanık deformasyonu kritik seviyede değil mi?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'İstif Düzeni ve Malzeme Dağınıklığı',
                        'kabul' => 'Masadaki dirsek sacları güvenli istiflenmiş, düşme tehlikesi giderilmiş mi?',
                    ],
                    [
                        'no' => '1.3',
                        'soru' => 'El Aletleri Durumu (Çekiç/Levye)',
                        'kabul' => 'Masa üzerindeki çapak kırma çekicinde gevşek sap veya çatlak kafa yok mu?',
                    ],
                    [
                        'grup' => '2. BASINÇLI GAZ TÜPÜ VE YANGIN EMNİYETİ',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Basınçlı Gaz Tüpü Sabitleme Zinciri',
                        'kabul' => 'Gaz tüpü dik vaziyette, devrilmeye karşı zincirle bağlı mı? (Kritik DÖF)',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Tüp Manometre ve Hortum Kaçak',
                        'kabul' => 'Regülatör saatleri sağlam, hortumlarda çatlak veya kaçak yok mu?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Duman Tahliye / Emiş Fanı',
                        'kabul' => 'Plazma dumanını çeken aspiratör ve baca kanalı aktif mi?',
                    ],
                    [
                        'grup' => '3. DUMAN EMİŞ, ELEKTRİK VE KORUYUCU DONANIM',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Kablo Kanalları ve Topraklama',
                        'kabul' => 'Masa gövde topraklaması tam, zemin kablo dağınıklığı giderilmiş mi?',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Operatör Kesilmez Eldiven & Gözlük',
                        'kabul' => 'Parça toplayan personel Seviye 5 kesilmez eldiven ve gözlük takıyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],

            // ---- Atölye / ekipman formları (13.10.2026) — AI taslağı, kullanıcı 14.10.2026'da ONAYLADI
            // Sorular kütüphanedeki ekipman risk maddeleriyle aynı
            // tehlikeleri kapsayacak şekilde hazırlandı. Kullanıcının kendi formu gelince
            // aynı 'ad'lı kayıt onunla değiştirilir (CNC, Torna gibi).
            ['ad' => 'Sütunlu Matkap', 'sorular' => [
                'Mandren koruyucusu yerinde ve sağlam mı?',
                'Mandren anahtarı çalıştırmadan önce mandrenden alındı mı?',
                'Stop/acil durdurma butonu erişilebilir ve çalışıyor mu?',
                'Kayış-kasnak kapağı kapalı ve sabit mi?',
                'Tabla ve yükseklik kilitleri sağlam, tabla sabitleniyor mu?',
                'İş parçası mengene veya bağlama aparatıyla sabitleniyor, elle tutulmuyor mu?',
                'Matkap ucu keskin, çatlak/eğik değil, devir malzemeye uygun mu?',
                'İlerleme kolu ve geri dönüş yayı düzgün çalışıyor mu?',
                'Makine zemine/tezgâha sabit, sallanma ve anormal ses yok mu?',
                'Kablo, fiş, şalter ve topraklama sağlam; hortum/kablo dönen kısma yakın değil mi?',
                'Talaş fırçayla temizleniyor, çevre düzenli mi?',
                'Operatör göz koruyucu kullanıyor; eldiven, bol giysi ve takı yok, saç toplanmış mı?',
            ]],
            ['ad' => 'Kesme Tezgâhı (Disk / Testere)', 'sorular' => [
                'Disk/bıçak sağlam; çatlak, eksik diş veya aşınma yok, devri makineye uygun mu?',
                'Disk/bıçak koruyucusu yerinde, yalnız kesim bölgesi açık mı?',
                'Acil durdurma butonu erişilebilir ve çalışıyor mu?',
                'İş parçası mengeneyle sabitleniyor, elle tutularak kesilmiyor mu?',
                'Uzun malzeme için destek sehpası veya makaralı ayak kullanılıyor mu?',
                'Kesici durmadan parçaya uzanılmıyor, kesim sonrası kesici tam duruyor mu?',
                'Tezgâh/sehpa sabit, kaymıyor mu?',
                'Kıvılcım alanında yanıcı malzeme ve gaz tüpü bulunmuyor mu?',
                'Besleme kablosu sağlam, keskin malzemeden uzak, topraklama uygun mu?',
                'Disk/bıçak değişimi enerji kesilip fiş çekilerek yapılıyor mu?',
                'Sıcak kesim artıkları ve talaş güvenli şekilde toplanıyor mu?',
                'Operatör yüz/göz koruyucu ve kulak koruyucu kullanıyor mu?',
            ]],
            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-BZM-02) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Taşlama / Bant Zımpara Makinesi',
                'baslik' => 'SÜTUNLU ZIMPARA MAKİNESİ (BANT ZIMPARA) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-BZM-02',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. BANT DURUMU VE GERGİ SİSTEMİ',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Bant Yüzeyi ve Ek Yeri',
                        'kabul' => 'Zımpara bandında çatlak, yıpranma veya ek açılması yok mu?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Bant Gerginliği & Merkezleme',
                        'kabul' => 'Bant kasnak üzerinde düzgün hizalı ve yeterince gergin mi?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Acil Stop Butonu / Pedalı',
                        'kabul' => 'Butona basıldığında motor derhal ve duraksamadan duruyor mu?',
                    ],
                    [
                        'grup' => '2. EMNİYET SİPERLİKLERİ VE ACİL STOP',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Kıvılcım & Koruma Siperliği',
                        'kabul' => 'Şeffaf koruyucu siperlik sağlam, ayarlanmış ve temiz mi?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Dayama Sehpası Açıklığı',
                        'kabul' => 'Dayama tablası ile bant arası azami 2-3 mm boşlukta mı?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Toz Emiş & Siklon Çekişi',
                        'kabul' => 'Toz emiş motoru ve boruları tıkanmasız çekiyor mu?',
                    ],
                    [
                        'grup' => '3. ELEKTRİK, TOZ EMİŞ VE ÇEVRESEL İSG',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Gövde Topraklaması',
                        'kabul' => 'Makine gövde koruma topraklaması sağlam mı?',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Operatör KKD & Eldiven Uyumu',
                        'kabul' => 'Operatör eldivensiz, yüz siperliği ve maskesi takılı mı?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],
            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-EKS-04) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Ekstrüzyon Hattı',
                'baslik' => 'PLASTİK EKSTRÜZYON HATTI (EKSTRUDER) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-EKS-04',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. KOVAN, REZİSTANS VE SICAKLIK KONTROLÜ',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Rezistans Koruma Sacları',
                        'kabul' => 'Isıtıcı rezistansların dış sac muhafazaları tam ve yalıtımlı mı?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Termokupl & Sıcaklık Göstergeleri',
                        'kabul' => 'Bölgelerin sıcaklık kontrol cihazları düzgün ölçüyor mu?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Bunker Güvenlik Izgarası',
                        'kabul' => 'Hammadde hunisinin iç ızgarası takılı ve sabit mi?',
                    ],
                    [
                        'grup' => '2. BUNKER VE MEKANİK TAHRİK AKSAMI',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Acil Stop Butonları',
                        'kabul' => 'Bunker yanında ve kontrol panosunda acil stop butonu çalışıyor mu?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Soğutma Suyu Banyosu & Kaçak',
                        'kabul' => 'Su sirkülasyonu açık, zemin ve kablolar üzerine su sızıntısı yok mu?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Lokal Egzoz & Havalandırma',
                        'kabul' => 'Kafa bölgesindeki duman tahliye davlumbazı aktif mi?',
                    ],
                    [
                        'grup' => '3. ELEKTRİK, SU SOĞUTMA VE HAVALANDIRMA',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Elektrik Panosu & Topraklama',
                        'kabul' => 'Pano kapağı kilitli, kablo bağlantıları yanmaz ve sağlam mı?',
                    ],
                    [
                        'no' => '3.4',
                        'soru' => 'Isıya Dayanıklı KKD Kullanımı',
                        'kabul' => 'Operatör kafa ayarlarında EN 407 ısı eldiveni ve siperlik takıyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],
            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-KIR-06) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Plastik Kırıcı / Granülatör',
                'baslik' => 'PLASTİK KIRMA MAKİNESİ (GRANÜL KRICI) — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-KIR-06',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. KAPAK EMNİYET SWITCHLERİ VE ACİL DURUŞ',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Bunker Emniyet Switchi (Limit Switch)',
                        'kabul' => 'Bunker kapağı açıldığında motor anında duruyor mu (köprüleme yok)?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Acil Stop Butonu',
                        'kabul' => 'Buton mekanik olarak sağlam ve basıldığında anında duruş veriyor mu?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Kayış - Kasnak Muhafaza Sacı',
                        'kabul' => 'Yan tahrik volan muhafazası tam kapalı ve cıvatalı mı?',
                    ],
                    [
                        'grup' => '2. BIÇAK HAZNESİ VE MEKANİK MUHAFAZALAR',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Geri Fırlama Önleyici Perdeler',
                        'kabul' => 'Bunker girişindeki kauçuk yönlendirme perdeleri sağlam mı?',
                    ],
                    [
                        'no' => '2.3',
                        'soru' => 'Bıçak ve Rotor Sıkılığı',
                        'kabul' => 'Bıçak cıvataları sıkı, haznede gevşeklik veya sürtme sesi var mı?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Alt Malzeme Çekmecesi Switchi',
                        'kabul' => 'Çekmece açıldığında makinenin çalışmasını engelleyen switch devrede mi?',
                    ],
                    [
                        'grup' => '3. ELEKTRİK, ÇEKMECE VE İŞ GÜVENLİĞİ',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Gövde Topraklaması & Titreşim',
                        'kabul' => 'Gövde topraklaması sağlam, aşırı sarsıntı/vibrasyon yok mu?',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Kulaklık ve Yüz Siperi Kullanımı',
                        'kabul' => 'Operatör yüksek sese karşı kulaklık ve yüz siperi takıyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],
            // Kullanıcının kendi formu (Atolye_Is_Ekipmanlari_Guvenli_Calisma_Talimati_ve_Kontrol_Formlari.docx, TLT-SEZ-03) — BİREBİR;
            // gruplu, ✓/X/- kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Merdaneli Makine',
                'baslik' => 'PLASTİK ÇEKME / EZME MERDANESİ — GÜNLÜK / VARDİYALI KONTROL VE DENETİM FORMU',
                'form_no' => 'TLT-SEZ-03',
                'kriterler' => [
                    '✓' => 'Uygun',
                    'X' => 'Uygun Değil (DÖF / Müdahale Gerekli)',
                    '-' => 'Uygulanamaz',
                ],
                'satirlar' => [
                    [
                        'grup' => '1. MERDANE MEKANİK VE BASKI SİSTEMİ',
                    ],
                    [
                        'no' => '1.1',
                        'soru' => 'Silindir Yüzeyleri & Temizlik',
                        'kabul' => 'Merdanelerde çizik, deformasyon veya yapışmış malzeme yok mu?',
                    ],
                    [
                        'no' => '1.2',
                        'soru' => 'Baskı ve Boşluk Ayarı',
                        'kabul' => 'İki silindir arası paralel ve baskı mekanizması düzgün mü?',
                    ],
                    [
                        'no' => '2.1',
                        'soru' => 'Acil Stop Butonu & Emniyet Teli',
                        'kabul' => 'Merdane girişindeki acil tel/bar çekildiğinde hareket anında duruyor mu?',
                    ],
                    [
                        'grup' => '2. ACİL DURDURMA VE EMNİYET BARLARI',
                    ],
                    [
                        'no' => '2.2',
                        'soru' => 'Giriş Koruma Parmaklığı/Siper',
                        'kabul' => 'Çekme ağzında dar açıklıklı parmak muhafazası yerinde mi?',
                    ],
                    [
                        'no' => '3.1',
                        'soru' => 'Motor & Redüktör Ses/Isı',
                        'kabul' => 'Redüktörde yağ kaçağı, aşırı ısınma ve anormal titreşim yok mu?',
                    ],
                    [
                        'no' => '3.2',
                        'soru' => 'Kumanda Panosu & Hız Ayarı',
                        'kabul' => 'Hız kontrol potansiyometresi ve acil butonlar sorunsuz mu?',
                    ],
                    [
                        'grup' => '3. MOTOR, REDÜKTÖR VE ELEKTRİK PANOSU',
                    ],
                    [
                        'no' => '3.3',
                        'soru' => 'Topraklama & Kablo Kanalı',
                        'kabul' => 'Motor ve gövde topraklaması tam, kablolar korumalı mı?',
                    ],
                    [
                        'no' => '3.4',
                        'soru' => 'Operatör Güvenliği',
                        'kabul' => 'Operatör dar kıyafetli, takısız ve kılavuz aparat kullanıyor mu?',
                    ],
                ],
                'karar' => [
                    'Ekipman Güvenli (Kullanılabilir)',
                    'KUSURLU / EMNİYETSİZ (KULLANIM DIŞI - EKED TAKILDI)',
                ],
                'imzalar' => [
                    'Kontrolü Yapan Operatör',
                    'Vardiya / Bakım Amiri',
                    'İş Güvenliği Uzmanı (İSG)',
                ],
            ],
            ['ad' => 'Gaz Tüpü ve Şaloma Seti', 'sorular' => [
                'Tüp içeriği etiketten doğrulanmış, renk ve etiket okunaklı mı?',
                'Tüpler dik ve zincir/kelepçeyle devrilmeye karşı sabitlenmiş mi?',
                'Vana koruma başlıkları kullanılmayan tüplerde takılı mı?',
                'Regülatör ve manometreler sağlam, gaza uygun mu?',
                'Hortumlar çatlaksız, gaza uygun renk/tipte ve kelepçeleri sağlam mı?',
                'Alev geri tepme emniyeti (regülatör ve şaloma tarafında) takılı mı?',
                'Kaçak kontrolü köpüklü su ile yapılıyor, alevle aranmıyor mu?',
                'Oksijen donanımında yağ/gres teması yok mu?',
                'Hortum ve tüpler geçiş yolunda değil, ezilme riski yok mu?',
                'Çalışma alanında yanıcı malzeme yok, yangın söndürücü hazır mı?',
                'Boş ve dolu tüpler ayrı, oksijen ve yanıcı gaz tüpleri ayrı depolanıyor mu?',
                'Kapalı alanda kullanılıyorsa havalandırma yeterli mi?',
            ]],
            // Kullanıcının kendi formu (Kompresor_ve_Hava_Tanki_Guvenli_Calisma_Talimati_ve_Gunluk_Kontrol_Formu.docx, İSG-FR-KMP-01) — BİREBİR;
            // gruplu, U/UD/Y/X kriterli. AI taslağının yerini aldı.
            [
                'ad' => 'Kompresör',
                'baslik' => 'KOMPRESÖR VE HAVA TANKI GÜNLÜK KONTROL FORMU',
                'form_no' => 'İSG-FR-KMP-01',
                'onaylayan' => 'Bakım Sorumlusu / İSG Uzmanı',
                'kriterler' => [
                    'U' => 'Uygun',
                    'UD' => 'Uygun Değil',
                    'Y' => 'Yapıldı / Tahliye Edildi',
                    'X' => 'Uygulanamaz',
                ],
                'gruplar' => [
                    [
                        'baslik' => '1. BASINÇ EMNİYETİ & GÖSTERGELER',
                        'sorular' => [
                            'Hava tankı ve kompresör emniyet ventilleri mühürlü, sağlam ve serbest mi?',
                            'Manometreler okunaklı, hasarsız ve azami işletme basıncı işaretli mi?',
                            'Presostat (basınç şalteri) yük-boş aralığı nominal değerlerde mi?',
                            'Acil Durdurma (Emergency Stop) butonu sağlam ve işlevsel mi?',
                        ],
                    ],
                    [
                        'baslik' => '2. YAĞLAMA, SOĞUTMA & ÇALIŞMA ŞARTLARI',
                        'sorular' => [
                            'Kompresör karter/vida yağ seviyesi gösterge camında normal mi?',
                            'Kompresör kafa/çıkış sıcaklığı normal limitler içinde mi?',
                            'Hava kurutucusu (dryer) devrede ve çiğlenme noktası uygun mu?',
                            'Gövdede veya tabanda anormal yağ kaçağı / sızıntısı var mı?',
                        ],
                    ],
                    [
                        'baslik' => '3. KONDENS (SU) TAHLİYESİ & FİLTRELER',
                        'sorular' => [
                            'Hava tankı alt tahliye vanasından biriken kondens (su/yağ) boşaltıldı mı?',
                            'Otomatik kondens tahliye cihazı (zaman ayarlı/şamandıralı) çalışıyor mu?',
                            'Hava emiş filtreleri temiz ve tıkanıklıktan uzak mı?',
                            'Hava hatları ve şartlandırıcılardaki su tutucular tahliye edildi mi?',
                        ],
                    ],
                    [
                        'baslik' => '4. MEKANİK KORUYUCULAR, HAVA HATLARI & ALAN GÜVENLİĞİ',
                        'sorular' => [
                            'Kayış-kasnak ve kaplin koruyucu kafesleri tam ve sabitlenmiş mi?',
                            'Hava boruları, esnek hortumlar ve rekorlarda çatlak/kaçak var mı?',
                            'Hava hatlarında kırbaçlanmayı önleyici emniyet teli/pimi (whipcheck) takılı mı?',
                            'Kompresör dairesi havalandırması, aydınlatması ve yangın tüpü uygun mu?',
                        ],
                    ],
                ],
            ],
            // Ekipman Değerlendirme Aracı karşılaştırması (19.10.2026) — yeni kontrol formları
            ['ad' => 'Araç Üstü Yüksek Platform (Sepetli Platform / Cherry Picker)', 'sorular' => [
                'Aşırı yük sınırlayıcı ve devrilme momenti uyarısı çalışıyor, alarm veriyor mu?',
                'Acil durdurma butonu kırmızı, çalışır durumda ve yalnız elle sıfırlanıyor mu?',
                'Güç kesildiğinde platformu indiren acil indirme sistemi çalışıyor mu?',
                'Ayaklar (outrigger) ve takozlar sağlam, zemin uygun ve araç yatay dengede mi?',
                'Bom, bağlantı parçaları ve hidrolik silindirlerde çatlak, deformasyon ve kaçak yok mu?',
                'Döner tabla bağlantıları ve (varsa) halat/zincirler sağlam, gevşeklik yok mu?',
                'Platform kapısı içeri açılıp kilitleniyor, korkuluk ve tekme tahtası tam mı?',
                'Platformdaki kişiler yaşam hattına/ankraj noktasına bağlı emniyet kemeri kullanıyor mu?',
                'Lastikler, frenler, farlar ve geri vites alarmı çalışıyor mu?',
                'Elektrik hatlarına güvenli mesafe korunuyor, kablolarda hasar yok mu?',
                'Yük / kişi sayısı ve rüzgâr sınırı levhaları okunaklı mı?',
                'Operatörün eğitim/belgesi var, periyodik muayene etiketi güncel mi?',
            ]],
            ['ad' => 'Gondol (Asılı Çalışma Platformu)', 'sorular' => [
                'Aşırı yük sınırlayıcı çalışıyor, aşırı yükte yükselme duruyor mu?',
                'Üst ve alt limit anahtarları çalışıyor mu?',
                'Acil durdurma butonu kırmızı ve yalnız elle sıfırlanıyor mu?',
                'Ana halat koptuğunda devreye giren emniyet (düşme önleyici) halatı/cihazı çalışıyor mu?',
                'Fren sistemi ve kaldırma motoru sorunsuz, ses ve titreşim normal mi?',
                'Çelik halatlarda tel kopması, ezilme ve korozyon yok, uç bağlantıları sağlam mı?',
                'Askı kolları, karşı ağırlıklar ve bağlantı noktaları projesine uygun, sağlam mı?',
                'Platformda çatlak/korozyon yok; korkuluk ve tekme tahtası tam mı?',
                'Her çalışan ayrı yaşam hattına bağlı emniyet kemeri kullanıyor mu?',
                'Yük kapasitesi ve güvenlik talimatları platformda asılı mı?',
                'Elektrik kabloları ve panosu hasarsız, topraklama yapılmış mı?',
                'Rüzgâr ve hava koşulları kontrol edildi, operatörlerin eğitim/belgesi var mı?',
            ]],
            ['ad' => 'Seyyar Yük Kaldırma Lifti (Sepetli Vinç / Mobilya Asansörü)', 'sorular' => [
                'Aşırı yük sınırlayıcı çalışıyor, alarmla durduruyor mu?',
                'Aşırı çekme/sarma önleyici (en alt ve en üst nokta) çalışıyor mu?',
                'Acil durdurma butonu yalnız elle sıfırlanabiliyor mu?',
                'Ayaklar (outrigger) ve takozlar sağlam, zemin uygun, araç dengede mi?',
                'Halatlarda tel kopması, ezilme ve korozyon yok mu?',
                'Döner tabla bağlantıları ve hidrolik silindir/hortumlar sağlam, kaçak yok mu?',
                'Teleskopik bom hareketleri düzgün, fren ve kavrama normal mi?',
                'Sepet/kafes yan korumaları ve kapısı tam, yük ve emniyet levhaları asılı mı?',
                'Elektrik hatlarına güvenli mesafe korunuyor mu?',
                'Operatör eğitimli ve belgeli, periyodik muayene etiketi güncel mi?',
                'Kaldırma yük tablosu (ağırlık-menzil) ve işaretleme levhaları görünür durumda mı?',
                'Yükü bağlama/çözme elemanları (sapan, zincir) sertifikalı ve hasarsız mı?',
            ]],
            ['ad' => 'Düşmeyi Önleyici Ekipman (Yaşam Hattı / Emniyet Kemeri)', 'sorular' => [
                'Koşum (kemer) kayışlarında yırtık, kesik, aşınma ve kimyasal hasar yok mu?',
                'Tüm dikişler sağlam, sökülme ve kopukluk yok mu?',
                'D-halkası, toka ve ayar parçalarında deformasyon, çatlak ve korozyon yok mu?',
                'Bağlantı halatı (lanyard) ve şok emici hasarsız, kullanım süresi içinde mi?',
                'Kanca ve emniyet mandalı sorunsuz çalışıyor, kendiliğinden kapanıyor mu?',
                'Yaşam hattı (yatay/dikey) ve ankraj noktaları projesine uygun, sağlam mı?',
                'Ankraj noktası düşme yükünü taşıyacak şekilde seçildi mi?',
                'Ekipman etiketi okunur, üretim/son kontrol tarihi ve CE işareti var mı?',
                'Üçüncü taraf muayene belgesi (yıllık) mevcut ve geçerli mi?',
                'Kullanıcı ekipmanı doğru takma konusunda eğitim aldı mı?',
                'Ekipman kullanımdan sonra uygun koşullarda (kuru, kimyasaldan ve keskin kenardan uzak) muhafaza ediliyor mu?',
                'Düşme yaşamış ekipman derhal kullanımdan kaldırılıp incelemeye gönderiliyor mu?',
            ]],
            ['ad' => 'Traktör', 'sorular' => [
                'Motor yağı, soğutma suyu ve hidrolik yağ seviyeleri uygun, kaçak yok mu?',
                'Lastikler, jantlar ve lastik basınçları uygun mu?',
                'Fren ve debriyaj pedalları ile el freni düzgün çalışıyor mu?',
                'Devrilme koruma yapısı (ROPS) ve emniyet kemeri sağlam, kullanılıyor mu?',
                'Farlar, sinyaller, korna ve geri vites alarmı çalışıyor mu?',
                'Kuyruk mili (PTO) ve dönen mil koruyucuları takılı mı?',
                'Bağlantı pimleri, çeki demiri ve hidrolik bağlantılar sağlam mı?',
                'Egzoz, akü ve yakıt sistemi kaçaksız, yangın söndürücü araçta mı?',
                'Eğim, zemin ve çalışma alanı güvenli, yaya/yolcu taşınmıyor mu?',
                'Operatörün ehliyeti/eğitimi var, günlük kontrol kaydı tutuluyor mu?',
                'Yakıt ikmali motor kapalıyken ve yangın riskine karşı önlem alınarak yapılıyor mu?',
                'Arızalı / hasarlı traktör etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
            ['ad' => 'Beton Karıştırıcı (Mikser)', 'sorular' => [
                'Tambur ve dişli/kayış-kasnak aksamında koruyucu kapaklar takılı mı?',
                'Besleme ağzı ve tamburun içine uzanmayı engelleyen koruma var mı?',
                'Acil durdurma ve elektrik panosu çalışır, topraklama yapılmış mı?',
                'Elektrik kabloları ve fişler hasarsız, kaçak akım rölesi çalışıyor mu?',
                'Devirme volanı/kolu ve kilit mekanizması sağlam mı?',
                'Tambur temizliği için motor kapatılıp enerji kesilerek müdahale ediliyor mu?',
                'Tekerlekler/ayaklar sabitlenmiş, makine dengeli ve düz zeminde mi?',
                'Toz ve çimento teması için maske, gözlük ve eldiven kullanılıyor mu?',
                'Çalışma alanında kaygan zemin ve takılma riski giderildi mi?',
                'Makine kullanımı için yetkili ve eğitimli operatör görevlendirildi mi?',
                'Çalışma alanı bariyer ve uyarı levhasıyla sınırlandırılmış mı?',
                'Arızalı makine etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
            ['ad' => 'Konsolidasyon Ekipmanı (Drenaj / Zemin İyileştirme)', 'sorular' => [
                'Aşırı sarma önleyici cihaz çalışıyor, kanca/çekiç üst noktaya gelmeden alarm veriyor mu?',
                'Ters dönme önleyici ve frenler sorunsuz çalışıyor mu?',
                'Halatlarda tel kopması, ezilme ve korozyon yok, uç bağlantıları sağlam mı?',
                'Kılavuz (leader) ve çekiç ağırlığı, yüksekliği ve dikliği talimata uygun mu?',
                'Hidrolik silindir ve hortumlarda kaçak, hasar ve aşınma yok mu?',
                'Palet ve çelik plakalar hasarsız, zemin taşıma kapasitesine uygun mu?',
                'Kum haznesi/kova doğru besleniyor, kova doğru boşaltılıyor mu?',
                'Ana yapı, bağlantı ve kaynak noktalarında deformasyon/çatlak yok mu?',
                'Jeneratör bağlantıları sağlam, aktif kısımlar açıkta değil mi?',
                'Karşı ağırlıklara keyfi müdahale edilmemiş, sabit mi?',
                'Çalışma çemberi bariyer ve uyarı levhalarıyla sınırlandırılmış, yer gözcüsü görevli mi?',
                'Operatör ve yardımcı personel eğitimli, günlük kontrol kaydı tutuluyor mu?',
            ]],
            ['ad' => 'Hidrolik Pres', 'sorular' => [
                'Basınç göstergesi çalışıyor ve emniyet (aşırı basınç) valfi ayarlı mı?',
                'Pres tablası ve silindirde çatlak, deformasyon ve yağ kaçağı yok mu?',
                'Hidrolik hortum, bağlantı ve rakorlar sağlam, kaçak yok mu?',
                'İki elle kumanda / ışık perdesi veya koruyucu çalışıyor, atlatılmamış mı?',
                'Acil durdurma butonu kolay erişilebilir ve çalışır durumda mı?',
                'Parça ve takımlar sabitleniyor, pres kapasitesi aşılmıyor mu?',
                'Bakımda kilitleme-etiketleme ve emniyet kilitleri uygulanıyor mu?',
                'Hidrolik yağ seviyesi ve sıcaklığı normal, yağ sızıntısı yok mu?',
                'Elektrik panosu ve kablolar hasarsız, topraklama yapılmış mı?',
                'Operatör eğitimli ve yetkili, çalışma talimatı asılı mı?',
                'Pres kapasitesi ve kullanım limitleri ekipman üzerinde işaretli ve aşılmıyor mu?',
                'Kalıp/takım değişiminde pres durdurulup enerji kesiliyor mu?',
            ]],
            ['ad' => 'El Matkabı / Darbeli Matkap / Tornavida', 'sorular' => [
                'Gövde ve kapaklar hasarsız, çatlak ve kırık yok mu?',
                'Yan tutamak/sap sağlam ve sıkı mı?',
                'Matkap ucu/mandren doğru sıkılmış, uç hasarsız mı?',
                'Güç anahtarı sorunsuz çalışıyor, bırakınca duruyor mu?',
                'Kablo ve fiş hasarsız; fişin topraklaması (veya çift yalıtım işareti) var mı?',
                'Aletin CE işareti ve kalite belgesi mevcut mu?',
                'Yalıtım direnci periyodik ölçülmüş, etiketi güncel mi?',
                'Kullanıcı gözlük ve gerekirse eldiven, kulaklık kullanıyor mu?',
                'Delinecek parça sabitleniyor, duvar/zemin altında kablo-boru taraması yapıldı mı?',
                'Güvenli kullanım talimatı mevcut ve kullanıcılar bilgilendirilmiş mi?',
                'Hasarlı/arızalı alet etiketlenip kullanımdan kaldırılıyor mu?',
                'Kullanım sonrası enerji kesilip alet güvenli yerde saklanıyor mu?',
            ]],
            ['ad' => 'Avuç Taşlama (Spiral)', 'sorular' => [
                'Koruyucu kapak takılı, hasarsız ve kullanıcıya göre doğru konumda mı?',
                'Disk hasarsız, çatlak/kırık yok; disk çevre hızı alete uygun mu?',
                'Disk doğru takılmış, somun sıkılmış ve disk sabit mi?',
                'Yan tutamak sağlam takılı, ölü adam (basılı tutma) anahtarı çalışıyor mu?',
                'Gövde ve havalandırma bölümleri hasarsız mı?',
                'Kablo ve fiş hasarsız, topraklama/çift yalıtım uygun mu?',
                'Alet CE işaretli ve kalite belgeli mi?',
                'Yalıtım direnci periyodik ölçülmüş mü?',
                'Yüz siperi/gözlük, eldiven ve kulaklık kullanılıyor mu?',
                'Kıvılcım yönünde yanıcı malzeme yok, yangın söndürücü yakında mı?',
                'Disk değişimi enerji kesilerek yapılıyor ve disk son kullanma tarihi kontrol ediliyor mu?',
                'Aletin güvenli kullanım talimatı mevcut, kullanıcı eğitimli mi?',
            ]],
            ['ad' => 'Dekupaj / El Tipi Daire Testere', 'sorular' => [
                'Testere bıçağı/lama hasarsız, keskin ve doğru takılmış mı?',
                'Bıçak koruyucusu (alt siperlik) serbestçe hareket edip kapanıyor mu?',
                'Bıçak sabitleme cıvatası/kilidi sağlam mı?',
                'Güç anahtarı sorunsuz, bırakınca motor duruyor mu?',
                'Kablo ve fiş hasarsız, topraklama/çift yalıtım uygun mu?',
                'Alet CE işaretli ve kalite belgeli mi?',
                'Kesilecek parça mengene/kıskaçla sabitlendi mi?',
                'Gözlük, kulaklık ve toz maskesi kullanılıyor mu?',
                'Talaş ve tozun uzaklaştırılması için emiş/havalandırma var mı?',
                'Kullanım sonrası enerji kesilip bıçak korunarak saklanıyor mu?',
                'Güvenli kullanım talimatı mevcut ve kullanıcılar eğitimli mi?',
                'Hasarlı alet etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
            ['ad' => 'Kırıcı / Delici (Jack Hammer)', 'sorular' => [
                'Gövde, tutamaklar ve titreşim emici parçalar hasarsız mı?',
                'Uç/keski doğru takılmış, aşınmamış ve tespit mekanizması sağlam mı?',
                'Hava/elektrik bağlantıları (hortum, kablo, fiş) hasarsız mı; hortum kelepçeleri sağlam mı?',
                'Ölü adam (basılı tutma) anahtarı çalışıyor mu?',
                'Çalışma alanında gizli kablo, boru ve gaz hattı taraması yapıldı mı?',
                'Kırılacak yüzey altındaki alan ve çevre bariyerle korunmuş mu?',
                'Kulaklık, gözlük/yüz siperi, toz maskesi, eldiven ve çelik burunlu ayakkabı kullanılıyor mu?',
                'Kullanıcı titreşim süresine göre rotasyona alınıyor mu?',
                'Toz bastırma için su/emiş uygulanıyor mu?',
                'Havalı kırıcıda kompresör basıncı ve hortum bağlantı emniyetleri (kelepçe/emniyet zinciri) uygun mu?',
                'Güvenli kullanım talimatı mevcut, kullanıcı eğitimli mi?',
                'Hasarlı/arızalı alet etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
            ['ad' => 'Ark Kaynak / Tig / Plazma Kesme Makinesi', 'sorular' => [
                'Makine gövdesinde hasar ve çatlak yok, kapaklar kapalı mı?',
                'Otomatik elektrik koruma (şalter/sigorta) kurulu ve çalışıyor mu?',
                'Gösterge, ayar düğmeleri ve anahtarlar sorunsuz çalışıyor mu?',
                'Besleme ve kaynak kabloları hasarsız; plastik yalıtımlı standart dışı kablo kullanılmıyor mu?',
                'Fiş ve güç bağlantı terminalleri (+/–) sağlam ve sıkı mı?',
                'Topraklama kelepçesi ve yay hasarsız, topraklama yapılmış mı?',
                'TIG için argon hortumu, torç ve torç anahtarı hasarsız mı?',
                'Alet CE işaretli ve kalite belgeli mi?',
                'Yalıtım direnci periyodik ölçülmüş mü?',
                'Yangın söndürücü yakın ve dolu, yangın battaniyesi mevcut mu?',
                'Kaynak maskesi, eldiven, önlük ve uygun koruyucu giysi kullanılıyor mu?',
                'Kaynak dumanı için lokal emiş/havalandırma var, kaynak perdesi kurulu mu?',
            ]],
            ['ad' => 'Isı Makineleri (Sıcak Hava Tabancası / Havya / PVC Kaynak)', 'sorular' => [
                'Gövde ve ısıtıcı uç hasarsız, çatlak yok mu?',
                'Uç/nozul sabitlemesi sağlam mı?',
                'Güç anahtarı ve sıcaklık ayarı sorunsuz çalışıyor mu?',
                'Kablo ve fiş hasarsız, topraklama/çift yalıtım uygun mu?',
                'Aletin CE işareti ve kalite belgesi mevcut mu?',
                'Yalıtım direnci periyodik ölçülmüş mü?',
                'Kullanılmadığı zamanlarda ısıtıcı uç güvenli standında bırakılıyor mu?',
                'Yanıcı malzeme çalışma alanından uzak, yangın söndürücü yakın mı?',
                'Alet kullanım sonrası soğumaya bırakılıyor, sıcakken saklanmıyor mu?',
                'Çalışma alanı havalandırması yeterli, duman/buhar maruziyeti kontrol altında mı?',
                'Güvenli kullanım talimatı mevcut, kullanıcı eğitimli mi?',
                'Hasarlı/arızalı alet etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
            ['ad' => 'Uzatma Kablosu / Kablo Makarası', 'sorular' => [
                'Kablo kılıfı ve makara gövdesi hasarsız, kesik/ezik ve açıkta iletken yok mu?',
                'Fiş ve priz hasarsız, topraklı ve gevşek değil mi?',
                'Kablo kesiti ve uzunluğu bağlanan cihazın gücüne uygun mu?',
                'Makara tamamen açılarak kullanılıyor (sargıda ısınma yok) mu?',
                'Makarada kaçak akım rölesi ve termik koruma kurulu mu?',
                'Kablo standart yalıtımlı (plastik yalıtımlı standart dışı kablo kullanılmıyor) mu?',
                'Yalıtım direnci periyodik ölçülmüş, etiketi güncel mi?',
                'Kablo yaya yolu ve araç geçişinden korunmuş, ıslak zeminde değil mi?',
                'Kabloda ek yeri veya bantlama yok, kablo tek parça mı?',
                'Kablo uzunluğu ihtiyaçtan fazla değil, kabloların birbirine zincirleme eklenmesi yapılmıyor mu?',
                'Dış mekân kullanımında su/toza karşı korumalı (IP) tip kullanılıyor mu?',
                'Hasarlı kablo etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
            ['ad' => 'Seyyar Transformatör ve Vantilatör', 'sorular' => [
                'Gövde ve havalandırma delikleri hasarsız, tıkalı değil mi?',
                'Kablo ve bağlantılar hasarsız, plastik yalıtımlı standart dışı kablo kullanılmıyor mu?',
                'Fiş ve topraklama (veya çift yalıtım) uygun mu?',
                'Transformatör nominal giriş/çıkış gerilimi doğru ayarlanmış mı?',
                'Aletin CE işareti ve kalite belgesi mevcut mu?',
                'Vantilatör pervane koruma ağı sağlam ve takılı mı?',
                'Yalıtım direnci periyodik ölçülmüş, etiketi güncel mi?',
                'Cihaz ıslak/ortamdan korunmuş, yanıcı malzemeden uzakta mı?',
                'Cihaz sabit ve devrilmeyecek şekilde yerleştirilmiş mi?',
                'Çalışma sonrası enerji kesiliyor, cihaz uygun yerde saklanıyor mu?',
                'Kullanıcılar cihazın güvenli kullanımı konusunda bilgilendirilmiş mi?',
                'Hasarlı cihaz etiketlenip kullanımdan kaldırılıyor mu?',
            ]],
        ],
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
        /*
         | 'hazir' = mehse'de bu kriteri hesaplayacak gerçek modül şu an var mı?
         | Yoksa 0/N döner; ilgili modül kurulunca `App\Support\PortfoyKarne`
         | gerçek sayıma geçer. (Not: eskiden 'manuel_anahtar' ile Profilim >
         | OSGB Takip'ten Excel'le elle işaretleme mümkündü — kullanıcı isteğiyle
         | 05.09.2026'da tamamen kaldırıldı, MIMARI.md'de not var.)
        */
        /*
         | 'takip' => true → olay/ihtiyaç bazlı kayıt (iş kazası, meslek hastalığı,
         |   ramak kala, iş izni...): her işyerinde olmak zorunda değil — sayılır,
         |   gösterilir ama "eksik" sayılmaz, uyum yüzdesine girmez. Varsayılandır;
         |   kullanıcı Ayarlar > Kontrol Merkezi'nde kriter başına değiştirebilir.
         | 'kosul' => 'gecen_yil_atali' → firma önceki yıldan beri atalıysa
         |   (sözleşme başlangıcı bu yıldan önce; tarih boşsa kapsamda sayılır).
         | 'kosul' => 'kaza_var' → firmada iş kazası kaydı varsa; kaza sonrası
         |   (son kaza tarihinden sonra) kayıt aranır.
        */
        'kriterler' => [
            ['anahtar' => 'risk_degerlendirmesi', 'ad' => 'Risk Değerlendirmesi', 'ikon' => 'heroicon-o-sparkles', 'hazir' => true, 'kategori' => 'Risk Değerlendirmesi'],
            ['anahtar' => 'yillik_calisma_plani', 'ad' => 'Yıllık Çalışma Planı', 'ikon' => 'heroicon-o-calendar-days', 'hazir' => true, 'kategori' => 'Planlama & Arşiv'],
            ['anahtar' => 'acil_durum_plani', 'ad' => 'Acil Durum Planı Belgesi (ADP)', 'ikon' => 'heroicon-o-exclamation-triangle', 'hazir' => true, 'kategori' => 'Acil Durum & Yangın'],
            ['anahtar' => 'acil_durum_destek', 'ad' => 'Acil Durum Destek Elemanları', 'ikon' => 'heroicon-o-user-group', 'hazir' => true, 'kategori' => 'Acil Durum & Yangın'],
            ['anahtar' => 'acil_durum_tatbikat', 'ad' => 'Acil Durum Tatbikat Tutanağı', 'ikon' => 'heroicon-o-fire', 'hazir' => true, 'kategori' => 'Acil Durum & Yangın'],
            ['anahtar' => 'isg_kurulu', 'ad' => 'İSG Kurulu Toplantısı', 'ikon' => 'heroicon-o-users', 'hazir' => true, 'kosul' => 'elli_calisan', 'kategori' => 'Çalışan & Kurul'],
            ['anahtar' => 'yillik_egitim_plani', 'ad' => 'Yıllık Eğitim Planı', 'ikon' => 'heroicon-o-academic-cap', 'hazir' => true, 'kategori' => 'Planlama & Arşiv'],
            ['anahtar' => 'yillik_degerlendirme', 'ad' => 'Yıllık Değerlendirme Raporu', 'ikon' => 'heroicon-o-document-chart-bar', 'hazir' => true, 'kosul' => 'gecen_yil_atali', 'kategori' => 'Planlama & Arşiv'],
            ['anahtar' => 'calisan_temsilcisi', 'ad' => 'Çalışan Temsilcisi Görevlendirmesi', 'ikon' => 'heroicon-o-identification', 'hazir' => true, 'kategori' => 'Çalışan & Kurul'],
            ['anahtar' => 'igu_atamasi', 'ad' => 'İş Güvenliği Uzmanı (İGU) Ataması', 'ikon' => 'heroicon-o-shield-check', 'hazir' => true, 'kategori' => 'Yönetim'],
            ['anahtar' => 'hekim_atamasi', 'ad' => 'İşyeri Hekimi Ataması', 'ikon' => 'heroicon-o-heart', 'hazir' => true, 'kategori' => 'Yönetim'],
            ['anahtar' => 'tespit_oneri', 'ad' => 'Tespit ve Öneri Defteri Kaydı', 'ikon' => 'heroicon-o-book-open', 'hazir' => true, 'kategori' => 'Saha Kontrolleri'],
            ['anahtar' => 'egitim_katilim_formu', 'ad' => 'Temel İSG Eğitimi (Katılım Formu)', 'ikon' => 'heroicon-o-clipboard-document-check', 'hazir' => true, 'kategori' => 'Eğitimler'],
            ['anahtar' => 'ise_donus_muayenesi', 'ad' => 'İşe Dönüş Muayenesi (kaza sonrası)', 'ikon' => 'heroicon-o-arrow-uturn-left', 'hazir' => true, 'kosul' => 'kaza_var', 'kategori' => 'İş Kazaları & Olaylar'],
            ['anahtar' => 'kaza_sonrasi_isbasi_egitimi', 'ad' => 'İşbaşı Eğitimi (kaza sonrası)', 'ikon' => 'heroicon-o-academic-cap', 'hazir' => true, 'kosul' => 'kaza_var', 'kategori' => 'İş Kazaları & Olaylar'],
            ['anahtar' => 'periyodik_kontrol_raporu', 'ad' => 'Periyodik Kontrol Raporu', 'ikon' => 'heroicon-o-wrench-screwdriver', 'hazir' => true, 'kosul' => 'ekipman_var', 'kategori' => 'Periyodik Kontrol & Ölçüm'],
            ['anahtar' => 'calisma_izin_formu', 'ad' => 'Çalışma İzin Formu', 'ikon' => 'heroicon-o-document-check', 'hazir' => true, 'takip' => true, 'kategori' => 'Diğer Belge & Yazışma'],
            ['anahtar' => 'saha_denetim_formu', 'ad' => 'Saha Denetim Formu', 'ikon' => 'heroicon-o-clipboard-document-list', 'hazir' => true, 'takip' => true, 'kategori' => 'Saha Kontrolleri'],
            ['anahtar' => 'is_kazasi_bildirimi', 'ad' => 'İş Kazası Bildirimi', 'ikon' => 'heroicon-o-exclamation-circle', 'hazir' => true, 'takip' => true, 'kategori' => 'İş Kazaları & Olaylar'],
            ['anahtar' => 'olay_ramak_kala_kaydi', 'ad' => 'Olay / Ramak Kala Kaydı', 'ikon' => 'heroicon-o-bell-alert', 'hazir' => true, 'takip' => true, 'kategori' => 'İş Kazaları & Olaylar'],
            ['anahtar' => 'meslek_hastaligi_bildirimi', 'ad' => 'Meslek Hastalığı Bildirimi', 'ikon' => 'heroicon-o-heart', 'hazir' => true, 'takip' => true, 'kategori' => 'Sağlık Gözetimi'],
            ['anahtar' => 'saglik_raporu', 'ad' => 'Sağlık Raporu', 'ikon' => 'heroicon-o-document-text', 'hazir' => true, 'takip' => true, 'kategori' => 'Sağlık Gözetimi'],
            ['anahtar' => 'onayli_defter_nushalari', 'ad' => 'Onaylı Defter Nüshaları', 'ikon' => 'heroicon-o-book-open', 'hazir' => true, 'kategori' => 'Planlama & Arşiv'],
            ['anahtar' => 'diger_evrak', 'ad' => 'Diğer', 'ikon' => 'heroicon-o-document', 'hazir' => false, 'kategori' => 'Diğer Belge & Yazışma'],
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
            ['ad' => 'Toplu Atama Sözleşmesi İndir', 'osgb' => false, 'yeni' => false, 'hazir' => true,
                'aciklama' => 'İSG-KATİP\'in kendi "Tümünü Dışa aktar" Excel\'ini yükleyin; uzman/hekim bazında aktif sözleşmeleri ve süre durumunu mehse\'de görün.'],
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

        // "Risk, tedbir ve uygulama" kutularının hazır örnek metinleri (Md.9 / Md.12).
        // Boş kutu ekranda bununla dolu gelir; uzman firmaya göre düzeltip Kaydet'e basar.
        'ornek_metinler' => [
            'onleyici_tedbirler' => "- Yanıcı ve parlayıcı maddeler (boya, tiner, yağ, gaz tüpleri) kapalı ve havalandırmalı alanda, ısı kaynaklarından uzakta, etiketli kaplarda depolanır.\n"
                ."- Kaynak, taşlama, kesme gibi sıcak işler iş izni ile yapılır; çevredeki yanıcı malzeme kaldırılır, yanında yangın söndürücü bulundurulur.\n"
                ."- Elektrik panoları kapalı/kilitli tutulur, önleri boş bırakılır; elektrik tesisatı ve topraklama yılda bir yetkili kişiye kontrol ettirilir.\n"
                ."- Yangın söndürücüler 6 ayda bir, yangın tesisatı ve alarm sistemi yılda bir periyodik kontrolden geçirilir.\n"
                ."- Kaldırma ekipmanları, basınçlı kaplar ve diğer iş ekipmanları periyodik kontrolden geçirilir; makine koruyucuları ve acil stop butonları çalışır durumda tutulur.\n"
                ."- Kaçış yolları, acil çıkış kapıları ve toplanma alanı sürekli açık ve işaretli tutulur; acil çıkış kapıları dışa açılır ve kilitlenmez.\n"
                ."- Belirlenen alanlar dışında sigara içilmez, açık ateş kullanılmaz.\n"
                ."- Yanıcı atıklar (yağlı bez, talaş, ambalaj) her gün kapaklı metal kaplarda toplanıp uzaklaştırılır.\n"
                ."- Raf, dolap ve ağır eşyalar devrilmeye karşı sabitlenir (deprem tedbiri).\n"
                ."- Elektrik ana şalteri ve doğalgaz/LPG vanası işaretlidir; yeri ve kapatılması ekip üyelerine gösterilmiştir.\n"
                .'- Acil durum ekiplerine eğitim verilir, yılda en az bir tatbikat yapılır.',
            'olcum_notu' => "- Elektrik tesisatı ve topraklama ölçümü yılda bir yaptırılır, rapor dosyada saklanır.\n"
                ."- Yangın söndürücü ve yangın tesisatı periyodik kontrol raporları dosyada saklanır.\n"
                .'- Gürültü, toz, aydınlatma ve kimyasal maruziyet ölçümleri risk değerlendirmesinde gerekli görüldüğü durumlarda yaptırılır.',
            'ekipman_kkd' => "- Yangın söndürücüler (KKT / CO2), işaretli ve kolay ulaşılabilir yerlerde\n"
                ."- İlkyardım dolabı / çantası (malzemeleri düzenli kontrol edilir)\n"
                ."- Acil durum aydınlatması ve fosforlu yönlendirme levhaları\n"
                ."- Yangın alarm butonu / sireni\n"
                ."- Acil durum ekipleri için yelek, baret, eldiven, el feneri, düdük\n"
                .'- Gerekli bölümlerde göz duşu / acil duş, yangın battaniyesi',
            'mudahale_yontemi' => "1. Acil durumu gören kişi yüksek sesle ve alarm butonuyla çevresini uyarır, amirine ve acil durum ekip liderine haber verir.\n"
                ."2. Ekip lideri veya görevli 112'yi arar; olayın türü, adres ve yaralı bilgisi verilir.\n"
                ."3. Söndürme ekibi, kendi güvenliğini tehlikeye atmadan ilk müdahaleyi yapar; elektrik ve gaz kesilir.\n"
                ."4. Tahliye kararı verilince çalışanlar makineleri güvenli şekilde durdurup en yakın acil çıkıştan, asansör kullanmadan, koşmadan çıkar.\n"
                ."5. Herkes toplanma alanında toplanır, ekip lideri sayım yapar; eksik kişi varsa kurtarma ekibine ve itfaiyeye bildirilir.\n"
                ."6. İlkyardım ekibi yaralılara müdahale eder, ambulansı yönlendirir.\n"
                .'7. Yetkili kişi izin vermeden binaya geri dönülmez.',
            'ozel_destek' => 'Engelli, hamile, yaşlı, geçici olarak hareket kısıtlılığı olan çalışan ve ziyaretçiler için birer refakatçi belirlenir; bu kişiler tahliyede önceliklidir ve refakatçileri tarafından toplanma alanına götürülür.',
            'alarm_yontemi' => 'Acil durumu fark eden kişi yüksek sesle "YANGIN / ACİL DURUM" diye uyarır; varsa en yakın yangın ihbar butonuna basar, siren / zil çalınır. Uyarı tüm bölümlere ulaştırılır; gürültülü alanlarda görevli tarafından birebir haber verilir.',
            'kayit_yedekleme' => 'Personel, muhasebe ve İSG kayıtları dijital ortamda tutulur ve düzenli olarak işyeri dışındaki bir ortama (bulut / harici disk) yedeklenir.',
        ],

        // Acil Durumlar Yön. Md.12(1)(f)(5) — ulusal acil durum hatları (işyerinden
        // bağımsız, sabit). İtfaiye/polis/jandarma/AFAD/sahil güvenlik hatları
        // 112 Acil Çağrı Merkezinde birleşti (eski 110/155/156/122/158 112'ye
        // yönlenir). Yerel irtibatlar (en yakın hastane, OSB itfaiyesi vb.) plan
        // sahibince "Acil iletişim listesi"ne elle eklenir.
        'irtibat_telefonlari' => [
            '112 Acil Çağrı Merkezi (itfaiye, ambulans, polis, jandarma, AFAD, sahil güvenlik)' => '112',
            'Ulusal Zehir Danışma Merkezi (UZEM)' => '114',
            'Elektrik Arıza' => '186',
            'Doğalgaz Acil' => '187',
        ],

        /*
         | Plan belgesi (FOM MAKİNA ADEP düzeni — kullanıcının referans belgesi).
         | PDF (`pdf/acil-durum-plani`) ve Word (`AcilDurumBelgeWord`) aynı veriyi
         | `AcilDurumBelgeVerisi` üzerinden kullanır. Senaryo metinleri ayrı
         | dosyada: config/acil_durum_senaryolari.php; akış şemaları
         | resources/belge/acil-durum-akis/<konu>.png.
        */
        // EK-4 / Bölüm 3.2: altı ekip. İlk dördü Md.11 zorunlu ekipler (plan
        // `ekipler` anahtarları), haberleşme ve teknik müdahale işletme ihtiyacı.
        'belge_ekipleri' => [
            'sondurme' => ['ad' => 'SÖNDÜRME', 'baslik' => 'Söndürme Ekibi'],
            'kurtarma' => ['ad' => 'KURTARMA', 'baslik' => 'Kurtarma Ekibi'],
            'koruma' => ['ad' => 'KORUMA / KOORDİNASYON', 'baslik' => 'Koruma Ekibi'],
            'ilk_yardim' => ['ad' => 'İLKYARDIM', 'baslik' => 'İlk Yardım Ekibi'],
            'haberlesme' => ['ad' => 'HABERLEŞME EKİBİ', 'baslik' => 'Haberleşme Ekibi'],
            'teknik' => ['ad' => 'TEKNİK MÜDAHALE EKİBİ', 'baslik' => 'Teknik Müdahale Ekibi'],
        ],

        // EK-2 dış iletişim listesi (birim => telefon). En yakın hastane plan
        // ekranından (uygulama.hastane_ad/hastane_tel) ayrıca eklenir.
        'dis_iletisim' => [
            'İTFAİYE' => '112',
            'AMBULANS İLKYARDIM' => '112',
            'POLİS' => '112',
            'JANDARMA' => '112',
            'SAHİL GÜVENLİK' => '112',
            'ORMAN YANGIN' => '112',
            'AFAD' => '112',
            'DOĞALGAZ' => '187',
            'ELEKTRİK' => '186',
            'ZEHİR DANIŞMA MERKEZİ' => '114',
            'ÇEVRE ŞİKAYET HATTI' => '181',
        ],

        // "Olası acil durumlar ve risk değerlendirmesi" tablosu (1 düşük – 5 yüksek).
        // Puanlar saha risk değerlendirmesine göre plan ekranında girilir.
        'risk_listesi' => [
            'deprem' => 'Deprem', 'yangin' => 'Yangın', 'bomba' => 'Bomba İhbarı, Patlaması',
            'sel' => 'Sel ve Ani Su Basması', 'firtina' => 'Şiddetli Rüzgar ve Fırtına', 'supheli_paket' => 'Şüpheli Paket',
            'teror_disi_patlama' => 'Terör Dışı Patlama', 'kar_don' => 'Kar ve Don', 'zehirlenme' => 'Zehirlenme',
            'is_kazasi' => 'İş Kazası', 'bulasici' => 'Bulaşıcı Hastalıklar', 'kimyasal' => 'Kimyasal Kirlilik',
            'kavga' => 'Toplu Kavga', 'trafik' => 'Trafik Kazası', 'intihar' => 'İntihar',
            'ziyaretci' => 'Ziyaretçi Hastalanması, Ölmesi', 'kemirgen' => 'Kemirgen', 'hasarat' => 'Haşarat',
            'hirsizlik' => 'Hırsızlık', 'gaz_patlamasi' => 'Gaz Patlaması',
        ],
        'risk_kolonlari' => [
            'olasilik' => 'Meydana Gelme Olasılığı', 'insan' => 'İnsan', 'isyeri' => 'İşyeri', 'is' => 'İş',
            'cevre' => 'Çevre', 'ic_kaynak' => 'İç Kaynak', 'dis_kaynak' => 'Dış Kaynak',
        ],

        // EK-6 çevresel riskler ve komşu tesisler (Md.8, Md.10/7).
        'cevresel_riskler' => [
            ['ad' => 'Yüksek Gerilim / Trafo Enerji Nakil Hattı', 'etki' => 'Elektrik / ark / yangın; yaklaşma ve kaçış etkisi.'],
            ['ad' => 'Komşu Akaryakıt / LPG İstasyonu', 'etki' => 'Yangın / patlama; yayılım ve tahliye etkisi.'],
            ['ad' => 'Yoğun Trafikli Ana Arter / Otoyol', 'etki' => 'Araç çarpması; giriş ve toplanma güvenliği.'],
            ['ad' => 'Heyelan / Şev Kayması Riski Taşıyan Zemin', 'etki' => 'Zemin kayması; bina / kaçış güvenliği.'],
            ['ad' => 'Havaalanı / Uçuş Koridoru ve İniş Güzergahı', 'etki' => 'Hava aracı olayı; konum ve dış etki.'],
            ['ad' => 'Deprem Fayı / Zemin Sıvılaşması Kuşağı', 'etki' => 'Sarsıntı / zemin hasarı; güvenli alan seçimi.'],
            ['ad' => 'Orman / Kuru Çalı Yangını Riski Olan Alan', 'etki' => 'Duman / yangın; rüzgâr ve tahliye etkisi.'],
            ['ad' => 'Dere Yatağı / Sel ve Su Baskını Riski Taşıyan Bölge', 'etki' => 'Su basması; kot ve yedek alan seçimi.'],
            ['ad' => 'Doğalgaz Ana Dağıtım Boru Hattı Yakınlığı', 'etki' => 'Gaz kaçağı / patlama; güvenli mesafe.'],
            ['ad' => 'Bitişik Sanayi / Kimyasal Üretim Tesisi', 'etki' => 'Komşu yangını / duman / kimyasal yayılım.'],
        ],

        // EK-3 faaliyetlere göre KKD listesi (D = dikkate alınır).
        'kkd_matrisi' => [
            'faaliyetler' => ['ELEKTRİK TAMİR', 'KAYNAK', 'KİMYASALLA ÇALIŞMA', 'MEKANİK TAMİR', 'ARITMA BÖLGESİ', 'YÜKLEME BOŞALTMA', 'TEMİZLİK', 'GÜVENLİK', 'TANK/SİLO GİRİŞ', 'YÜKSEKTE ÇALIŞMA'],
            'kkd' => [
                'İŞ AYAKKABISI' => '1001011111',
                'ELEKTRİKÇİ AYAKKABISI' => '1000000000',
                'PVC ÇİZME' => '0000101000',
                'ÇİZME - KİMYASAL' => '0010001000',
                'TOZ MASKESİ' => '0010010000',
                'KAYNAK MASKESİ' => '0100000000',
                'GAZ MASKESİ' => '0010000000',
                'KULAKLIK' => '0101010000',
                'BARET' => '0000010011',
                'GÖZLÜK' => '1111001000',
                'İŞ ELBİSESİ' => '1111111111',
                'KİMYASAL ELBİSESİ' => '0010001000',
                'KAYNAKÇI ÖNLÜĞÜ' => '0100000000',
                'EMNİYET KEMERİ' => '0000000001',
                'ELDİVEN - KİMYASAL' => '0010001000',
                'CAN YELEĞİ/SİMİDİ' => '0000100000',
                'ELDİVEN - MONTAJ' => '0001010000',
                'ELDİVEN - Y GERİLİM' => '1000000000',
                'ELDİVEN - TEMİZLİK' => '0000001000',
                'ELEKTRİKÇİ MASKESİ' => '1000000000',
                'BEL KEMERİ (KKD DEĞİL)' => '0000000000',
                'TOPRAKLAMA/ISTAKASI' => '1000000000',
            ],
        ],

        // Plan yenileme periyodu tehlike sınıfına göre (yıl) — Acil Durumlar Yön.
        'gecerlilik_yili' => [
            'az_tehlikeli' => 6,
            'tehlikeli' => 4,
            'cok_tehlikeli' => 2,
        ],

        /*
         | Acil Durum Planı — gerçek referans Word/PowerPoint şablonları kütüphanesi.
         | `AcilDurumWordUretici`/`AcilDurumKapakUretici` kullanıcının kendi gerçek
         | belgesini BİREBİR ŞABLON olarak kullanır (kendi tasarımımız değil);
         | yalnızca firmaya özel değerler değişir. Kullanıcı zamanla farklı
         | biçimli acil durum planı örnekleri ekleyecek — her yeni şablon için
         | (1) dosya `resources/belge/`'ye eklenir, (2) burada yeni bir giriş
         | açılır ('dosya' + varsa 'kapak_dosya' + o dosyadaki orijinal (örnek
         | firma) değerlerin haritası). `eslemeler`'in anahtarları semantik
         | (unvan/adres/... ), değerleri şablondaki gerçek eski metindir —
         | Uretici sınıfları bu semantik anahtarlardan güncel firma değerini
         | üretip eski metnin yerine yazar. `docx`/`kapak` ayrı tutulur çünkü
         | aynı bilgi iki dosyada farklı biçimlendirilmiş olabilir (örn. kapak
         | sayfasında adres sonunda yazım hatası kaynaklı fazladan "/" vardı).
        */
        'word_sablonlari' => [
            // Kapsamlı plan (FOM MAKİNA ADEP düzeni) — 'tur' => 'belge': metin
            // yaması değil, gövde PDF ile aynı Blade'den üretilir; kapak, üst bilgi,
            // alt imza şeridi ve stiller bu dosyadan gelir (AcilDurumBelgeWord).
            'fom' => [
                'ad' => 'Şablon 1 — Kapsamlı ADEP',
                'aciklama' => 'FOM MAKİNA referans belgesinin düzeni: 7 bölüm, risk tablosu, EK-1…EK-7; tüm bilgiler sistemden dolar (PDF ile aynı içerik).',
                'dosya' => 'acil-durum-plani-fom.docx',
                'tur' => 'belge',
            ],
            'orijinal' => [
                'ad' => 'Şablon 2 — ADEP Klasik',
                'aciklama' => 'Gerçek referans belge (11 acil durum listesi + görsel akış şemaları + kapak sayfası).',
                'dosya' => 'acil-durum-plani-sablonu.docx',
                'kapak_dosya' => 'acil-durum-kapak-sablonu.pptx',
                'docx' => [
                    'unvan' => 'ALTIN YAKUT YAPI ADİ ORTAKLIĞI',
                    'adres' => 'MEHMET AKİF ERSOY MAHALLESİ 4091 SOK. 1380 ADA 9 PARSEL YALOVA MERKEZ NO:19',
                    'sgk_sicil_no' => '44100010111205450770133000',
                    'rapor_tarihi' => '26.03.2026',
                    'gecerlilik_tarihi' => '26.03.2028',
                    'tehlike_sinifi' => 'ÇOK TEHLİKELİ',
                    'uzman_adi' => 'MEHMET ÖZDEMİR',
                    'uzman_unvani' => 'A Sınıfı İş Güvenliği Uzmanı',
                ],
                'kapak' => [
                    'unvan' => 'ALTIN YAKUT YAPI ADİ ORTAKLIĞI',
                    'adres' => 'MEHMET AKİF ERSOY MAHALLESİ 4091 SOK. 1380 ADA 9 PARSEL YALOVA MERKEZ NO:19/',
                    'sgk_sicil_no' => '44100010111205450770133000',
                    'rapor_tarihi' => '26.03.2026',
                    'gecerlilik_tarihi' => '26.03.2028',
                    'nace_kodu' => '41.00.01',
                ],
            ],
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

        // Acil Durum Ekipleri / Destek Elemanları (isgsuite acil_ekipler).
        // `oran`: tehlike sınıfına göre kaç çalışana kadar 1 destek elemanı
        // (Acil Durumlar Yön. Md.11; ilk yardım: İlkyardım Yön. — 20/15/10).
        // `oran` null ise yasal oran yok, öneri olarak 1 kişi. Çalışan 10'dan
        // azsa tek eğitimli kişi yeterlidir (min 1). `belge_ay`: eğitim /
        // sertifika geçerliliği (belge bitişi boşsa tarihten hesaplanır).
        // `plan`: Acil Durum Planı'ndaki ekip anahtarı.
        'ekip_turleri' => [
            'sondurme' => ['ad' => 'Söndürme Ekibi', 'oran' => ['az_tehlikeli' => 50, 'tehlikeli' => 40, 'cok_tehlikeli' => 30], 'belge_ay' => 12, 'plan' => 'sondurme'],
            'kurtarma' => ['ad' => 'Kurtarma Ekibi', 'oran' => ['az_tehlikeli' => 50, 'tehlikeli' => 40, 'cok_tehlikeli' => 30], 'belge_ay' => 12, 'plan' => 'kurtarma'],
            'koruma' => ['ad' => 'Koruma Ekibi', 'oran' => ['az_tehlikeli' => 50, 'tehlikeli' => 40, 'cok_tehlikeli' => 30], 'belge_ay' => 12, 'plan' => 'koruma'],
            'ilk_yardim' => ['ad' => 'İlk Yardım Ekibi', 'oran' => ['az_tehlikeli' => 20, 'tehlikeli' => 15, 'cok_tehlikeli' => 10], 'belge_ay' => 36, 'plan' => 'ilk_yardim'],
            'tahliye' => ['ad' => 'Tahliye Ekibi', 'oran' => null, 'belge_ay' => 12, 'plan' => null],
            'haberlesme' => ['ad' => 'Haberleşme Ekibi', 'oran' => null, 'belge_ay' => 12, 'plan' => 'haberlesme'],
            'teknik' => ['ad' => 'Teknik Müdahale Ekibi', 'oran' => null, 'belge_ay' => 12, 'plan' => 'teknik'],
            'diger' => ['ad' => 'Diğer Ekip', 'oran' => null, 'belge_ay' => 12, 'plan' => null],
        ],
        'ekip_belge_yaklasan_gun' => 30,
        'vardiyalar' => ['Gündüz', 'Akşam', 'Gece', 'Tüm vardiyalar'],

        // 7 acil durum afişi — A3/A4 talimat (numaralı adımlar).
        'afisler' => [
            'yangin' => [
                'ad' => 'Yangınla Mücadele', 'baslik' => 'YANGINLA MÜCADELE VE TAHLİYE TALİMATI',
                'dosya' => 'yangin.pdf',
                'adimlar' => [
                    'İtfaiyeyi arayın (112).',
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
        // Sonuçlandır adımında puan girilirse bunun altındakiler belge almaz
        // ve yenileme takibinde eğitimli sayılmaz (puan boşsa yalnız katılım).
        'gecme_puani' => 60,

        // Tehlike sınıfına göre İLK DEFA verilen eğitimin toplam süresi
        // (Çalışanların İSG Eğitimlerinin Usul ve Esasları Hak. Yön. Ek-1:
        // 8/12/16 saat) — kullanıcı tarafından doğrulandı: 4 blok (Genel/Sağlık/
        // Teknik/İşyerine Özgü Riskler) EŞİT PAYLA bu toplama bölünür: az
        // tehlikelide blok başına 2 saat, tehlikelide 3 saat, çok tehlikelide
        // 4 saat (4 × 2/3/4 = 8/12/16). `blok_fiili_dk`, "1 ders saati = 45 dk
        // fiili + 15 dk dinlenme" oranıyla (bkz. EgitimIcerikOlusturucu::
        // bolumSuresi, dinlenme=fiili/3) bu saat hedefine karşılık gelen FİİLİ
        // ders dakikasıdır — 4 fiili/3 = saat cinsinden duvar saati verir
        // (ör. az tehlikeli: 90 dk fiili + 30 dk dinlenme = 120 dk = 2 saat).
        'sureler' => [
            'ilk' => [
                'az_tehlikeli' => ['saat' => 8, 'blok_fiili_dk' => 90],
                'tehlikeli' => ['saat' => 12, 'blok_fiili_dk' => 135],
                'cok_tehlikeli' => ['saat' => 16, 'blok_fiili_dk' => 180],
            ],
            // TEKRAR eğitiminde tehlike sınıfından BAĞIMSIZ, her zaman toplam
            // 8 saat (4 blok × 2 saat) — kullanıcı tarafından doğrulandı.
            'tekrar' => ['saat' => 8, 'blok_fiili_dk' => 90],
        ],

        // Tekrar eğitiminin yenilenme periyodu (bilgi amaçlı — otomatik
        // hesaplama/hatırlatma YAPILMAZ, yalnız arayüzde/PDF'te gösterilir).
        'tekrar_periyodu_yil' => [
            'az_tehlikeli' => 3,
            'tehlikeli' => 2,
            'cok_tehlikeli' => 1,
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
            'asansor' => ['ad' => 'Asansör', 'maddeler' => [
                'Asansör kuyusu ve makine dairesinde çalışma güvenliği', 'Halat, kılavuz ray ve fren sistemlerinin kontrolü',
                'Sıkışma, ezilme ve yüksekten düşme riskleri', 'Kabin ve karşı ağırlık sisteminde güvenli çalışma',
                'Acil durum ve mahsur kalan kullanıcı kurtarma prosedürleri',
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
            // isgsuite "Özel Eğitimler" — hijyen uzmanlık eğitimleri (4 saat, yüz yüze).
            'isyeri_hijyen' => ['ad' => 'İşyeri İçi Hijyen ve Sanitasyon Bilgilendirme Eğitimi', 'maddeler' => [
                'Kişisel hijyenin temel ilkeleri',
                'El hijyeni ve doğru el yıkama tekniği',
                'İş kıyafetleri ve kişisel koruyucu donanım temizliği',
                'Temizlik, dezenfeksiyon ve sanitasyon kavramları',
                'Biyolojik riskler ve bulaşma yolları',
                'Çapraz bulaşmanın önlenmesi',
            ]],
            'gida_su_hijyen' => ['ad' => 'Gıda ve Su Sektöründe Hijyen Eğitimi', 'maddeler' => [
                'Gıda ve su hijyeninin amacı ve çalışan sorumlulukları',
                'Kişisel hijyen, el yıkama ve el antiseptiği',
                'Bulaşma yolları ve çapraz bulaşmanın önlenmesi',
                'Gıda kaynaklı biyolojik, kimyasal ve fiziksel tehlikeler',
                'İçme ve kullanma suyunun güvenliği; su deposu ve dağıtım',
                'Soğuk zincir, sıcaklık kontrolü ve güvenli depolama',
            ]],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eğitim Kayıtları — Profilim > Eğitimler (isgpratik 139-140.jpg)
    |--------------------------------------------------------------------------
    | Bu liste artık doğrudan matris sütunu DEĞİL — yalnız "Konu Ekle" modalının
    | hazır katalogu. Kullanıcı Profilim > Eğitimler'i ilk açtığında yalnız ilk
    | madde (Temel İSG Eğitimi) kendi EgitimTuru satırı olarak oluşturulur
    | (EgitimTuru::aktifListe); geri kalanlar istenirse tek tek eklenir.
    | 'gecerlilik_ay' null olan tek madde (temel İSG eğitimi) tehlike sınıfına
    | göre değişir — geçerliliği 'egitim_yenileme_yili' (yıl) ile hesaplanır,
    | sonradan eklenen konularda bu alan zorunludur (sabit ay).
    */
    'egitim_kayit_turleri' => [
        ['anahtar' => 'is_sagligi_guvenligi_egitimi', 'ad' => 'Temel İş Sağlığı ve Güvenliği Eğitimi', 'kategori' => 'genel', 'gecerlilik_ay' => null],
        ['anahtar' => 'ilkyardim_temel', 'ad' => 'İlkyardım (Temel Bilgilendirme)', 'kategori' => 'saglik', 'gecerlilik_ay' => 36],
        ['anahtar' => 'kkd_kullanimi', 'ad' => 'Kişisel Koruyucu Donanım (KKD) Kullanımı', 'kategori' => 'teknik', 'gecerlilik_ay' => 36],
        ['anahtar' => 'yuksekte_calisma', 'ad' => 'Yüksekte Çalışma', 'kategori' => 'risk_bazli', 'gecerlilik_ay' => 12],
        ['anahtar' => 'iskele_kurulum_sokme', 'ad' => 'İskele Kurulum, Sökme ve Kullanım', 'kategori' => 'risk_bazli', 'gecerlilik_ay' => 12],
        ['anahtar' => 'asbestle_calisma', 'ad' => 'Asbestle Çalışma', 'kategori' => 'risk_bazli', 'gecerlilik_ay' => 12],
        ['anahtar' => 'isg_kurul_uyesi_egitimi', 'ad' => 'İSG Kurul Üyesi Eğitimi', 'kategori' => 'gorev', 'gecerlilik_ay' => 36],
        ['anahtar' => 'calisan_temsilcisi_ozel_egitimi', 'ad' => 'Çalışan Temsilcisi Özel Eğitimi', 'kategori' => 'gorev', 'gecerlilik_ay' => 36],
        ['anahtar' => 'destek_elemani_egitimi', 'ad' => 'Destek Elemanı Eğitimi', 'kategori' => 'gorev', 'gecerlilik_ay' => 12],
        ['anahtar' => 'ilkyardimci_sertifikasi_16saat', 'ad' => 'İlkyardımcı Sertifikası (16 Saat)', 'kategori' => 'gorev', 'gecerlilik_ay' => 36],
        ['anahtar' => 'yangin_sondurme_gorevlisi', 'ad' => 'Yangın Söndürme Görevlisi', 'kategori' => 'gorev', 'gecerlilik_ay' => 12],
        ['anahtar' => 'acil_durum_tatbikati_yillik', 'ad' => 'Acil Durum Tatbikatı (Yıllık)', 'kategori' => 'gorev', 'gecerlilik_ay' => 12],
        ['anahtar' => 'risk_degerlendirme_ekibi_uyesi', 'ad' => 'Risk Değerlendirme Ekibi Üyesi', 'kategori' => 'gorev', 'gecerlilik_ay' => 36],
        ['anahtar' => 'isveren_isveren_vekili_egitimi', 'ad' => 'İşveren / İşveren Vekili Eğitimi', 'kategori' => 'gorev', 'gecerlilik_ay' => 36],
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
                'ad' => 'Risk Değerlendirme Ekibi', 'tip' => 'toplu', 'ikon' => 'heroicon-o-shield-exclamation',
                'aciklama' => 'İSG Risk Değerlendirmesi Yönetmeliği md.6 uyarınca oluşturulur. İGU, işyeri hekimi, çalışan '.
                    'temsilcisi ve bilgi sahibi çalışan(lar) sistemden otomatik gelir; yalnız destek elemanı girilir, '.
                    'her üye için ayrı sayfalı toplu yazı üretilir.',
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
        // Tutanak başlığının sol kutusu: firma logosu yoksa bu OSGB logosu (resources/ altında).
        'varsayilan_logo' => 'belge/logolar/yildiz-grup-osgb.png',
        /*
         | Kurul üyelik rolleri — İSG Kurulları Hakkında Yönetmelik Md.6(1).
         | zorunlu=true olanlar eksikse toplantı yalnız "Taslak" kaydedilebilir
         | (isgsuite.tr "Resmî durum engeli"). kaynak: firma kaydından otomatik
         | aday önerisi (App\Support\KurulUyeleri::oneriler()).
         */
        'roller' => [
            'baskan' => ['ad' => 'İşveren / İşveren Vekili (Başkan)', 'zorunlu' => true, 'kaynak' => 'isveren'],
            'sekreter' => ['ad' => 'İş Güvenliği Uzmanı (Sekreter)', 'zorunlu' => true, 'kaynak' => 'igu'],
            'hekim' => ['ad' => 'İşyeri Hekimi', 'zorunlu' => true, 'kaynak' => 'hekim'],
            'ik' => ['ad' => 'İK / Personel / İdari-Mali İşler Sorumlusu', 'zorunlu' => true, 'kaynak' => null],
            'calisan_temsilcisi' => ['ad' => 'Çalışan Temsilcisi (Baş Temsilci)', 'zorunlu' => true, 'kaynak' => 'temsilci'],
            'sivil_savunma' => ['ad' => 'Sivil Savunma Uzmanı (varsa)', 'zorunlu' => false, 'kaynak' => null],
            'formen' => ['ad' => 'Formen / Ustabaşı / Usta (varsa)', 'zorunlu' => false, 'kaynak' => null],
            'diger' => ['ad' => 'Diğer Üye', 'zorunlu' => false, 'kaynak' => null],
        ],
        // Toplantı sıklığı (ay) — Yönetmelik Md.9: çok tehlikeli ayda bir, tehlikeli
        // iki ayda bir, az tehlikeli üç ayda bir. Sonraki toplantı önerisi buradan.
        'periyot_ay' => ['cok_tehlikeli' => 1, 'tehlikeli' => 2, 'az_tehlikeli' => 3],
        'turler' => ['olagan' => 'Olağan', 'olaganustu' => 'Olağanüstü'],
        'durumlar' => ['taslak' => 'Taslak', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'],
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
        'zamanlar' => [
            'once' => 'Eğitim Öncesi (Ön Test)',
            'sonra' => 'Eğitim Sonrası (Son Test)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Soru Bankası — kalıcı, kaynaklı, onaylı sektörel İSG sınav sorusu havuzu
    |--------------------------------------------------------------------------
    | Sektör listesi risk_ai.sektorler ile ortak. Eğitim Soruları ve Uzaktan
    | Eğitim sınavları YALNIZ durum=onaylandi soruları havuzdan çeker.
    */
    'soru_bankasi' => [
        'zorluklar' => [
            'kolay' => 'Kolay',
            'orta' => 'Orta',
            'zor' => 'Zor',
        ],
        // Editoryal akış: taslak → incelemede → yayımlandı (onaylandi) →
        // kullanımdan kaldırıldı (arsiv). Sınavlar yalnız 'onaylandi' kullanır.
        'durumlar' => [
            'taslak' => 'Taslak',
            'incelemede' => 'İncelemede',
            'onaylandi' => 'Yayımlandı',
            'arsiv' => 'Kaldırıldı',
        ],
        // NACE kapsama hedefi (isgsuite akıllı sınav motoru: 5 sabit temel
        // + 15 işe özgü soru). Temel = ortak havuz, işe özgü = NACE kapsamlı.
        'kapsama' => ['temel' => 5, 'ise_ozgu' => 15],
        // Konu = eğitim başlığı / risk teması. isg.egitim genel içerik başlıklarıyla uyumlu.
        'konular' => [
            'genel_isg' => 'Genel İSG / Mevzuat',
            'calisan_haklari' => 'Çalışan Hak ve Yükümlülükleri',
            'risk_degerlendirmesi' => 'Risk Değerlendirmesi',
            'acil_durum' => 'Acil Durumlar ve Tahliye',
            'yangin' => 'Yangın Güvenliği',
            'ilk_yardim' => 'İlk Yardım',
            'kkd' => 'Kişisel Koruyucu Donanım',
            'ergonomi' => 'Ergonomi ve Duruş',
            'elle_tasima' => 'Elle Taşıma İşleri',
            'kimyasal' => 'Kimyasal Etkenler',
            'biyolojik' => 'Biyolojik Etkenler',
            'gurultu_titresim' => 'Gürültü ve Titreşim',
            'elektrik' => 'Elektrik Güvenliği',
            'is_ekipmanlari' => 'İş Ekipmanları ve Makine Koruyucuları',
            'yuksekte_calisma' => 'Yüksekte Çalışma',
            'kapali_alan' => 'Kapalı / Dar Alan',
            'kazi' => 'Kazı ve Hafriyat',
            'is_kazasi' => 'İş Kazası ve Ramak Kala',
            'meslek_hastaligi' => 'Meslek Hastalıkları ve Sağlık Gözetimi',
            'termal_konfor' => 'Termal Konfor ve Aydınlatma',
            'ellecleme_istif' => 'İstifleme ve Depolama',
            'trafik_saha' => 'Saha İçi Trafik ve İş Makineleri',
        ],
        // AI üretiminde ve manuel eklemede birer örnek kaynak (serbest metin, zorunlu değil).
        'ornek_kaynaklar' => [
            '6331 sayılı İSG Kanunu',
            'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği',
            'Yapı İşlerinde İSG Yönetmeliği',
            'KKD Yönetmeliği',
            'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Yönetmeliği',
            'İşyerlerinde Acil Durumlar Hakkında Yönetmelik',
            'Elle Taşıma İşleri Yönetmeliği',
            'Çalışanların Gürültü ile İlgili Risklerden Korunmalarına Dair Yönetmelik',
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
        // Uygun Değil maddelerde isteğe bağlı risk seviyesi (master saha gözlem defteri: Düşük / Orta / Yüksek).
        'risk_seviyeleri' => [
            'dusuk' => 'Düşük',
            'orta' => 'Orta',
            'yuksek' => 'Yüksek',
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
            'Yüksekte çalışma öncesinde uygun KKD ve izin hazır olmalıdır.',
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
    /*
    |--------------------------------------------------------------------------
    | KKD Takip — isgsuite "KKD Takip" (zimmet sicili + stok kartları)
    |--------------------------------------------------------------------------
    | Kategori / tür listesi 'kkd.kategoriler' katalogundan gelir. "Yaklaşan"
    | eşiği kullanıcı ayarı (KullaniciAyarlari esik 'kkd', varsayılan 30 gün).
    */
    /*
    |--------------------------------------------------------------------------
    | Taşeron / Alt İşveren Yönetimi — isgsuite "Taşeron Yönetimi"
    |--------------------------------------------------------------------------
    | 'zorunlu' = uygunluk kontrolünde bu türdeki firma için aranan belgeler.
    | Taşeron çalışanları ana personel listesine (calisanlar) eklenmez.
    */
    /*
    |--------------------------------------------------------------------------
    | Ziyaretçi Yönetimi — isgsuite "Ziyaretçi Yönetimi"
    |--------------------------------------------------------------------------
    | Süreli QR'lı geçiş kartı. 'kurallar' kartın arkasında / altında basılan
    | kısa ziyaretçi güvenlik kurallarıdır (işyerine göre değiştirilebilir).
    */
    'ziyaretci' => [
        'durumlar' => [
            'planli' => 'Planlandı',
            'gecerli' => 'Geçerli',
            'icerde' => 'İçeride',
            'cikti' => 'Çıkış yaptı',
            'suresi_doldu' => 'Süresi doldu',
            'iptal' => 'İptal',
        ],
        'varsayilan_sure_saat' => 8,
        'kurallar' => [
            'Bu kartı işyerinde bulunduğunuz süre boyunca görünür şekilde taşıyın.',
            'Refakatçiniz olmadan üretim / saha alanlarına girmeyin.',
            'Size verilen kişisel koruyucu donanımı (baret, yelek, gözlük vb.) kullanın.',
            'Yaya yollarını kullanın; forklift ve iş makinelerinden uzak durun.',
            'Uyarı ve yasak levhalarına uyun; izinsiz fotoğraf / video çekmeyin.',
            'Sigara yalnızca belirlenmiş alanlarda içilebilir.',
            'Alarm duyduğunuzda refakatçinizi izleyerek en yakın acil toplanma alanına gidin.',
            'Çıkışta kartı ve verilen donanımı güvenliğe teslim edin.',
        ],
    ],

    'taseron' => [
        'turler' => [
            'alt_isveren' => 'Alt İşveren',
            'taseron' => 'Taşeron / Yüklenici (hizmet alımı)',
        ],
        'belge_turleri' => [
            'sozlesme' => ['ad' => 'Sözleşme (alt işverenlik / hizmet)', 'zorunlu' => ['alt_isveren', 'taseron']],
            'sgk_tescil' => ['ad' => 'SGK işyeri tescil / alt işveren bildirgesi', 'zorunlu' => ['alt_isveren']],
            'isg_hizmet' => ['ad' => 'İSG hizmet sözleşmesi / İGU–hekim görevlendirmesi', 'zorunlu' => ['alt_isveren', 'taseron']],
            'risk_degerlendirmesi' => ['ad' => 'Risk değerlendirmesi', 'zorunlu' => ['alt_isveren']],
            'egitim_kaydi' => ['ad' => 'İSG eğitim kayıtları', 'zorunlu' => ['alt_isveren', 'taseron']],
            'acil_durum_plani' => ['ad' => 'Acil durum planı', 'zorunlu' => []],
            'ekipman_kontrol' => ['ad' => 'Ekipman periyodik kontrol raporları', 'zorunlu' => []],
            'sigorta' => ['ad' => 'Sigorta poliçesi', 'zorunlu' => []],
            'diger' => ['ad' => 'Diğer', 'zorunlu' => []],
        ],
        // Sözleşme / belge bitişine bu kadar gün kala "yaklaşıyor" uyarısı.
        'yaklasan_gun' => 30,
    ],

    'kkd_takip' => [
        'durumlar' => [
            'teslim_edildi' => 'Teslim Edildi',
            'iade_edildi' => 'İade Edildi',
            'yenilendi' => 'Yenilendi',
            'kayip' => 'Kayıp',
            'hasarli' => 'Hasarlı / Hurda',
        ],
        'takip_durumlari' => [
            'aktif' => 'Aktif',
            'yaklasan' => 'Yenileme Yaklaşıyor',
            'gecikmis' => 'Yenileme Gecikti',
        ],
        'stok_durumlari' => [
            'yeterli' => 'Yeterli',
            'yaklasan' => 'Süre Yaklaşıyor',
            'dusuk' => 'Düşük Stok',
            'tukendi' => 'Tükendi',
            'suresi_gecmis' => 'Süresi Geçmiş',
        ],
        'hareket_tipleri' => [
            'giris' => 'Stok Girişi',
            'zimmet' => 'Zimmet Çıkışı',
            'iade' => 'İade',
            'fire' => 'Fire / Hurda',
        ],
    ],

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
            'kapali_alan' => 'Kapalı / Dar Alan',
            'elektrik' => 'Elektrik Çalışması',
            'kazi' => 'Kazı Çalışması',
            'kaldirma' => 'Kaldırma / Vinç Operasyonu',
            'loto' => 'Enerji İzolasyonu (LOTO)',
            'radyografi' => 'Radyografik Muayene (NDT)',
            'basincli_test' => 'Basınçlı Test',
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
            ['madde' => 'Sürekli dış gözcü görevlendirildi', 'tur' => 'kapali_alan'],
            ['madde' => 'Enerji kesildi ve kilitlendi (LOTO)', 'tur' => 'elektrik'],
            ['madde' => 'Topraklama kontrolü yapıldı', 'tur' => 'elektrik'],
            ['madde' => 'Gerilim yokluğu test cihazıyla doğrulandı', 'tur' => 'elektrik'],
            ['madde' => 'Yeraltı hatları (elektrik/gaz/su) sorgulandı ve işaretlendi', 'tur' => 'kazi'],
            ['madde' => 'Kazı kenarı şevlendi / iksa yapıldı', 'tur' => 'kazi'],
            ['madde' => 'Kazı kenarına malzeme/araç mesafesi bırakıldı', 'tur' => 'kazi'],
            ['madde' => 'Vinç periyodik kontrolü ve kapasite tablosu geçerli', 'tur' => 'kaldirma'],
            ['madde' => 'Sapan / kaldırma aksesuarları kontrol edildi', 'tur' => 'kaldirma'],
            ['madde' => 'Yük altına giriş engellendi / işaretçi görevlendirildi', 'tur' => 'kaldirma'],
            ['madde' => 'Tüm enerji kaynakları (elektrik/hidrolik/pnömatik/mekanik) izole edildi', 'tur' => 'loto'],
            ['madde' => 'Kilit + etiket takıldı, anahtar yetkili kişide', 'tur' => 'loto'],
            ['madde' => 'Artık enerji boşaltıldı ve sıfır enerji doğrulandı', 'tur' => 'loto'],
            ['madde' => 'Radyasyon alanı bariyerlendi ve ikaz işaretlendi', 'tur' => 'radyografi'],
            ['madde' => 'Dozimetre / alan ölçümü yapıldı, yetkisiz giriş yok', 'tur' => 'radyografi'],
            ['madde' => 'Radyografi yetki belgeli personel ve TAEK izni mevcut', 'tur' => 'radyografi'],
            ['madde' => 'Test basıncı ve kademeleri belirlendi', 'tur' => 'basincli_test'],
            ['madde' => 'Test hattı emniyet valfi ve manometre kalibreli', 'tur' => 'basincli_test'],
            ['madde' => 'Test sırasında güvenlik mesafesi / bariyer sağlandı', 'tur' => 'basincli_test'],
        ],
        'kkd_secenekleri' => [
            'Baret', 'İş Ayakkabısı', 'Reflektörlü Yelek', 'Koruyucu Gözlük',
            'Kaynak Maskesi', 'Emniyet Kemeri', 'Gaz Maskesi', 'Kulaklık',
            'Yüz Siperi', 'Kesilmez Eldiven', 'Yalıtkan Eldiven', 'Solunum Cihazı',
        ],

        // Onay / kapanış yaşam döngüsü — durum rozeti + sonraki adım.
        'durumlar' => [
            'taslak' => 'Taslak',
            'onay_bekliyor' => 'Onay Bekliyor',
            'onaylandi' => 'Onaylandı (Çalışma Yetkisi Verildi)',
            'reddedildi' => 'Reddedildi',
            'is_tamamlandi' => 'İş Tamamlandı',
            'kapatildi' => 'Kapatıldı (Saha Teslim)',
            'iptal' => 'İptal Edildi',
        ],
        'onay_durumlari' => [
            'bekliyor' => 'Bekliyor',
            'onayladi' => 'Onayladı',
            'reddetti' => 'Reddetti',
        ],

        // PTW operasyon kontrolleri (isgsuite referansı). 'turler' doluysa yalnız o
        // izin türlerinde varsayılan "bekliyor", diğerlerinde "gerekli değil" gelir;
        // boşsa her izinde "bekliyor". Tam onay, bekleyen/uygun olmayan kontrol varken verilmez.
        'operasyon_kontrolleri' => [
            'loto' => ['ad' => 'LOTO / enerji izolasyonu', 'turler' => ['loto', 'elektrik']],
            'gaz' => ['ad' => 'Gaz ölçümü', 'turler' => ['kapali_alan', 'sicak_is']],
            'kkd' => ['ad' => 'KKD kontrolü', 'turler' => []],
            'ekipman' => ['ad' => 'Ekipman kontrolü', 'turler' => []],
            'acil_durum' => ['ad' => 'Acil durum hazırlığı', 'turler' => []],
            'yetkinlik' => ['ad' => 'Yetkinlik belgesi', 'turler' => []],
        ],
        'kontrol_durumlari' => [
            'bekliyor' => 'Kontrol bekliyor',
            'uygun' => 'Uygun',
            'uygun_degil' => 'Uygun değil',
            'gerekli_degil' => 'Gerekli değil',
        ],

        /*
        | İzin Kütüphanesi — hazır PTW kataloğu. Her şablon: türler + o türlerin
        | filtreli önlemlerine EK önlemler + KKD + geçerlilik (saat) + uyarılar +
        | onay başlıkları. Uzman kendi şablonlarını is_izin_sablonlari tablosuna
        | ekler; ekranda ikisi birlikte listelenir.
        */
        'kutuphane' => [
            [
                'ad' => 'Sıcak İş İzni (Kaynak / Kesme / Taşlama)',
                'aciklama' => 'Alev, kıvılcım veya ısı üreten her türlü çalışma.',
                'turler' => ['sicak_is'],
                'ek_onlemler' => ['Çalışma sonrası 30 dk yangın gözetimi yapılacak'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Kaynak Maskesi', 'Yüz Siperi', 'Kesilmez Eldiven'],
                'gecerlilik_saat' => 8,
                'uyarilar' => ['İzin yalnızca belirtilen vardiya için geçerlidir', 'Yangın söndürücü çalışma noktasında bulundurulacak'],
                'onay1_baslik' => 'Saha Sorumlusu / Formen',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Yüksekte Çalışma İzni',
                'aciklama' => '2 m ve üzeri yükseklikte, düşme riskli çalışma.',
                'turler' => ['yukseklik'],
                'ek_onlemler' => ['Çift kancalı lanyard kullanılacak', 'Altında çalışma / geçiş engellendi'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Emniyet Kemeri'],
                'gecerlilik_saat' => 10,
                'uyarilar' => ['Rüzgâr hızı 10 m/s üzerinde çalışma durdurulur', 'Kurtarma planı hazır olmalı'],
                'onay1_baslik' => 'Saha Sorumlusu / Formen',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Kapalı / Dar Alan Giriş İzni',
                'aciklama' => 'Tank, kazan, rögar, silo vb. sınırlı alana giriş.',
                'turler' => ['kapali_alan'],
                'ek_onlemler' => ['Giriş-çıkış kayıt çizelgesi tutulacak', 'Ölçüm periyodik olarak tekrarlanacak'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Gaz Maskesi', 'Solunum Cihazı', 'Emniyet Kemeri'],
                'gecerlilik_saat' => 4,
                'uyarilar' => ['O2 %19,5-23,5 dışında GİRİŞ YASAK', 'Sürekli dış gözcü zorunlu', 'Kurtarma ekipmanı girişte hazır'],
                'onay1_baslik' => 'Saha Sorumlusu / Formen',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Elektrik Çalışması / LOTO İzni',
                'aciklama' => 'Gerilim altında veya yakınında çalışma, pano/hat müdahalesi.',
                'turler' => ['elektrik', 'loto'],
                'ek_onlemler' => ['Tek hat şeması kontrol edildi', 'Yetkili elektrikçi (yüksek gerilim belgesi) görevli'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Yalıtkan Eldiven', 'Yüz Siperi'],
                'gecerlilik_saat' => 8,
                'uyarilar' => ['Gerilim yokluğu doğrulanmadan dokunma', 'Kilit anahtarı yalnızca işi yapanda'],
                'onay1_baslik' => 'Elektrik Sorumlusu',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Kazı Çalışması İzni',
                'aciklama' => '1,5 m ve üzeri kazı; yeraltı hattı riski.',
                'turler' => ['kazi'],
                'ek_onlemler' => ['Kazı çıkış merdiveni her 8 m’de bir', 'Kazı günlük olarak yetkili kişi tarafından muayene edilecek'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Reflektörlü Yelek'],
                'gecerlilik_saat' => 24,
                'uyarilar' => ['Yağış sonrası kazıya girmeden yeniden muayene', 'Kazı kenarına 1 m içinde yük/araç yok'],
                'onay1_baslik' => 'Saha Sorumlusu / Formen',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Kaldırma / Vinç Operasyonu İzni',
                'aciklama' => 'Mobil/kule vinç ile kritik veya ağır yük kaldırma.',
                'turler' => ['kaldirma'],
                'ek_onlemler' => ['Kaldırma planı (lift plan) hazırlandı', 'Zemin taşıma kapasitesi teyit edildi'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Reflektörlü Yelek'],
                'gecerlilik_saat' => 8,
                'uyarilar' => ['Yük altında personel bulunamaz', 'İşaretçi (rigger) dışında komut verilmez'],
                'onay1_baslik' => 'Kaldırma Süpervizörü',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Radyografik Muayene (NDT) İzni',
                'aciklama' => 'Gama/X-ışını kaynağı ile tahribatsız muayene.',
                'turler' => ['radyografi'],
                'ek_onlemler' => ['Çalışma gece / düşük yoğunluklu saatte planlandı', 'Alan sınırı doz hızı ölçümüyle belirlendi'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Reflektörlü Yelek'],
                'gecerlilik_saat' => 6,
                'uyarilar' => ['TAEK lisanslı firma ve personel zorunlu', 'Bariyer içine yetkisiz giriş yasak'],
                'onay1_baslik' => 'Radyasyondan Korunma Sorumlusu',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Basınçlı Test İzni',
                'aciklama' => 'Boru hattı / kap hidrostatik veya pnömatik testi.',
                'turler' => ['basincli_test'],
                'ek_onlemler' => ['Test prosedürü ve P&ID onaylı', 'Pnömatik testte güvenlik mesafesi hesabı yapıldı'],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Koruyucu Gözlük', 'Yüz Siperi'],
                'gecerlilik_saat' => 6,
                'uyarilar' => ['Test sırasında hat üzerinde bakım yapılamaz', 'Basınç kademeli artırılır ve beklenir'],
                'onay1_baslik' => 'Mekanik Sorumlusu',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
            [
                'ad' => 'Genel Yüksek Riskli İş İzni',
                'aciklama' => 'Yukarıdaki kategorilere girmeyen ama risk değerlendirmesinde izin gerektiren işler.',
                'turler' => [],
                'ek_onlemler' => [],
                'kkdler' => ['Baret', 'İş Ayakkabısı', 'Reflektörlü Yelek'],
                'gecerlilik_saat' => 8,
                'uyarilar' => ['İş kapsamı değişirse izin yenilenir'],
                'onay1_baslik' => 'Saha Sorumlusu / Formen',
                'onay2_baslik' => 'İSG Uzmanı / İşveren Vekili',
            ],
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

        // 5 Neden (5N) — sabit sıralı sorular (isgpratik İş Kazası İnceleme Raporu).
        'bes_neden_sorulari' => [
            'Neden yaralandı?',
            'Neden bu durum oluştu?',
            'Neden önlem alınmadı?',
            'Neden sistem bunu engellemedi?',
            'Kök neden nedir?',
        ],

        // DÖF (Düzeltici / Önleyici Faaliyet) — kaza raporu içi tablo.
        'dof_onlem_tipleri' => [
            'teknik' => 'Teknik Önlem',
            'yonetsel' => 'Yönetsel Önlem',
            'egitim' => 'Eğitim',
        ],
        'dof_durumlari' => [
            'acik' => 'Açık',
            'devam' => 'Devam Ediyor',
            'tamamlandi' => 'Tamamlandı',
        ],

        'rapor_durumlari' => [
            'taslak' => 'Taslak',
            'tamamlandi' => 'Tamamlandı',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Yangın Güvenliği Genel Durum Değerlendirmesi
    |--------------------------------------------------------------------------
    | Binaların Yangından Korunması Hakkında Yönetmelik. Bina kullanım türü +
    | bölümler + faaliyet/depolama riskleri → bina yangın tehlike sınıfı
    | (düşük / orta / yüksek). Riskler kütüphanesindeki 'sinif' değerleri
    | maksimumu sınıfı belirler; tespit kütüphanesi ile eksikler raporlanır.
    */
    'yangin_guvenligi' => [
        'yapi_durumlari' => ['Betonarme', 'Çelik Konstrüksiyon', 'Yığma', 'Prefabrik', 'Karma'],

        'kullanim_turleri' => [
            'endustriyel' => 'Endüstriyel Yapı (fabrika / atölye / imalathane)',
            'depolama' => 'Depolama Yapısı (antrepo / lojistik / soğuk hava)',
            'ticaret' => 'Ticaret Yapısı (mağaza / AVM / showroom)',
            'buro' => 'Büro Yapısı (ofis / plaza)',
            'toplanma' => 'Toplanma Amaçlı Yapı (yeme-içme / eğlence / spor)',
            'konaklama' => 'Konaklama Amaçlı Yapı (otel / yurt / pansiyon)',
            'saglik' => 'Sağlık Yapısı',
            'egitim' => 'Eğitim Yapısı',
            'karma' => 'Karma Kullanım',
        ],

        'bolum_kutuphanesi' => [
            'Satış / hizmet alanı', 'Ürün veya ham madde deposu', 'Kimyasal depolama alanı',
            'Yüksek raflı depo', 'Üretim / atölye', 'Mutfak / fırın', 'Soğuk hava deposu',
            'Ofis', 'Kazan dairesi', 'Jeneratör / enerji odası', 'Trafo / elektrik odası',
            'Kapalı otopark', 'Atrium / galeri boşluğu', 'Arşiv', 'Boyahane', 'Kaynakhane',
        ],

        // Her riskin bina yangın tehlike sınıfına katkısı (dusuk=1, orta=2, yuksek=3).
        'risk_kutuphanesi' => [
            'Yanıcı / parlayıcı sıvı (tiner, solvent, yakıt)' => 'yuksek',
            'LPG / yanıcı gaz kullanımı veya depolama' => 'yuksek',
            'Aerosol ürün depolama' => 'yuksek',
            'Yanıcı kimyasal madde' => 'yuksek',
            'Oksitleyici / reaktif kimyasal' => 'yuksek',
            'Yanıcı toz (ahşap, un, metal, plastik)' => 'yuksek',
            'Basınçlı gaz tüpleri' => 'orta',
            'Patlayıcı madde / piroteknik' => 'yuksek',
            'Yoğun karton / kağıt / plastik ambalaj' => 'orta',
            'Ahşap / tekstil / kauçuk / köpük stoğu' => 'orta',
            'Yüksek raflı depolama (> 4 m)' => 'yuksek',
            'Mutfak / kızartma / açık alev' => 'orta',
            'Kaynak / taşlama / sıcak çalışma' => 'orta',
            'Boya kabini / solventli yüzey işlem' => 'yuksek',
            'Akü şarj alanı / lityum batarya' => 'orta',
            'Elektrikli araç / iş makinesi şarjı' => 'orta',
            'Kazan dairesi (katı / sıvı / gaz yakıtlı)' => 'orta',
            'Jeneratör (yakıt tanklı)' => 'orta',
            'Trafo / yüksek gerilim odası' => 'orta',
            'Kapalı otopark' => 'orta',
        ],

        'tespit_kutuphanesi' => [
            ['madde' => 'Yangın algılama ve alarm sistemi kurulmalı / kapsamı genişletilmeli', 'oncelik' => 'yuksek'],
            ['madde' => 'Otomatik yağmurlama (sprinkler) sistemi değerlendirilmeli', 'oncelik' => 'yuksek'],
            ['madde' => 'Yangın dolabı / hidrant sayısı ve yerleşimi yönetmeliğe uygun hale getirilmeli', 'oncelik' => 'orta'],
            ['madde' => 'Yangın söndürme cihazları (YSC) her 200 m² için 1 adet ve max 25 m aralıkla yerleştirilmeli', 'oncelik' => 'orta'],
            ['madde' => 'Kaçış yolları ve yönlendirme/acil aydınlatma armatürleri tamamlanmalı', 'oncelik' => 'yuksek'],
            ['madde' => 'Yangın merdiveni / korunumlu kaçış koridoru sağlanmalı', 'oncelik' => 'yuksek'],
            ['madde' => 'Yangın kapıları (EI 60/90) yangın bölmelerine takılmalı, kendinden kapanır olmalı', 'oncelik' => 'orta'],
            ['madde' => 'Kazan dairesi ayrı yangın bölmesi, patlama yükü atma yüzeyi ve gaz dedektörü ile teçhiz edilmeli', 'oncelik' => 'yuksek'],
            ['madde' => 'Yanıcı sıvı / kimyasal ayrı, havalandırmalı ve topraklamalı depoda tutulmalı', 'oncelik' => 'yuksek'],
            ['madde' => 'Elektrik panoları termal kamera ile kontrol edilmeli, pano önleri boş tutulmalı', 'oncelik' => 'orta'],
            ['madde' => 'Paratoner ve topraklama ölçümleri yıllık yaptırılmalı', 'oncelik' => 'orta'],
            ['madde' => 'Sıcak çalışma (kaynak/taşlama) için iş izni ve yangın nöbeti prosedürü uygulanmalı', 'oncelik' => 'orta'],
            ['madde' => 'Yıllık yangın tatbikatı yapılmalı ve kaydı tutulmalı', 'oncelik' => 'orta'],
            ['madde' => 'Acil durum ekipleri (söndürme / kurtarma / ilk yardım / koruma) görevlendirilmeli ve eğitilmeli', 'oncelik' => 'orta'],
            ['madde' => 'Duman tahliye / basınçlandırma sistemi kapalı otopark ve atrium için değerlendirilmeli', 'oncelik' => 'orta'],
        ],

        'tehlike_siniflari' => [
            'dusuk' => 'Düşük Tehlike',
            'orta' => 'Orta Tehlike',
            'yuksek' => 'Yüksek Tehlike',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İşe Dönüş Belgesi — uzun rapor / iş kazası / meslek hastalığı sonrası
    |--------------------------------------------------------------------------
    | İşyeri hekiminin işe dönüşte uygunluk değerlendirmesi ve geçici kısıtlar
    | (6331 s.K. Md.15). Kaza sonrası "İşe Dönüş Belgesi" olarak da kullanılır.
    */
    'ise_donus' => [
        'nedenler' => [
            'is_kazasi' => 'İş Kazası Sonrası',
            'meslek_hastaligi' => 'Meslek Hastalığı Sonrası',
            'hastalik' => 'Hastalık / Uzun Süreli Rapor',
            'ameliyat' => 'Ameliyat / Operasyon Sonrası',
            'dogum' => 'Doğum / Analık İzni Sonrası',
            'diger' => 'Diğer',
        ],
        'uygunluk' => [
            'tam' => 'Eski Görevine Tam Uygun',
            'kisitli' => 'Kısıtlı / Şartlı Uygun (aşağıdaki kısıtlarla)',
            'gecici_gorev' => 'Geçici Başka Göreve Uygun',
            'uygun_degil' => 'Çalışmaya Uygun Değil (istirahat devam)',
        ],
        'kisitlama_kutuphanesi' => [
            'Ağır kaldırma yasağı (10 kg üzeri)',
            'Elle taşıma / itme-çekme kısıtlaması',
            'Yüksekte çalışma yasağı',
            'Kapalı / dar alanda çalışma yasağı',
            'Gece vardiyası muafiyeti',
            'Uzun süre ayakta durma kısıtlaması',
            'Tekrarlı hareket / titreşimli el aleti kısıtlaması',
            'Araç / iş makinesi kullanma kısıtlaması',
            'Gürültülü ortamda çalışma kısıtlaması',
            'Kimyasal / toz maruziyeti olan bölümde çalışma yasağı',
            'Sıcak / soğuk ortamda çalışma kısıtlaması',
            'Fazla mesai muafiyeti',
            'Oturarak / hafif işte çalışma',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Balık Kılçığı (Ishikawa) — 6M Modeli
    |--------------------------------------------------------------------------
    | İş Kazası İnceleme Raporu ve Olay Kayıtları kök neden analizinde ortak
    | kullanılır. Her kategori için tıkla-ekle örnek bulgular.
    */
    'balik_kilcigi' => [
        'kategoriler' => [
            'insan' => [
                'ad' => 'İnsan (Man)',
                'aciklama' => 'Çalışanın bilgi, beceri, davranış ve psikolojik durumuna bağlı faktörler',
                'ornekler' => ['Eğitim eksikliği', 'Yorgunluk / dikkatsizlik', 'Yetkinlik yetersizliği', 'Talimata uymama', 'Acelecilik / iş baskısı'],
            ],
            'makine' => [
                'ad' => 'Makine (Machine)',
                'aciklama' => 'Kullanılan ekipman, araç-gereç ve makinelere ilişkin sorunlar',
                'ornekler' => ['Bakımsız ekipman', 'Koruyucusu olmayan makine', 'Uygunsuz el aleti', 'Arızalı / periyodik kontrolsüz ekipman'],
            ],
            'metot' => [
                'ad' => 'Metot (Method)',
                'aciklama' => 'Çalışma prosedürleri, talimatlar ve iş yapış şekillerine dair eksiklikler',
                'ornekler' => ['Talimat olmaması', 'Yanlış çalışma prosedürü', 'Risk analizindeki eksiklikler', 'İş izni sistemi yok'],
            ],
            'malzeme' => [
                'ad' => 'Malzeme (Material)',
                'aciklama' => 'Kullanılan malzemeler, KKD ve ham maddelere ilişkin sorunlar',
                'ornekler' => ['Standart dışı KKD', 'Kalitesiz ham madde', 'Uygunsuz kimyasal madde', 'KKD temin edilmemiş'],
            ],
            'olcum' => [
                'ad' => 'Ölçüm (Measurement)',
                'aciklama' => 'Ölçüm, kalibrasyon, denetim ve kontrol sistemlerindeki aksaklıklar',
                'ornekler' => ['Hatalı kalibrasyon', 'Yanlış ortam ölçümü (gaz, gürültü)', 'Yetersiz gaz dedektörü', 'Denetim / gözetim eksikliği'],
            ],
            'cevre' => [
                'ad' => 'Çevre / Ortam (Environment)',
                'aciklama' => 'Fiziksel çalışma ortamı ve çevresel koşullara bağlı faktörler',
                'ornekler' => ['Kötü aydınlatma', 'Kaygan zemin', 'Aşırı sıcaklık / soğuk', 'Dağınık / düzensiz çalışma alanı'],
            ],
        ],
        // kök_neden_kategorileri anahtarı → 6M kategorisi (otomatik taşıma).
        'kok_neden_6m' => [
            'insan_faktoru' => 'insan',
            'ekipman_arizasi' => 'makine',
            'kkd_kullanilmamasi' => 'malzeme',
            'egitim_eksikligi' => 'insan',
            'talimat_eksikligi' => 'metot',
            'calisma_ortami' => 'cevre',
            'yonetim_sistemi' => 'olcum',
            'diger' => 'metot',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Olay Kayıtları / Ramak Kala
    |--------------------------------------------------------------------------
    | İş Kazası Raporu'ndan AYRI bir olay defteri: yaralanma olmasa da her İSG
    | olayını (ramak kala, tehlikeli durum/davranış, ilk yardım, maddi hasar,
    | çevre) kaydeder. Her kayıtta sınıflandırma + potansiyel risk (olasılık ×
    | şiddet, 1-25) + 5 Neden (5N) kök neden zinciri + düzeltici faaliyet var;
    | düzeltici faaliyet tek tıkla DÖF Oluştur'a aktarılabilir (dof_aktarim).
    */
    'olay' => [
        'tipler' => [
            'ramak_kala' => 'Ramak Kala (Kazaya Ramak Kalan Olay)',
            'tehlikeli_durum' => 'Tehlikeli Durum',
            'tehlikeli_davranis' => 'Tehlikeli Davranış',
            'ilk_yardim' => 'İlk Yardım Müdahalesi (Kayıtsız)',
            'maddi_hasar' => 'Maddi Hasarlı Olay',
            'cevre_olayi' => 'Çevre Olayı (Dökülme / Sızıntı / Emisyon)',
            'is_kazasi' => 'İş Kazası',
            'meslek_hastaligi_supheli' => 'Meslek Hastalığı Şüphesi',
        ],
        'sonuc_turleri' => [
            'yaralanmasiz' => 'Yaralanmasız / Hasarsız',
            'ilk_yardim' => 'İlk Yardım Gerektiren',
            'tibbi_mudahale' => 'Tıbbi Müdahale / Reçeteli Tedavi',
            'is_gunu_kaybi' => 'İş Günü Kaybına Yol Açan',
            'kalici_hasar' => 'Kalıcı Hasar / Uzuv Kaybı',
            'olum' => 'Ölümle Sonuçlanan',
        ],
        'etkilenen_kategorileri' => [
            'calisan' => 'Şirket Çalışanı',
            'taseron' => 'Taşeron / Alt İşveren Çalışanı',
            'ziyaretci' => 'Ziyaretçi / 3. Şahıs',
            'ekipman' => 'Ekipman / Makine',
            'malzeme' => 'Malzeme / Ürün',
            'cevre' => 'Çevre',
            'uretim' => 'Üretim / İş Sürekliliği',
        ],
        // Potansiyel risk — "olabilecek en kötü sonuç" değerlendirilir (ramak
        // kalada gerçekleşen sonuç yok; önemli olan tekrarında ne olabileceği).
        'olasiliklar' => [
            'cok_dusuk' => 'Çok Düşük (pratikte imkânsıza yakın)',
            'dusuk' => 'Düşük (nadiren olabilir)',
            'orta' => 'Orta (zaman zaman olabilir)',
            'yuksek' => 'Yüksek (sıklıkla olabilir)',
            'cok_yuksek' => 'Çok Yüksek (kaçınılmaz)',
        ],
        'siddetler' => [
            'cok_hafif' => 'Çok Hafif (ilk yardım, iş kaybı yok)',
            'hafif' => 'Hafif (ayakta tedavi, kısa istirahat)',
            'orta' => 'Orta (iş günü kaybı, geçici iş göremezlik)',
            'ciddi' => 'Ciddi (uzun tedavi / kalıcı sınırlı hasar)',
            'cok_ciddi' => 'Çok Ciddi (ölüm / sürekli iş göremezlik)',
        ],

        // Kaydın inceleme durumu (defter + Excel "Durum" sütunu).
        'durumlar' => [
            'acik' => 'Açık',
            'incelemede' => 'İncelemede',
            'kapandi' => 'Kapandı',
        ],

        // Sınıflandırma — olaya yol açan tehlike kaynağı (isgsuite "Sınıflandırma").
        'siniflandirmalar' => [
            'yuksekte_calisma' => 'Yüksekte çalışma / düşme',
            'kayma_takilma' => 'Kayma / takılma / aynı seviyede düşme',
            'malzeme_dusmesi' => 'Malzeme / cisim düşmesi',
            'makine' => 'Makine / hareketli parça',
            'el_aleti' => 'El aleti / elektrikli el aleti',
            'is_makinesi' => 'Forklift / iş makinesi / araç',
            'kaldirma' => 'Kaldırma ekipmanı (vinç / caraskal)',
            'elektrik' => 'Elektrik',
            'kaynak_dumani' => 'Kaynak dumanı',
            'sicak_calisma' => 'Sıcak çalışma / kaynak / kesme',
            'kimyasal' => 'Kimyasal madde',
            'toz' => 'Toz',
            'gurultu_titresim' => 'Gürültü / titreşim',
            'basincli_kap' => 'Basınçlı kap / tesisat',
            'yangin_patlama' => 'Yangın / patlama',
            'kapali_alan' => 'Kapalı alan',
            'ergonomi' => 'Ergonomi / elle taşıma',
            'termal' => 'Termal konfor (sıcak / soğuk)',
            'biyolojik' => 'Biyolojik etken',
            'psikososyal' => 'Psikososyal / şiddet',
            'trafik' => 'Trafik / yol',
            'diger' => 'Diğer',
        ],

        // Olay etkileri ve sonuç göstergeleri — hızlı değerlendirme kutucukları.
        'etkiler' => [
            'yaralanma' => ['ad' => 'Yaralanma oldu', 'aciklama' => 'Fiziksel yaralanma oluştuysa işaretleyin'],
            'saglik_sikayeti' => ['ad' => 'Sağlık şikayeti', 'aciklama' => 'Ağrı, rahatsızlık veya semptom varsa'],
            'tibbi_mudahale' => ['ad' => 'Tıbbi müdahale', 'aciklama' => 'İlk yardım veya medikal destek verildiyse'],
            'is_goremezlik' => ['ad' => 'İş göremezlik raporu', 'aciklama' => 'Rapor veya istirahat düzenlendiyse'],
            'ekipman_hasari' => ['ad' => 'Ekipman hasarı', 'aciklama' => 'Makine, araç veya ekipmanda zarar varsa'],
            'ramak_kala' => ['ad' => 'Farklı gelişse yaralanma olurdu', 'aciklama' => 'Ramak kala niteliği taşıyorsa işaretleyin'],
        ],

        'risk_analizi_durumlari' => [
            'evet' => 'Evet — risk analizinde var',
            'kismen' => 'Kısmen — güncellenmeli',
            'hayir' => 'Hayır — risk analizine eklenmeli',
        ],

        'acil_durum_iliskileri' => [
            'yok' => 'Acil durumla ilişkisi yok',
            'yangin' => 'Yangın',
            'patlama' => 'Patlama',
            'elektrik' => 'Elektrik kaynaklı acil durum',
            'kimyasal' => 'Kimyasal sızıntı / dökülme',
            'gaz' => 'Gaz kaçağı / zehirlenme',
            'deprem' => 'Deprem / doğal afet',
            'sel' => 'Sel / su baskını',
            'kapali_alan' => 'Kapalı alanda mahsur kalma',
            'ilk_yardim' => 'İlk yardım gerektiren acil durum',
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
            ['baslik' => 'Forklift Kullanma Talimatı', 'kategori' => 'is_makineleri', 'aciklama' => 'Forklift operatörlerinin uyması gereken güvenli kullanım esasları.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Çelik burunlu iş ayakkabısı',
                2 => 'Reflektörlü yelek',
            ], 'maddeler' => [
                'Forkliftı yalnızca geçerli forklift operatörlük belgesi olan personel kullanabilir.',
                'Kullanım öncesi fren, korna, ikaz lambası, direksiyon ve çatal bağlantıları günlük kontrol formuna göre kontrol edilir.',
                'Yük, kapasite levhasında belirtilen sınırı ve yük merkezini aşmayacak şekilde taşınır.',
                'Yük kaldırılmışken forkliftin altından veya yakınından yaya geçişine izin verilmez.',
                'Dönemeçlerde ve kavşaklarda korna çalınır, hız yayaların güvenle kaçabileceği seviyede tutulur.',
                'Emniyet kemeri her zaman takılı olarak sürüş yapılır; sürücü koltukta olmadan forklift hareket ettirilmez.',
                'Forklift, park edildiğinde çatallar yere indirilir, kontak kapatılır ve el freni çekilir.',
            ]],
            ['baslik' => 'Torna Tezgahı Kullanma Talimatı', 'kategori' => 'is_makineleri', 'aciklama' => 'Torna tezgahında güvenli talaşlı imalat çalışmaları için temel kurallar.', 'kkdler' => [
                0 => 'Koruyucu gözlük',
                1 => 'Çelik burunlu iş ayakkabısı',
                2 => 'Vücuda oturan tulum',
            ], 'maddeler' => [
                'Torna tezgahını yalnızca eğitim almış ve yetkilendirilmiş personel kullanabilir.',
                'Çalışma öncesi ayna anahtarı tezgahtan çıkarılır, koruyucular yerinde ve sağlam olduğu kontrol edilir.',
                'Kravat, bilezik, eldiven gibi dönen parçaya takılabilecek aksesuarlar çalışma sırasında kullanılmaz; saç toplanır.',
                'Talaş temizliği yalnızca tezgah dururken ve fırça/kanca ile yapılır, elle veya basınçlı hava ile yapılmaz.',
                'İş parçası ayna/punta arasına sağlam bağlanmadan tezgah çalıştırılmaz.',
                'Tezgah çalışırken koruyucu siperlik açık bırakılmaz, göz koruma gözlüğü sürekli takılı tutulur.',
                'Acil durum düğmesinin yeri bilinir, anormal ses/titreşimde derhal durdurulur ve bakım ekibine bildirilir.',
            ]],
            ['baslik' => 'Hidrolik Pres Makinesi Talimatı', 'kategori' => 'is_makineleri', 'aciklama' => 'Hidrolik/eksantrik pres makinelerinde kalıp ve baskı işlemleri.', 'kkdler' => [
                0 => 'Koruyucu gözlük',
                1 => 'Çelik burunlu iş ayakkabısı',
                2 => 'Anti-vibrasyon eldiveni',
            ], 'maddeler' => [
                'Pres, çift el kumandası veya ışık perdesi gibi koruyucu tertibatlar devre dışıyken çalıştırılmaz.',
                'Kalıp değişimi ve ayarı yalnızca enerji kesilip LOTO (kilitle-etiketle) uygulandıktan sonra yapılır.',
                'El veya herhangi bir uzuv, kalıp bölgesine kesinlikle sokulmaz; parça yerleştirme/alma özel aparatlarla yapılır.',
                'Çift el kumandası her iki elin aynı anda ve tam basılmasını gerektirir; tek elle veya bantlayarak devre dışı bırakılmaz.',
                'Makinede anormal ses, sızıntı veya gecikmeli tepki fark edilirse derhal durdurulur ve yetkiliye bildirilir.',
                'Vardiya başı ve sonu kontrol formu doldurulur, acil stop düğmesinin çalıştığı test edilir.',
            ]],
            ['baslik' => 'Spiral (Avuç İçi) Taşlama Makinesi Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Spiral taşlama makinesinin güvenli kullanımı için kurallar.', 'kkdler' => [
                0 => 'Yüz siperliği',
                1 => 'Koruyucu gözlük',
                2 => 'Anti-vibrasyon eldiveni',
            ], 'maddeler' => [
                'Taşlama diski, yapılacak işe ve malzemeye uygun tip ve azami devirde seçilir; disk üzerindeki devir sınırı aşılmaz.',
                'Disk koruyucu siperi kesinlikle sökülmez veya devre dışı bırakılmaz.',
                'Yeni takılan disk, çalıştırmadan önce en az 30 saniye boşta test edilir; kenarında durup çalıştırılır.',
                'Kıvılcım sıçrama yönünde yanıcı madde, başka çalışan veya geçiş yolu bulunmadığından emin olunur.',
                'Makine iki elle sıkıca tutulur, disk sıkışmasına karşı geri tepmeye (kickback) hazırlıklı olunur.',
                'Kullanım sonrası makine kapatılıp disk tamamen durmadan yere bırakılmaz.',
            ]],
            ['baslik' => 'Sütunlu/El Matkap Kullanma Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Sütunlu ve el matkabı ile güvenli delik delme operasyonu.', 'kkdler' => [
                0 => 'Koruyucu gözlük',
                1 => 'Çelik burunlu iş ayakkabısı',
                2 => 'Vücuda oturan tulum',
            ], 'maddeler' => [
                'İş parçası, işkence veya uygun bağlama aparatı ile sabitlenmeden delinmez; elde tutularak delme yapılmaz.',
                'Matkap ucu, delinecek malzemeye ve çapa uygun seçilir, körelmiş uçlar kullanılmaz.',
                'Eldiven, sütunlu matkapta dönen mile dolanma riski nedeniyle takılmaz (el matkabında iş güvenliği eldiveni kullanılabilir).',
                'Talaş temizliği fırça ile yapılır, elle veya üfleyerek temizlenmez.',
                'Devir hızı, malzeme ve çapa uygun seçilir; aşırı baskı uygulanmaz.',
            ]],
            ['baslik' => 'Elektrikli Kırıcı/Delici Kullanma Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Kırıcı tip elektrikli delici makinelerin güvenli kullanımı.', 'kkdler' => [
                0 => 'Anti-vibrasyon eldiveni',
                1 => 'Koruyucu gözlük',
                2 => 'FFP3 toz maskesi',
            ], 'maddeler' => [
                'Kırıcı kullanılmadan önce kırılacak yüzeyin altında/içinde elektrik, su, doğalgaz tesisatı olmadığı proje üzerinden teyit edilir.',
                'Uzun süreli kullanımda titreşime maruziyeti azaltmak için düzenli mola verilir (el-kol titreşim yönetmeliği sınırları gözetilir).',
                'Toz oluşan ortamlarda su püskürtme veya lokal aspirasyon ile toz bastırma uygulanır.',
                'Kırıcı, iş bitiminde veya bırakılırken enerjisi/hava beslemesi kesilir, uç dik pozisyonda ve destekli bırakılır.',
                'Kulak koruyucu kullanılır, gürültü seviyesi yüksek alanlarda çalışma süresi sınırlandırılır.',
            ]],
            ['baslik' => 'El Aletleri (Çekiç, Tornavida, Pense) Talimatı', 'kategori' => 'el_aletleri', 'aciklama' => 'Genel el aletlerinin güvenli kullanımı ve bakımı.', 'kkdler' => [
                0 => 'Mekanik dirençli eldiven',
                1 => 'Koruyucu gözlük',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'El aletleri yalnızca üretildiği amaç için kullanılır; örneğin tornavida keski/kaldıraç olarak kullanılmaz.',
                'Sapı çatlamış, gevşemiş veya kafası deforme olmuş aletler kullanılmadan önce değiştirilir/tamir edilir.',
                'Alet, kullanılmadığı zamanlarda uygun bir kılıf/kutuda, düşme veya yaralanmaya sebep olmayacak şekilde muhafaza edilir.',
                'Keskin uçlu aletler taşınırken uç kısmı korumaya alınır, cepte açık taşınmaz.',
                'Elektrikli el aletlerinin kablo ve fişleri periyodik olarak kontrol edilir, hasarlı olanlar kullanılmaz.',
            ]],
            ['baslik' => 'Kimyasal Madde Depolama ve Kullanma Talimatı', 'kategori' => 'kimyasal_madde', 'aciklama' => 'Tehlikeli kimyasalların depolanması ve kullanılması.', 'kkdler' => [
                0 => 'Kimyasala dayanıklı eldiven',
                1 => 'Kimyasal gaz maskesi',
                2 => 'Yarım/tam yüz maske + uygun filtre',
            ], 'maddeler' => [
                'Her kimyasal için Güvenlik Bilgi Formu (GBF) işyerinde bulundurulur ve çalışanlar bilgilendirilir.',
                'Birbiriyle reaksiyona girebilecek kimyasallar (asit-baz, oksitleyici-yanıcı vb.) ayrı bölmelerde depolanır.',
                'Kimyasal kaplar orijinal etiketiyle saklanır; başka kaplara aktarılıyorsa içerik ve tehlike bilgisi etiketlenir.',
                'Kimyasal kullanılan alanlarda göz duşu/acil duş erişilebilir ve çalışır durumda bulundurulur.',
                'Dökülme durumunda GBF\'de belirtilen müdahale prosedürü uygulanır, uygun absorban malzeme hazır bulundurulur.',
                'Kimyasal depolarında yeterli havalandırma sağlanır, ateşleme kaynağı bulundurulmaz.',
            ]],
            ['baslik' => 'Boya ve Cila Uygulama Talimatı', 'kategori' => 'kimyasal_madde', 'aciklama' => 'Endüstriyel boya, vernik ve cila uygulamalarında güvenli çalışma.', 'kkdler' => [
                0 => 'Yarım/tam yüz organik gaz maskesi',
                1 => 'Kimyasal dirençli eldiven',
                2 => 'Koruyucu gözlük',
            ], 'maddeler' => [
                'Boya uygulaması, yeterli havalandırmalı boya kabini veya açık alanda yapılır; kapalı, havasız ortamda boya yapılmaz.',
                'Solvent bazlı boya kullanılan alanda ateş, kaynak, sigara gibi ateşleme kaynaklarına kesinlikle izin verilmez.',
                'Boya artıkları ve solvent bezleri kendiliğinden tutuşma riskine karşı kapalı metal kaplarda biriktirilir.',
                'Uygulama öncesi ve sonrası eller yıkanır, KKD\'ler iş dışı ortama taşınmaz.',
                'Statik elektrik birikimine karşı boya kabini ve ekipmanları topraklanır.',
            ]],
            ['baslik' => 'Asbest ile Çalışma Talimatı', 'kategori' => 'kimyasal_madde', 'aciklama' => 'Asbest içeren malzemelerin sökümü ve bertarafında güvenli çalışma.', 'kkdler' => [
                0 => 'Tam yüz maskesi + P3 filtre',
                1 => 'Tek kullanımlık tulum',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Asbest söküm işleri yalnızca yetki belgeli firma/personel tarafından, Asbest Yönetmeliği\'ne uygun olarak yapılır.',
                'Çalışma alanı izole edilir, negatif basınçlı hava filtrasyon sistemi kullanılır, alan dışına toz yayılımı önlenir.',
                'Asbest malzemeler kırılmadan, ıslatılarak ve bütün halinde sökülmeye çalışılır; taşlama/kesme ile toz oluşturulmaz.',
                'Atıklar sızdırmaz, etiketli özel torbalarda toplanır ve lisanslı bertaraf tesisine gönderilir.',
                'Çalışanlar periyodik akciğer/solunum fonksiyon testine tabi tutulur ve maruziyet kaydı tutulur.',
            ]],
            ['baslik' => 'Elektrik Panosunda Çalışma Talimatı', 'kategori' => 'elektrik_isleri', 'aciklama' => 'Elektrik panolarında bakım/onarım yapacak yetkili personel için talimat.', 'kkdler' => [
                0 => 'Elektrik yalıtımlı eldiven',
                1 => 'Yüz siperliği',
                2 => 'Yalıtkan bot',
            ], 'maddeler' => [
                'Pano üzerinde çalışma öncesi enerji kesilir, kilitleme-etiketleme (LOTO) uygulanır ve gerilim ölçülerek kesildiği teyit edilir.',
                'Çalışma yalnızca elektrik iç tesisat yönetmeliğine göre yetkilendirilmiş elektrikçi tarafından yapılır.',
                'Gerilim altında (canlı) çalışma zorunlu ise ayrı özel talimat ve izin prosedürü uygulanır, uygun yalıtkan ekipman kullanılır.',
                'Pano önünde yalıtkan paspas bulundurulur, ıslak zeminde çalışılmaz.',
                'İş bitiminde tüm koruyucu kapaklar takılır, enerji ancak kontrol tamamlandıktan sonra verilir.',
            ]],
            ['baslik' => 'Jeneratör Kullanma Talimatı', 'kategori' => 'elektrik_isleri', 'aciklama' => 'Dizel/benzinli jeneratörlerin güvenli çalıştırılması ve bakımı.', 'kkdler' => [
                0 => 'Kulak tıkacı/kulaklık',
                1 => 'Yalıtkan eldiven',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Jeneratör iyi havalandırılan, kapalı olmayan bir alanda çalıştırılır; egzoz gazı yaşam alanlarından uzak tutulur.',
                'Yakıt ikmali yalnızca motor durdurulmuş ve soğumuş haldeyken yapılır.',
                'Jeneratör, şebeke ile paralel çalışacaksa mutlaka uygun transfer switch kullanılır; geri besleme riskine karşı önlem alınır.',
                'Topraklama bağlantısı kontrol edilmeden jeneratör devreye alınmaz.',
                'Periyodik bakım (yağ, filtre, akü) üretici talimatına göre takip edilir ve kayıt altına alınır.',
            ]],
            ['baslik' => 'Kablo Döşeme ve Bağlantı Talimatı', 'kategori' => 'elektrik_isleri', 'aciklama' => 'Kablo çekimi, döşeme ve bağlantı işlerinde güvenli çalışma.', 'kkdler' => [
                0 => 'Yalıtkan eldiven',
                1 => 'Koruyucu gözlük',
                2 => 'Yalıtkan ayakkabı',
            ], 'maddeler' => [
                'Kablo döşenecek güzergahta önceden mevcut enerji hattı/tesisat olup olmadığı kontrol edilir.',
                'Yer altı/yer üstü geçici kablo döşemelerinde mekanik hasara karşı koruma (kablo kanalı, tabela) sağlanır.',
                'Bağlantılar yalnızca uygun boyut ve tipte klemens/konnektör ile yapılır; bantla izole edilmiş geçici bağlantı bırakılmaz.',
                'Islak/nemli ortamda çalışırken IP korumalı ekipman ve kaçak akım rölesi (RCD) kullanılır.',
                'Kablo makarası taşınırken/açılırken ezilme ve çekme risklerine dikkat edilir.',
            ]],
            ['baslik' => 'Yükseklerde Çalışma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => '2 metre ve üzeri yükseklikte yapılacak çalışmalar için temel güvenlik kuralları.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri',
                1 => 'Çift kancalı şok emici lanyard',
                2 => 'Çene bağlantılı baret',
            ], 'maddeler' => [
                'Mümkün olduğunca toplu koruma (korkuluk, ağ) tercih edilir; kişisel düşme koruma sistemi son çare olarak kullanılır.',
                'Emniyet kemeri, sağlam bir ankraj noktasına, omuz hizasının üzerinde bir noktaya bağlanır.',
                'Çalışma öncesi kemer, lanyard ve karabina gözle muayene edilir; hasarlı ekipman kullanılmaz.',
                'İki metre ve üzeri yükseklikte, kenarında korkuluk olmayan her noktada düşme koruma sistemi zorunludur.',
                'Rüzgar, yağış gibi olumsuz hava koşullarında yükseklik çalışması durdurulur.',
                'Yükseklikte çalışma izni (İş İzin Formu) alınmadan çalışmaya başlanmaz.',
            ]],
            ['baslik' => 'Çatı Üzerinde Çalışma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => 'Çatı bakım, onarım ve izolasyon işlerinde güvenli çalışma.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri',
                1 => 'Çift kancalı lanyard',
                2 => 'Çene bağlantılı baret',
            ], 'maddeler' => [
                'Çatıya çıkmadan önce taşıyıcı yapının yükü kaldırabileceği ve yürünebilir olduğu kontrol edilir.',
                'Işıklık (aydınlatma) ve kırılgan yüzeyler işaretlenir, üzerine basılmaz; gerekiyorsa geçiş platformu kurulur.',
                'Çatı kenarına güvenlik ağı veya korkuluk kurulamıyorsa yaşam hattı/ankraj noktası kullanılır.',
                'Çatı işleri sırasında alt bölgeye erişim engellenir, düşen cisimlere karşı bariyer/uyarı bandı çekilir.',
                'Islak, buzlu veya rüzgarlı havada çatı çalışması yapılmaz.',
            ]],
            ['baslik' => 'Portatif Merdiven Kullanma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => 'A tipi, uzatma ve tek ayaklı portatif merdivenlerin güvenli kullanımı.', 'kkdler' => [
                0 => 'Kaymaz iş ayakkabısı',
                1 => 'Baret',
                2 => 'Mekanik eldiven',
            ], 'maddeler' => [
                'Merdiven, yapılacak iş ve erişilecek yükseklik için uygun tip ve uzunlukta seçilir.',
                'Merdiven sağlam, düz ve kaymaz bir zemine, 4\'e 1 açı kuralına uygun (dikey 4 birime karşı 1 birim taban açıklığı) kurulur.',
                'Merdivenin en üst iki basamağına çıkılmaz, üç nokta teması (iki el bir ayak veya iki ayak bir el) korunur.',
                'Merdiven üzerinde ağır malzeme taşınmaz, çalışma öncesi ellerin serbest olması için malzeme kemer/torbayla taşınır.',
                'Kullanım öncesi basamak, ayak tamponu ve kilit mekanizması hasar yönünden kontrol edilir.',
            ]],
            ['baslik' => 'Makaslı/Eklemli Platform (Manlift) Kullanma Talimatı', 'kategori' => 'yukseklerde_calisma', 'aciklama' => 'Yükseltilebilir çalışma platformlarının güvenli kullanımı.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri',
                1 => 'Çene bağlantılı baret',
                2 => 'Kısa lanyard (platform içi)',
            ], 'maddeler' => [
                'Platform yalnızca eğitim almış ve operatör belgesi olan personel tarafından kullanılır.',
                'Kullanım öncesi günlük kontrol formu doldurulur; acil indirme, alarm ve emniyet kilitleri test edilir.',
                'Platform üzerinde emniyet kemeri sabit noktaya bağlanır; platforma tırmanarak boy uzatılmaz.',
                'Zemin eğimi, denge ayakları ve azami yük sınırı üretici talimatına göre kontrol edilir.',
                'Havai hat, bina çıkıntısı gibi çarpışma riski olan noktalara karşı güvenli mesafe korunur.',
            ]],
            ['baslik' => 'Kapalı Alanda Çalışma Talimatı', 'kategori' => 'kapali_alan', 'aciklama' => 'Tank, depo, kazan gibi kapalı alanlarda güvenli giriş ve çalışma.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri + kurtarma halatı',
                1 => 'Solunum cihazı',
                2 => 'Çoklu gaz dedektörü',
            ], 'maddeler' => [
                'Kapalı alana girmeden önce Çalışma İzin Formu alınır ve gözcü (nöbetçi) görevlendirilir.',
                'Giriş öncesi ve çalışma boyunca sürekli gaz ölçümü yapılır (oksijen, patlayıcı gaz, zehirli gaz).',
                'Kapalı alan girişinden önce izole edilebilen enerji kaynakları (elektrik, buhar, akışkan hattı) kilitlenir/etiketlenir.',
                'Gözcü, giren personelle sürekli iletişim halinde olur; asla kapalı alana girmeden dışarıdan müdahale eder.',
                'Acil tahliye ve kurtarma planı, giriş öncesi tüm ekiple paylaşılır; kurtarma ekipmanı hazır bulundurulur.',
                'Oksijen seviyesi %19,5 altına düşerse veya zehirli/patlayıcı gaz tespit edilirse alan derhal terk edilir.',
            ]],
            ['baslik' => 'Silo ve Tank İçi Temizlik Talimatı', 'kategori' => 'kapali_alan', 'aciklama' => 'Silo, tank ve depo içi temizlik çalışmalarında güvenli giriş ve çalışma.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri + kurtarma halatı',
                1 => 'Solunum koruma',
                2 => 'Çoklu gaz dedektörü',
            ], 'maddeler' => [
                'Silo/tank temizliği öncesi malzeme akışı tamamen durdurulur, besleme hatları kilitlenir.',
                'Köprüleşme (asma) riski olan siloda üstten girilmez; alt boşaltım ile malzemenin serbest akması sağlanmadan içeri girilmez.',
                'Toz patlaması riskine karşı statik elektrik boşaltımı yapılır, ateşleme kaynağı bulundurulmaz.',
                'Havalandırma (mekanik) sağlanmadan ve gaz ölçümü tamamlanmadan tanka girilmez.',
                'Kurtarma tripodu/vinç sistemi giriş noktasına kurulur ve giriş boyunca hazır bekletilir.',
            ]],
            ['baslik' => 'Oksijen-Asetilen Kaynak/Kesme Talimatı', 'kategori' => 'kaynak_kesme', 'aciklama' => 'Gazaltı kaynak ve oksi-asetilen kesme işleri için güvenli çalışma kuralları.', 'kkdler' => [
                0 => 'Kaynak maskesi',
                1 => 'Kaynakçı eldiveni',
                2 => 'Krom deri önlük ve tozluk',
            ], 'maddeler' => [
                'Tüpler dik konumda, sağlam sabitlenmiş, oksijen ve asetilen ayrı ayrı zincirlenmiş olarak bulundurulur.',
                'Çalışma öncesi hortum, regülatör ve geri tepme valfleri sızıntı ve hasar yönünden kontrol edilir.',
                'Kaynak/kesme alanında yanıcı madde bulunmaz; en az 10 metre çevre yangına karşı temizlenir.',
                'Kaynak yapılan alanda yangın söndürme tüpü ve yangın gözcüsü hazır bulundurulur, iş bitiminden sonra da bir süre alan kontrol edilir.',
                'Tüp değişiminde valfler yavaşça açılır, yağlı/gres\'li ellerle oksijen valfine dokunulmaz.',
            ]],
            ['baslik' => 'Elektrik Ark Kaynağı (MIG/MAG/TIG) Talimatı', 'kategori' => 'kaynak_kesme', 'aciklama' => 'Elektrik ark kaynağı (MIG/MAG/TIG) ile güvenli çalışma kuralları.', 'kkdler' => [
                0 => 'Otomatik kararmalı kaynak maskesi',
                1 => 'Kaynakçı eldiveni',
                2 => 'Alev geciktirici tulum',
            ], 'maddeler' => [
                'Kaynak makinesi ve kablolar topraklanmış, yalıtımı sağlam olmalıdır; hasarlı kablo kullanılmaz.',
                'Kaynak dumanı maruziyetine karşı lokal aspirasyon veya iyi havalandırma sağlanır.',
                'Kaynak ışığına karşı çevredeki diğer çalışanlar paravan/perde ile korunur.',
                'Kaynak alanı yanıcı maddelerden arındırılır, gerekiyorsa ateş gözcüsü görevlendirilir.',
                'Kaynak sonrası sıcak parçalar işaretlenir, soğumadan taşınmaz veya dokunulmaz.',
            ]],
            ['baslik' => 'Plazma Kesim Talimatı', 'kategori' => 'kaynak_kesme', 'aciklama' => 'Plazma ark kesim makinelerinin güvenli kullanımı.', 'kkdler' => [
                0 => 'Kaynak/kesim gözlüğü',
                1 => 'Kaynakçı eldiveni',
                2 => 'Yanmaz önlük',
            ], 'maddeler' => [
                'Kesim öncesi malzemenin altında yanıcı madde veya kablo/boru bulunmadığı kontrol edilir.',
                'Cihaz topraklaması ve hava/gaz besleme basıncı üretici talimatına göre ayarlanır.',
                'Kesim sırasında oluşan metal sıçrantısına (spatter) karşı çevre korumaya alınır.',
                'Kesilen kenarlar sıcak olduğundan soğumadan tutulmaz, uygun maşa/pense kullanılır.',
            ]],
            ['baslik' => 'Manuel Transpalet Kullanma Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Manuel transpalet ile yük taşıma ve istifleme kuralları.', 'kkdler' => [
                0 => 'Çelik burunlu iş ayakkabısı',
                1 => 'Mekanik dirençli eldiven',
                2 => 'Reflektörlü yelek',
            ], 'maddeler' => [
                'Yük, palet üzerinde dengeli yerleştirilir; transpalet kapasitesi aşılmaz.',
                'Transpalet iterek kullanılır, çekerek veya koşarak hareket ettirilmez.',
                'Rampa ve eğimli zeminlerde transpalet, yük aşağı yönde iken kontrollü indirilir.',
                'Ayak ve el sıkışması riskine karşı çatal altına ve tekerlek yakınına el/ayak sokulmaz.',
                'Görüşü kapatan yüksek yükler geriye doğru çekilerek taşınır.',
            ]],
            ['baslik' => 'İstif Makinesi (Reach Truck) Kullanma Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Depo içi dar koridor istif makinesi ile güvenli yük taşıma ve istifleme.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Çelik burunlu iş ayakkabısı',
                2 => 'Reflektörlü yelek',
            ], 'maddeler' => [
                'Reach truck yalnızca operatörlük belgesi olan personel tarafından kullanılır.',
                'Yüksek raftan indirme/yükleme yapılırken çatal, raf hizasına dik ve tam ortalanmış olmalıdır.',
                'Görüş açısı kısıtlıysa yardımcı personel yönlendirmesiyle veya kamera sistemiyle manevra yapılır.',
                'Koridor içinde azami hız sınırına uyulur, dar koridorlarda yaya bulunmadığı teyit edilmeden ilerlenmez.',
                'Yük kaldırılmış vaziyette koridor değiştirilmez; taşıma alçak pozisyonda yapılır.',
            ]],
            ['baslik' => 'Depo ve Raf Sistemleri Güvenlik Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Depo raf sistemlerinin güvenli kullanımı, istifleme ve bakım kuralları.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Çelik burunlu iş ayakkabısı',
                2 => 'Mekanik eldiven',
            ], 'maddeler' => [
                'Raflar üzerindeki azami yük kapasitesi levhaları okunur ve aşılmaz.',
                'Ağır ürünler alt raflara, hafif ürünler üst raflara yerleştirilir.',
                'Hasarlı (eğik, çatlak, paslı) raf ayağı/kirişi tespit edilirse derhal kullanım dışı bırakılıp bildirilir.',
                'Raflar arası koridor genişliği kullanılan ekipmana (transpalet/forklift) uygun bırakılır, daraltılmaz.',
                'Periyodik raf denetimi (görsel muayene) yapılır ve kayıt altına alınır.',
            ]],
            ['baslik' => 'Elle Malzeme Taşıma (Manuel Elleçleme) Talimatı', 'kategori' => 'tasima_depolama', 'aciklama' => 'Elle kaldırma ve taşıma işlerinde bel/sırt yaralanmalarını önlemeye yönelik kurallar.', 'kkdler' => [
                0 => 'Bel koruma kemeri (gerekirse)',
                1 => 'Mekanik dirençli eldiven',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Kaldırma sırasında sırt düz tutulur, diz bükülerek kaldırma yapılır; bel bükülerek kaldırma yapılmaz.',
                'Yük, vücuda mümkün olduğunca yakın tutularak taşınır.',
                'Tek başına kaldırılamayacak ağırlıktaki yükler iki kişiyle veya mekanik yardımla taşınır.',
                'Ani dönüş hareketinden kaçınılır, yön değiştirmek için ayaklarla dönülür.',
                'Tekrarlı kaldırma işlerinde düzenli mola verilir, mümkün olduğunda mekanik kaldırma ekipmanı tercih edilir.',
            ]],
            ['baslik' => 'Mutfak Bıçak ve Kesim Aletleri Talimatı', 'kategori' => 'mutfak_yemekhane', 'aciklama' => 'Mutfak personeli için bıçak, kıyma makinesi ve kesim aletlerinin güvenli kullanımı.', 'kkdler' => [
                0 => 'Kesilmeye dirençli eldiven',
                1 => 'Kaymaz iş ayakkabısı',
                2 => 'Mutfak önlüğü',
            ], 'maddeler' => [
                'Bıçaklar keskin tutulur; körelmiş bıçak daha fazla kuvvet gerektirdiğinden kayma riskini artırır.',
                'Bıçak taşınırken ucu aşağı ve vücuttan uzak tutulur, koşarak taşınmaz.',
                'Kıyma makinesi gibi ekipmanlara elle malzeme itilmez, iticiler kullanılır; koruyucular çıkarılmaz.',
                'Bıçaklar yıkanmak üzere bulaşık suyuna bırakılmaz, elle yıkanır ve görünür şekilde tutulur.',
                'Kullanılmayan bıçaklar bıçaklıkta veya belirlenmiş güvenli yerde muhafaza edilir.',
            ]],
            ['baslik' => 'Endüstriyel Fırın ve Ocak Kullanma Talimatı', 'kategori' => 'mutfak_yemekhane', 'aciklama' => 'Yemekhane ve endüstriyel fırın, ocak ve buharlı cihazların güvenli kullanımı.', 'kkdler' => [
                0 => 'Sıcak iş eldiveni',
                1 => 'Kaymaz iş ayakkabısı',
                2 => 'Mutfak önlüğü',
            ], 'maddeler' => [
                'Ocak/fırın açılmadan önce gaz kaçağı olmadığı kontrol edilir, ortam havalandırılır.',
                'Sıcak yüzeylere ve kaplara çıplak elle dokunulmaz, ısıya dayanıklı eldiven kullanılır.',
                'Yağ yangınına su ile müdahale edilmez; ocak üstü yangın söndürme battaniyesi/CO2 tüpü hazır bulundurulur.',
                'Fritöz gibi cihazlarda yağ seviyesi belirlenen sınırın üzerine çıkarılmaz.',
                'Vardiya sonunda tüm ısıtıcı cihazlar kapatılır ve gaz vanası kontrol edilir.',
            ]],
            ['baslik' => 'Ofis ve Ekran Önü Çalışma Talimatı', 'kategori' => 'ofis_buro', 'aciklama' => 'Ekran karşısında uzun süre çalışan ofis personeli için ergonomi kuralları.', 'kkdler' => [
                0 => 'Bilgisayar gözlüğü',
                1 => 'Bilek desteği',
                2 => 'Bel/sırt desteği',
            ], 'maddeler' => [
                'Ekran, göz hizasının hafif altında ve gözden yaklaşık 50-70 cm mesafede konumlandırılır.',
                'Her 50-60 dakikada bir kısa mola verilerek gözler ekrandan uzağa (en az 20 saniye, 6 metre öteye) odaklanır.',
                'Sandalye yüksekliği ayarlanarak ayaklar tam yere basacak, dizler 90 derece açıda olacak şekilde oturulur.',
                'Klavye ve fare kullanımında bilekler nötr (düz) pozisyonda tutulur, aşırı bükülmeden kaçınılır.',
                'Uzun süreli oturmanın önüne geçmek için gün içinde ayağa kalkıp kısa yürüyüşler yapılır.',
            ]],
            ['baslik' => 'Yazıcı ve Fotokopi Makinesi Güvenlik Talimatı', 'kategori' => 'ofis_buro', 'aciklama' => 'Lazer yazıcı, fotokopi makinesi ve tarayıcıların güvenli kullanımı.', 'kkdler' => [
                0 => 'Toner değişiminde nitril eldiven',
                1 => 'FFP2 maske',
                2 => 'Koruyucu gözlük',
            ], 'maddeler' => [
                'Toner değişimi sırasında toz solunmaması için eldiven ve gerekiyorsa maske kullanılır.',
                'Kağıt sıkışması giderilirken cihaz kapatılıp sıcak yüzeylere (fuser ünitesi) dokunulmaz.',
                'Cihazlar iyi havalandırılan alanlarda konumlandırılır, ozon/toz birikimine karşı düzenli temizlik yapılır.',
                'Elektrik kablosu hasarlıysa cihaz kullanılmaz, yetkili servise bildirilir.',
            ]],
            ['baslik' => 'Kazı İşleri Güvenlik Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Hendek, temel ve altyapı kazı işlerinde güvenli çalışma kuralları.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Su geçirmez çizme',
                2 => 'Reflektörlü yelek',
            ], 'maddeler' => [
                'Kazı öncesi bölgede yer altı tesisatı (elektrik, su, doğalgaz) haritaları kontrol edilir, gerekirse ilgili kurumdan teyit alınır.',
                '1,5 metreden derin kazılarda şev açısı zemin cinsine göre ayarlanır veya iksa (istinat) sistemi kurulur.',
                'Kazı kenarına en az 1 metre mesafede güvenlik şeridi/bariyer çekilir, malzeme kazı kenarına yığılmaz.',
                'Kazı çukuruna giriş-çıkış için uygun merdiven bulundurulur, atlayarak inilmez/çıkılmaz.',
                'Günlük kazı kontrolü (özellikle yağış sonrası) yapılır, göçük belirtisi (çatlak, su sızıntısı) izlenir.',
                'Kazı çevresinde iş makinesi çalışırken kazı içinde personel bulunmaz.',
            ]],
            ['baslik' => 'Beton Dökümü ve Kalıp İşleri Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Beton dökümü, kalıp kurulum/söküm ve vibratör kullanımında güvenlik.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Alkali dirençli eldiven',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Kalıp iskeletinin taşıyıcı hesaplara uygun kurulduğu, payandaların sağlam ve yeterli sayıda olduğu kontrol edilir.',
                'Islak beton cilde temas ettirilmez (alkalik yanık riski); eldiven ve uzun kollu giysi kullanılır.',
                'Beton pompası hortumu ve bomu çalışırken hattın altında/önünde durulmaz.',
                'Vibratör kullanımında elektrik çarpması ve titreşim maruziyetine karşı yalıtımlı ekipman ve düzenli mola uygulanır.',
                'Kalıp söküm işlemi, betonun yeterli mukavemete ulaştığı (proje/laboratuvar onayı) teyit edilmeden yapılmaz.',
            ]],
            ['baslik' => 'Cephe İskelesinde Çalışma Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Cephe iskelesinin kurulumu, kullanımı ve söküm sırasında güvenli çalışma.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri',
                1 => 'Çift kancalı şok emici lanyard',
                2 => 'Çene bağlamalı baret',
            ], 'maddeler' => [
                'İskele, yalnızca iskele kurulum/söküm eğitimi almış yetkili ekip tarafından kurulur ve sökülür.',
                'Kurulum sonrası iskele, yetkili kişi tarafından muayene edilip "kullanıma uygundur" etiketi asılmadan kullanılmaz.',
                'İskele üzerinde çalışma platformunda korkuluk, ara korkuluk ve topuk levhası eksiksiz bulunur.',
                'İskele üzerine kapasitesini aşan malzeme yüklenmez, malzeme düzenli istiflenir.',
                'İskele ayakları sağlam, düz zemine ve gerekiyorsa taban plakası ile oturtulur; tekerlekli iskelede fren kilitlenmeden çıkılmaz.',
                'Kuvvetli rüzgar (yaklaşık 40 km/s üzeri) ve fırtınalı havada iskelede çalışılmaz.',
            ]],
            ['baslik' => 'İskele Kurulum ve Söküm Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Cephe/sehpa iskelelerinin kurulum ve söküm işlerinde özel güvenlik kuralları.', 'kkdler' => [
                0 => 'Tam vücut emniyet kemeri',
                1 => 'Çift kancalı lanyard',
                2 => 'Baret',
            ], 'maddeler' => [
                'Kurulum/söküm sırasında henüz korkuluğu tamamlanmamış seviyelerde çalışan mutlaka emniyet kemerini bağımsız ankraja bağlar.',
                'Parçalar aşağıdan yukarıya sırayla monte edilir; atlanmış kat/seviye bırakılmaz.',
                'Kurulum ekibi malzemeyi elden ele iletir, yukarıdan aşağı atarak malzeme indirilmez.',
                'Söküm, kurulumun tersi sırayla yapılır; alt seviyeler sökülmeden üst seviyeler ayakta bırakılmaz.',
                'Kurulum/söküm alanı çevresi bariyerle kapatılır, altından geçişe izin verilmez.',
            ]],
            ['baslik' => 'Kule Vinç ile Çalışma ve Sinyalizasyon Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Kule vinç operasyonlarında yer ekibi ve sinyalman için güvenli çalışma kuralları.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Reflektörlü yelek',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Vinç operasyonu, yalnızca yetkili operatör ve işaretçi (sinyalman) koordinasyonunda yürütülür.',
                'Standart el işaretleri veya telsiz kullanılır; anlaşılamayan işarette yük hareket ettirilmez.',
                'Yük altında ve tahmini sallanma/salınım alanında personel bulundurulmaz.',
                'Sapan ve bağlama aparatları yükün ağırlığına ve şekline uygun seçilir, kancaya güvenlik mandalı takılı olmalıdır.',
                'Rüzgar hızı üretici sınırını aştığında (genelde ~45-72 km/s bölgesine göre değişir) vinç operasyonu durdurulur.',
                'Vinç, günlük ve periyodik muayeneden geçmeden ve geçerli muayene raporu olmadan çalıştırılmaz.',
            ]],
            ['baslik' => 'Şantiye Trafiği ve Araç-Yaya Ayrımı Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Şantiye içi araç ve iş makinesi trafiğinde yaya güvenliğini sağlamaya yönelik kurallar.', 'kkdler' => [
                0 => 'Reflektörlü yelek',
                1 => 'Baret',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Şantiye içinde araç ve yaya güzergahları ayrı işaretlenir, mümkün olan yerlerde fiziksel bariyerle ayrılır.',
                'Şantiye içi azami hız sınırı belirlenir ve tabelalarla duyurulur; hız sınırına uyulur.',
                'Geri manevra yapan araç/iş makinesinde sesli ikaz cihazı çalışır olmalı, gerekiyorsa yönlendirici personel kullanılır.',
                'Tüm şantiye çalışanları ve ziyaretçiler reflektörlü yelek giymeden saha içine giremez.',
                'Kör noktalar, kavşaklar ve giriş-çıkış noktalarında uygun görüş açısı ve trafik aynası sağlanır.',
            ]],
            ['baslik' => 'Demir/Donatı Bağlama İşleri Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'İnşaat demiri kesim, bükme ve bağlama işlerinde güvenli çalışma.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Kesilmeye dayanıklı eldiven',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Dikey duran demir çubukların uçlarına, göz/vücut yaralanmasını önlemek için koruyucu kapak (mantar) takılır.',
                'Demir kesme/bükme makineleri yalnızca eğitimli personel tarafından, koruyucuları yerindeyken kullanılır.',
                'Donatı taşınırken uçlarının çevredeki kişilere veya elektrik hatlarına temas etmediğinden emin olunur.',
                'Bağlama teli kesilirken uçların sıçrama yönü kontrol altında tutulur, göz koruması kullanılır.',
                'Kalıp üzerinde donatı döşenirken düşme riskine karşı iskele/platform korkulukları tam olmalıdır.',
            ]],
            ['baslik' => 'Yıkım İşleri Güvenlik Talimatı', 'kategori' => 'insaat_saha', 'aciklama' => 'Bina/yapı yıkım çalışmalarında güvenli planlama ve uygulama kuralları.', 'kkdler' => [
                0 => 'Baret',
                1 => 'Tam yüz koruyucu maske',
                2 => 'Çelik burunlu iş ayakkabısı',
            ], 'maddeler' => [
                'Yıkım öncesi yapıda asbest, elektrik, gaz, su tesisatı gibi tehlikeler tespit edilip devre dışı bırakılır.',
                'Yıkım, mühendislik hesabına dayalı bir yıkım planına göre, yukarıdan aşağıya doğru sistematik yapılır.',
                'Yıkım alanı çevresi güvenlik bariyeriyle kapatılır, yetkisiz giriş engellenir.',
                'Toz kontrolü için su püskürtme uygulanır, çalışanlar toz maskesi kullanır.',
                'Yıkım makinesi operatör kabini, düşen enkaza karşı koruyucu (FOPS) donanımlı olmalıdır.',
            ]],
            ['baslik' => 'İşyeri Temizlik ve Hijyen Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'İşyeri genel temizliği, hijyen kuralları ve dezenfeksiyon prosedürleri.', 'kkdler' => [
                0 => 'Kimyasal eldiven',
                1 => 'Kaymaz iş ayakkabısı',
                2 => 'Koruyucu gözlük',
            ], 'maddeler' => [
                'Çalışma alanları vardiya sonunda düzenli olarak temizlenir, geçiş yolları malzeme ile kapatılmaz.',
                'Temizlik kimyasalları birbiriyle karıştırılmaz (özellikle çamaşır suyu ve asit içerikli ürünler).',
                'Dökülen sıvı/yağ derhal temizlenir, gerekiyorsa "kaygan zemin" uyarı levhası konur.',
                'Atıklar türüne göre (geri dönüştürülebilir, tehlikeli, evsel) ayrı toplanır.',
                'Tuvalet, soyunma odası ve yemekhane gibi ortak alanların hijyeni düzenli denetlenir.',
            ]],
            ['baslik' => 'Genel İşyeri İSG Kuralları Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Tüm çalışanların uyması gereken işyeri genel iş sağlığı ve güvenliği kuralları.', 'kkdler' => [
                0 => 'Çalışma alanına özel KKD\'leri tak',
                1 => 'Genel: baret',
            ], 'maddeler' => [
                'İşyerine giren her çalışan/ziyaretçi, alanın gerektirdiği KKD\'leri eksiksiz kullanır.',
                'Görülen her tehlikeli durum veya ramak kala olay, gecikmeden amire veya İSG uzmanına bildirilir.',
                'Acil durum çıkışları, yangın söndürme ekipmanları ve toplanma alanı yeri her çalışan tarafından bilinir.',
                'Alkol/uyuşturucu etkisi altında veya yorgun/uykusuz şekilde çalışılmaz, iş makinesi kullanılmaz.',
                'Talimat ve prosedürlere aykırı "kısayol" davranışlardan kaçınılır, iş güvenliği kuralına aykırı talimat verilse dahi uygulanmadan önce sorgulanır.',
                'Yeni işe başlayan veya görev değişikliği olan her çalışan, ilgili oryantasyon/işbaşı eğitimini tamamlamadan çalışmaya başlamaz.',
            ]],
            ['baslik' => 'Kişisel Koruyucu Donanım (KKD) Genel Kullanım Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Tüm KKD\'lerin doğru seçimi, kullanımı, bakımı ve saklanmasına ilişkin genel kurallar.', 'kkdler' => [
                0 => 'İşe özgü tüm KKD\'ler',
            ], 'maddeler' => [
                'KKD, çalışana ücretsiz temin edilir ve teslim tutanağı ile zimmetlenir.',
                'KKD, yalnızca CE işaretli ve ilgili işe uygun standartta seçilir.',
                'Hasarlı, yıpranmış veya son kullanma tarihi geçmiş KKD kullanılmaz, derhal değiştirilir.',
                'KKD kullanım öncesi gözle kontrol edilir (çatlak, yırtık, aşınma vb.).',
                'KKD, üretici talimatına uygun şekilde temizlenir ve saklanır; kişisel olmayan KKD ortak kullanılmaz (özellikle solunum maskesi/kulaklık gibi hijyen gerektirenler).',
            ]],
            ['baslik' => 'İlkyardım ve Acil Müdahale Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'İş kazası/rahatsızlık anında yapılması gereken ilkyardım ve bildirim adımları.', 'kkdler' => [
                0 => 'Tek kullanımlık eldiven (ilkyardımda)',
            ], 'maddeler' => [
                'Kaza/rahatsızlık anında önce alan güvenliği sağlanır, ikincil kazaya meydan verilmez.',
                'Bilinci kapalı veya ciddi yaralanma olan kişi, tıbbi eğitim almamış kişilerce hareket ettirilmez (omurga riski).',
                'İlkyardım eğitimli personel/ekip derhal çağrılır, gerekiyorsa 112 aranır.',
                'Olay yeri, soruşturma için mümkün olduğunca müdahale sonrası korunur, fotoğraflanır.',
                'Her olay, ne kadar küçük olursa olsun İSG uzmanına/işverene bildirilir ve kayıt altına alınır.',
            ]],
            ['baslik' => 'Yangın Güvenliği ve Söndürme Cihazı Kullanma Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Yangın önleme, ihbar ve ilk müdahale kuralları.', 'kkdler' => [
            ], 'maddeler' => [
                'Yangın söndürme cihazları önü açık, erişilebilir ve son kontrol tarihi güncel tutulur.',
                'Yangın algılandığında önce alarm verilir/haber verilir, sonra güvenli ise ilk müdahale yapılır.',
                'Söndürme cihazı kullanırken PASS yöntemi izlenir: Pimi çek, hortumu ateşin dibine tut, tetiği sık, yelpaze hareketiyle söndür.',
                'Elektrik kaynaklı yangında su bazlı söndürücü kullanılmaz; CO2 veya kuru kimyevi tozlu söndürücü tercih edilir.',
                'Müdahale edilemeyecek büyüklükteki yangında derhal tahliye edilir, toplanma alanına gidilir.',
            ]],
            ['baslik' => 'Kilitleme-Etiketleme (LOTO) Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Bakım/onarım öncesi enerji kaynaklarının izolasyonu (Lockout-Tagout) prosedürü.', 'kkdler' => [
                0 => 'İşe özgü KKD',
            ], 'maddeler' => [
                'Bakım/onarım öncesi ekipmanın tüm enerji kaynakları (elektrik, hidrolik, pnömatik, potansiyel enerji) belirlenir.',
                'Her enerji kaynağı, ilgili çalışanın kendi kilidiyle kilitlenir ve üzerine isim/tarih bilgili etiket asılır.',
                'Kilitleme sonrası ekipmanın gerçekten enerjisiz olduğu, çalıştırma denemesi ile test edilir.',
                'Birden fazla kişi çalışıyorsa grup kilitleme prosedürü (kilit kutusu) uygulanır; herkes kendi kilidini takar.',
                'İş bitiminde kilit/etiket yalnızca onu takan kişi tarafından kaldırılır; alan güvenli hale getirilmeden enerji verilmez.',
            ]],
            ['baslik' => 'Sıcak İşler (Kaynak, Kesme, Taşlama) İzin Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Belirlenmiş sıcak iş alanı dışında yapılacak kaynak/kesme/taşlama işlerinde izin prosedürü.', 'kkdler' => [
                0 => 'İşe özgü KKD (kaynak maskesi, eldiven vb.)',
            ], 'maddeler' => [
                'Sabit sıcak iş alanı (kaynakhane) dışında yapılacak her kaynak/kesme işi için Sıcak İş İzni alınır.',
                'İzin öncesi çevredeki yanıcı maddeler kaldırılır veya yanmaz örtü ile kapatılır.',
                'Çalışma boyunca ve bitiminden sonra en az 30-60 dakika yangın gözcüsü bölgede beklemeye devam eder.',
                'Uygun söndürme cihazı iş alanında hazır bulundurulur.',
                'Kapalı/dar alanda sıcak iş yapılacaksa ayrıca kapalı alan giriş izni de alınır.',
            ]],
            ['baslik' => 'Gürültülü Ortamda Çalışma Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Yüksek gürültü seviyesine maruz kalınan alanlarda işitme koruma kuralları.', 'kkdler' => [
                0 => 'Kulak tıkacı veya kulaklık',
            ], 'maddeler' => [
                'Gürültü seviyesi 80 dB(A) üzerinde olan alanlarda işitme koruyucu KKD bulundurulur, 85 dB(A) üzerinde kullanımı zorunludur.',
                'Gürültülü alan sınırları işaretlenir ve KKD zorunluluğu tabela ile belirtilir.',
                'İşitme koruyucu, kulak kanalına/kulağa tam oturacak şekilde takılır; sürekli kullanım sırasında çıkarılmaz.',
                'Yüksek gürültüye maruz kalan çalışanlar periyodik odyometri (işitme) testine tabi tutulur.',
            ]],
            ['baslik' => 'Titreşimli El Aletleriyle Çalışma Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'El-kol titreşimine maruziyeti azaltmaya yönelik kurallar.', 'kkdler' => [
                0 => 'Anti-vibrasyon eldiven',
            ], 'maddeler' => [
                'Titreşimli alet (kırıcı, taşlama vb.) kullanım süresi günlük maruziyet sınır değerini aşmayacak şekilde planlanır.',
                'Alet mümkün olduğunca hafif tutuşla kullanılır, aşırı sıkı kavrama titreşim etkisini artırır.',
                'Düzenli aralıklarla mola verilerek eller ısıtılır, kan dolaşımı desteklenir.',
                'Aletlerin titreşim seviyesi düşük modellerle değiştirilmesi ve düzenli bakımı sağlanır.',
            ]],
            ['baslik' => 'Acil Durum Tahliye Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Yangın, deprem veya diğer acil durumlarda güvenli tahliye kuralları.', 'kkdler' => [
            ], 'maddeler' => [
                'Acil durum anında en yakın çıkış ve kaçış yolu kullanılarak sakin ve hızlı şekilde tahliye edilir; asansör kullanılmaz.',
                'Tahliye sırasında yanınızdakine yardım edin, engelli/yardıma muhtaç kişilere öncelik verilir.',
                'Belirlenen toplanma alanına gidilir, bölüm sorumlusu tarafından yoklama alınır.',
                'Yoklamada eksik kişi tespit edilirse derhal arama-kurtarma ekiplerine bildirilir; tekrar içeri girilmez.',
                'Tahliye tatbikatlarına eksiksiz katılım sağlanır, kaçış yolları ve toplanma alanı önceden öğrenilir.',
            ]],
            ['baslik' => 'Deprem Anında Davranış Talimatı', 'kategori' => 'genel_isg', 'aciklama' => 'Deprem sırasında ve sonrasında uygulanacak güvenli davranış kuralları.', 'kkdler' => [
            ], 'maddeler' => [
                'Deprem sırasında "Çök-Kapan-Tutun" kuralı uygulanır: sağlam bir mobilya yanında çökülür, baş ve boyun korunur, sağlam bir yere tutunulur.',
                'Pencere, cam, raf ve ağır eşyalardan uzak durulur.',
                'Sarsıntı bitmeden asansör veya merdivenlere koşulmaz.',
                'Sarsıntı durduktan sonra sakin şekilde, belirlenen güzergahtan tahliye edilir.',
                'Dışarıda ise bina, elektrik direği ve ağaçlardan uzaklaşılıp açık alana geçilir.',
            ]],
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

        // Yıllık Eğitim Planı kategori grupları (referans: ABDULLETİF YALÇINKAYA
        // YILLIK EĞİTİM PLANI.xlsx — "GENEL / SAĞLIK / TEKNİK / DİĞER" bölümleri).
        'egitim_kategorileri' => [
            'genel' => 'Genel Konular',
            'saglik' => 'Sağlık Konuları',
            'teknik' => 'Teknik Konular',
            // 4. bölüm — firmaya/şantiyeye özgü riskler; Eğitim Katılım formunun
            // "işyerine özgü" bölümüne ve sertifikaya da buradan önerilir.
            'ise_ozgu' => 'İşe ve İşyerine Özgü Riskler',
            'diger' => 'Diğer Eğitimler',
        ],

        // Çalışma ve eğitim planı içeriği artık tek şablondan gelir:
        // config/yillik_plan_sablonu.php (kullanıcının VİZYON Excel'lerinden birebir) —
        // bkz. App\Support\YillikPlanSablonu. Eski genel varsayılan listeler kaldırıldı (01.10.2026).

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
        // isgsuite "Tatbikat Yönetimi" durumları. Kontrol Merkezi'nde yalnız
        // yapildi + takip "yapılmış tatbikat" sayılır (TatbikatTutanagi::YAPILMIS).
        'durumlar' => [
            'planlandi' => 'Planlandı',
            'yapildi' => 'Yapıldı',
            'takip' => 'Yapıldı — takip gerekiyor',
            'iptal' => 'İptal',
        ],
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
    /*
    |--------------------------------------------------------------------------
    | Meslek Hastalığı Bildirimi — 6331 s.K. m.14, öğrenilme tarihinden
    | itibaren 3 iş günü içinde SGK'ya (e-SGK/e-Devlet) bildirim zorunluluğu.
    |--------------------------------------------------------------------------
    */
    'meslek_hastaligi' => [
        'ogrenme_kaynaklari' => [
            'isyeri_hekimi' => 'İşyeri Hekimi',
            'saglik_kurulusu' => 'Sağlık Hizmeti Sunucusu / Hastane',
            'sigortali_kendisi' => 'Sigortalının Kendisi',
        ],
        'bildirim_yontemleri' => [
            'e_sgk' => 'e-SGK İş Kazası ve Meslek Hastalığı E-Bildirim',
            'e_devlet' => 'e-Devlet Kapısı',
        ],
        'kurul_onay_durumlari' => [
            'bekliyor' => 'SGK Sağlık Kurulu Değerlendirmesi Bekleniyor',
            'onaylandi' => 'Onaylandı (Meslek Hastalığı)',
            'reddedildi' => 'Reddedildi',
        ],
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Pazarlama (Saha CRM) — Profilim > Pazarlama (isgpratik 143.jpg)
    |--------------------------------------------------------------------------
    */
    'pazarlama' => [
        'asamalar' => [
            'aday' => 'Aday',
            'teklif' => 'Teklif Aşamasında',
            'kazanildi' => 'Kazanıldı',
            'kaybedildi' => 'Kaybedildi',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Arşiv — Profilim > Arşiv (isgpratik 144.jpg)
    |--------------------------------------------------------------------------
    */
    'arsiv' => [
        'kota_mb' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Raporlar — Profilim > Raporlar (isgpratik 146.jpg)
    |--------------------------------------------------------------------------
    | mehse'de üretilen HER belge türü tek listede — App\Support\RaporKayitlari
    | bu listeyi gezip her modelin kendi kayıtlarını "created_at" üzerinden
    | birleştirir. Her satır: model + (varsa) dinamik tip etiketi metodu +
    | birincil üretici (Uretici::pdf($kayit)) + (varsa) ikincil format.
    | Yeni bir belge modülü eklendiğinde Raporlar'a girmesi için TEK satır yeter.
    */
    'raporlar' => [
        'kaynaklar' => [
            ['model' => Sertifika::class, 'ad' => 'Sertifika', 'tip_metod' => 'tipEtiketi',
                'uretici' => SertifikaUretici::class, 'pdf_metod' => 'pdf',
                'ikincil_uretici' => SertifikaYildizGrupUretici::class, 'ikincil_metod' => 'indir', 'ikincil_etiket' => 'Yıldız Grup Şablonu'],
            ['model' => EgitimKatilim::class, 'ad' => 'Eğitim Katılım Formu', 'tip_metod' => 'basliklarEtiketi',
                'uretici' => EgitimKatilimUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => RiskDegerlendirmesi::class, 'ad' => 'Risk Değerlendirmesi', 'tip_metod' => null,
                'uretici' => RiskDegerlendirmesiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => SahaDenetimi::class, 'ad' => 'Saha Denetimi', 'tip_metod' => null,
                'uretici' => SahaDenetimiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => AtamaYazisi::class, 'ad' => 'Atama Yazısı', 'tip_metod' => 'rolEtiketi',
                'uretici' => AtamaYazisiUretici::class, 'pdf_metod' => 'pdf',
                'ikincil_uretici' => AtamaYazisiWordUretici::class, 'ikincil_metod' => 'docx', 'ikincil_etiket' => 'Word'],
            ['model' => DofRaporu::class, 'ad' => 'DÖF Raporu', 'tip_metod' => null,
                'uretici' => DofRaporuUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => IseDonusBelgesi::class, 'ad' => 'İşe Dönüş Belgesi', 'tip_metod' => 'nedenEtiketi',
                'uretici' => IseDonusBelgesiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => IsKazasiRaporu::class, 'ad' => 'İş Kazası Raporu', 'tip_metod' => 'kazaTuruEtiketi',
                'uretici' => IsKazasiRaporuUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => OlayKaydi::class, 'ad' => 'Olay Kaydı / Ramak Kala', 'tip_metod' => 'tipEtiketi',
                'uretici' => OlayKaydiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => KurulToplantisi::class, 'ad' => 'Kurul Toplantı Tutanağı', 'tip_metod' => null,
                'uretici' => KurulToplantisiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => SahaAnalizi::class, 'ad' => 'AI Saha Analizi Raporu', 'tip_metod' => null,
                'uretici' => SahaAnaliziUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => CezaTebligTutanagi::class, 'ad' => 'Ceza ve Tebliğ Tutanağı', 'tip_metod' => 'yaptirimEtiketi',
                'uretici' => CezaTebligTutanagiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => IpcTebligi::class, 'ad' => 'İşverene İPC Tebliği', 'tip_metod' => null,
                'uretici' => IpcTebligiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => IsbasiEgitimTutanagi::class, 'ad' => 'İşbaşı Eğitim Tutanağı', 'tip_metod' => null,
                'uretici' => IsbasiEgitimTutanagiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => TatbikatTutanagi::class, 'ad' => 'Tatbikat Tutanağı', 'tip_metod' => 'senaryoEtiketi',
                'uretici' => TatbikatTutanagiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => EgitimSinavi::class, 'ad' => 'Eğitim Sınav Kağıdı', 'tip_metod' => null,
                'uretici' => EgitimSinaviUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => MuayeneFormu::class, 'ad' => 'Muayene Formu (EK-2)', 'tip_metod' => 'muayeneTuruEtiketi',
                'uretici' => MuayeneFormuUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => MeslekHastaligiBildirimi::class, 'ad' => 'Meslek Hastalığı Bildirimi', 'tip_metod' => null,
                'uretici' => MeslekHastaligiBildirimiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => TespitOneriDefteri::class, 'ad' => 'Tespit ve Öneri Defteri', 'tip_metod' => null,
                'uretici' => TespitOneriDefteriUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => PeriyodikKontrol::class, 'ad' => 'Periyodik Kontrol Takip Listesi', 'tip_metod' => null,
                'uretici' => PeriyodikKontrolUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => OrtamOlcumu::class, 'ad' => 'Ortam Ölçümleri Takip Listesi', 'tip_metod' => null,
                'uretici' => OrtamOlcumuUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => KazaIstatistigi::class, 'ad' => 'Kaza İstatistikleri', 'tip_metod' => null,
                'uretici' => KazaIstatistigiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => KkdMatrisi::class, 'ad' => 'KKD Seçim Matrisi', 'tip_metod' => null,
                'uretici' => KkdMatrisiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => SaglikGozetimi::class, 'ad' => 'Sağlık Gözetimi Takip Çizelgesi', 'tip_metod' => null,
                'uretici' => SaglikGozetimiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => KimyasalRiskDegerlendirmesi::class, 'ad' => 'Kimyasal Risk Değerlendirmesi', 'tip_metod' => null,
                'uretici' => KimyasalRiskUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => YanginGuvenligiDegerlendirmesi::class, 'ad' => 'Yangın Güvenliği Değerlendirme Raporu', 'tip_metod' => null,
                'uretici' => YanginGuvenligiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => KkdZimmetFormu::class, 'ad' => 'KKD Zimmet Formu', 'tip_metod' => null,
                'uretici' => KkdZimmetFormuUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => IsIzinFormu::class, 'ad' => 'İş İzin Formu', 'tip_metod' => null,
                'uretici' => IsIzinFormuUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => Talimat::class, 'ad' => 'Çalışma Talimatı', 'tip_metod' => 'kategoriEtiketi',
                'uretici' => TalimatUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => YillikPlan::class, 'ad' => 'Yıllık Plan', 'tip_metod' => null,
                'uretici' => YillikPlanUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => ZiyaretProgrami::class, 'ad' => 'Ziyaret Programı', 'tip_metod' => null,
                'uretici' => ZiyaretProgramiUretici::class, 'pdf_metod' => 'pdf'],
            ['model' => AcilDurumPlani::class, 'ad' => 'Acil Durum Planı', 'tip_metod' => null,
                'uretici' => AcilDurumPlaniUretici::class, 'pdf_metod' => 'pdf',
                'ikincil_uretici' => AcilDurumWordUretici::class, 'ikincil_metod' => 'docx', 'ikincil_etiket' => 'Word'],
            ['model' => FirmaJsa::class, 'ad' => 'JSA (İşe Özgü Risk)', 'tip_metod' => 'baslikEtiketi',
                'uretici' => FirmaJsaUretici::class, 'pdf_metod' => 'pdf'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Acil Durum Kroki Düzenleyici
    |--------------------------------------------------------------------------
    |
    | Çekirdek sembol seti — isgpratik'teki geniş ISO 7010 kütüphanesinin tam
    | kopyası değil, en sık kullanılan 8 sembolle sınırlı ilk sürüm. Şekiller
    | basitleştirilmiş şematik piktogramlardır (resmi ISO 7010 sertifikalı
    | sanat değil) — gerçek, yönetmeliğe uygun İSG işaretleri zaten Acil Durum
    | Planı → "Acil Durum Afişleri" altında ayrı gerçek PDF dosyaları olarak var;
    | bu kroki yalnızca konum/yerleşim şeması içindir.
    |
    */
    'kroki' => [
        // Tuval boyutu (A4 yatay oranı). Eski (1000x700) krokiler açılışta ölçeklenir.
        'genislik' => 1400,
        'yukseklik' => 990,
        // İşaret şekilleri ISO 7010 renk/biçim kurallarını izler (resmî piktogram
        // sanatı değil): kaçış/ilk yardım yeşil kare, yangın kırmızı kare, uyarı
        // sarı üçgen, yasak kırmızı halka + çapraz, emredici mavi daire.
        'kategoriler' => [
            'kacis' => 'Kaçış & İlk Yardım',
            'yangin' => 'Yangın Ekipmanı',
            'uyari' => 'Uyarı / İkaz',
            'yasak' => 'Yasaklayıcı',
            'emredici' => 'Emredici / KKD',
            'yapi' => 'Kapı / Merdiven / Yapı',
            'makine' => 'Makine & Ekipman',
        ],
        'semboller' => [
            // Kaçış & ilk yardım
            'cikis' => ['ad' => 'Acil Çıkış', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '🏃'],
            'cikis_sol' => ['ad' => 'Kaçış Yönü (sol)', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '⬅'],
            'cikis_sag' => ['ad' => 'Kaçış Yönü (sağ)', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '➡'],
            'toplanma' => ['ad' => 'Toplanma Alanı', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '👪'],
            'merdiven' => ['ad' => 'Tahliye Merdiveni', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '🪜'],
            'ilkyardim' => ['ad' => 'İlk Yardım', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '✚'],
            'sedye' => ['ad' => 'Acil Sedye', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '🛏'],
            'goz_dusu' => ['ad' => 'Göz Duşu', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '👁'],
            'acil_dus' => ['ad' => 'Acil Boy Duşu', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '🚿'],
            'aed' => ['ad' => 'OED (AED)', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '❤'],
            'acil_telefon' => ['ad' => 'Acil Telefon', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '☎'],
            'engelli' => ['ad' => 'Engelli Tahliye', 'renk' => '#15803d', 'kategori' => 'kacis', 'sekil' => 'kare', 'ikon' => '♿'],
            'buradasiniz' => ['ad' => 'Buradasınız', 'renk' => '#dc2626', 'kategori' => 'kacis', 'sekil' => 'daire', 'ikon' => '📍'],
            // Yangın
            'sondurucu' => ['ad' => 'Yangın Söndürücü', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🧯'],
            'co2' => ['ad' => 'CO₂ Söndürücü', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => 'CO₂'],
            'kkt' => ['ad' => 'KKT Söndürücü', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => 'KKT'],
            'kopuk' => ['ad' => 'Köpüklü Söndürücü', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => 'KÖP'],
            'hidrant' => ['ad' => 'Yangın Dolabı', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🌀'],
            'dis_hidrant' => ['ad' => 'Hidrant', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🚒'],
            'alarm' => ['ad' => 'Yangın Alarm Butonu', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🔔'],
            'siren' => ['ad' => 'Siren & Flaşör', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🚨'],
            'sprinkler' => ['ad' => 'Sprinkler', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '💦'],
            'yangin_kapisi' => ['ad' => 'Yangın Kapısı', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🚪'],
            'acil_lamba' => ['ad' => 'Acil Aydınlatma', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '💡'],
            'kum' => ['ad' => 'Yangın Kovası / Kum', 'renk' => '#dc2626', 'kategori' => 'yangin', 'sekil' => 'kare', 'ikon' => '🪣'],
            // Uyarı
            'genel_tehlike' => ['ad' => 'Genel Tehlike', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '!'],
            'elektrik' => ['ad' => 'Elektrik Tehlikesi', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '⚡'],
            'parlayici' => ['ad' => 'Parlayıcı Madde', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '🔥'],
            'patlayici' => ['ad' => 'Patlayıcı Madde', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '💥'],
            'zehirli' => ['ad' => 'Zehirli Madde', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '☠'],
            'biyolojik' => ['ad' => 'Biyolojik Risk', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '☣'],
            'radyasyon' => ['ad' => 'Radyasyon', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '☢'],
            'forklift' => ['ad' => 'Forklift Dikkat', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '🚜'],
            'dusme' => ['ad' => 'Düşme Tehlikesi', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '⤵'],
            'asili_yuk' => ['ad' => 'Asılı Yük', 'renk' => '#facc15', 'kategori' => 'uyari', 'sekil' => 'ucgen', 'ikon' => '🏗'],
            // Yasaklayıcı
            'sigara' => ['ad' => 'Sigara İçilmez', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '🚬'],
            'acik_alev' => ['ad' => 'Açık Alev Yasak', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '🔥'],
            'yetkisiz' => ['ad' => 'Yetkisiz Giremez', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '🚶'],
            'forklift_giremez' => ['ad' => 'Forklift Giremez', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '🚜'],
            'su_yasak' => ['ad' => 'Su ile Söndürmeyin', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '💧'],
            'dokunma' => ['ad' => 'Dokunmayın', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '✋'],
            'icilmez' => ['ad' => 'İçilmez Su', 'renk' => '#dc2626', 'kategori' => 'yasak', 'sekil' => 'yasak', 'ikon' => '🚰'],
            // Emredici
            'baret' => ['ad' => 'Baret Kullan', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '⛑'],
            'gozluk' => ['ad' => 'Gözlük Kullan', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '🥽'],
            'eldiven' => ['ad' => 'Eldiven Kullan', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '🧤'],
            'kulaklik' => ['ad' => 'Kulaklık Kullan', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '🎧'],
            'maske' => ['ad' => 'Maske Kullan', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '😷'],
            'ayakkabi' => ['ad' => 'İş Ayakkabısı', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '🥾'],
            'yelek' => ['ad' => 'Reflektif Yelek', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '🦺'],
            'emniyet_kemeri' => ['ad' => 'Emniyet Kemeri', 'renk' => '#1d4ed8', 'kategori' => 'emredici', 'sekil' => 'daire', 'ikon' => '🪢'],
            // Yapı
            'kapi' => ['ad' => 'Kapı', 'renk' => '#475569', 'kategori' => 'yapi', 'sekil' => 'kapi', 'ikon' => ''],
            'cift_kapi' => ['ad' => 'Çift Kanatlı Kapı', 'renk' => '#475569', 'kategori' => 'yapi', 'sekil' => 'cift_kapi', 'ikon' => ''],
            'merdiven_yapi' => ['ad' => 'Merdiven', 'renk' => '#475569', 'kategori' => 'yapi', 'sekil' => 'merdiven', 'ikon' => ''],
            'asansor' => ['ad' => 'Asansör (yangında kullanmayın)', 'renk' => '#475569', 'kategori' => 'yapi', 'sekil' => 'kare', 'ikon' => '🛗'],
            'elektrik_pano' => ['ad' => 'Elektrik Panosu', 'renk' => '#475569', 'kategori' => 'yapi', 'sekil' => 'kare', 'ikon' => '⚡'],
            'gaz_vana' => ['ad' => 'Gaz Kesme Vanası', 'renk' => '#475569', 'kategori' => 'yapi', 'sekil' => 'kare', 'ikon' => '⛽'],
            'oda' => ['ad' => 'Oda / Alan Etiketi', 'renk' => '#6b7280', 'kategori' => 'yapi', 'sekil' => 'kare', 'ikon' => 'A'],
            // Makine & ekipman — kat planında taban izi (gri dikdörtgen + ad).
            // Kroki işareti değil yerleşim öğesidir; kaçış yolunu daralttığı yerleri
            // göstermek için. Etiket alanına makine no / adı yazılabilir.
            'cnc' => ['ad' => 'CNC Tezgâhı', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'CNC'],
            'torna' => ['ad' => 'Torna', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'TORNA'],
            'freze' => ['ad' => 'Freze', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'FREZE'],
            'matkap' => ['ad' => 'Sütunlu Matkap', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'MATKAP'],
            'taslama' => ['ad' => 'Taşlama', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'TAŞLAMA'],
            'testere' => ['ad' => 'Şerit Testere', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'TESTERE'],
            'pres' => ['ad' => 'Pres', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'PRES'],
            'abkant' => ['ad' => 'Abkant / Giyotin', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'ABKANT'],
            'kaynak_makinesi' => ['ad' => 'Kaynak Makinesi', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'KAYNAK'],
            'kompresor' => ['ad' => 'Kompresör', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'KOMPRESÖR'],
            'vinc' => ['ad' => 'Köprülü / Gezer Vinç', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'VİNÇ'],
            'forklift_park' => ['ad' => 'Forklift Park / Şarj', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'FORKLİFT'],
            'jenerator' => ['ad' => 'Jeneratör', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'JENERATÖR'],
            'kazan' => ['ad' => 'Kazan / Basınçlı Kap', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'KAZAN'],
            'tezgah' => ['ad' => 'Çalışma Tezgâhı', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'TEZGAH'],
            'raf' => ['ad' => 'Raf / İstif Alanı', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'RAF'],
            'makine' => ['ad' => 'Diğer Makine', 'renk' => '#334155', 'kategori' => 'makine', 'sekil' => 'makine', 'ikon' => 'MAKİNE'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mevzuat Kütüphanesi
    |--------------------------------------------------------------------------
    |
    | İSG pratiğinde en sık başvurulan Kanun/Yönetmelik/Tebliğ/Rehber'lerin
    | kısa, yönlendirici bir dizini — isgpratik'teki genel mevzuat.gov.tr
    | tarzı TAM kütüphane değil, mehse'nin kendi modüllerinde zaten dayanak
    | olarak kullanılan mevzuatla sınırlı, İSG'ye özgü bir seçki. Açıklamalar
    | genel bilgilendirme amaçlıdır; madde numarası/tarih gibi ayrıntılar için
    | resmi kaynağa (mevzuat.gov.tr, resmigazete.gov.tr) bakılmalıdır.
    |
    */
    'mevzuat' => [
        'kategoriler' => [
            'anayasa' => 'Anayasa',
            'kanun' => 'Kanunlar',
            'yonetmelik' => 'Yönetmelikler',
            'teblig' => 'Tebliğler',
            'rehber' => 'Rehberler',
        ],
        'liste' => [
            ['baslik' => 'Türkiye Cumhuriyeti Anayasası', 'kategori' => 'anayasa',
                'aciklama' => 'Çalışma hakkı, dinlenme hakkı ve sosyal güvenlik hakkına ilişkin temel hükümleriyle İSG mevzuatının anayasal dayanağıdır.'],

            ['baslik' => '6331 sayılı İş Sağlığı ve Güvenliği Kanunu', 'kategori' => 'kanun',
                'aciklama' => 'İSG\'nin temel kanunu — işveren yükümlülükleri, risk değerlendirmesi zorunluluğu, İSG uzmanı/işyeri hekimi görevlendirme, İSG kurulu ve idari para cezalarını düzenler.'],
            ['baslik' => '4857 sayılı İş Kanunu', 'kategori' => 'kanun',
                'aciklama' => 'İş sözleşmesi, çalışma süreleri, fazla mesai, yıllık ücretli izin ve iş güvencesine ilişkin temel çalışma hayatı hükümleri.'],
            ['baslik' => '5510 sayılı Sosyal Sigortalar ve Genel Sağlık Sigortası Kanunu', 'kategori' => 'kanun',
                'aciklama' => 'İş kazası ve meslek hastalığı bildirimi, sigortalılık ve sağlık hizmetlerine ilişkin hükümler.'],
            ['baslik' => '6098 sayılı Türk Borçlar Kanunu', 'kategori' => 'kanun',
                'aciklama' => 'İşverenin işçiyi gözetme borcu gibi hizmet sözleşmesine ilişkin genel hükümler içerir.'],

            ['baslik' => 'İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'Risk değerlendirmesi ekibinin oluşumu, yöntemi, süreci ve yenileme sürelerini düzenler.'],
            ['baslik' => 'İşyerlerinde Acil Durumlar Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Acil durum planı hazırlama zorunluluğu, tahliye ve söndürme/kurtarma/koruma/ilk yardım destek ekiplerini düzenler.'],
            ['baslik' => 'İş Sağlığı ve Güvenliği Kurulları Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => '50 ve üzeri çalışanı olan işyerlerinde İSG kurulu kurma şartı, üyeleri ve toplantı düzenini belirler.'],
            ['baslik' => 'Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Zorunlu İSG eğitimlerinin konu başlıklarını ve tehlike sınıfına göre sürelerini düzenler.'],
            ['baslik' => 'Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'KKD seçimi, ücretsiz temini, teslim tutanağı ve kullanım zorunluluğuna ilişkin esasları belirler.'],
            ['baslik' => 'İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'İş ekipmanlarının (vinç, forklift, basınçlı kap vb.) periyodik kontrol yükümlülüklerini (Ek-3) düzenler.'],
            ['baslik' => 'İşyeri Hekimi ve Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'İşyeri hekiminin görevlerini ve tehlike sınıfına göre periyodik muayene sürelerini düzenler.'],
            ['baslik' => 'İş Güvenliği Uzmanlarının Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'İSG uzmanı sınıflarını (A/B/C) ve görevlerini, çalışma sürelerini düzenler.'],
            ['baslik' => 'İş Sağlığı ve Güvenliği Hizmetleri Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'OSGB/İSGB hizmet esasları ile yıllık çalışma planı hazırlama yükümlülüğünü düzenler.'],
            ['baslik' => 'Elle Taşıma İşleri Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'Elle taşıma kaynaklı kas-iskelet sistemi risklerinin değerlendirilmesi ve önlenmesini düzenler.'],
            ['baslik' => 'Ekranlı Araçlarla Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Bilgisayar/ekran başında çalışmada ergonomi, mola ve göz sağlığı önlemlerini düzenler.'],
            ['baslik' => 'Çalışanların Gürültü ile İlgili Risklerden Korunmalarına Dair Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Gürültü maruziyet sınır ve eylem değerlerini (alt/üst eylem değeri, sınır değer) düzenler.'],
            ['baslik' => 'Çalışanların Titreşimle İlgili Risklerden Korunmalarına Dair Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'El-kol ve tüm vücut titreşimi maruziyet sınırlarını ve önlemlerini düzenler.'],
            ['baslik' => 'Kimyasal Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Tehlikeli kimyasallarla çalışmada risk değerlendirmesi, ölçüm ve KKD önlemlerini düzenler.'],
            ['baslik' => 'Kanserojen veya Mutajen Maddelerle Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Kanserojen/mutajen maddelere maruziyetin önlenmesi ve sağlık gözetimini düzenler.'],
            ['baslik' => 'Biyolojik Etkenlere Maruziyet Risklerinin Önlenmesi Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Bakteri, virüs gibi biyolojik etkenlere maruziyetin risk gruplarına göre sınıflandırılmasını ve önlenmesini düzenler.'],
            ['baslik' => 'Yapı İşlerinde İş Sağlığı ve Güvenliği Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'İnşaat/şantiye işlerinde yüksekte çalışma, kazı, iskele gibi risklere özgü önlemleri düzenler.'],
            ['baslik' => 'Maden İşyerlerinde İş Sağlığı ve Güvenliği Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'Yeraltı ve yerüstü maden işletmelerine özgü İSG önlemlerini düzenler.'],
            ['baslik' => 'Çalışanların Patlayıcı Ortamların Tehlikelerinden Korunması Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Patlayıcı ortam oluşabilecek işyerlerinde bölgelendirme (zone) ve önlemleri düzenler.'],
            ['baslik' => 'İşyeri Bina ve Eklentilerinde Alınacak Sağlık ve Güvenlik Önlemlerine İlişkin Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Zemin, merdiven, aydınlatma, havalandırma gibi işyeri bina şartlarını düzenler.'],
            ['baslik' => 'Makina Emniyeti Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'Makinelerin piyasaya arzında CE işareti ve temel sağlık/güvenlik gereklerini düzenler.'],
            ['baslik' => 'Sağlık ve Güvenlik İşaretleri Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'İşyerlerinde kullanılacak uyarı, yasak, yangın ve kurtarma işaretlerinin standartlarını düzenler.'],
            ['baslik' => 'İlkyardım Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'İşyerlerinde bulundurulması gereken ilkyardımcı sayısını (tehlike sınıfına göre) ve sertifika şartını düzenler.'],
            ['baslik' => 'Tehlikeli ve Çok Tehlikeli İşlerde Çalıştırılacakların Mesleki Eğitimlerine Dair Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Tehlikeli/çok tehlikeli işlerde MYK mesleki yeterlilik belgesi zorunluluğunun dayanağıdır (bkz. Araçlar → MYK Zorunluluk Sorgula).'],
            ['baslik' => 'Postalar Halinde İşçi Çalıştırılarak Yürütülen İşlerde Çalışmalara İlişkin Özel Usul ve Esaslar Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Vardiyalı (postalı) çalışmada dinlenme süresi ve postalar arası geçiş kurallarını düzenler.'],
            ['baslik' => 'Binaların Yangından Korunması Hakkında Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Yangın söndürme sistemleri, kaçış yolları ve periyodik bakım yükümlülüklerini düzenler.'],
            ['baslik' => 'Asansör İşletme, Bakım ve Periyodik Kontrol Yönetmeliği', 'kategori' => 'yonetmelik',
                'aciklama' => 'Asansörlerin yıllık periyodik kontrol ve bakım yükümlülüklerini düzenler.'],
            ['baslik' => 'İşyerlerinde İşin Durdurulmasına Dair Yönetmelik', 'kategori' => 'yonetmelik',
                'aciklama' => 'Hayati tehlike halinde işin durdurulması usul ve esaslarını düzenler.'],

            ['baslik' => 'İş Sağlığı ve Güvenliğine İlişkin İşyeri Tehlike Sınıfları Tebliği', 'kategori' => 'teblig',
                'aciklama' => 'NACE koduna göre işyeri tehlike sınıflarını (az/tehlikeli/çok tehlikeli) belirler (bkz. Araçlar → NACE Kod Sorgula).'],
            ['baslik' => 'Meslekî Yeterlilik Kurumu Meslekî Yeterlilik Belgesi Zorunluluğu Getirilen Mesleklere İlişkin Tebliğ', 'kategori' => 'teblig',
                'aciklama' => 'MYK belgesi zorunlu meslekleri ve yeterlilik kodlarını kademeli olarak (Sıra No ile) genişleten tebliğ dizisi (bkz. Araçlar → MYK Zorunluluk Sorgula).'],

            ['baslik' => 'Çalışma ve Sosyal Güvenlik Bakanlığı — Risk Değerlendirmesi Rehberleri', 'kategori' => 'rehber',
                'aciklama' => 'İSG Genel Müdürlüğü\'nün sektörel risk değerlendirmesi ve KKD seçimine yönelik uygulama rehberleri (ailevi.gov.tr / çalışma bakanlığı yayınları).'],
            ['baslik' => 'SGK İş Kazası ve Meslek Hastalığı Bildirim Rehberi', 'kategori' => 'rehber',
                'aciklama' => 'İş kazası/meslek hastalığının SGK\'ya bildirim süresi ve usulüne ilişkin uygulama rehberi.'],
        ],
    ],

    // Toolbox Konuşması hazır kütüphanesi — şantiye işleri + lojistik sektörü
    // (ToolboxKonusmalariniSeedEt komutu ile her kullanıcıya kopyalanır,
    // dosyalar storage/app/public/toolbox-konusmalari/ altındadır).
    'toolbox_ornekleri' => [
        ['grup' => 'santiye', 'baslik' => 'Kazı ve Hafriyat İşleri Toolbox Konuşması', 'dosya_adi' => 'Kazı ve Hafriyat İşleri Toolbox Konuşması.docx', 'aciklama' => 'Kazı ve hafriyat çalışmalarında göçük, düşme ve yer altı tesisatı kaynaklı kazaları önlemek.', 'boyut' => 8558],
        ['grup' => 'santiye', 'baslik' => 'Kalıp İşleri Toolbox Konuşması', 'dosya_adi' => 'Kalıp İşleri Toolbox Konuşması.docx', 'aciklama' => 'Kalıp kurulum ve söküm işlerinde düşme, ezilme ve yapı çökmesi risklerini azaltmak.', 'boyut' => 8526],
        ['grup' => 'santiye', 'baslik' => 'Demir/Donatı İşleri Toolbox Konuşması', 'dosya_adi' => 'DemirDonatı İşleri Toolbox Konuşması.docx', 'aciklama' => 'Demir bağlama, kesme ve taşıma işlerinde kesik, batma ve yaralanmaları önlemek.', 'boyut' => 8478],
        ['grup' => 'santiye', 'baslik' => 'Beton Dökümü Toolbox Konuşması', 'dosya_adi' => 'Beton Dökümü Toolbox Konuşması.docx', 'aciklama' => 'Beton döküm, pompalama ve vibratör kullanımında güvenli çalışmayı sağlamak.', 'boyut' => 8519],
        ['grup' => 'santiye', 'baslik' => 'Duvar ve Sıva İşleri Toolbox Konuşması', 'dosya_adi' => 'Duvar ve Sıva İşleri Toolbox Konuşması.docx', 'aciklama' => 'Duvar örme, sıva ve harç işlerinde düşme ve kas-iskelet yaralanmalarını azaltmak.', 'boyut' => 8532],
        ['grup' => 'santiye', 'baslik' => 'Boya ve İzolasyon İşleri Toolbox Konuşması', 'dosya_adi' => 'Boya ve İzolasyon İşleri Toolbox Konuşması.docx', 'aciklama' => 'Boya, vernik ve izolasyon uygulamalarında solunum ve yangın risklerini azaltmak.', 'boyut' => 8516],
        ['grup' => 'santiye', 'baslik' => 'İskele Kurulum/Söküm ve Yükseklik Toolbox Konuşması', 'dosya_adi' => 'İskele KurulumSöküm ve Yükseklik Toolbox Konuşması.docx', 'aciklama' => 'İskelede ve yükseklikte güvenli çalışmayı, düşmeleri önlemeyi sağlamak.', 'boyut' => 8521],
        ['grup' => 'santiye', 'baslik' => 'Kaynak ve Kesme İşleri Toolbox Konuşması', 'dosya_adi' => 'Kaynak ve Kesme İşleri Toolbox Konuşması.docx', 'aciklama' => 'Kaynak/kesme işlerinde yangın, yanık ve gaz maruziyetini önlemek.', 'boyut' => 8516],
        ['grup' => 'santiye', 'baslik' => 'Elektrik İşleri Toolbox Konuşması', 'dosya_adi' => 'Elektrik İşleri Toolbox Konuşması.docx', 'aciklama' => 'Elektrik tesisat ve pano işlerinde elektrik çarpmasını önlemek.', 'boyut' => 8445],
        ['grup' => 'santiye', 'baslik' => 'Vinç ve Kaldırma İşleri Toolbox Konuşması', 'dosya_adi' => 'Vinç ve Kaldırma İşleri Toolbox Konuşması.docx', 'aciklama' => 'Vinçle yük kaldırma operasyonlarında yaralanma ve çarpma risklerini azaltmak.', 'boyut' => 8518],
        ['grup' => 'santiye', 'baslik' => 'İş Makinesi ve Forklift Operatörleri Toolbox Konuşması', 'dosya_adi' => 'İş Makinesi ve Forklift Operatörleri Toolbox Konuşması.docx', 'aciklama' => 'Şantiye içi iş makinesi/forklift operasyonlarında yaya-araç çarpışmalarını önlemek.', 'boyut' => 8490],
        ['grup' => 'santiye', 'baslik' => 'Yangın Güvenliği Toolbox Konuşması', 'dosya_adi' => 'Yangın Güvenliği Toolbox Konuşması.docx', 'aciklama' => 'Şantiyede yangın önleme ve ilk müdahale bilincini artırmak.', 'boyut' => 8452],
        ['grup' => 'santiye', 'baslik' => 'Genel Şantiye Düzeni ve KKD Toolbox Konuşması', 'dosya_adi' => 'Genel Şantiye Düzeni ve KKD Toolbox Konuşması.docx', 'aciklama' => 'Şantiye genel düzeni, KKD kullanımı ve temel İSG kurallarını hatırlatmak.', 'boyut' => 8471],
        ['grup' => 'santiye', 'baslik' => 'Çatı İşleri Toolbox Konuşması', 'dosya_adi' => 'Çatı İşleri Toolbox Konuşması.docx', 'aciklama' => 'Çatı bakım, onarım ve izolasyon işlerinde düşme risklerini azaltmak.', 'boyut' => 8493],
        ['grup' => 'lojistik', 'baslik' => 'Depoda Forklift Kullanımı Toolbox Konuşması', 'dosya_adi' => 'Depoda Forklift Kullanımı Toolbox Konuşması.docx', 'aciklama' => 'Depo içi forklift operasyonlarında yaya çarpması ve raf kazalarını önlemek.', 'boyut' => 8501],
        ['grup' => 'lojistik', 'baslik' => 'Depo İstifleme ve Raf Güvenliği Toolbox Konuşması', 'dosya_adi' => 'Depo İstifleme ve Raf Güvenliği Toolbox Konuşması.docx', 'aciklama' => 'Depo raf sistemlerinde güvenli istifleme ve düşme risklerini azaltmak.', 'boyut' => 8443],
        ['grup' => 'lojistik', 'baslik' => 'Yükleme-Boşaltma Rampası Toolbox Konuşması', 'dosya_adi' => 'YüklemeBoşaltma Rampası Toolbox Konuşması.docx', 'aciklama' => 'Araç yükleme/boşaltma rampalarında düşme ve sıkışma risklerini azaltmak.', 'boyut' => 8447],
        ['grup' => 'lojistik', 'baslik' => 'Elle Malzeme Elleçleme Toolbox Konuşması', 'dosya_adi' => 'Elle Malzeme Elleçleme Toolbox Konuşması.docx', 'aciklama' => 'Elle kaldırma/taşıma işlerinde bel ve sırt yaralanmalarını önlemek.', 'boyut' => 8462],
        ['grup' => 'lojistik', 'baslik' => 'Sürücü/Şoför Güvenliği Toolbox Konuşması', 'dosya_adi' => 'SürücüŞoför Güvenliği Toolbox Konuşması.docx', 'aciklama' => 'Karayolu taşımacılığında sürücü kaynaklı kazaları azaltmak.', 'boyut' => 8476],
        ['grup' => 'lojistik', 'baslik' => 'Şantiye/Depo Trafiği ve Rampa Güvenliği Toolbox Konuşması', 'dosya_adi' => 'ŞantiyeDepo Trafiği ve Rampa Güvenliği Toolbox Konuşması.docx', 'aciklama' => 'Araç trafiği ile yaya güvenliğini bir arada sağlamak.', 'boyut' => 8429],
        ['grup' => 'lojistik', 'baslik' => 'Tehlikeli Madde Taşımacılığı (ADR) Temel Bilgilendirme Toolbox Konuşması', 'dosya_adi' => 'Tehlikeli Madde Taşımacılığı ADR Temel Bilgilendirme Toolbox Konuşması.docx', 'aciklama' => 'Tehlikeli madde taşımacılığında temel güvenlik farkındalığı oluşturmak.', 'boyut' => 8519],
        ['grup' => 'lojistik', 'baslik' => 'Konteyner Açma ve Boşaltma Toolbox Konuşması', 'dosya_adi' => 'Konteyner Açma ve Boşaltma Toolbox Konuşması.docx', 'aciklama' => 'Konteyner açma/boşaltma işlerinde düşme ve sıkışma risklerini azaltmak.', 'boyut' => 8486],
        ['grup' => 'lojistik', 'baslik' => 'Akülü Forklift Şarj ve Batarya Güvenliği Toolbox Konuşması', 'dosya_adi' => 'Akülü Forklift Şarj ve Batarya Güvenliği Toolbox Konuşması.docx', 'aciklama' => 'Forklift/istif makinesi batarya şarj işlemlerinde yangın ve kimyasal riskleri azaltmak.', 'boyut' => 8525],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kayıt / başvuru (isgsuite.tr "Başvuru seçenekleri" referansı)
    |--------------------------------------------------------------------------
    | Bireysel İGU kaydı anında hesap açar (App\Filament\Auth\KayitOl); OSGB
    | başvurusu sahip onayı bekler (App\Filament\Pages\Basvurular).
    |
    | yasal_metinler: onay kutusu + "görüntüle" modalı. Metin, `gorunum` Blade
    | dosyası VARSA devreye girer (yoksa kutu gösterilmez). Metinler kullanıcı
    | tarafından sağlanır — kendimiz yasal metin YAZMIYORUZ. Metin değişince
    | `revizyon`u güncelle; her onay revizyonuyla birlikte kaydedilir.
    */
    'kayit' => [
        'osgb_deneme_gun' => 90,
        'sertifika_siniflari' => [
            'a_sinifi' => 'A Sınıfı',
            'b_sinifi' => 'B Sınıfı',
            'c_sinifi' => 'C Sınıfı',
        ],
        'yasal_metinler' => [
            // Metinler: kullanıcının verdiği Mehse_com_Hizmet_Sozlesmesi_ve_KVKK.docx (birebir).
            // Onay kutusu ifadeleri belgenin kendisinden: sözleşme md.11, KVKK md.7.
            // Kutu = `baglanti` (metni açan bağlantı) + `onay_sonrasi`.
            'sozlesme' => [
                'baslik' => 'Mehse Hizmet ve Kullanım Sözleşmesi',
                'baglanti' => 'Mehse Hizmet ve Kullanım Sözleşmesini',
                'onay_sonrasi' => 'okudum ve kabul ediyorum',
                'gorunum' => 'yasal.sozlesme',
                'revizyon' => 'Sürüm 1.1 · 30.09.2026',
            ],
            'kvkk' => [
                'baslik' => 'Mehse KVKK Aydınlatma Metni',
                'baglanti' => 'Mehse KVKK Aydınlatma Metnini',
                'onay_sonrasi' => 'okudum ve kişisel verilerimin işlenmesi hakkında bilgilendirildim.',
                'gorunum' => 'yasal.kvkk',
                'revizyon' => 'Sürüm 1.2 · 30.09.2026',
            ],
        ],
    ],

];
