<?php

namespace App\Console\Commands;

use App\Models\Firma;
use App\Models\RiskDegerlendirmesi;
use App\Support\HastaneRiskIceAktarici;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Bursa Şehir Hastanesi risk dosyalarını (klasördeki tüm .xlsx) hastane firmasına, dosya başına
 * AYRI bir risk değerlendirmesi olarak aktarır; çıktı için kaynak dosyayı şablon olarak saklar.
 * Tekrar çalıştırılabilir: aynı şablon dosyasıyla kayıtlı değerlendirme varsa atlanır.
 */
class HastaneRiskAktar extends Command
{
    protected $signature = 'risk:hastane-aktar {klasor : Excel dosyalarının klasörü} {--firma= : Firma id (yoksa unvanı "Bursa Şehir Hastanesi" olan)} {--dry-run}';

    protected $description = 'Hastane risk Excel dosyalarını içe aktarır (olduğu gibi, hastane şablonuyla)';

    /** Eski / tekrarlı dosyalar — kullanıcı kararı 27.10.2026: Ameliyathane "Revizyon", ASM "revize2". */
    private const ATLANAN_DOSYALAR = ['19-AMELİYATHANE.xlsx', '32- ASM.xlsx'];

    /** Aynı dosyadaki yinelenen sayfa (yalnız ilk sayfa alınır). */
    private const SAYFA_SINIRI = ['1- HASTANE GENELİ.xlsx' => ['HASTANE GENELİ']];

    /** Dosya adından okunaksız kalan birim adları. */
    private const BIRIM_ADLARI = [
        '1- REVİZYON AMELİYATHANE RİSK DEĞERLENDİRMESİ.xlsx' => 'AMELİYATHANE',
        '30- MEKANİK ALANLAR-ALİ BERKAN GEÇEN-14012026.xlsx' => 'MEKANİK ALANLAR',
        '32-asm revize2.xlsx' => 'ASM',
        '29-JenaratörTrijenIPSsistemi.xlsx' => 'JENERATÖR / TRİJEN / İPS SİSTEMİ',
        '28-Asansör.xlsx' => 'ASANSÖR',
        '12- KİMYASAL MADDELER.xlsx' => 'KİMYASAL MADDELER',
    ];

    public function handle(): int
    {
        $klasor = rtrim((string) $this->argument('klasor'), '/\\');
        if (! is_dir($klasor)) {
            $this->error("Klasör yok: {$klasor}");

            return self::FAILURE;
        }

        $firma = $this->option('firma')
            ? Firma::find($this->option('firma'))
            : Firma::where('unvan', 'like', '%Bursa%Şehir%Hastane%')->first();
        if (! $firma) {
            $this->error('Hastane firması bulunamadı (--firma=ID verin).');

            return self::FAILURE;
        }
        $this->info("Firma: #{$firma->id} {$firma->unvan}");

        $dosyalar = collect(File::files($klasor))
            ->filter(fn ($f) => strtolower($f->getExtension()) === 'xlsx' && ! str_starts_with($f->getFilename(), '~$'))
            ->reject(fn ($f) => in_array($f->getFilename(), self::ATLANAN_DOSYALAR, true))
            ->sortBy(fn ($f) => (int) $f->getFilename().'|'.$f->getFilename(), SORT_NATURAL)
            ->values();

        $hedef = resource_path(config('hastane_risk.sablon_klasoru'));
        if (! $this->option('dry-run')) {
            File::ensureDirectoryExists($hedef);
            $this->varsayilanSablonuHazirla($klasor, $hedef);
        }

        $toplamRd = $toplamMadde = 0;

        foreach ($dosyalar as $dosya) {
            $ad = $dosya->getFilename();
            $sayfalar = HastaneRiskIceAktarici::oku($dosya->getPathname(), self::SAYFA_SINIRI[$ad] ?? null);
            if (! $sayfalar) {
                $this->warn("Atlandı (hastane formu bulunamadı): {$ad}");

                continue;
            }

            foreach ($sayfalar as $i => $sayfa) {
                $sablon = Str::slug(pathinfo($ad, PATHINFO_FILENAME), '-').($i ? '-'.($i + 1) : '').'.xlsx';
                $birim = self::BIRIM_ADLARI[$ad] ?? trim(preg_replace('/^\d+\s*-\s*/u', '', pathinfo($ad, PATHINFO_FILENAME)));
                $say = count($sayfa['maddeler']);

                if (RiskDegerlendirmesi::where('firma_id', $firma->id)->where('cikti_sablonu', 'hastane')->where('proje_adi', $birim)->exists()) {
                    $this->line("Zaten var: {$birim}");

                    continue;
                }

                $this->line(sprintf('%-34s %3d madde  ← %s [%s]', $birim, $say, $ad, $sayfa['sayfa']));
                if ($this->option('dry-run')) {
                    continue;
                }

                File::copy($dosya->getPathname(), $hedef.DIRECTORY_SEPARATOR.$sablon);
                HastaneRiskIceAktarici::kaydet($firma, $sayfa, $birim, $sablon);
                $toplamRd++;
                $toplamMadde += $say;
            }
        }

        $this->info("Tamam: {$toplamRd} değerlendirme, {$toplamMadde} madde.");

        return self::SUCCESS;
    }

    /** Sistemde sıfırdan açılan hastane değerlendirmeleri için varsayılan şablon + logo. */
    private function varsayilanSablonuHazirla(string $klasor, string $hedef): void
    {
        $kaynak = $klasor.DIRECTORY_SEPARATOR.'1- HASTANE GENELİ.xlsx';
        if (! is_file($kaynak)) {
            return;
        }

        File::copy($kaynak, $hedef.DIRECTORY_SEPARATOR.config('hastane_risk.varsayilan_sablon.dosya'));

        $zip = new ZipArchive;
        if ($zip->open($kaynak) === true) {
            $logo = $zip->getFromName('xl/media/image1.jpeg');
            if ($logo !== false) {
                File::put($hedef.DIRECTORY_SEPARATOR.'logo.jpg', $logo);
            }
            $zip->close();
        }
    }
}
