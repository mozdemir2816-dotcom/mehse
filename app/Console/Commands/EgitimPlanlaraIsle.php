<?php

namespace App\Console\Commands;

use App\Models\Firma;
use App\Models\YillikPlan;
use App\Support\EgitimKonulari;
use Illuminate\Console\Command;

/**
 * Geçmiş temel İSG eğitimi kayıtlarını yıllık eğitim planlarına "tamamlandı"
 * olarak işler (EgitimKonulari::kayitlariPlanaIsle). Planlar önce ortak konu
 * listesine çevrilir (YillikPlan::firmaYilIcin). Tekrar çalıştırılabilir.
 * Canlıda shell olmadığı için aynı iş Yıllık Eğitim Planı sayfasındaki
 * "Verilen Eğitimleri İşle" düğmesiyle (ve planın ilk açılışında) yapılır.
 */
class EgitimPlanlaraIsle extends Command
{
    protected $signature = 'egitim:planlara-isle';

    protected $description = 'Geçmiş eğitim katılım kayıtlarını yıllık eğitim planlarına işler';

    public function handle(): int
    {
        $toplam = 0;

        foreach (YillikPlan::query()->orderBy('firma_id')->orderBy('yil')->get() as $plan) {
            $firma = Firma::find($plan->firma_id);

            if (! $firma) {
                continue;
            }

            // Çevrilmemiş planda işleme firmaYilIcin içinde de olur — sayı öncesi/sonrası farkından.
            $once = self::tamamlananSayisi($plan->egitimler ?? []);
            $plan = YillikPlan::firmaYilIcin($firma, $plan->yil);
            EgitimKonulari::kayitlariPlanaIsle($plan);
            $sayi = self::tamamlananSayisi($plan->fresh()->egitimler ?? []) - $once;

            if ($sayi) {
                $this->line("#{$firma->id} {$firma->unvan} {$plan->yil}: {$sayi} hücre");
                $toplam += $sayi;
            }
        }

        $this->info("Toplam {$toplam} plan hücresi işlendi.");

        return self::SUCCESS;
    }

    private static function tamamlananSayisi(array $satirlar): int
    {
        return collect($satirlar)->sum(fn ($s) => count(array_keys($s['aylar'] ?? [], 'tamamlandi', true)));
    }
}
