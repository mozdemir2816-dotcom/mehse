<?php

namespace Database\Seeders;

use App\Models\RiskSablonu;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * "İnşaat / Yapı" sektörü için hazır Fine-Kinney master risk analizi (1671 madde).
 *
 * Kaynak: kullanıcının "İnşaat İSG Risk Analizi ve Fine-Kinney Programı.xlsx" dosyası.
 * Satırlar database/data/insaat-risk-fine-kinney.php'de; O/F/Ş değerleri ve risk
 * seviyeleri dosyadaki gibi birebir korunur. Şablon paylaşımlı (`paylasildi = true`)
 * kaydedilir; Risk Sihirbazı → "Şablonlar" adımından her kullanıcı tek tıkla uygular.
 *
 * Idempotent: aynı ada sahip şablon varsa maddeler tazelenir, yenisi eklenmez.
 */
class RiskSablonuInsaatFineKinneySeeder extends Seeder
{
    private const AD = 'İnşaat / Yapı — Fine-Kinney Master Risk Analizi';

    public function run(): void
    {
        $sahip = User::query()->oldest('id')->first();

        if (! $sahip) {
            $this->command?->warn('RiskSablonuInsaatFineKinneySeeder: kullanıcı yok, atlandı.');

            return;
        }

        $maddeler = require database_path('data/insaat-risk-fine-kinney.php');

        RiskSablonu::query()->updateOrCreate(
            ['ad' => self::AD, 'sektor' => 'insaat'],
            [
                'user_id' => $sahip->id,
                'sektor_adi' => null,
                'yontem' => 'fine_kinney',
                'maddeler' => $maddeler,
                'paylasildi' => true,
            ],
        );

        $this->command?->info(count($maddeler).' maddelik inşaat Fine-Kinney şablonu hazır.');
    }
}
