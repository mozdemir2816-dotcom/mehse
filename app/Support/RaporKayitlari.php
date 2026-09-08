<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Profilim > Raporlar — sistemde üretilen HER belge türünü tek listede
 * birleştirir (isgpratik 146.jpg). config isg.raporlar.kaynaklar'daki her
 * modelin kayıtlarını "created_at" üzerinden çekip ortak bir satır şekline
 * dökme + birleştirip sıralama işini yapar. Yeni bir belge modülü eklenince
 * buraya kod yazmaya gerek yok — config'e tek satır yeter.
 */
class RaporKayitlari
{
    /**
     * @return array<int, array{kayit: Model, kaynak: array, baslik: string, tip: string, firma: ?string, tarih: Carbon}>
     */
    public static function hepsi(int $userId, ?string $arama = null, ?string $tipFiltre = null): array
    {
        $satirlar = collect();

        foreach (config('isg.raporlar.kaynaklar', []) as $kaynak) {
            /** @var class-string<Model> $model */
            $model = $kaynak['model'];

            $kayitlar = $model::query()
                ->whereHas('firma', fn ($q) => $q->where('user_id', $userId))
                ->with('firma:id,unvan')
                ->latest()
                ->limit(100)
                ->get();

            foreach ($kayitlar as $kayit) {
                $tip = $kaynak['tip_metod'] && method_exists($kayit, $kaynak['tip_metod'])
                    ? $kayit->{$kaynak['tip_metod']}()
                    : $kaynak['ad'];

                $satirlar->push([
                    'kayit' => $kayit,
                    'kaynak' => $kaynak,
                    'baslik' => ($kayit->firma?->unvan ?? '—').' — '.$tip,
                    'tip' => $kaynak['ad'],
                    'firma' => $kayit->firma?->unvan,
                    'tarih' => $kayit->created_at,
                ]);
            }
        }

        if (filled($arama)) {
            $aramaNorm = mb_strtolower($arama);
            $satirlar = $satirlar->filter(fn (array $s) => str_contains(mb_strtolower($s['baslik']), $aramaNorm));
        }

        if (filled($tipFiltre)) {
            $satirlar = $satirlar->filter(fn (array $s) => $s['kaynak']['ad'] === $tipFiltre);
        }

        return $satirlar->sortByDesc('tarih')->values()->all();
    }

    /** İkincil format butonunun (Word/Yıldız Grup vb.) bu kayıt için gösterilip gösterilmeyeceği. */
    public static function ikincilUygunMu(array $kaynak, Model $kayit): bool
    {
        if (! ($kaynak['ikincil_uretici'] ?? null)) {
            return false;
        }

        return match ($kaynak['ikincil_uretici']) {
            SertifikaYildizGrupUretici::class => SertifikaYildizGrupUretici::uygunMu($kayit),
            AtamaYazisiWordUretici::class => AtamaYazisiWordUretici::sablonVarMi($kayit->rol_anahtari),
            default => true,
        };
    }

    /** @return Collection<int, string> Raporlar'da tip filtresi seçenekleri */
    public static function tipSecenekleri(): Collection
    {
        return collect(config('isg.raporlar.kaynaklar', []))->pluck('ad');
    }
}
