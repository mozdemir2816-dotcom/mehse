<?php

namespace Database\Seeders;

use App\Models\NaceKodu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 26/12/2012 tarihli ve 28509 sayılı Resmî Gazete'de yayımlanan İş Sağlığı ve
 * Güvenliğine İlişkin İşyeri Tehlike Sınıfları Tebliği, EK-1 listesinden
 * (database/data/nace-kodlari.php) — 2182 satır, taban 2012 metni.
 */
class NaceKoduSeeder extends Seeder
{
    public function run(): void
    {
        $satirlar = require database_path('data/nace-kodlari.php');
        $simdi = Carbon::now();

        $kayitlar = array_map(
            fn (array $s) => [
                'kod' => $s[0],
                'tanim' => $s[1],
                'tehlike_sinifi' => $s[2],
                'sektor_adi' => $s[3],
                'created_at' => $simdi,
                'updated_at' => $simdi,
            ],
            $satirlar,
        );

        foreach (array_chunk($kayitlar, 500) as $parca) {
            NaceKodu::upsert($parca, ['kod'], ['tanim', 'tehlike_sinifi', 'sektor_adi', 'updated_at']);
        }
    }
}
