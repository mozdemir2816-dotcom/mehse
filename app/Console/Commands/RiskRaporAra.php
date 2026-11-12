<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use Illuminate\Console\Command;

/**
 * Salt okunur arama (12.11.2026): belge numarası ya da firma adının bir parçasıyla risk değerlendirmelerini
 * listeler. Canlıda deploy/onbellek.mjs --komut ile çalıştırılır (yalnız risk:* izinli); veri değiştirmez.
 *
 *   php artisan risk:rapor-ara --ara=127
 *   php artisan risk:rapor-ara --ara=BULUT
 */
class RiskRaporAra extends Command
{
    protected $signature = 'risk:rapor-ara {--ara= : Belge no ya da firma adı parçası}';

    protected $description = 'Risk değerlendirmelerini belge no / firma adına göre listeler (salt okunur)';

    public function handle(): int
    {
        $ara = trim((string) $this->option('ara'));
        if ($ara === '') {
            $this->error('--ara= gerekli.');

            return self::FAILURE;
        }

        $sonuc = RiskDegerlendirmesi::query()
            ->where(fn ($q) => $q->where('belge_no', 'like', "%{$ara}%")->orWhere('firma_unvan', 'like', "%{$ara}%"))
            ->withCount('maddeler')->orderBy('id')->limit(50)->get();

        $this->info('Toplam analiz: '.RiskDegerlendirmesi::count().' · eşleşen: '.$sonuc->count());
        foreach ($sonuc as $rd) {
            $this->line("#{$rd->id} [{$rd->belge_no}] hex=".bin2hex((string) $rd->belge_no)
                ." · {$rd->firma_unvan} · {$rd->yontem} · {$rd->maddeler_count} madde · güncelleme {$rd->updated_at}");
        }

        return self::SUCCESS;
    }
}
