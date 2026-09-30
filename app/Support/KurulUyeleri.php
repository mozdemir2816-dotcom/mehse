<?php

namespace App\Support;

use App\Models\Firma;
use App\Models\KurulUyesi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * İSG Kurulu üyelik kuralları (İSG Kurulları Hakkında Yönetmelik):
 * - Md.6 üyelik rolleri + zorunluluk → config isg.kurul_toplantisi.roller
 * - firma kaydından otomatik aday önerisi (işveren, İGU, hekim, çalışan temsilcisi)
 * - eksik zorunlu üyeler → toplantı yalnız "Taslak" kaydedilebilir
 * - Md.9 toplantı periyodu → sonraki toplantı önerisi
 */
class KurulUyeleri
{
    /** @return array<string, array{ad: string, zorunlu: bool, kaynak: ?string}> */
    public static function roller(): array
    {
        return config('isg.kurul_toplantisi.roller', []);
    }

    /** @return Collection<int, KurulUyesi> */
    public static function aktifUyeler(Firma $firma): Collection
    {
        return $firma->kurulUyeleri()->where('aktif', true)->get()
            ->sortBy(fn (KurulUyesi $u) => array_search($u->rol, array_keys(self::roller()), true))
            ->values();
    }

    /**
     * Rol başına firma kaydından önerilen kişi — kaynak yoksa ya da firmada
     * atama boşsa null ("Bu işyerine işyeri hekimi atanmamış" uyarısı).
     *
     * @return array<string, array{ad_soyad: string, gorev: ?string}|null>
     */
    public static function oneriler(Firma $firma): array
    {
        $oneri = [];

        foreach (self::roller() as $rol => $tanim) {
            $oneri[$rol] = match ($tanim['kaynak'] ?? null) {
                'isveren' => filled($firma->isveren_vekili)
                    ? ['ad_soyad' => $firma->isveren_vekili, 'gorev' => 'İşveren Vekili']
                    : (filled($firma->isveren_ad) ? ['ad_soyad' => $firma->isveren_ad, 'gorev' => 'İşveren'] : null),
                'igu' => $firma->igu ? ['ad_soyad' => $firma->igu->ad_soyad, 'gorev' => $firma->igu->tipEtiketi()] : null,
                'hekim' => $firma->isyeriHekimi ? ['ad_soyad' => $firma->isyeriHekimi->ad_soyad, 'gorev' => 'İşyeri Hekimi'] : null,
                'temsilci' => ($aday = $firma->calisanTemsilcisiSecimi?->secilenAday())
                    ? ['ad_soyad' => $aday['ad_soyad'], 'gorev' => $aday['unvan'] ?: 'Çalışan Temsilcisi']
                    : null,
                default => null,
            };
        }

        return $oneri;
    }

    /** @return array<string, string> eksik zorunlu rol anahtarı => etiket */
    public static function eksikZorunlular(Firma $firma): array
    {
        $dolu = self::aktifUyeler($firma)->pluck('rol')->unique()->all();

        return collect(self::roller())
            ->filter(fn (array $t, string $rol): bool => ($t['zorunlu'] ?? false) && ! in_array($rol, $dolu, true))
            ->map(fn (array $t): string => $t['ad'])
            ->all();
    }

    /** Toplantı tarihinden Md.9 periyoduna göre sonraki toplantı önerisi. */
    public static function sonrakiToplanti(Firma $firma, Carbon|string|null $tarih = null): Carbon
    {
        $ay = (int) config("isg.kurul_toplantisi.periyot_ay.{$firma->tehlike_sinifi}", 3);

        return Carbon::parse($tarih ?? now())->addMonthsNoOverflow($ay);
    }

    /** "Ayda bir" / "2 ayda bir" — ekranda periyot bilgisi. */
    public static function periyotEtiketi(Firma $firma): string
    {
        $ay = (int) config("isg.kurul_toplantisi.periyot_ay.{$firma->tehlike_sinifi}", 3);

        return $ay === 1 ? 'Ayda bir' : "{$ay} ayda bir";
    }

    /**
     * Toplantıya kopyalanacak katılımcı listesi (tarihsel snapshot).
     *
     * @return array<int, array{ad_soyad: string, gorev: ?string, rol: string, katildi: bool}>
     */
    public static function katilimciSnapshot(Firma $firma): array
    {
        return self::aktifUyeler($firma)
            ->map(fn (KurulUyesi $u): array => [
                'ad_soyad' => $u->ad_soyad,
                'gorev' => $u->gorev,
                'rol' => $u->rol,
                'katildi' => true,
            ])
            ->all();
    }
}
