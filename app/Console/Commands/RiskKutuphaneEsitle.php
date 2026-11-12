<?php

namespace App\Console\Commands;

use App\Models\RiskDegerlendirmesi;
use App\Models\RiskMaddesi;
use App\Support\AnalizKutuphanesi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mevcut analizlerin kütüphaneye bağlı olmayan maddelerini kütüphaneye bağlar / ekler (12.11.2026,
 * App\Support\AnalizKutuphanesi). Yalnız sahip hesabın Fine-Kinney / 5x5 analizleri; hastane şablonu hariç.
 *
 *   php artisan risk:kutuphane-esitle --kuru        → değiştirmeden sayar
 *   php artisan risk:kutuphane-esitle               → uygular (her analiz ayrı işlem; yarıda kalırsa kaldığı yerden sürer)
 *   php artisan risk:kutuphane-esitle --rapor=RD-2026-015
 */
class RiskKutuphaneEsitle extends Command
{
    protected $signature = 'risk:kutuphane-esitle
        {--rapor=* : Yalnız bu belge numaraları}
        {--kuru : Hiçbir şeyi değiştirmeden ne yapılacağını say}';

    protected $description = 'Analiz maddelerini kütüphaneye bağlar; kütüphanede olmayanları sektör klasörüne ekler';

    public function handle(): int
    {
        @set_time_limit(0);

        $sorgu = RiskDegerlendirmesi::query()->with('firma.user')
            ->whereIn('yontem', AnalizKutuphanesi::YONTEMLER)
            ->whereHas('maddeler', fn ($q) => $q->whereNull('tehlike_id'))
            ->when($this->option('rapor'), fn ($q, $r) => $q->whereIn('belge_no', (array) $r))
            ->orderBy('id');

        $toplam = ['analiz' => 0, 'baglanan' => 0, 'eklenen' => 0, 'atlanan' => 0];

        if ($this->option('kuru')) {
            DB::beginTransaction();
        }

        try {
            foreach ($sorgu->get() as $rd) {
                if (! AnalizKutuphanesi::kapsamdaMi($rd)) {
                    $toplam['atlanan']++;

                    continue;
                }

                $bekleyen = RiskMaddesi::where('risk_degerlendirmesi_id', $rd->id)->whereNull('tehlike_id')->count();

                // Kuru çalıştırmada tümü tek geri alınan işlemde (analizler arası mükerrer önleme de sayıya yansır).
                $sonuc = $this->option('kuru') ? AnalizKutuphanesi::esitle($rd) : DB::transaction(fn () => AnalizKutuphanesi::esitle($rd));

                $toplam['analiz']++;
                $toplam['baglanan'] += $sonuc['baglanan'];
                $toplam['eklenen'] += $sonuc['eklenen'];
                $this->line("{$rd->belge_no} ({$rd->firma_unvan}): {$bekleyen} kütüphanesiz → {$sonuc['baglanan']} mevcut kütüphane maddesine bağlandı, {$sonuc['eklenen']} yeni eklendi");
            }
        } finally {
            if ($this->option('kuru')) {
                DB::rollBack();
            }
        }

        $this->info(($this->option('kuru') ? '[KURU — değişiklik yapılmadı] ' : '')
            ."{$toplam['analiz']} analiz: {$toplam['baglanan']} bağlandı, {$toplam['eklenen']} kütüphaneye eklendi"
            .($toplam['atlanan'] ? ", {$toplam['atlanan']} analiz kapsam dışı (hastane şablonu ya da başka hesap)" : '').'.');

        return self::SUCCESS;
    }
}
