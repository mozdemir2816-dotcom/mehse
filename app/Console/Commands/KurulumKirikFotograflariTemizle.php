<?php

namespace App\Console\Commands;

use App\Models\DofRaporu;
use App\Models\KontrolKarti;
use App\Models\SahaAnalizi;
use App\Models\SahaBulgusu;
use App\Models\SahaDenetimi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Diskte (public storage) karşılığı bulunmayan kırık saha/DÖF fotoğraf
 * yollarını veritabanı kayıtlarından temizler; kayıtların metin içeriğine ve
 * diskte gerçekten var olan geçerli fotoğraflara dokunmaz.
 *
 * Çağrı (canlı): node deploy/onbellek.mjs --komut "kurulum:kirik-fotograflari-temizle"
 */
class KurulumKirikFotograflariTemizle extends Command
{
    protected $signature = 'kurulum:kirik-fotograflari-temizle {--dry-run : Değişiklik yazmadan sadece raporlar}';

    protected $description = 'Diskte bulunmayan kırık saha ve DÖF fotoğraf referanslarını temizler';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');
        $varMi = fn (?string $yol): bool => filled($yol) && $disk->exists((string) $yol);

        $ozet = [
            'saha_bulgulari' => ['kayit' => 0, 'foto' => 0],
            'dof_raporlari' => ['kayit' => 0, 'foto' => 0],
            'saha_denetimleri' => ['kayit' => 0, 'foto' => 0],
            'saha_analizleri' => ['kayit' => 0, 'foto' => 0],
            'kontrol_kartlari' => ['kayit' => 0, 'foto' => 0],
        ];

        // 1. Saha Bulguları (fotograflar: string[])
        if (Schema::hasTable('saha_bulgulari')) {
            SahaBulgusu::query()->whereNotNull('fotograflar')->chunkById(200, function ($kayitlar) use ($dry, $varMi, &$ozet): void {
                foreach ($kayitlar as $b) {
                    $onceki = array_values(array_filter($b->fotograflar ?? [], 'is_string'));
                    if ($onceki === []) {
                        continue;
                    }
                    $kalan = array_values(array_filter($onceki, fn (string $y) => $varMi($y)));
                    $silinen = count($onceki) - count($kalan);
                    if ($silinen > 0) {
                        $ozet['saha_bulgulari']['kayit']++;
                        $ozet['saha_bulgulari']['foto'] += $silinen;
                        if (! $dry) {
                            $b->fotograflar = $kalan ?: null;
                            $b->saveQuietly();
                        }
                    }
                }
            });
        }

        // 2. DÖF Raporları (maddeler[*].foto_yolu)
        if (Schema::hasTable('dof_raporlari')) {
            DofRaporu::query()->whereNotNull('maddeler')->chunkById(200, function ($kayitlar) use ($dry, $varMi, &$ozet): void {
                foreach ($kayitlar as $d) {
                    $maddeler = $d->maddeler ?? [];
                    $silinen = 0;
                    foreach ($maddeler as $i => $m) {
                        $yol = $m['foto_yolu'] ?? null;
                        if (is_string($yol) && $yol !== '' && ! $varMi($yol)) {
                            $maddeler[$i]['foto_yolu'] = null;
                            $silinen++;
                        }
                    }
                    if ($silinen > 0) {
                        $ozet['dof_raporlari']['kayit']++;
                        $ozet['dof_raporlari']['foto'] += $silinen;
                        if (! $dry) {
                            $d->maddeler = $maddeler;
                            $d->saveQuietly();
                        }
                    }
                }
            });
        }

        // 3. Saha Denetimleri (cevaplar[*].fotolar / foto_yolu)
        if (Schema::hasTable('saha_denetimleri')) {
            SahaDenetimi::query()->whereNotNull('cevaplar')->chunkById(200, function ($kayitlar) use ($dry, $varMi, &$ozet): void {
                foreach ($kayitlar as $d) {
                    $cevaplar = $d->cevaplar ?? [];
                    $silinen = 0;
                    foreach ($cevaplar as $k => $c) {
                        if (! is_array($c)) {
                            continue;
                        }
                        if (! empty($c['fotolar']) && is_array($c['fotolar'])) {
                            $onceki = array_values(array_filter($c['fotolar'], 'is_string'));
                            $kalan = array_values(array_filter($onceki, fn (string $y) => $varMi($y)));
                            $fark = count($onceki) - count($kalan);
                            if ($fark > 0) {
                                $cevaplar[$k]['fotolar'] = $kalan;
                                $silinen += $fark;
                            }
                        }
                        if (! empty($c['foto_yolu']) && is_string($c['foto_yolu']) && ! $varMi($c['foto_yolu'])) {
                            $cevaplar[$k]['foto_yolu'] = null;
                            $silinen++;
                        }
                    }
                    if ($silinen > 0) {
                        $ozet['saha_denetimleri']['kayit']++;
                        $ozet['saha_denetimleri']['foto'] += $silinen;
                        if (! $dry) {
                            $d->cevaplar = $cevaplar;
                            $d->saveQuietly();
                        }
                    }
                }
            });
        }

        // 4. AI Saha Analizleri (fotograflar + bulgular[*].foto_yolu)
        if (Schema::hasTable('saha_analizleri')) {
            SahaAnalizi::query()->chunkById(200, function ($kayitlar) use ($dry, $varMi, &$ozet): void {
                foreach ($kayitlar as $s) {
                    $silinen = 0;
                    $fotolar = $s->fotograflar ?? [];
                    if ($fotolar !== []) {
                        $yeniFotolar = [];
                        foreach ($fotolar as $f) {
                            $yol = is_array($f) ? ($f['yol'] ?? null) : (is_string($f) ? $f : null);
                            if (is_string($yol) && $yol !== '' && ! $varMi($yol)) {
                                $silinen++;

                                continue;
                            }
                            $yeniFotolar[] = $f;
                        }
                        $fotolar = $yeniFotolar;
                    }
                    $bulgular = $s->bulgular ?? [];
                    foreach ($bulgular as $i => $b) {
                        $yol = $b['foto_yolu'] ?? null;
                        if (is_string($yol) && $yol !== '' && ! $varMi($yol)) {
                            $bulgular[$i]['foto_yolu'] = null;
                            $silinen++;
                        }
                    }
                    if ($silinen > 0) {
                        $ozet['saha_analizleri']['kayit']++;
                        $ozet['saha_analizleri']['foto'] += $silinen;
                        if (! $dry) {
                            $s->fotograflar = $fotolar ?: null;
                            $s->bulgular = $bulgular;
                            $s->saveQuietly();
                        }
                    }
                }
            });
        }

        // 5. Kontrol Kartları (foto_yolu)
        if (Schema::hasTable('kontrol_kartlari')) {
            KontrolKarti::query()->whereNotNull('foto_yolu')->chunkById(200, function ($kayitlar) use ($dry, $varMi, &$ozet): void {
                foreach ($kayitlar as $k) {
                    if (is_string($k->foto_yolu) && $k->foto_yolu !== '' && ! $varMi($k->foto_yolu)) {
                        $ozet['kontrol_kartlari']['kayit']++;
                        $ozet['kontrol_kartlari']['foto']++;
                        if (! $dry) {
                            $k->foto_yolu = null;
                            $k->saveQuietly();
                        }
                    }
                }
            });
        }

        $toplamFoto = array_sum(array_column($ozet, 'foto'));
        $toplamKayit = array_sum(array_column($ozet, 'kayit'));

        foreach ($ozet as $tablo => $s) {
            if ($s['foto'] > 0) {
                $this->line("{$tablo}: {$s['kayit']} kayıt, {$s['foto']} kırık fotoğraf ".($dry ? 'tespit edildi' : 'temizlendi'));
            }
        }

        $this->info(($dry ? '[DRY-RUN] ' : '')."Toplam {$toplamKayit} kayıttan {$toplamFoto} kırık fotoğraf referansı ".($dry ? 'bulundu.' : 'temizlendi.'));

        return self::SUCCESS;
    }
}
