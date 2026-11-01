<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Günlük veritabanı yedeği — Hizmet Sözleşmesi md.5'te taahhüt edilen
 * "günlük otomatik yedek, 30 gün saklama". Canlıda proc_open kapalı olduğu
 * için mysqldump çağrılamaz; döküm saf PHP ile, tablo tablo ve parça parça
 * (bellek şişmeden) gzip'li SQL dosyasına yazılır. Dosyalar herkese kapalı
 * storage/app/private/yedekler altında durur; süresi dolanlar silinir.
 */
class VeritabaniYedekle extends Command
{
    protected $signature = 'mehse:yedekle {--gun=30 : Bu kadar günden eski yedekleri sil}';

    protected $description = 'Veritabanının gzip\'li SQL yedeğini alır ve eski yedekleri temizler';

    public const KLASOR = 'app/private/yedekler';

    private const PARCA = 500;

    private const AZAMI_KOMUT = 512_000;

    public function handle(): int
    {
        $klasor = storage_path(self::KLASOR);
        File::ensureDirectoryExists($klasor);
        // Ek güvence: sunucuda uygulama klasörü zaten 403 veriyor; yine de klasör web'den okunamasın.
        if (! File::exists($klasor.'/.htaccess')) {
            File::put($klasor.'/.htaccess', "Require all denied
");
        }

        $dosya = $klasor.'/mehse-'.now()->format('Y-m-d_His').'.sql.gz';
        $gecici = $dosya.'.yaziliyor';
        $gz = gzopen($gecici, 'wb6');

        if ($gz === false) {
            $this->error('Yedek dosyası açılamadı: '.$gecici);

            return self::FAILURE;
        }

        try {
            $tablolar = $this->dokum($gz);
        } catch (\Throwable $e) {
            gzclose($gz);
            @unlink($gecici);
            report($e);
            $this->error('Yedek alınamadı: '.$e->getMessage());

            return self::FAILURE;
        }

        gzclose($gz);
        rename($gecici, $dosya);

        $silinen = $this->eskileriSil($klasor, (int) $this->option('gun'));

        $this->info(sprintf('Yedek alındı: %s (%d tablo, %s KB). Silinen eski yedek: %d',
            basename($dosya), $tablolar, number_format(filesize($dosya) / 1024, 0, ',', '.'), $silinen));

        return self::SUCCESS;
    }

    /** @param resource $gz */
    private function dokum($gz): int
    {
        $db = DB::connection();
        $mysql = $db->getDriverName() === 'mysql';
        $pdo = $db->getPdo();
        $q = fn (string $ad) => $mysql ? '`'.str_replace('`', '``', $ad).'`' : '"'.str_replace('"', '""', $ad).'"';

        gzwrite($gz, '-- mehse veritabanı yedeği '.now()->format('d.m.Y H:i:s')."\n");

        if ($mysql) {
            gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            $tablolar = collect($db->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
                ->map(fn ($r) => array_values((array) $r)[0]);
        } else {
            $tablolar = collect($db->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
                ->pluck('name');
        }

        foreach ($tablolar as $tablo) {
            $olustur = $mysql
                ? array_values((array) $db->selectOne('SHOW CREATE TABLE '.$q($tablo)))[1]
                : $db->selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $tablo])->sql;

            gzwrite($gz, "DROP TABLE IF EXISTS {$q($tablo)};\n{$olustur};\n");

            $sonId = null;
            $kaydir = 0;
            $sirali = $this->siralamaSutunu($db, $tablo, $mysql);

            while (true) {
                $sorgu = $db->table($tablo)->limit(self::PARCA);
                if ($sirali) {
                    $sorgu->orderBy($sirali);
                    if ($sonId !== null) {
                        $sorgu->where($sirali, '>', $sonId);
                    }
                } else {
                    $sorgu->offset($kaydir);
                }

                $satirlar = $sorgu->get();
                if ($satirlar->isEmpty()) {
                    break;
                }

                $sutunlar = implode(', ', array_map($q, array_keys((array) $satirlar->first())));
                $basla = "INSERT INTO {$q($tablo)} ({$sutunlar}) VALUES
";
                $tampon = '';

                foreach ($satirlar as $s) {
                    $deger = '('.implode(', ', array_map(
                        fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)),
                        array_values((array) $s)
                    )).')';

                    // Tek INSERT ~500 KB'ı geçmesin (tek satır bundan büyükse kendi komutunda kalır): geri yüklerken max_allowed_packet sınırına takılmasın.
                    if ($tampon !== '' && strlen($tampon) + strlen($deger) > self::AZAMI_KOMUT) {
                        gzwrite($gz, $basla.$tampon.";
");
                        $tampon = '';
                    }
                    $tampon .= ($tampon === '' ? '' : ",
").$deger;
                }
                gzwrite($gz, $basla.$tampon.";
");

                $sonId = $sirali ? ((array) $satirlar->last())[$sirali] : null;
                $kaydir += $satirlar->count();

                if ($satirlar->count() < self::PARCA) {
                    break;
                }
            }

            gzwrite($gz, "\n");
        }

        if ($mysql) {
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        }

        return $tablolar->count();
    }

    /** Tek sütunlu tamsayı birincil anahtar varsa onunla sayfalanır (büyük tablolarda OFFSET yavaşlar). */
    private function siralamaSutunu($db, string $tablo, bool $mysql): ?string
    {
        if (! $mysql) {
            return null;
        }

        $anahtar = $db->select('SHOW KEYS FROM `'.str_replace('`', '``', $tablo)."` WHERE Key_name = 'PRIMARY'");

        if (count($anahtar) !== 1) {
            return null;
        }

        $sutun = $anahtar[0]->Column_name;
        $tip = $db->selectOne('SHOW COLUMNS FROM `'.str_replace('`', '``', $tablo).'` WHERE Field = ?', [$sutun])->Type ?? '';

        return str_contains(strtolower($tip), 'int') ? $sutun : null;
    }

    private function eskileriSil(string $klasor, int $gun): int
    {
        $sinir = now()->subDays($gun)->getTimestamp();
        $silinen = 0;

        foreach (File::glob($klasor.'/mehse-*.sql.gz*') as $f) {
            if (filemtime($f) < $sinir && @unlink($f)) {
                $silinen++;
            }
        }

        return $silinen;
    }
}
