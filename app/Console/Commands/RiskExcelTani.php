<?php

namespace App\Console\Commands;

use App\Support\HastaneRiskIceAktarici;
use App\Support\RiskDegerlendirmesiExcelOkuyucu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Klasördeki risk Excel'lerini (alt klasörler dahil) "Excel Yükle" okuyucusundan geçirip
 * dosya başına özet verir. Veritabanına YAZMAZ; farklı düzenli tabloların nerede
 * okunamadığını / puansız kaldığını görmek içindir.
 */
class RiskExcelTani extends Command
{
    protected $signature = 'risk:excel-tani {klasor : Excel dosyalarının klasörü} {--csv= : Sonucu bu CSV dosyasına da yaz}';

    protected $description = 'Risk Excel dosyalarının yükleme okuyucusunda nasıl okunduğunu raporlar (yazma yok)';

    private const ALANLAR = ['bolum', 'faaliyet', 'tehlike', 'risk', 'mevcut_onlem', 'oneri', 'sorumlu', 'termin'];

    public function handle(): int
    {
        @set_time_limit(0);

        $klasor = $this->argument('klasor');
        if (! is_dir($klasor)) {
            $this->error("Klasör yok: {$klasor}");

            return self::FAILURE;
        }

        $dosyalar = collect(File::allFiles($klasor))
            ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['xlsx', 'xlsm', 'xls'], true)
                && ! str_starts_with($f->getFilename(), '~$'))
            ->sortBy(fn ($f) => $f->getPathname())
            ->values();

        $satirlar = [];

        foreach ($dosyalar as $f) {
            $baslangic = microtime(true);
            $satirlar[] = $this->incele($f->getPathname(), ltrim(str_replace($klasor, '', $f->getPathname()), '\\/'))
                + ['sn' => round(microtime(true) - $baslangic, 1)];
        }

        $this->table(
            ['Dosya', 'Tür', 'Madde', 'Puan', 'Son', 'Dolu alanlar', 'Durum', 'sn'],
            array_map(fn ($s) => [
                mb_strimwidth($s['dosya'], 0, 48, '…'), $s['tur'], $s['madde'], $s['puan'], $s['son'],
                $s['alanlar'], $s['durum'], $s['sn'],
            ], $satirlar),
        );

        $sorunlu = array_filter($satirlar, fn ($s) => $s['durum'] !== 'OK');
        $this->newLine();
        $this->info(count($satirlar).' dosya, '.(count($satirlar) - count($sorunlu)).' sorunsuz, '.count($sorunlu).' incelenmeli.');

        if ($csv = $this->option('csv')) {
            $h = fopen($csv, 'w');
            fwrite($h, "\xEF\xBB\xBF");
            fputcsv($h, array_keys($satirlar[0] ?? []), ';');
            foreach ($satirlar as $s) {
                fputcsv($h, $s, ';');
            }
            fclose($h);
        }

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function incele(string $yol, string $ad): array
    {
        $bos = ['dosya' => $ad, 'tur' => '-', 'madde' => 0, 'puan' => '-', 'son' => '-', 'alanlar' => '', 'durum' => ''];

        try {
            $hastane = HastaneRiskIceAktarici::oku($yol);
            if ($hastane) {
                $madde = array_sum(array_map(fn ($s) => count($s['maddeler']), $hastane));

                return ['tur' => 'hastane'] + ['madde' => $madde, 'durum' => 'OK'] + $bos;
            }

            $sonuc = RiskDegerlendirmesiExcelOkuyucu::oku($yol);
        } catch (\Throwable $e) {
            return ['durum' => 'HATA: '.mb_strimwidth($e->getMessage(), 0, 60, '…')] + $bos;
        }

        $adaylar = $sonuc['adaylar'];
        if (! $adaylar) {
            return ['durum' => 'OKUNAMADI: '.mb_strimwidth($sonuc['hatalar'][0] ?? '', 0, 60, '…')] + $bos;
        }

        $n = count($adaylar);
        $dolu = fn (string $alan) => count(array_filter($adaylar, fn ($a) => ($a[$alan] ?? null) !== null && $a[$alan] !== ''));
        // Yöntem sayfa bazında belirlenir (yükleme de karışık dosyayı sayfa başına böler).
        $fkSayfalar = collect($adaylar)->groupBy('sayfa')->map(fn ($g) => $g->contains(fn ($a) => ($a['frekans'] ?? null) !== null));
        $fk = $fkSayfalar->contains(true);
        $karisik = $fkSayfalar->unique()->count() > 1;
        $puan = count(array_filter($adaylar, fn ($a) => ($a['olasilik'] ?? null) !== null && ($a['siddet'] ?? null) !== null
            && (! $fkSayfalar[$a['sayfa'] ?? ''] || ($a['frekans'] ?? null) !== null)));
        $son = count(array_filter($adaylar, fn ($a) => ($a['son_olasilik'] ?? null) !== null));

        $alanlar = collect(self::ALANLAR)->filter(fn ($a) => $dolu($a) > 0)->implode(',');

        $uyari = [];
        if ($puan === 0) {
            $uyari[] = 'puan yok';
        } elseif ($puan < $n * 0.8) {
            $uyari[] = "puan eksik ({$puan}/{$n})";
        }
        if ($dolu('risk') < $n * 0.5 && $dolu('oneri') < $n * 0.5) {
            $uyari[] = 'risk/öneri boş';
        }
        if ($n < 3) {
            $uyari[] = 'az madde';
        }

        return [
            'tur' => $karisik ? 'FK+5x5' : ($fk ? 'FK' : '5x5'),
            'madde' => $n,
            'puan' => "{$puan}/{$n}",
            'son' => "{$son}/{$n}",
            'alanlar' => $alanlar,
            'durum' => $uyari ? implode('; ', $uyari) : 'OK',
        ] + $bos;
    }
}
