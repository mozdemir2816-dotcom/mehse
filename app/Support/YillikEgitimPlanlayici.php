<?php

namespace App\Support;

use App\Models\Firma;

/**
 * Yıllık Eğitim Planı Online / Yüz Yüze (Karma) dağıtım ve RG 02.04.2026 (33212)
 * mevzuat uyumluluk hesaplayıcısı.
 *
 * Kurallar (Çalışanların İSG Eğitimlerinin Usul ve Esasları Hk. Yönetmelik — RG 02.04.2026):
 *  - İlk defa temel eğitim: Az Tehlikeli 8 saat, Tehlikeli 12 saat, Çok Tehlikeli 16 saat.
 *  - Yenileme (tekrar) eğitimi: tüm sınıflarda 8 saat (periyot: 3 / 2 / 1 yıl).
 *  - Md.12/3: 4. konu başlığı (İşe ve İşyerine Özgü Riskler) Tehlikeli ve Çok Tehlikeli
 *    sınıflarda ZORUNLU yüz yüze verilir (3 saat / 4 saat); Az Tehlikeli'de (2 saat) uzaktan verilebilir.
 *  - 5. Diğer Eğitimler (Söndürme, Kurtarma, İlkyardımcı, İşe Başlama Md.7): zorunlu yüz yüze / uygulamalı.
 */
class YillikEgitimPlanlayici
{
    public const VARSAYILAN_MOD = 'karma_lms';

    public const MODLAR = [
        'karma_lms' => 'Karma — MEHSE LMS (8s/4s Online + Kalanı Yüz Yüze)',
        'karma_tavan' => 'Karma — Mevzuat Online Tavanı (1–3. Bölüm Online, 4. Bölüm Yüz Yüze)',
        'yuz_yuze' => 'Tamamen Yüz Yüze (Klasik)',
    ];

    public const YONTEMLER = [
        'uzaktan' => 'Online (Uzaktan)',
        'yuz_yuze' => 'Yüz Yüze',
        'karma' => 'Karma (Online + Yüz Yüze)',
    ];

    /** Çok tehlikeli sınıfta pratik/saha ağırlığı nedeniyle yüz yüze planlanan teknik konular. */
    public const COK_TEHLIKELI_YUZ_YUZE_TEKNIK = ['T4', 'T5', 'T10', 'T12'];

    /** Çok tehlikeli sınıfta karma (teorik online + uygulama yüz yüze) sağlık konusu: İlk yardım. */
    public const COK_TEHLIKELI_KARMA_SAGLIK = ['S4'];

    public static function tehlikeSinifi(?Firma $firma): string
    {
        $sinif = $firma?->tehlike_sinifi;

        return in_array($sinif, ['az_tehlikeli', 'tehlikeli', 'cok_tehlikeli'], true)
            ? $sinif
            : 'cok_tehlikeli';
    }

    /** RG 02.04.2026 Md.12/3 ve Md.7 gereği bu satır online (uzaktan) verilemez mi? */
    public static function zorunluYuzYuzeMi(array $satir, ?Firma $firma): bool
    {
        $kat = $satir['kategori'] ?? null;

        if ($kat === 'diger') {
            return true;
        }

        if ($kat === 'ise_ozgu') {
            $zorunluSiniflar = (array) config('isg.uzaktan_egitim.yuz_yuze_dorduncu_konu', ['tehlikeli', 'cok_tehlikeli']);

            return in_array(self::tehlikeSinifi($firma), $zorunluSiniflar, true);
        }

        return false;
    }

    /**
     * Seçilen plan moduna ve firmanın tehlike sınıfına göre 4 temel bölümün
     * yasal saat kırılımını (İlk Defa + Tekrar) döndürür.
     *
     * @return array{
     *     mod: string,
     *     tehlike: string,
     *     bolumler: array<string, array{ilk_online: float, ilk_yuz_yuze: float, ilk_toplam: float, tekrar_online: float, tekrar_yuz_yuze: float, tekrar_toplam: float}>,
     *     ilk_online: float,
     *     ilk_yuz_yuze: float,
     *     ilk_toplam: float,
     *     tekrar_online: float,
     *     tekrar_yuz_yuze: float,
     *     tekrar_toplam: float
     * }
     */
    public static function bolumDagilimi(?Firma $firma, string $mod = self::VARSAYILAN_MOD): array
    {
        $mod = array_key_exists($mod, self::MODLAR) ? $mod : self::VARSAYILAN_MOD;
        $sinif = self::tehlikeSinifi($firma);

        if ($mod === 'yuz_yuze') {
            $b = match ($sinif) {
                'az_tehlikeli' => 2.0,
                'tehlikeli' => 3.0,
                default => 4.0,
            };
            $bolumler = [
                'genel' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => $b, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 2.0],
                'saglik' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => $b, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 2.0],
                'teknik' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => $b, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 2.0],
                'ise_ozgu' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => $b, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 2.0],
            ];
        } elseif ($mod === 'karma_tavan') {
            $bolumler = match ($sinif) {
                'az_tehlikeli' => [
                    'genel' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    'saglik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    'teknik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    'ise_ozgu' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                ],
                'tehlikeli' => [
                    'genel' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'saglik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'teknik' => ['ilk_online' => 4.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    // Kullanıcı kararı (24.11.2026): Tehlikeli sınıfta 4. bölüm en az 3 saat yerine 4 saat yüz yüze; tekrar da 4s online + 4s yüz yüze.
                    'ise_ozgu' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => 4.0, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 4.0],
                ],
                default => [
                    'genel' => ['ilk_online' => 4.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'saglik' => ['ilk_online' => 4.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'teknik' => ['ilk_online' => 4.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    'ise_ozgu' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => 4.0, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 4.0],
                ],
            };
        } else {
            // karma_lms (Varsayılan): MEHSE LMS 8 saat (ilk defa) / 4 saat (yenileme) paketiyle birebir uyumlu.
            $bolumler = match ($sinif) {
                'az_tehlikeli' => [
                    'genel' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'saglik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'teknik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 2.0],
                    'ise_ozgu' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 2.0],
                ],
                'tehlikeli' => [
                    'genel' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'saglik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'teknik' => ['ilk_online' => 4.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    // Kullanıcı kararı (24.11.2026): Tehlikeli sınıfta 4. bölüm en az 3 saat yerine 4 saat yüz yüze planlanır (8s Online + 4s YY = 12s; Tekrar 4s Online + 4s YY = 8s).
                    'ise_ozgu' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => 4.0, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 4.0],
                ],
                default => [
                    'genel' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 0.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'saglik' => ['ilk_online' => 2.0, 'ilk_yuz_yuze' => 1.0, 'tekrar_online' => 1.0, 'tekrar_yuz_yuze' => 0.0],
                    'teknik' => ['ilk_online' => 4.0, 'ilk_yuz_yuze' => 3.0, 'tekrar_online' => 2.0, 'tekrar_yuz_yuze' => 0.0],
                    'ise_ozgu' => ['ilk_online' => 0.0, 'ilk_yuz_yuze' => 4.0, 'tekrar_online' => 0.0, 'tekrar_yuz_yuze' => 4.0],
                ],
            };
        }

        $ilkOnline = $ilkYy = $tekOnline = $tekYy = 0.0;
        foreach ($bolumler as $k => $v) {
            $bolumler[$k]['ilk_toplam'] = $v['ilk_online'] + $v['ilk_yuz_yuze'];
            $bolumler[$k]['tekrar_toplam'] = $v['tekrar_online'] + $v['tekrar_yuz_yuze'];
            $ilkOnline += $v['ilk_online'];
            $ilkYy += $v['ilk_yuz_yuze'];
            $tekOnline += $v['tekrar_online'];
            $tekYy += $v['tekrar_yuz_yuze'];
        }

        return [
            'mod' => $mod,
            'tehlike' => $sinif,
            'bolumler' => $bolumler,
            'ilk_online' => $ilkOnline,
            'ilk_yuz_yuze' => $ilkYy,
            'ilk_toplam' => $ilkOnline + $ilkYy,
            'tekrar_online' => $tekOnline,
            'tekrar_yuz_yuze' => $tekYy,
            'tekrar_toplam' => $tekOnline + $tekYy,
        ];
    }

    /**
     * Mevcut plan satırlarına seçilen moda göre yöntem (`uzaktan` | `yuz_yuze` | `karma`)
     * ve saat dağılımını (`sure_saat`, `online_saat`, `yuz_yuze_saat`, `plan_modu`) uygular.
     *
     * @param  array<int, array<string, mixed>>  $egitimler
     * @return array<int, array<string, mixed>>
     */
    public static function satirlaraUygula(array $egitimler, ?Firma $firma, string $mod = self::VARSAYILAN_MOD): array
    {
        $dagilim = self::bolumDagilimi($firma, $mod);
        $sinif = $dagilim['tehlike'];

        // Önce her satıra hedef yöntemi ata
        $satirlar = [];
        foreach ($egitimler as $s) {
            $kat = $s['kategori'] ?? 'genel';
            $kod = $s['kod'] ?? EgitimKonulari::kodBul($s['konu'] ?? null);

            if ($mod === 'yuz_yuze' || self::zorunluYuzYuzeMi($s, $firma)) {
                $yontem = 'yuz_yuze';
            } elseif ($mod === 'karma_tavan') {
                $yontem = 'uzaktan';
            } else {
                // karma_lms
                if ($kat === 'saglik' && $sinif === 'cok_tehlikeli' && in_array($kod, self::COK_TEHLIKELI_KARMA_SAGLIK, true)) {
                    $yontem = 'karma';
                } elseif ($kat === 'teknik' && $sinif === 'cok_tehlikeli' && in_array($kod, self::COK_TEHLIKELI_YUZ_YUZE_TEKNIK, true)) {
                    $yontem = 'yuz_yuze';
                } else {
                    $yontem = 'uzaktan';
                }
            }

            $s['yontem'] = $yontem;
            $s['plan_modu'] = $mod;
            $satirlar[] = $s;
        }

        // 4 temel bölümün saatlerini satırlara dengeli dağıt (toplam bölüm saati birebir tutar)
        foreach (['genel', 'saglik', 'teknik', 'ise_ozgu'] as $kat) {
            $b = $dagilim['bolumler'][$kat];
            $indeksler = [];
            $onlineIdx = [];
            $yyIdx = [];

            foreach ($satirlar as $i => $s) {
                if (($s['kategori'] ?? null) !== $kat) {
                    continue;
                }
                $indeksler[] = $i;
                if (($s['yontem'] ?? 'yuz_yuze') === 'uzaktan') {
                    $onlineIdx[] = $i;
                } elseif (($s['yontem'] ?? 'yuz_yuze') === 'yuz_yuze') {
                    $yyIdx[] = $i;
                } else {
                    // karma satır hem online hem yüz yüze havuzundan pay alır
                    $onlineIdx[] = $i;
                    $yyIdx[] = $i;
                }
            }

            if ($indeksler === []) {
                continue;
            }

            $onlinePay = self::saatDagit($b['ilk_online'], $onlineIdx);
            $yyPay = self::saatDagit($b['ilk_yuz_yuze'], $yyIdx);

            foreach ($indeksler as $i) {
                $on = $onlinePay[$i] ?? 0.0;
                $yy = $yyPay[$i] ?? 0.0;
                $satirlar[$i]['online_saat'] = $on;
                $satirlar[$i]['yuz_yuze_saat'] = $yy;
                $satirlar[$i]['sure_saat'] = round($on + $yy, 2);
            }
        }

        // 5. Diğer Eğitimler: konu adındaki "(4 saat)" gibi süreyi oku, tamamı yüz yüze
        foreach ($satirlar as $i => $s) {
            if (($s['kategori'] ?? null) !== 'diger') {
                continue;
            }

            $saat = self::digerSaatCikar($s);
            $satirlar[$i]['yontem'] = 'yuz_yuze';
            $satirlar[$i]['online_saat'] = 0.0;
            $satirlar[$i]['yuz_yuze_saat'] = $saat;
            if ($saat > 0) {
                $satirlar[$i]['sure_saat'] = $saat;
            }
        }

        return $satirlar;
    }

    /** Mevcut satırlardan planın aktif modunu tespit eder. */
    public static function moduTespitEt(array $egitimler, ?Firma $firma): string
    {
        foreach ($egitimler as $s) {
            if (isset($s['plan_modu']) && array_key_exists($s['plan_modu'], self::MODLAR)) {
                return (string) $s['plan_modu'];
            }
        }

        $temel = collect($egitimler)->whereIn('kategori', ['genel', 'saglik', 'teknik']);
        if ($temel->isEmpty() || ! $temel->contains(fn ($s) => in_array($s['yontem'] ?? null, ['uzaktan', 'karma'], true))) {
            return 'yuz_yuze';
        }

        return $temel->contains(fn ($s) => in_array($s['yontem'] ?? null, ['yuz_yuze', 'karma'], true))
            ? 'karma_lms'
            : 'karma_tavan';
    }

    /**
     * Ekrandaki Yasal Süre & Uyumluluk Özet Bandı için canlı hesaplama.
     *
     * @param  array<int, array<string, mixed>>  $egitimler
     * @return array<string, mixed>
     */
    public static function ozet(array $egitimler, ?Firma $firma): array
    {
        $mod = self::moduTespitEt($egitimler, $firma);
        $dagilim = self::bolumDagilimi($firma, $mod);
        $sinif = $dagilim['tehlike'];

        $onlineSayi = 0;
        $yySayi = 0;
        $karmaSayi = 0;
        $onlineSaat = 0.0;
        $yySaat = 0.0;
        $digerSaat = 0.0;
        $uyarilar = [];
        $mevzuatIhlali = false;

        $satirSaatVarMi = collect($egitimler)
            ->whereIn('kategori', ['genel', 'saglik', 'teknik', 'ise_ozgu'])
            ->contains(fn ($s) => array_key_exists('online_saat', $s) || array_key_exists('yuz_yuze_saat', $s));

        foreach ($egitimler as $s) {
            $kat = $s['kategori'] ?? 'genel';
            $yontem = $s['yontem'] ?? 'yuz_yuze';

            if ($kat === 'diger') {
                $digerSaat += self::digerSaatCikar($s);
                if ($yontem !== 'yuz_yuze') {
                    $mevzuatIhlali = true;
                    $uyarilar[] = '"'.($s['konu'] ?? 'Diğer eğitim').'" uygulamalı/özel eğitim olduğu için yüz yüze planlanmalıdır.';
                }
                continue;
            }

            if ($yontem === 'uzaktan') {
                $onlineSayi++;
            } elseif ($yontem === 'karma') {
                $karmaSayi++;
            } else {
                $yySayi++;
            }

            if (self::zorunluYuzYuzeMi($s, $firma) && $yontem !== 'yuz_yuze') {
                $mevzuatIhlali = true;
                $uyarilar[] = 'RG 02.04.2026 Md.12/3 gereği "'.($s['konu'] ?? 'İşe özgü konu').'" bu tehlike sınıfında yüz yüze verilmelidir.';
            }

            if ($satirSaatVarMi) {
                $onlineSaat += (float) ($s['online_saat'] ?? 0);
                $yySaat += (float) ($s['yuz_yuze_saat'] ?? 0);
            }
        }

        if (! $satirSaatVarMi) {
            $onlineSaat = $dagilim['ilk_online'];
            $yySaat = $dagilim['ilk_yuz_yuze'];
        }

        $onlineSaat = round($onlineSaat, 1);
        $yySaat = round($yySaat, 1);
        $toplamSaat = round($onlineSaat + $yySaat, 1);
        $yasalIlk = $dagilim['ilk_toplam'];

        if ($toplamSaat + 0.05 < $yasalIlk) {
            $uyarilar[] = 'Planlanan temel eğitim süresi ('.self::saatMetni($toplamSaat).') yasal asgarinin ('.self::saatMetni($yasalIlk).') altında.';
        }

        return [
            'mod' => $mod,
            'mod_etiketi' => self::MODLAR[$mod],
            'tehlike' => $sinif,
            'tehlike_etiketi' => match ($sinif) {
                'az_tehlikeli' => 'Az Tehlikeli',
                'tehlikeli' => 'Tehlikeli',
                default => 'Çok Tehlikeli',
            },
            'yasal_ilk_saat' => $yasalIlk,
            'yasal_tekrar_saat' => $dagilim['tekrar_toplam'],
            'ilk_online' => $onlineSaat,
            'ilk_yuz_yuze' => $yySaat,
            'ilk_toplam' => $toplamSaat,
            'tekrar_online' => $dagilim['tekrar_online'],
            'tekrar_yuz_yuze' => $dagilim['tekrar_yuz_yuze'],
            'tekrar_toplam' => $dagilim['tekrar_toplam'],
            'online_konu' => $onlineSayi,
            'yuz_yuze_konu' => $yySayi,
            'karma_konu' => $karmaSayi,
            'diger_saat' => round($digerSaat, 1),
            'bolumler' => $dagilim['bolumler'],
            'uyumlu' => ! $mevzuatIhlali && $uyarilar === [],
            'uyarilar' => $uyarilar,
        ];
    }

    /**
     * Excel çıktısı (YillikPlanExcelUretici) için dinamik RG 02.04.2026 bölüm
     * açıklamaları ve alt süre şeridi metinlerini üretir.
     *
     * @param  array<int, array<string, mixed>>  $egitimler
     * @return array{toplam_sure: string, bolum_sureleri: array<string, string>, bolum_aciklamalari: array<string, string>}
     */
    public static function excelBolumBilgileri(array $egitimler, ?Firma $firma): array
    {
        $ozet = self::ozet($egitimler, $firma);
        $mod = $ozet['mod'];
        $b = $ozet['bolumler'];

        // Bölümlerin gerçek satır saatleri varsa onları kullan
        $gercek = [];
        foreach (['genel', 'saglik', 'teknik', 'ise_ozgu'] as $kat) {
            $satirlar = collect($egitimler)->where('kategori', $kat);
            if ($satirlar->contains(fn ($s) => array_key_exists('online_saat', $s) || array_key_exists('yuz_yuze_saat', $s))) {
                $on = round((float) $satirlar->sum(fn ($s) => (float) ($s['online_saat'] ?? 0)), 1);
                $yy = round((float) $satirlar->sum(fn ($s) => (float) ($s['yuz_yuze_saat'] ?? 0)), 1);
                $gercek[$kat] = ['on' => $on, 'yy' => $yy, 'top' => round($on + $yy, 1)];
            } else {
                $gercek[$kat] = ['on' => $b[$kat]['ilk_online'], 'yy' => $b[$kat]['ilk_yuz_yuze'], 'top' => $b[$kat]['ilk_toplam']];
            }
        }

        $toplamOnline = round(array_sum(array_column($gercek, 'on')), 1);
        $toplamYy = round(array_sum(array_column($gercek, 'yy')), 1);
        $toplam = round($toplamOnline + $toplamYy, 1);

        $toplamSure = $mod === 'yuz_yuze'
            ? self::saatSayi($toplam).' SAAT (YÜZ YÜZE)'
            : self::saatSayi($toplam).' SAAT ('.self::saatSayi($toplamOnline).'s ONLİNE + '.self::saatSayi($toplamYy).'s YÜZ YÜZE)';

        $etiket = fn (string $baslik, string $kat): string => sprintf(
            '%s: %s (%s) / Tekrar: %s',
            $baslik,
            self::saatSayi($gercek[$kat]['top']).' SAAT',
            self::kisaYontemMetni($gercek[$kat]['on'], $gercek[$kat]['yy']),
            self::kisaYontemMetni($b[$kat]['tekrar_online'], $b[$kat]['tekrar_yuz_yuze']),
        );

        $bolumSureleri = [
            'genel' => $etiket('GENEL KONULAR', 'genel'),
            'saglik' => $etiket('SAĞLIK KONULARI', 'saglik'),
            'teknik' => $etiket('TEKNİK KONULAR', 'teknik'),
            'ise_ozgu' => $etiket('İŞE ÖZGÜ RİSKLER', 'ise_ozgu'),
        ];

        $aciklama = function (string $kat, string $ekNot) use ($gercek, $b, $firma): string {
            $g = $gercek[$kat];
            $ilkKirilim = self::uzunYontemMetni($g['on'], $g['yy']);
            $tekKirilim = self::uzunYontemMetni($b[$kat]['tekrar_online'], $b[$kat]['tekrar_yuz_yuze']);
            $zorunlu = $kat === 'ise_ozgu' && self::zorunluYuzYuzeMi(['kategori' => 'ise_ozgu'], $firma);

            $metin = "İlk defa temel İSG eğitiminde toplam ".self::saatMetni($g['top'])." ({$ilkKirilim}), tekrar eğitiminde ".self::saatMetni($b[$kat]['tekrar_toplam'])." ({$tekKirilim}) uygulanacaktır.";
            if ($zorunlu) {
                $metin .= ' RG 02.04.2026 (33212) Md.12/3 gereği bu bölüm zorunlu olarak yüz yüze verilir.';
            }

            return $metin.' '.$ekNot;
        };

        $bolumAciklamalari = [
            'genel' => $aciklama('genel', 'İlgili eğitim grubundaki tüm çalışanlar katılacaktır; uzaktan eğitim kısmı LMS üzerinden ön/son test ve aktif yoklama ile tamamlanır.'),
            'saglik' => $aciklama('saglik', 'İçerik yapılan işten kaynaklanan sağlık riskleri ve korunma yöntemlerine göre işyeri hekimi tarafından yürütülür.'),
            'teknik' => $aciklama('teknik', 'Teorik konular uzaktan, saha/uygulama ağırlıklı konular (yangın, KKD, ekipman, tahliye) işyeri şartlarına göre yüz yüze verilir.'),
            'ise_ozgu' => $aciklama('ise_ozgu', 'İçerik işyerinin risk değerlendirmesi, acil durum planı ve varsa patlamadan korunma dokümanı esas alınarak hazırlanır.'),
        ];

        return [
            'toplam_sure' => $toplamSure,
            'bolum_sureleri' => $bolumSureleri,
            'bolum_aciklamalari' => $bolumAciklamalari,
        ];
    }

    /**
     * Eğitim Katılım Formu için 3 ayrı oturum modunu (yuz_yuze | uzaktan | karma)
     * konu bloklarına ve dakikalara uygular (1 ders saati = 45 dk fiili + 15 dk dinlenme).
     *
     *  - yuz_yuze: Tüm 4 bölüm tam süreyle yüz yüze (8/12/16s ilk; 8s tekrar).
     *  - uzaktan : Yalnızca online (LMS) konular ve online süre (8s ilk / 4s tekrar; tehlikeli & çok tehlikelide 4. bölüm hariç).
     *  - karma   : Online sonrası yüz yüze verilecek kalan konular (Tehlikeli & Tekrar: 4s; Çok Tehlikeli ilk: 8s).
     *
     * @param  array<string, mixed>  $icerik
     * @return array<string, mixed>
     */
    public static function katilimFormuModuUygula(array $icerik, ?Firma $firma, string $egitimSekli, string $egitimTuru = 'ilk'): array
    {
        if (($icerik['tip'] ?? null) !== 'genel') {
            return $icerik;
        }

        $sinif = self::tehlikeSinifi($firma);
        $tekrar = $egitimTuru === 'tekrar';
        $dagilim = self::bolumDagilimi($firma, 'karma_lms');
        $b = $dagilim['bolumler'];

        $olcekle = static function (array $maddeler, float $hedefSaat, ?array $sadeceKodlar = null): array {
            $hedefDk = (int) round($hedefSaat * 45);
            if ($hedefDk <= 0) {
                return array_map(fn (array $m) => [...$m, 'dahil' => false], $maddeler);
            }

            $aktifIdx = [];
            foreach ($maddeler as $i => $m) {
                $kod = $m['kod'] ?? EgitimKonulari::kodBul($m['madde'] ?? null);
                $secili = $sadeceKodlar === null || in_array($kod, $sadeceKodlar, true);
                if ($secili) {
                    $aktifIdx[] = $i;
                }
            }

            $adet = count($aktifIdx);
            $taban = $adet > 0 ? intdiv($hedefDk, $adet) : 0;
            $kalan = $adet > 0 ? ($hedefDk - ($taban * $adet)) : 0;
            $sira = 0;

            foreach ($maddeler as $i => $m) {
                if (in_array($i, $aktifIdx, true)) {
                    $dk = max(1, $taban + ($sira < $kalan ? 1 : 0));
                    $maddeler[$i]['dahil'] = true;
                    $maddeler[$i]['dakika'] = $dk;
                    $sira++;
                } else {
                    $maddeler[$i]['dahil'] = false;
                }
            }

            return $maddeler;
        };

        $k = $tekrar ? 'tekrar' : 'ilk';
        $toplamYasal = (int) round($dagilim["{$k}_toplam"]);
        $toplamOnline = (int) round($dagilim["{$k}_online"]);
        $toplamYy = (int) round($dagilim["{$k}_yuz_yuze"]);

        if ($egitimSekli === 'uzaktan') {
            $icerik['genel_konular'] = $olcekle($icerik['genel_konular'] ?? [], $b['genel']["{$k}_online"]);
            $icerik['saglik_konulari'] = $olcekle($icerik['saglik_konulari'] ?? [], $b['saglik']["{$k}_online"]);
            $icerik['teknik_konular'] = $olcekle($icerik['teknik_konular'] ?? [], $b['teknik']["{$k}_online"]);
            if (isset($icerik['isyerine_ozgu']['maddeler'])) {
                $icerik['isyerine_ozgu']['maddeler'] = $olcekle($icerik['isyerine_ozgu']['maddeler'], $b['ise_ozgu']["{$k}_online"]);
            }
            $icerik['saat'] = $toplamOnline;
            $icerik['form_modu'] = 'uzaktan';
            $icerik['oturum_notu'] = $toplamYy > 0
                ? "Uzaktan (Online / LMS) Eğitim Oturumu: {$toplamOnline} Ders Saati. Kalan {$toplamYy} Ders Saati (4. Bölüm dahil) yüz yüze tamamlama oturumunda ayrı form ile verilir."
                : "Uzaktan (Online / LMS) Eğitim Oturumu: {$toplamOnline} Ders Saati (tüm konular uzaktan eğitim ile tamamlanmıştır).";

            return $icerik;
        }

        if ($egitimSekli === 'karma') {
            $icerik['genel_konular'] = $olcekle($icerik['genel_konular'] ?? [], $b['genel']["{$k}_yuz_yuze"]);
            $icerik['saglik_konulari'] = $olcekle(
                $icerik['saglik_konulari'] ?? [],
                $b['saglik']["{$k}_yuz_yuze"],
                ($sinif === 'cok_tehlikeli' && ! $tekrar) ? self::COK_TEHLIKELI_KARMA_SAGLIK : null,
            );
            $icerik['teknik_konular'] = $olcekle(
                $icerik['teknik_konular'] ?? [],
                $b['teknik']["{$k}_yuz_yuze"],
                ($sinif === 'cok_tehlikeli' && ! $tekrar) ? self::COK_TEHLIKELI_YUZ_YUZE_TEKNIK : null,
            );
            if (isset($icerik['isyerine_ozgu']['maddeler'])) {
                $icerik['isyerine_ozgu']['maddeler'] = $olcekle($icerik['isyerine_ozgu']['maddeler'], $b['ise_ozgu']["{$k}_yuz_yuze"]);
            }
            $icerik['saat'] = $toplamYy > 0 ? $toplamYy : $toplamYasal;
            $icerik['form_modu'] = 'karma';
            $icerik['oturum_notu'] = "Karma Eğitim — Yüz Yüze Tamamlama Oturumu: {$toplamYy} Ders Saati (Toplam {$toplamYasal}s = {$toplamOnline}s Uzaktan/LMS + {$toplamYy}s Yüz Yüze). Bu imza tutanağı yüz yüze oturuma aittir.";

            return $icerik;
        }

        // yuz_yuze: Tüm konular yüz yüze (tam süre)
        $icerik['genel_konular'] = $olcekle($icerik['genel_konular'] ?? [], $b['genel']["{$k}_toplam"]);
        $icerik['saglik_konulari'] = $olcekle($icerik['saglik_konulari'] ?? [], $b['saglik']["{$k}_toplam"]);
        $icerik['teknik_konular'] = $olcekle($icerik['teknik_konular'] ?? [], $b['teknik']["{$k}_toplam"]);
        if (isset($icerik['isyerine_ozgu']['maddeler'])) {
            $icerik['isyerine_ozgu']['maddeler'] = $olcekle($icerik['isyerine_ozgu']['maddeler'], $b['ise_ozgu']["{$k}_toplam"]);
        }
        $icerik['saat'] = $toplamYasal;
        $icerik['form_modu'] = 'yuz_yuze';
        $icerik['oturum_notu'] = null;

        return $icerik;
    }

    /**
     * Excel "Eğitici" hücresi için kısa yöntem etiketi.
     */
    public static function excelYontemEtiketi(?string $yontem): string
    {
        return match ($yontem) {
            'uzaktan' => 'ONLİNE (UZAKTAN)',
            'karma' => 'KARMA (ONLİNE + YÜZ YÜZE)',
            'yuz_yuze' => 'YÜZ YÜZE',
            default => '',
        };
    }

    public static function saatMetni(float|int|string|null $saat): string
    {
        if ($saat === null || $saat === '') {
            return '—';
        }
        $n = (float) $saat;

        return self::saatSayi($n).' saat';
    }

    public static function saatSayi(float $n): string
    {
        $r = round($n, 2);
        if (abs($r - round($r)) < 0.01) {
            return (string) (int) round($r);
        }

        return rtrim(rtrim(number_format($r, 2, ',', ''), '0'), ',');
    }

    private static function kisaYontemMetni(float $on, float $yy): string
    {
        if ($on > 0 && $yy > 0) {
            return self::saatSayi($on).'s Online + '.self::saatSayi($yy).'s Yüz Yüze';
        }
        if ($on > 0) {
            return self::saatSayi($on).'s Online';
        }

        return self::saatSayi($yy).'s Yüz Yüze';
    }

    private static function uzunYontemMetni(float $on, float $yy): string
    {
        if ($on > 0 && $yy > 0) {
            return self::saatMetni($on).' uzaktan/online + '.self::saatMetni($yy).' yüz yüze';
        }
        if ($on > 0) {
            return 'tamamı uzaktan/online';
        }

        return 'tamamı yüz yüze';
    }

    /**
     * Toplam saati verilen satır indekslerine 0,25 saatlik adımlarla tam bölünerek dağıtır;
     * toplam yuvarlama farkı son satıra eklenir, toplam birebir korunur.
     *
     * @param  array<int, int>  $indeksler
     * @return array<int, float>
     */
    private static function saatDagit(float $toplam, array $indeksler): array
    {
        $adet = count($indeksler);
        if ($adet === 0 || $toplam <= 0) {
            return [];
        }

        // Çeyrek saat (15 dk) biriminde tam bölüştür
        $toplamCeyrek = (int) round($toplam * 4);
        $taban = intdiv($toplamCeyrek, $adet);
        $kalan = $toplamCeyrek - ($taban * $adet);

        $sonuc = [];
        foreach ($indeksler as $pos => $idx) {
            $ceyrek = $taban + ($pos < $kalan ? 1 : 0);
            $sonuc[$idx] = round($ceyrek / 4, 2);
        }

        return $sonuc;
    }

    private static function digerSaatCikar(array $satir): float
    {
        if (isset($satir['sure_saat']) && is_numeric($satir['sure_saat']) && (float) $satir['sure_saat'] > 0) {
            return (float) $satir['sure_saat'];
        }

        if (preg_match('/\((\d+(?:[.,]\d+)?)\s*saat\)/iu', (string) ($satir['konu'] ?? ''), $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return 0.0;
    }
}
