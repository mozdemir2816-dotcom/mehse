<?php

namespace Database\Seeders;

use App\Models\KimyasalKutuphane;
use Illuminate\Database\Seeder;

/**
 * Sık kullanılan endüstriyel kimyasallar — saf madde adı, CAS ve genel GHS sınıfları.
 * Ticari ürünün bileşimi farklı olabilir; envantere eklendikten sonra ürünün kendi
 * SDS'ine göre düzeltilmelidir. İdempotent (ad + CAS ile güncellenir).
 */
class KimyasalKutuphanesiSeeder extends Seeder
{
    public function run(): void
    {
        $dep = [
            'yanici' => 'Serin, iyi havalandırılan, ateşleme kaynaklarından uzak, kapalı kapta; yanıcı sıvı dolabında.',
            'asit' => 'Asit dayanımlı kapta, bazlardan ve metallerden ayrı; sızdırmazlık havuzu içinde.',
            'baz' => 'Kapalı kapta, asitlerden ve alüminyumdan ayrı; sızdırmazlık havuzu içinde.',
            'oksit' => 'Yanıcı ve organik maddelerden ayrı, serin ve kuru yerde.',
            'tup' => 'Tüpler dik, zincirle sabitlenmiş, vanası kapalı, başlık takılı; serin ve havalandırılan yerde.',
            'genel' => 'Orijinal kabında, kapalı, serin ve kuru yerde.',
        ];

        $liste = [
            ['Aseton', '67-64-1', 'sivi', ['ghs02', 'ghs07'], $dep['yanici']],
            ['Etanol', '64-17-5', 'sivi', ['ghs02', 'ghs07'], $dep['yanici']],
            ['İzopropil alkol (2-Propanol)', '67-63-0', 'sivi', ['ghs02', 'ghs07'], $dep['yanici']],
            ['Metanol', '67-56-1', 'sivi', ['ghs02', 'ghs06', 'ghs08'], $dep['yanici']],
            ['Toluen', '108-88-3', 'sivi', ['ghs02', 'ghs07', 'ghs08'], $dep['yanici']],
            ['Ksilen (karışım izomerler)', '1330-20-7', 'sivi', ['ghs02', 'ghs07'], $dep['yanici']],
            ['Benzin', '86290-81-5', 'sivi', ['ghs02', 'ghs07', 'ghs08', 'ghs09'], $dep['yanici']],
            ['Motorin (dizel yakıt)', '68334-30-5', 'sivi', ['ghs02', 'ghs07', 'ghs08', 'ghs09'], $dep['yanici']],
            ['Tiner (solvent karışımı)', null, 'sivi', ['ghs02', 'ghs07', 'ghs08'], $dep['yanici']],
            ['Hidroklorik asit (%32-37)', '7647-01-0', 'sivi', ['ghs05', 'ghs07'], $dep['asit']],
            ['Sülfürik asit (%96-98)', '7664-93-9', 'sivi', ['ghs05'], $dep['asit']],
            ['Nitrik asit (%65)', '7697-37-2', 'sivi', ['ghs03', 'ghs05'], $dep['asit']],
            ['Fosforik asit (%85)', '7664-38-2', 'sivi', ['ghs05'], $dep['asit']],
            ['Sodyum hidroksit (kostik soda)', '1310-73-2', 'kati', ['ghs05'], $dep['baz']],
            ['Potasyum hidroksit', '1310-58-3', 'kati', ['ghs05', 'ghs07'], $dep['baz']],
            ['Amonyak çözeltisi (%25)', '1336-21-6', 'sivi', ['ghs05', 'ghs07', 'ghs09'], $dep['baz']],
            ['Sodyum hipoklorit (çamaşır suyu, %5-15)', '7681-52-9', 'sivi', ['ghs05', 'ghs09'], 'Asitlerden ayrı, serin yerde, ışıktan uzak; klor gazı çıkışına karşı havalandırılan alan.'],
            ['Hidrojen peroksit (%35)', '7722-84-1', 'sivi', ['ghs03', 'ghs05', 'ghs07'], $dep['oksit']],
            ['Asetilen', '74-86-2', 'gaz', ['ghs02', 'ghs04'], $dep['tup']],
            ['Oksijen (sıkıştırılmış)', '7782-44-7', 'gaz', ['ghs03', 'ghs04'], $dep['tup']],
            ['Azot (sıkıştırılmış)', '7727-37-9', 'gaz', ['ghs04'], $dep['tup']],
            ['LPG (propan-bütan)', '68476-85-7', 'gaz', ['ghs02', 'ghs04'], $dep['tup']],
            ['Argon (sıkıştırılmış)', '7440-37-1', 'gaz', ['ghs04'], $dep['tup']],
            ['Karbondioksit (sıkıştırılmış)', '124-38-9', 'gaz', ['ghs04'], $dep['tup']],
            ['Çimento (Portland)', '65997-15-1', 'toz', ['ghs05', 'ghs07'], 'Kuru yerde, nemden korunmuş, torbalar kapalı.'],
            ['Epoksi reçine', '25068-38-6', 'sivi', ['ghs07', 'ghs09'], $dep['genel']],
            ['Boya (solvent bazlı)', null, 'sivi', ['ghs02', 'ghs07', 'ghs08'], $dep['yanici']],
            ['Yağlama gresi / makine yağı', null, 'sivi', ['ghs09'], $dep['genel']],
            ['Antifriz (etilen glikol)', '107-21-1', 'sivi', ['ghs07', 'ghs08'], $dep['genel']],
        ];

        foreach ($liste as [$ad, $cas, $hal, $ghs, $depolama]) {
            KimyasalKutuphane::updateOrCreate(
                ['user_id' => null, 'urun_adi' => $ad],
                ['cas_no' => $cas, 'fiziksel_hal' => $hal, 'ghs' => $ghs, 'depolama' => $depolama],
            );
        }
    }
}
