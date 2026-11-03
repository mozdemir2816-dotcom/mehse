<?php

namespace App\Support;

use App\Models\EgitimKatilim;
use App\Models\Firma;
use App\Models\YillikPlan;
use Illuminate\Support\Collection;

/**
 * Yıllık Eğitim Planı'nın 4. bölümü ("İşe ve İşyerine Özgü Riskler") ile Eğitim Katılım formunun işyerine özgü
 * bölümünü tek kaynak gibi tutar (30.10.2026):
 *  - formdanPlana: formda kaydedilen işe özgü konular, o firmanın o yılki planının 4. bölümüne yazılır
 *    (zaten plandaki konuların ay işaretleri korunur, formda olmayanlar çıkar, yenileri 2. hafta / tüm aylar P eklenir).
 *  - naceOrtakSatirlar: kütüphaneden eşleşme çıkmayan yeni planlarda, NACE'si aynı diğer firmaların son planındaki
 *    4. bölüm konuları (önce tam kod, sonra sınıf xx.yy, sonra bölüm xx) başlangıç olarak gelir.
 */
class IseOzguSenkron
{
    public static function formdanPlana(EgitimKatilim $kayit): bool
    {
        $icerik = $kayit->konu_secimleri ?? [];
        $firma = $kayit->firma;

        if ($kayit->baslik_anahtari !== 'genel' || ($icerik['tip'] ?? 'genel') !== 'genel' || ! $firma || ! $kayit->belge_tarihi) {
            return false;
        }

        $konular = collect($icerik['isyerine_ozgu']['maddeler'] ?? [])
            ->filter(fn ($m) => ($m['dahil'] ?? true) && filled(trim((string) ($m['madde'] ?? ''))))
            ->map(fn ($m) => trim((string) $m['madde']))
            ->unique(fn (string $k) => mb_strtolower($k))
            ->values();

        // Formda hiç işe özgü konu yoksa planı boşaltma — kullanıcı büyük olasılıkla bu bölümü doldurmadı.
        if ($konular->isEmpty()) {
            return false;
        }

        $yil = (int) $kayit->belge_tarihi->year;
        $plan = YillikPlan::query()->where('firma_id', $firma->id)->where('yil', $yil)->first();

        if (! $plan) {
            return false;
        }

        // Çalışan grubu / iş kolu formu: plan firma geneli olduğundan bu grubun konuları plana EKLENİR, planda
        // zaten olan diğer grupların konuları çıkarılmaz (aksi hâlde ikinci grup formu birincinin konusunu siler).
        if (filled($kayit->is_kolu)) {
            $konular = collect($plan->egitimler ?? [])->where('kategori', 'ise_ozgu')->pluck('konu')
                ->map(fn ($k) => trim((string) $k))->filter()->merge($konular)
                ->unique(fn (string $k) => mb_strtolower($k))->values();
        }

        $kilit = $firma->planKilitAyIndeksi($yil);
        $mevcutlar = collect($plan->egitimler ?? [])->where('kategori', 'ise_ozgu')
            ->keyBy(fn ($e) => mb_strtolower(trim((string) ($e['konu'] ?? ''))));
        $kutuphane = IseOzguEgitimKutuphanesi::tumu($firma->user_id)
            ->mapWithKeys(fn ($k, $a) => [mb_strtolower(trim($k['ad'])) => $a]);
        $tumu = IseOzguEgitimKutuphanesi::tumu($firma->user_id);

        $satirlar = $konular->map(function (string $konu) use ($mevcutlar, $kutuphane, $tumu, $kilit): array {
            $anahtar = mb_strtolower($konu);

            if ($mevcutlar->has($anahtar)) {
                return $mevcutlar[$anahtar];
            }

            if ($kutuphane->has($anahtar)) {
                return IseOzguEgitimKutuphanesi::planSatiri($tumu[$kutuphane[$anahtar]], $kilit);
            }

            return YillikPlan::maddeAylarIle([[
                'konu' => $konu,
                'kategori' => 'ise_ozgu',
                'hedef' => '',
                'egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI,
                'hafta' => 2,
                'varsayilan_aylar' => range(0, 11),
            ]], $kilit)[0];
        })->all();

        $yeni = YillikPlanSablonu::iseOzguYerlestir($plan->egitimler ?? [], $satirlar);

        if ($yeni === ($plan->egitimler ?? [])) {
            return false;
        }

        $plan->update(['egitimler' => $yeni]);

        return true;
    }

    /**
     * Kullanıcının NACE'si aynı diğer firmalarının en son planındaki 4. bölüm satırları (ay işaretleri sıfırlanır).
     * Önce tam kod, sonra sınıf, sonra bölüm; ilk dolu eşleşme kullanılır.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function naceOrtakSatirlar(Firma $firma, int $yil, int $kilitAy): array
    {
        $rakam = preg_replace('/\D/', '', (string) $firma->nace_kodu);

        if (strlen($rakam) < 2) {
            return [];
        }

        $onekler = array_unique(array_filter([
            strlen($rakam) >= 6 ? substr($rakam, 0, 2).'.'.substr($rakam, 2, 2).'.'.substr($rakam, 4, 2) : null,
            strlen($rakam) >= 4 ? substr($rakam, 0, 2).'.'.substr($rakam, 2, 2) : null,
            substr($rakam, 0, 2),
        ]));

        foreach ($onekler as $onek) {
            $planlar = YillikPlan::query()
                ->where('firma_id', '!=', $firma->id)
                ->whereHas('firma', fn ($q) => $q->withoutGlobalScopes()->where('user_id', $firma->user_id)->where('nace_kodu', 'like', $onek.'%'))
                ->orderByDesc('yil')->orderByDesc('id')
                ->get();

            /** @var Collection<int, array<string, mixed>> $satirlar */
            $satirlar = $planlar
                ->map(fn (YillikPlan $p) => collect($p->egitimler ?? [])->where('kategori', 'ise_ozgu')->values())
                ->first(fn (Collection $s) => $s->isNotEmpty());

            if ($satirlar) {
                return YillikPlan::maddeAylarIle(
                    $satirlar->map(fn (array $e) => collect($e)->only(['konu', 'kategori', 'hedef', 'egitici', 'hafta', 'varsayilan_aylar', 'kutuphane_anahtari'])
                        ->put('varsayilan_aylar', $e['varsayilan_aylar'] ?? range(0, 11))->all())->all(),
                    $kilitAy,
                );
            }
        }

        return [];
    }
}
