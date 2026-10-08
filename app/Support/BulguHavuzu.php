<?php

namespace App\Support;

use App\Models\DofRaporu;
use App\Models\SahaAnalizi;
use App\Models\SahaBulgusu;
use App\Models\TespitOneriDefteri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Saha kontrolleri "tek bulgu, çok çıktı" 3. aşama: raporların maddeleri ile
 * ortak SahaBulgusu kayıtları arasındaki bağ.
 *
 *  - esle()        Rapor kaydedilince bulgusu olmayan her madde için bulgu
 *                  açar, maddeye bulgu_id yazar (tekrar çağrılması güvenli).
 *                  DÖF maddesinin durumu bağlı bulguya yansır.
 *  - dofaYansit()  Bulgu durumu değişince ona bağlı DÖF maddeleri güncellenir.
 *  - eklenebilirler()  Rapora "Saha Bulgularından Ekle" ile eklenebilecek
 *                  açık bulgular.
 *
 * Model kancaları: DofRaporu / TespitOneriDefteri saved, SahaAnalizi saved
 * (yalnız tamamlanınca), SahaBulgusu updated (durum değişince).
 */
class BulguHavuzu
{
    /** Rapor sınıfı => [madde alanı, biçim] */
    private const RAPORLAR = [
        DofRaporu::class => ['maddeler', 'dof'],
        TespitOneriDefteri::class => ['maddeler', 'tespit_oneri'],
        SahaAnalizi::class => ['bulgular', 'gozlem'],
    ];

    /** esle() bulguyu güncellerken dofaYansit() geri tetiklenmesin. */
    private static bool $esleniyor = false;

    public static function esle(Model $rapor): void
    {
        [$alan, $bicim] = self::RAPORLAR[$rapor::class] ?? [null, null];
        $maddeler = $alan ? ($rapor->{$alan} ?? []) : [];

        if (! $maddeler || ! $rapor->firma_id) {
            return;
        }

        $bagli = SahaBulgusu::query()
            ->where('firma_id', $rapor->firma_id)
            ->whereIn('id', collect($maddeler)->pluck('bulgu_id')->filter()->all())
            ->get()->keyBy('id');

        $degisti = false;
        self::$esleniyor = true;

        try {
            foreach ($maddeler as $i => $m) {
                if (! is_array($m) || blank($m['tespit'] ?? null)) {
                    continue;
                }

                $bulgu = $bagli->get((int) ($m['bulgu_id'] ?? 0));

                if (! $bulgu) {
                    $alanlar = match ($bicim) {
                        'dof' => BulguDonusturucu::dofMaddesinden($m),
                        'gozlem' => BulguDonusturucu::gozlemMaddesinden($m),
                        default => BulguDonusturucu::tespitOneriMaddesinden($m),
                    };

                    $bulgu = SahaBulgusu::create($alanlar + [
                        'firma_id' => $rapor->firma_id,
                        'kaynak_tablo' => $rapor->getTable(),
                        'kaynak_kayit_id' => $rapor->getKey(),
                        'kaynak_sira' => $i,
                        'kaydeden' => auth()->user()?->name,
                    ]);

                    $maddeler[$i]['bulgu_id'] = $bulgu->id;
                    $degisti = true;

                    continue;
                }

                // DÖF, aksiyon takibinin yapıldığı belge: maddenin durumu bulguya geçer.
                if ($bicim === 'dof') {
                    $durum = BulguDonusturucu::dofDurumundan($m['durum'] ?? 'acik');

                    if ($bulgu->durum !== $durum) {
                        $kapandi = $durum === 'kapandi';
                        $bulgu->update([
                            'durum' => $durum,
                            'kapanis_tarihi' => $kapandi ? ($m['kapanis_tarihi'] ?? $m['kapatma_tarihi'] ?? today()->toDateString()) : null,
                            'kapanis_notu' => $kapandi ? ($m['kapanis_notu'] ?? $m['kapatma_notu'] ?? $bulgu->kapanis_notu) : $bulgu->kapanis_notu,
                        ]);
                    }
                }
            }
        } finally {
            self::$esleniyor = false;
        }

        if ($degisti) {
            $rapor->{$alan} = $maddeler;
            $rapor->saveQuietly();
        }
    }

    /** Bulgunun durumu → ona bağlı DÖF maddeleri (kapanış tarihi/notuyla). */
    public static function dofaYansit(SahaBulgusu $b): void
    {
        if (self::$esleniyor) {
            return;
        }

        $dofDurumu = BulguDonusturucu::dofDurumu($b->durum);

        DofRaporu::query()->where('firma_id', $b->firma_id)->get()
            ->each(function (DofRaporu $r) use ($b, $dofDurumu): void {
                $maddeler = $r->maddeler ?? [];
                $degisti = false;

                foreach ($maddeler as $i => $m) {
                    if ((int) ($m['bulgu_id'] ?? 0) !== $b->id || ($m['durum'] ?? 'acik') === $dofDurumu) {
                        continue;
                    }

                    $maddeler[$i]['durum'] = $dofDurumu;

                    if ($dofDurumu === 'tamamlandi') {
                        $maddeler[$i]['kapatma_tarihi'] = $b->kapanis_tarihi?->toDateString() ?? today()->toDateString();
                        $maddeler[$i]['kapatma_notu'] = $b->kapanis_notu;
                    }

                    $degisti = true;
                }

                if ($degisti) {
                    $r->maddeler = $maddeler;
                    $r->saveQuietly();
                }
            });
    }

    /**
     * Rapora eklenebilecek açık / devam eden bulgular (rapordakiler hariç).
     *
     * @param  array<int, array<string, mixed>>  $mevcutMaddeler
     * @return Collection<int, SahaBulgusu>
     */
    public static function eklenebilirler(int $firmaId, array $mevcutMaddeler = []): Collection
    {
        $haric = collect($mevcutMaddeler)->pluck('bulgu_id')->filter()->map(fn ($id) => (int) $id)->all();

        return SahaBulgusu::query()
            ->where('firma_id', $firmaId)
            ->whereIn('durum', ['acik', 'devam_ediyor'])
            ->whereNotIn('id', $haric)
            ->latest('id')
            ->get();
    }

    /** CheckboxList seçenekleri: id => "SB-2026-0004 · Kritik · uygunsuzluk…" @return array<int, string> */
    public static function secenekler(int $firmaId, array $mevcutMaddeler = []): array
    {
        return static::eklenebilirler($firmaId, $mevcutMaddeler)
            ->mapWithKeys(fn (SahaBulgusu $b) => [$b->id => $b->bulgu_no.' · '.$b->oncelikEtiketi().' · '.mb_strimwidth($b->uygunsuzluk, 0, 90, '…')])
            ->all();
    }
}
