<?php

namespace App\Console\Commands;

use App\Models\ToolboxKonusmasi;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Şantiye işleri + lojistik sektörü için hazır toolbox konuşması kütüphanesini
 * (config('isg.toolbox_ornekleri'), gerçek .docx dosyaları storage/app/public/
 * toolbox-konusmalari/ altında) her kullanıcıya kopyalar. Aynı başlıkla zaten
 * kaydı olan kullanıcıda o kayıt atlanır (tekrar çalıştırılabilir/idempotent).
 * Paylaşımlı hosting'te cPanel Cron Jobs ile bir kereliğine çalıştırılır.
 */
class ToolboxKonusmalariniSeedEt extends Command
{
    protected $signature = 'toolbox:seed-ornekler';

    protected $description = 'Hazır toolbox konuşması kütüphanesini (şantiye + lojistik) tüm kullanıcılara ekler';

    public function handle(): int
    {
        $ornekler = config('isg.toolbox_ornekleri', []);
        $eklenen = 0;
        $atlanan = 0;

        foreach (User::all() as $user) {
            $mevcutBasliklar = ToolboxKonusmasi::where('user_id', $user->id)->pluck('baslik')->all();

            foreach ($ornekler as $o) {
                if (in_array($o['baslik'], $mevcutBasliklar, true)) {
                    $atlanan++;

                    continue;
                }

                ToolboxKonusmasi::create([
                    'user_id' => $user->id,
                    'baslik' => $o['baslik'],
                    'dosya_adi' => $o['dosya_adi'],
                    'dosya_yolu' => 'toolbox-konusmalari/'.$o['dosya_adi'],
                    'boyut' => $o['boyut'],
                    'aciklama' => $o['aciklama'],
                ]);
                $eklenen++;
            }
        }

        $this->info("{$eklenen} kayıt eklendi, {$atlanan} zaten vardı (atlandı).");

        return self::SUCCESS;
    }
}
