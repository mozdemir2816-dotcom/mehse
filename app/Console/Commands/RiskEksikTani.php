<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\RiskAlanTamamlama;
use App\Support\RiskSkorlama;
use Illuminate\Console\Command;

/**
 * Teşhis (08.11.2026, YAZMA YOK): bir raporda "Eksik Alanları AI ile Tamamla"nın neden ilerlemediğini gösterir —
 * hangi alanların boş olduğu ve ilk 5 eksik madde için yapay zekânın ne döndürdüğü (kaydedilmez).
 *
 *   php artisan risk:eksik-tani --rapor=RD-2026-127
 */
class RiskEksikTani extends Command
{
    protected $signature = 'risk:eksik-tani {--rapor= : Belge numarası} {--ai-yok : Yapay zekâ denemesini atla}';

    protected $description = 'Eksik alanlı risk maddelerini teşhis eder (veri değiştirmez)';

    public function handle(): int
    {
        $rd = RiskDegerlendirmesi::where('belge_no', (string) $this->option('rapor'))->first();
        if (! $rd) {
            $this->error('Rapor bulunamadı.');

            return self::FAILURE;
        }

        $ucEksen = RiskSkorlama::ucEksenliMi($rd->yontem);
        $eksikler = RiskPuanTamamla::eksikSorgu($rd)->orderBy('sira')->orderBy('id')->get();
        $this->line("Yöntem: {$rd->yontem} · hastane: ".($rd->hastaneMi() ? 'evet' : 'hayır')
            .' · eksik: '.$eksikler->count().' / '.RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->count());

        $say = [];
        $alanlar = ['mevcut_onlem', 'oneri', 'olasilik', 'siddet', 'son_olasilik', 'son_siddet'];
        if ($ucEksen) {
            array_push($alanlar, 'frekans', 'son_frekans');
        }
        foreach ($eksikler as $m) {
            foreach ($alanlar as $a) {
                if (RiskAlanTamamlama::bosMu($m->{$a})) {
                    $say[$a] = ($say[$a] ?? 0) + 1;
                }
            }
            if (RiskAlanTamamlama::riskEksikMi($m)) {
                $say['risk (boş / tehlike kopyası)'] = ($say['risk (boş / tehlike kopyası)'] ?? 0) + 1;
            }
            if (RiskAlanTamamlama::sorumluEksikMi($m)) {
                $say['sorumlu (boş / varsayılan)'] = ($say['sorumlu (boş / varsayılan)'] ?? 0) + 1;
            }
        }
        foreach ($say as $a => $n) {
            $this->line("  boş {$a}: {$n}");
        }

        if ($this->option('ai-yok') || $eksikler->isEmpty()) {
            return self::SUCCESS;
        }

        $ornek = $eksikler->take(5);
        $bas = microtime(true);
        $oneriler = GeminiRiskPuanTamamlayici::alanTopluOner($ornek->values(), $rd->yontem);
        $this->line(sprintf('AI denemesi (5 madde): %d yanıt, %.1f sn, hata durumu: %s', count($oneriler),
            microtime(true) - $bas, GeminiRiskPuanTamamlayici::$sonHataDurumu ?? 'yok'));

        foreach ($ornek as $m) {
            $p = $oneriler[$m->id] ?? null;
            $kopya = clone $m;
            $doldu = $p ? RiskAlanTamamlama::uygula($kopya, $p, $ucEksen) : [];   // kopya — kaydedilmez
            $this->line("  #{$m->id} ".mb_substr((string) $m->tehlike, 0, 50).' → '
                .($p ? ($doldu ? 'dolar: '.implode(', ', array_keys($kopya->getDirty())).' · AI alanları: '.implode(', ', array_keys(array_filter($p, 'filled'))) : 'AI yanıt verdi ama hiçbir alan dolmuyor · AI alanları: '.implode(', ', array_keys(array_filter($p, 'filled')))) : 'AI yanıtı yok'));
        }

        return self::SUCCESS;
    }
}
