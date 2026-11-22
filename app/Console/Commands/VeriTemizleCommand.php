<?php

namespace App\Console\Commands;

use App\Models\Basvuru;
use App\Models\Calisan;
use App\Models\Firma;
use Illuminate\Console\Command;

/**
 * Canlı / yerel veritabanındaki kirli ve test kayıtlarını güvenle denetler ve
 * `--apply` verildiğinde temizler:
 * 1) Firma ünvanlarındaki `__` ve mükerrer `A.Ş. A.Ş.` yazımları
 * 2) Çalışan `sube` sütunundaki anlamsız / taşeron etiketleri (0, ADA, PARSEL, TAŞERON...)
 * 3) Başvurular tablosundaki belirgin spam / test kayıtları (örn. xdsadsadas / dsdsds@)
 */
class VeriTemizleCommand extends Command
{
    protected $signature = 'mehse:veri-temizle {--apply : Değişiklikleri veritabanına uygular (aksi halde salt-okunur önizleme)}';

    protected $description = 'Firma ünvanı, çalışan şube alanı ve test başvurularındaki kirli verileri denetler / temizler.';

    public function handle(): int
    {
        $uygula = (bool) $this->option('apply');
        $this->info($uygula ? 'MOD: UYGULA (--apply)' : 'MOD: ÖNİZLEME (değişiklik yapılmaz; uygulamak için --apply ekleyin)');

        // 1. Firma ünvanları
        $firmaAdet = 0;
        Firma::query()->withoutGlobalScopes()->each(function (Firma $f) use ($uygula, &$firmaAdet): void {
            $yeni = Firma::temizUnvan((string) $f->unvan);
            if ($yeni !== (string) $f->unvan) {
                $firmaAdet++;
                $this->line("  [Firma #{$f->id}] {$f->unvan}  →  {$yeni}");
                if ($uygula) {
                    $f->update(['unvan' => $yeni]);
                }
            }
        });
        $this->info("Firma ünvanı düzeltmesi: {$firmaAdet} kayıt");

        // 2. Çalışan şube alanı kirli değerler
        $subeAdet = 0;
        Calisan::query()
            ->whereNotNull('sube')
            ->where('sube', '!=', '')
            ->each(function (Calisan $c) use ($uygula, &$subeAdet): void {
                $s = trim((string) $c->sube);
                $kirli = $s === '0'
                    || preg_match('/^(ADA|\d+\s*PARSEL)$/iu', $s)
                    || preg_match('/TA[ŞS]ERON/iu', $s);
                if ($kirli) {
                    $subeAdet++;
                    $this->line("  [Çalışan #{$c->id}] sube='{$s}' → NULL");
                    if ($uygula) {
                        $c->update(['sube' => null]);
                    }
                }
            });
        $this->info("Çalışan şube temizliği: {$subeAdet} kayıt");

        // 3. Test / spam başvurular
        $basvuruAdet = 0;
        if (class_exists(Basvuru::class)) {
            Basvuru::query()->each(function (Basvuru $b) use ($uygula, &$basvuruAdet): void {
                $ad = mb_strtolower(trim((string) ($b->ad_soyad ?? '')), 'UTF-8');
                $eposta = mb_strtolower(trim((string) ($b->eposta ?? $b->email ?? '')), 'UTF-8');
                $testMi = str_contains($ad, 'xdsads') || str_contains($ad, 'gasasd') || str_starts_with($eposta, 'dsdsds@');
                if ($testMi) {
                    $basvuruAdet++;
                    $this->line("  [Başvuru #{$b->id}] {$ad} ({$eposta}) → silinecek");
                    if ($uygula) {
                        $b->delete();
                    }
                }
            });
        }
        $this->info("Test başvuru temizliği: {$basvuruAdet} kayıt");

        return self::SUCCESS;
    }
}
