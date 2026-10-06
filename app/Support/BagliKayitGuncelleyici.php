<?php

namespace App\Support;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\IsgProfesyoneli;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Personel / firma / İSG profesyoneli bilgisi düzeltilince, o bilginin
 * KOPYALANARAK saklandığı tüm evraklarda da güncellenmesi (kullanıcı isteği
 * 06.10.2026). Evraklar kişi bilgisini anlık kopya olarak tutar (tutanak,
 * atama yazısı, iş izni, eğitim katılım...); ilişkiyle bağlı kayıtlar zaten
 * günceldir, burada yalnız kopyalar ele alınır.
 *
 * Kural: yalnız ESKİ değerle birebir eşleşen yer değişir (aynı firmada, ad
 * Türkçe büyük/küçük harf ve boşluk farkı gözetilmeden; T.C. varsa T.C. ile).
 * Elle farklı yazılmış değerlere dokunulmaz. Model `updated` olaylarından
 * çağrılır (AppServiceProvider); güncellenen kayıt sayısı `$sonuc`ta birikir.
 */
class BagliKayitGuncelleyici
{
    /** Son işlemlerde güncellenen evrak sayısı (tablo => adet) — bildirim için. */
    public static array $sonuc = [];

    /** Düz sütunda kişi kopyası tutan tablolar: [tablo, ad, tc, görev, calisan_id]. */
    private const KISI_SUTUNLARI = [
        ['ceza_teblig_tutanaklari', 'calisan_ad_soyad', 'calisan_tc', 'calisan_gorev', null],
        ['is_kazasi_raporlari', 'kazazede_ad_soyad', 'kazazede_tc', 'kazazede_gorev', null],
        ['isbasi_egitim_tutanaklari', 'calisan_ad_soyad', 'calisan_tc', null, null],
        ['ise_donus_belgeleri', 'calisan_ad_soyad', 'calisan_tc', 'gorev', null],
        ['meslek_hastaligi_bildirimleri', 'calisan_ad_soyad', 'calisan_tc', 'calisan_gorevi', null],
        ['muayene_formlari', 'calisan_ad_soyad', 'calisan_tc', 'calisan_gorevi', null],
        ['olay_kayitlari', 'etkilenen_ad_soyad', null, 'etkilenen_gorev', null],
        ['olay_kayitlari', 'bildiren_ad_soyad', null, null, null],
        ['kurul_uyeleri', 'ad_soyad', null, 'gorev', 'calisan_id'],
        ['kkd_zimmetleri', 'personel_ad_soyad', null, null, 'calisan_id'],
    ];

    /** JSON içinde kişi listesi / adı tutan sütunlar: tablo => [sütunlar]. */
    private const KISI_JSON = [
        'kurul_toplantilari' => ['katilimcilar'],
        'atama_yazilari' => ['uyeler'],
        'is_izin_formlari' => ['calisanlar'],
        'kkd_zimmet_formlari' => ['calisanlar'],
        'egitim_katilimlari' => ['katilimcilar'],
        'egitim_sinavlari' => ['katilimcilar'],
        'sertifikalar' => ['katilimcilar'],
        'tatbikat_tutanaklari' => ['katilimcilar', 'ekipler'],
        'saha_denetimleri' => ['ekip_uyeleri'],
        'calisan_temsilcisi_secimleri' => ['adaylar'],
        'ceza_teblig_tutanaklari' => ['taniklar'],
        'is_kazasi_raporlari' => ['taniklar'],
        'olay_kayitlari' => ['taniklar'],
        'acil_durum_planlari' => ['ekipler'],
        'risk_degerlendirmeleri' => ['ekip'],
    ];

    /** İşveren / işveren vekili adını kopyalayan düz sütunlar. */
    private const ISVEREN_SUTUNLARI = [
        ['atama_yazilari', 'isveren_vekili_adi'],
        ['dof_raporlari', 'isveren_vekili_adi'],
        ['saha_analizleri', 'isveren_vekili_adi'],
        ['olay_kayitlari', 'isveren_vekili'],
        ['tatbikat_tutanaklari', 'isveren_vekili'],
    ];

    /** İGU / işyeri hekimi adını kopyalayan düz sütunlar. */
    private const PROFESYONEL_SUTUNLARI = [
        ['egitim_katilimlari', 'isg_uzmani_adi'],
        ['egitim_katilimlari', 'isyeri_hekimi_adi'],
        ['sertifikalar', 'egitici_igu_adi'],
        ['sertifikalar', 'egitici_hekim_adi'],
        ['muayene_formlari', 'hekim_adi'],
        ['ise_donus_belgeleri', 'hekim_adi'],
        ['is_kazasi_raporlari', 'isyeri_hekimi_adi'],
        ['saha_denetimleri', 'denetci_adi'],
    ];

    private static array $sutunOnbellek = [];

    /*
    |--------------------------------------------------------------------------
    | Olay girişleri
    |--------------------------------------------------------------------------
    */

    public static function calisanGuncellendi(Calisan $calisan): void
    {
        $degisen = array_intersect_key($calisan->getChanges(), array_flip(['ad_soyad', 'tc', 'gorev']));

        if (! $degisen || ! $calisan->firma_id) {
            return;
        }

        $eski = [
            'ad' => (string) $calisan->getOriginal('ad_soyad'),
            'tc' => (string) $calisan->getOriginal('tc'),
            'gorev' => (string) $calisan->getOriginal('gorev'),
        ];
        $yeni = ['ad' => (string) $calisan->ad_soyad, 'tc' => (string) $calisan->tc, 'gorev' => (string) $calisan->gorev];

        self::kisiyiGuncelle([(int) $calisan->firma_id], $eski, $yeni, (int) $calisan->getKey());

        // Acil durum ekip üyeleri firmaya ekip üzerinden bağlı — calisan_id ile.
        if (self::sutunVar('acil_ekip_uyeleri', 'calisan_id')) {
            foreach (DB::table('acil_ekip_uyeleri')->where('calisan_id', $calisan->getKey())->get(['id', 'ad_soyad', 'gorev']) as $s) {
                $guncel = ['ad_soyad' => $yeni['ad']];
                if (self::ayni($s->gorev, $eski['gorev']) || blank($s->gorev)) {
                    $guncel['gorev'] = $yeni['gorev'] ?: $s->gorev;
                }
                self::satirGuncelle('acil_ekip_uyeleri', $s->id, (array) $s, $guncel);
            }
        }
    }

    public static function firmaGuncellendi(Firma $firma): void
    {
        $degisen = $firma->getChanges();

        foreach (['isveren_vekili', 'isveren_ad'] as $alan) {
            $eski = (string) $firma->getOriginal($alan);

            if (! array_key_exists($alan, $degisen) || blank($eski) || blank($firma->{$alan})) {
                continue;
            }

            $kisiEski = ['ad' => $eski, 'tc' => '', 'gorev' => ''];
            $kisiYeni = ['ad' => (string) $firma->{$alan}, 'tc' => '', 'gorev' => ''];
            self::kisiyiGuncelle([(int) $firma->getKey()], $kisiEski, $kisiYeni);
            self::adSutunlariniGuncelle([(int) $firma->getKey()], self::ISVEREN_SUTUNLARI, $eski, (string) $firma->{$alan});
        }

        if (array_key_exists('unvan', $degisen) && filled($firma->getOriginal('unvan')) && self::sutunVar('risk_degerlendirmeleri', 'firma_unvan')) {
            $adet = DB::table('risk_degerlendirmeleri')->where('firma_id', $firma->getKey())
                ->where('firma_unvan', $firma->getOriginal('unvan'))->update(['firma_unvan' => $firma->unvan]);
            self::say('risk_degerlendirmeleri', $adet);
        }
    }

    public static function profesyonelGuncellendi(IsgProfesyoneli $profesyonel): void
    {
        $eski = (string) $profesyonel->getOriginal('ad_soyad');

        if (! array_key_exists('ad_soyad', $profesyonel->getChanges()) || blank($eski)) {
            return;
        }

        // Profesyonelin hesabındaki ve atandığı firmalar
        $firmalar = Firma::withoutGlobalScopes()
            ->where(fn ($q) => $q->where('user_id', $profesyonel->user_id)
                ->orWhere('igu_id', $profesyonel->getKey())
                ->orWhere('isyeri_hekimi_id', $profesyonel->getKey()))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        $yeni = (string) $profesyonel->ad_soyad;
        self::kisiyiGuncelle($firmalar, ['ad' => $eski, 'tc' => '', 'gorev' => ''], ['ad' => $yeni, 'tc' => '', 'gorev' => '']);
        self::adSutunlariniGuncelle($firmalar, self::PROFESYONEL_SUTUNLARI, $eski, $yeni);
    }

    /** Kaydetme sonrası bildirim (Filament): kaç evrakta güncellendiği. */
    public static function bildir(): void
    {
        $toplam = self::sonucuAl();

        if ($toplam > 0) {
            \Filament\Notifications\Notification::make()
                ->title("Bağlı {$toplam} evrak da güncellendi")
                ->body('Tutanak, atama yazısı, iş izni, eğitim katılım gibi kayıtlardaki eski bilgi yenisiyle değiştirildi.')
                ->success()->send();
        }
    }

    /** Biriken sonucu okuyup sıfırlar: toplam evrak sayısı. */
    public static function sonucuAl(): int
    {
        $toplam = array_sum(self::$sonuc);
        self::$sonuc = [];

        return $toplam;
    }

    /*
    |--------------------------------------------------------------------------
    | Çekirdek
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<int, int>  $firmaIdleri
     * @param  array{ad: string, tc: string, gorev: string}  $eski
     * @param  array{ad: string, tc: string, gorev: string}  $yeni
     */
    private static function kisiyiGuncelle(array $firmaIdleri, array $eski, array $yeni, ?int $calisanId = null): void
    {
        if (! $firmaIdleri || (blank($eski['ad']) && blank($eski['tc']))) {
            return;
        }

        // 1) Düz sütunlar
        foreach (self::KISI_SUTUNLARI as [$tablo, $adS, $tcS, $gorevS, $idS]) {
            if (! self::sutunVar($tablo, $adS) || ! self::sutunVar($tablo, 'firma_id')) {
                continue;
            }

            $tcS = $tcS && self::sutunVar($tablo, $tcS) ? $tcS : null;
            $gorevS = $gorevS && self::sutunVar($tablo, $gorevS) ? $gorevS : null;
            $idS = $idS && $calisanId && self::sutunVar($tablo, $idS) ? $idS : null;

            $satirlar = DB::table($tablo)->whereIn('firma_id', $firmaIdleri)
                ->get(array_values(array_filter(['id', $adS, $tcS, $gorevS, $idS])));

            foreach ($satirlar as $s) {
                $eslesti = ($idS && (int) $s->{$idS} === $calisanId)
                    || ($tcS && filled($eski['tc']) && (string) $s->{$tcS} === $eski['tc'])
                    || (filled($eski['ad']) && self::ayni($s->{$adS}, $eski['ad']));

                if (! $eslesti) {
                    continue;
                }

                $guncel = [$adS => $yeni['ad']];
                if ($tcS && filled($yeni['tc'])) {
                    $guncel[$tcS] = $yeni['tc'];
                }
                if ($gorevS && filled($yeni['gorev']) && (self::ayni($s->{$gorevS}, $eski['gorev']) || blank($s->{$gorevS}))) {
                    $guncel[$gorevS] = $yeni['gorev'];
                }

                self::satirGuncelle($tablo, $s->id, (array) $s, $guncel);
            }
        }

        // 2) JSON kopyalar
        foreach (self::KISI_JSON as $tablo => $sutunlar) {
            $sutunlar = array_values(array_filter($sutunlar, fn ($s) => self::sutunVar($tablo, $s)));

            if (! $sutunlar || ! self::sutunVar($tablo, 'firma_id')) {
                continue;
            }

            foreach (DB::table($tablo)->whereIn('firma_id', $firmaIdleri)->get(['id', ...$sutunlar]) as $s) {
                $guncel = [];

                foreach ($sutunlar as $sutun) {
                    $veri = json_decode((string) $s->{$sutun}, true);

                    if (! is_array($veri)) {
                        continue;
                    }

                    $degisti = false;
                    $yeniVeri = self::jsonGez($veri, $eski, $yeni, $degisti);

                    if ($degisti) {
                        $guncel[$sutun] = json_encode($yeniVeri, JSON_UNESCAPED_UNICODE);
                    }
                }

                if ($guncel) {
                    DB::table($tablo)->where('id', $s->id)->update($guncel);
                    self::say($tablo);
                }
            }
        }
    }

    /** Kişi listesi / iç içe yapı: eşleşen kişi nesnesini ya da ad metnini değiştirir. */
    private static function jsonGez(array $veri, array $eski, array $yeni, bool &$degisti): array
    {
        $adAnahtari = collect(['ad_soyad', 'ad', 'isim', 'adi_soyadi'])->first(fn ($a) => array_key_exists($a, $veri) && is_string($veri[$a]));

        if ($adAnahtari !== null) {
            $tcAnahtari = collect(['tc', 'tc_no', 'tckn'])->first(fn ($a) => array_key_exists($a, $veri));
            $eslesti = ($tcAnahtari && filled($eski['tc']) && (string) $veri[$tcAnahtari] === $eski['tc'])
                || (filled($eski['ad']) && self::ayni($veri[$adAnahtari], $eski['ad']));

            if ($eslesti) {
                $once = $veri;
                $veri[$adAnahtari] = $yeni['ad'];

                if ($tcAnahtari && filled($yeni['tc'])) {
                    $veri[$tcAnahtari] = $yeni['tc'];
                }

                foreach (['gorev', 'unvan', 'gorevi'] as $g) {
                    if (array_key_exists($g, $veri) && filled($yeni['gorev'])
                        && (self::ayni($veri[$g], $eski['gorev']) || blank($veri[$g]))) {
                        $veri[$g] = $yeni['gorev'];
                    }
                }

                $degisti = $degisti || $once !== $veri;

                return $veri;
            }
        }

        foreach ($veri as $k => $v) {
            if (is_array($v)) {
                $veri[$k] = self::jsonGez($v, $eski, $yeni, $degisti);
            } elseif (is_string($v) && array_is_list($veri) && filled($eski['ad']) && self::ayni($v, $eski['ad'])) {
                // Yalnız ad listeleri (örn. ekipler: {sondurme: ["Ali Veli"]})
                $veri[$k] = $yeni['ad'];
                $degisti = true;
            }
        }

        return $veri;
    }

    /** @param  array<int, array{0: string, 1: string}>  $sutunlar */
    private static function adSutunlariniGuncelle(array $firmaIdleri, array $sutunlar, string $eski, string $yeni): void
    {
        foreach ($sutunlar as [$tablo, $sutun]) {
            if (! $firmaIdleri || ! self::sutunVar($tablo, $sutun) || ! self::sutunVar($tablo, 'firma_id')) {
                continue;
            }

            foreach (DB::table($tablo)->whereIn('firma_id', $firmaIdleri)->get(['id', $sutun]) as $s) {
                if (self::ayni($s->{$sutun}, $eski)) {
                    self::satirGuncelle($tablo, $s->id, (array) $s, [$sutun => $yeni]);
                }
            }
        }
    }

    private static function satirGuncelle(string $tablo, int $id, array $mevcut, array $guncel): void
    {
        $guncel = array_filter($guncel, fn ($v, $k) => (string) ($mevcut[$k] ?? '') !== (string) $v, ARRAY_FILTER_USE_BOTH);

        if ($guncel) {
            DB::table($tablo)->where('id', $id)->update($guncel);
            self::say($tablo);
        }
    }

    private static function say(string $tablo, int $adet = 1): void
    {
        if ($adet > 0) {
            self::$sonuc[$tablo] = (self::$sonuc[$tablo] ?? 0) + $adet;
        }
    }

    /** Türkçe büyük/küçük harf ve fazla boşluk farkı gözetmeden eşitlik. */
    public static function ayni(mixed $a, mixed $b): bool
    {
        // ı/i eşit sayılır: ASCII büyük harfle yazılmış "OZDEMIR" = "Özdemir"deki "ozdemir".
        $n = fn ($s) => str_replace('ı', 'i', preg_replace('/\s+/u', ' ', trim(mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], (string) $s)))));

        return $n($a) !== '' && $n($a) === $n($b);
    }

    private static function sutunVar(string $tablo, string $sutun): bool
    {
        return self::$sutunOnbellek[$tablo.'.'.$sutun] ??= Schema::hasTable($tablo) && Schema::hasColumn($tablo, $sutun);
    }
}
