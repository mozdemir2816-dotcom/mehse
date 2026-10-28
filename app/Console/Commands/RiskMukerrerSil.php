<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use App\Support\RiskMaddeMukerrer;
use Illuminate\Console\Command;

/**
 * Kayıtlı risk değerlendirmelerindeki mükerrer maddeleri (aynı bölüm + faaliyet + tehlike + risk) siler.
 * Hastane şablonlu değerlendirmelere dokunmaz. Önce --dry-run ile sayıyı görün.
 */
class RiskMukerrerSil extends Command
{
    protected $signature = 'risk:mukerrer-sil {--dry-run : Silmeden yalnız say} {--degerlendirme= : Yalnız bu değerlendirme id}';

    protected $description = 'Risk değerlendirmelerindeki mükerrer maddeleri siler';

    public function handle(): int
    {
        $sorgu = RiskDegerlendirmesi::query()->orderBy('id');

        if ($id = $this->option('degerlendirme')) {
            $sorgu->whereKey($id);
        }

        $dry = (bool) $this->option('dry-run');
        $satirlar = [];
        $toplam = 0;

        foreach ($sorgu->get() as $rd) {
            $n = RiskMaddeMukerrer::degerlendirmeTemizle($rd, $dry);

            if ($n) {
                $satirlar[] = [$rd->id, $rd->firma_id, mb_strimwidth((string) $rd->proje_adi, 0, 30, '…'), $n];
                $toplam += $n;
            }
        }

        $this->table(['Değerlendirme', 'Firma', 'Ad', $dry ? 'Silinecek' : 'Silinen'], $satirlar);
        $this->info(($dry ? 'Silinecek' : 'Silinen').": {$toplam} mükerrer madde, ".count($satirlar).' değerlendirme.');

        return self::SUCCESS;
    }
}
