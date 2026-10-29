<?php

namespace App\Support;

use App\Models\DofRaporu;
use App\Models\EgitimKatilim;
use App\Models\KkdZimmet;
use App\Models\KurulToplantisi;
use App\Models\OlayKaydi;
use App\Models\SahaDenetimi;
use App\Models\TatbikatTutanagi;
use App\Models\YillikPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Yıllık Çalışma Planı "G" (Gerçekleşti) otomasyonu (30.10.2026): sistemde kaydı olan
 * faaliyetlerin kayıt aylarını ilgili plan satırında yeşile çevirir. 720 hücreye elle
 * tıklama gerekmez. Yalnız ekler (tamamlandi'yi geri almaz); kaydı olan ay P ya da boş
 * olsa da G olur (plansız yapılan iş de gerçekleşmiştir — hucreDurumu ile aynı anlayış).
 *
 * Eşleştirme faaliyet metnindeki anahtar sözcüğe göre yapılır (metin elle değişmişse
 * o satır elle takip edilir). Kaynaklar: tatbikat tutanağı, saha denetimi, DÖF raporu,
 * İSG Kurulu toplantısı (tamamlandı), olay kaydı, KKD zimmeti, temel eğitim katılımı.
 */
class YillikPlanGerceklesme
{
    /** @return array<string, array{0: string, 1: \Closure}> kural adı => [aranan sözcük (ASCII), ay kümesi üretici] */
    private static function kurallar(): array
    {
        $aylar = fn (Collection $tarihler): array => $tarihler->filter()->map(fn ($t) => (int) $t->format('n') - 1)->unique()->values()->all();

        return [
            'tatbikat' => ['tatbikat', fn (YillikPlan $p) => $aylar(TatbikatTutanagi::query()->where('firma_id', $p->firma_id)
                ->whereIn('durum', TatbikatTutanagi::YAPILMIS)->whereYear('tatbikat_tarihi', $p->yil)->pluck('tatbikat_tarihi'))],
            'saha' => ['saha gozetimi', fn (YillikPlan $p) => $aylar(SahaDenetimi::query()->where('firma_id', $p->firma_id)
                ->whereYear('denetim_tarihi', $p->yil)->pluck('denetim_tarihi'))],
            'dof' => ['dof acilmasi', fn (YillikPlan $p) => $aylar(DofRaporu::query()->where('firma_id', $p->firma_id)
                ->whereYear('rapor_tarihi', $p->yil)->pluck('rapor_tarihi'))],
            'kurul' => ['isg kurulu uyelik', fn (YillikPlan $p) => $aylar(KurulToplantisi::query()->where('firma_id', $p->firma_id)
                ->where('durum', 'tamamlandi')->whereYear('tarih', $p->yil)->pluck('tarih'))],
            'olay' => ['kaza / ramak kala', fn (YillikPlan $p) => $aylar(OlayKaydi::query()->where('firma_id', $p->firma_id)
                ->whereYear('olay_tarihi', $p->yil)->pluck('olay_tarihi'))],
            'kkd' => ['zimmetle teslimi', fn (YillikPlan $p) => $aylar(KkdZimmet::query()->where('firma_id', $p->firma_id)
                ->whereYear('teslim_tarihi', $p->yil)->pluck('teslim_tarihi'))],
            'egitim' => ['temel isg egitimlerinin', fn (YillikPlan $p) => $aylar(EgitimKatilim::query()->where('firma_id', $p->firma_id)
                ->whereYear('belge_tarihi', $p->yil)->pluck('belge_tarihi'))],
        ];
    }

    /** Planı sistem kayıtlarına göre günceller; yeni G'ye dönen hücre sayısını verir. */
    public static function isle(YillikPlan $plan): int
    {
        $faaliyetler = $plan->faaliyetler ?? [];
        $kurallar = static::kurallar();
        $degisen = 0;

        foreach ($faaliyetler as $i => $f) {
            $metin = static::sade(($f['ana_konu'] ?? '').' '.($f['faaliyet'] ?? ''));

            foreach ($kurallar as [$aranan, $aylariBul]) {
                if (! str_contains($metin, $aranan)) {
                    continue;
                }

                $aylar = $f['aylar'] ?? array_fill(0, 12, 'bos');

                foreach ($aylariBul($plan) as $ay) {
                    if (($aylar[$ay] ?? 'bos') !== 'tamamlandi') {
                        $aylar[$ay] = 'tamamlandi';
                        $degisen++;
                    }
                }

                $faaliyetler[$i]['aylar'] = $aylar;
                break;
            }
        }

        if ($degisen > 0) {
            $plan->update(['faaliyetler' => $faaliyetler]);
        }

        return $degisen;
    }

    private static function sade(string $metin): string
    {
        return Str::of(Str::ascii(mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $metin))))->toString();
    }
}
