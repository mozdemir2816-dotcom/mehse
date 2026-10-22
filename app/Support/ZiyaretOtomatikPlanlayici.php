<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\ZiyaretProgrami;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Her ayın başında çalıştırılan ziyaret takvimi doldurucu (bkz.
 * App\Console\Commands\ZiyaretTakvimiOtomatikPlanla). İki katman:
 *
 * 1. ÖZEL_KURALLAR — kullanıcının kendi seçtiği, sabit haftalık/iki haftalık
 *    gün deseni olan firmalar (unvan alt metniyle eşleşir).
 * 2. Geri kalan tüm aktif firmalar — Firma::iguAylikDk() (İSG-KATİP süresi /
 *    tehlike sınıfı × çalışan sayısı) kadar gerekli saat, günde en fazla
 *    GUNLUK_MAX_SAAT olacak şekilde iş günlerine eşit aralıklı dağıtılır.
 *
 * SADECE hâlâ boş olan ay hücreleri doldurulur — kullanıcının kendi elle
 * düzenlediği (veya önceki bir çalıştırmada dolan) aylara dokunulmaz, çünkü
 * "müşterinin istediği bazen değişiyor" ve kullanıcı takvime müdahale
 * edebiliyor.
 */
class ZiyaretOtomatikPlanlayici
{
    public const GUNLUK_MAX_SAAT = 10.0;

    /** @var array<int, array{eslesme: string, gun: int, frekans: string, haftalar?: array<int,int>, sure_saat: float, notlar: ?string}> */
    public const OZEL_KURALLAR = [
        // Carbon dayOfWeekIso: Pazartesi=1 … Pazar=7 (ZiyaretProgrami::HAFTA_GUNLERI ile aynı sıra).
        ['eslesme' => 'FOM MAKİNA', 'gun' => 4, 'frekans' => 'haftalik', 'sure_saat' => 4.0, 'notlar' => 'Öğlene kadar'],
        ['eslesme' => 'ORMAN', 'gun' => 2, 'frekans' => 'iki_haftada_bir', 'haftalar' => [1, 3], 'sure_saat' => 4.0, 'notlar' => null],
    ];

    /**
     * @return array{firma: int, doldurulan_ay: int, atlanan_ay: int, detay: array<int, string>}
     */
    public static function calistir(int $yil, ?Carbon $baslangic = null): array
    {
        $baslangic = ($baslangic ?? now())->copy()->startOfDay();
        $rapor = ['firma' => 0, 'doldurulan_ay' => 0, 'atlanan_ay' => 0, 'detay' => []];

        foreach (Firma::query()->where('aktif', true)->get() as $firma) {
            $ozel = static::ozelKural($firma->unvan);
            $program = ZiyaretProgrami::firmaYilIcin($firma, $yil);
            $aylar = $program->ziyaretler ?? array_fill(0, 12, [ZiyaretProgrami::bosGirdi()]);
            $degisti = false;

            for ($ay = 0; $ay < 12; $ay++) {
                $ayBaslangic = Carbon::create($yil, $ay + 1, 1);

                if ($ayBaslangic->copy()->endOfMonth()->lt($baslangic->copy()->startOfMonth())) {
                    continue; // geçmiş ay — dokunma
                }

                if (! static::ayBosMu($aylar[$ay] ?? null)) {
                    $rapor['atlanan_ay']++;

                    continue; // zaten planlanmış (elle veya önceki çalıştırmadan) — dokunma
                }

                $girdiler = $ozel
                    ? static::ozelKuraldanGirdiler($ozel, $yil, $ay, $baslangic)
                    : static::otomatikGirdiler($firma, $yil, $ay, $baslangic);

                if (! $girdiler) {
                    continue;
                }

                $aylar[$ay] = $girdiler;
                $degisti = true;
                $rapor['doldurulan_ay']++;
            }

            if ($degisti) {
                $program->update(['ziyaretler' => $aylar]);
                $rapor['firma']++;
                $rapor['detay'][] = $firma->unvan;
            }
        }

        return $rapor;
    }

    /** @return array{eslesme: string, gun: int, frekans: string, haftalar?: array<int,int>, sure_saat: float, notlar: ?string}|null */
    private static function ozelKural(string $unvan): ?array
    {
        $normalize = fn (string $s) => mb_strtoupper(strtr($s, ['ı' => 'I', 'i' => 'İ']), 'UTF-8');
        $unvanBuyuk = $normalize($unvan);

        foreach (self::OZEL_KURALLAR as $kural) {
            if (str_contains($unvanBuyuk, $normalize($kural['eslesme']))) {
                return $kural;
            }
        }

        return null;
    }

    /** Ay hücresi hâlâ "boş" mu (elle/otomatik hiç dokunulmamış varsayılan girdi). */
    private static function ayBosMu(?array $ay): bool
    {
        $girdiler = ZiyaretProgrami::ayGirdileri($ay);

        return count($girdiler) === 1
            && blank($girdiler[0]['tarih'] ?? null)
            && ($girdiler[0]['durum'] ?? 'bos') === 'bos';
    }

    /** @return array<int, array<string, mixed>>|null */
    private static function ozelKuraldanGirdiler(array $kural, int $yil, int $ayIndex, Carbon $baslangic): array
    {
        $ayBaslangic = Carbon::create($yil, $ayIndex + 1, 1);
        $altSinir = $baslangic->gt($ayBaslangic) ? $baslangic->copy() : $ayBaslangic->copy();
        $gunler = static::ayinGunleri($ayBaslangic, $altSinir)
            ->filter(fn (Carbon $g) => $g->dayOfWeekIso === $kural['gun'])
            ->filter(fn (Carbon $g) => $kural['frekans'] !== 'iki_haftada_bir' || in_array(static::haftaNo($g), $kural['haftalar'], true))
            ->values();

        return $gunler->map(fn (Carbon $g) => [
            'tarih' => $g->toDateString(),
            'amac' => 'Genel Saha Gözetimi',
            'durum' => 'planlandi',
            'sure_saat' => $kural['sure_saat'],
            'notlar' => $kural['notlar'],
        ])->all() ?: null;
    }

    /** @return array<int, array<string, mixed>>|null */
    private static function otomatikGirdiler(Firma $firma, int $yil, int $ayIndex, Carbon $baslangic): ?array
    {
        $dk = $firma->iguAylikDk();

        if ($dk <= 0) {
            return null;
        }

        $saat = $dk / 60;
        $gunSayisi = max(1, (int) ceil($saat / self::GUNLUK_MAX_SAAT));

        $ayBaslangic = Carbon::create($yil, $ayIndex + 1, 1);
        $altSinir = $baslangic->gt($ayBaslangic) ? $baslangic->copy() : $ayBaslangic->copy();
        $isGunleri = static::ayinGunleri($ayBaslangic, $altSinir)->filter(fn (Carbon $g) => $g->isWeekday())->values();

        if ($isGunleri->isEmpty()) {
            return null;
        }

        $secili = static::esitAralikliSec($isGunleri, $gunSayisi);
        $kalanSaat = $saat;
        $girdiler = [];

        foreach ($secili as $tarih) {
            $buGun = min(self::GUNLUK_MAX_SAAT, $kalanSaat);
            $girdiler[] = [
                'tarih' => $tarih->toDateString(),
                'amac' => 'Genel Saha Gözetimi',
                'durum' => 'planlandi',
                'sure_saat' => round($buGun, 1),
                'notlar' => 'Otomatik planlandı — İSG-KATİP süresine göre bu ay '.round($saat, 1).' saat gerekli.',
            ];
            $kalanSaat -= $buGun;
        }

        return $girdiler;
    }

    /** @return Collection<int, Carbon> ayBaslangic'ten ayın sonuna, altSinir'den erken olmayan günler */
    private static function ayinGunleri(Carbon $ayBaslangic, Carbon $altSinir): Collection
    {
        $baslangic = $altSinir->gt($ayBaslangic) ? $altSinir->copy() : $ayBaslangic->copy();
        $bitis = $ayBaslangic->copy()->endOfMonth();

        if ($baslangic->gt($bitis)) {
            return collect();
        }

        return collect(CarbonPeriod::create($baslangic, $bitis))->map(fn ($d) => Carbon::instance($d));
    }

    /** Ayın kaçıncı haftası (1-7=1, 8-14=2, 15-21=3, 22-28=4, 29-31=5). */
    private static function haftaNo(Carbon $gun): int
    {
        return (int) ceil($gun->day / 7);
    }

    /** @param  Collection<int, Carbon>  $gunler @return Collection<int, Carbon> */
    private static function esitAralikliSec(Collection $gunler, int $adet): Collection
    {
        if ($gunler->count() <= $adet) {
            return $gunler;
        }

        $aralik = $gunler->count() / $adet;
        $secili = collect();

        for ($i = 0; $i < $adet; $i++) {
            $secili->push($gunler[(int) floor($i * $aralik)]);
        }

        return $secili->unique(fn (Carbon $g) => $g->toDateString())->values();
    }
}
