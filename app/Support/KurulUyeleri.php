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

    /**
     * Kaşeli görevliler (başkan / İGU / hekim). Kurul tutanağında adları ARTIK
     * basılır (kullanıcı isteği 06.10.2026); toplantı kaydında ad boşsa firma
     * kaydından doldurulur, İGU/hekimde belge numarası görev sütununa eklenir.
     */
    public const KASELI_ROLLER = ['baskan', 'sekreter', 'hekim'];

    /** Atama Yazıları rolü => kurul rolü (kurul rolü boş katılımcıda otomatik). */
    private const ATAMA_KURUL_ROLU = [
        'isveren_vekili' => 'baskan',
        'calisan_temsilcisi' => 'calisan_temsilcisi',
    ];

    /**
     * Tutanak çıktısı için katılımcılar, görevleri otomatik zenginleştirilmiş:
     * - is_gorevi: firmanın çalışan kaydındaki görev (işe giriş bildirgesi /
     *   İSG-KATİP aktarımı); çalışan değilse (İGU, hekim) toplantıdaki görev.
     * - kurul_gorevi: kurul rolü; rol yoksa kişinin Atama Yazıları'ndaki
     *   görevlendirmesi (işveren vekili → başkan, çalışan temsilcisi; diğer
     *   atamalar "Kurul Üyesi (Destek Elemanı...)" olarak).
     *
     * @return array<int, array{ad_soyad: string, ad_basilir: bool, is_gorevi: string, kurul_gorevi: string, katildi: bool}>
     */
    public static function tutanakKatilimcilari(\App\Models\KurulToplantisi $toplanti): array
    {
        $firma = $toplanti->firma;
        $anahtar = fn (?string $ad): string => preg_replace('/\s+/u', ' ', trim(mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], (string) $ad))));

        $calisanGorevi = $firma
            ? $firma->calisanlar()->whereNotNull('gorev')->get(['ad_soyad', 'gorev'])
                ->mapWithKeys(fn ($c) => [$anahtar($c->ad_soyad) => $c->gorev])
            : collect();

        // Kişi adı => Atama Yazıları rol anahtarları
        $atamalar = [];

        foreach ($firma?->atamaYazilari()->get(['rol_anahtari', 'uyeler']) ?? [] as $a) {
            foreach ((array) $a->uyeler as $u) {
                if (filled($u['ad_soyad'] ?? null)) {
                    $atamalar[$anahtar($u['ad_soyad'])][] = $a->rol_anahtari;
                }
            }
        }

        $oneriler = $firma ? self::oneriler($firma) : [];
        $profesyoneller = ['sekreter' => $firma?->igu, 'hekim' => $firma?->isyeriHekimi];

        return collect($toplanti->katilimcilar ?? [])
            ->map(function (array $k) use ($anahtar, $calisanGorevi, $atamalar, $oneriler, $profesyoneller): array {
                $kayitRolu = $k['rol'] ?? null;

                // Ad boşsa firma kaydından: işveren vekili / atanmış İGU / işyeri hekimi
                if (blank($k['ad_soyad'] ?? null) && in_array($kayitRolu, self::KASELI_ROLLER, true)) {
                    $k['ad_soyad'] = $oneriler[$kayitRolu]['ad_soyad'] ?? '';
                    $k['gorev'] = filled($k['gorev'] ?? null) ? $k['gorev'] : ($oneriler[$kayitRolu]['gorev'] ?? null);
                }

                // İGU / hekim: firmaya atanmış profesyonelse unvan + belge numarası
                $profesyonel = $profesyoneller[$kayitRolu] ?? null;
                $profesyonelGorevi = $profesyonel && $anahtar($profesyonel->ad_soyad) === $anahtar($k['ad_soyad'] ?? '')
                    ? ($profesyonel->unvan ?: $profesyonel->tipEtiketi()).(filled($profesyonel->sertifika_no) ? ' — Belge No: '.$profesyonel->sertifika_no : '')
                    : null;

                $ad = $anahtar($k['ad_soyad'] ?? '');
                $kisiAtamalari = array_values(array_unique($atamalar[$ad] ?? []));
                $rol = filled($k['rol'] ?? null) && $k['rol'] !== 'diger' ? $k['rol'] : null;

                foreach ($kisiAtamalari as $atama) {
                    $rol ??= self::ATAMA_KURUL_ROLU[$atama] ?? null;
                }

                $kurulGorevi = $rol
                    ? config("isg.kurul_toplantisi.roller.{$rol}.ad", $rol)
                    : ($kisiAtamalari
                        ? 'Kurul Üyesi ('.collect($kisiAtamalari)->map(fn ($r) => config("isg.atama.roller.{$r}.ad", $r))->implode(', ').')'
                        : 'Kurul Üyesi');

                return [
                    'ad_soyad' => (string) ($k['ad_soyad'] ?? ''),
                    'ad_basilir' => true,
                    'is_gorevi' => (string) ($profesyonelGorevi ?? $calisanGorevi[$ad] ?? $k['gorev'] ?? '') ?: '—',
                    'kurul_gorevi' => $kurulGorevi,
                    'katildi' => (bool) ($k['katildi'] ?? false),
                ];
            })
            ->values()
            ->all();
    }
}
