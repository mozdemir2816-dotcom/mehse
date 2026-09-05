<?php

namespace Database\Seeders;

use App\Models\MykMeslek;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * MYK (Mesleki Yeterlilik Kurumu) resmi portalının "Belge Zorunluluğu Kapsamındaki
 * Meslekler" sorgu sayfasından (database/data/myk-meslekleri.php) — 242 yeterlilik
 * kodu, 05.09.2026 anlık görüntüsü.
 */
class MykMeslekSeeder extends Seeder
{
    public function run(): void
    {
        $satirlar = require database_path('data/myk-meslekleri.php');
        $simdi = Carbon::now();

        $kayitlar = array_map(
            fn (array $s) => [
                'yeterlilik_kodu' => $s[0],
                'yeterlilik_adi' => $s[1],
                'belge_zorunluluk_tarihi' => $s[2],
                'created_at' => $simdi,
                'updated_at' => $simdi,
            ],
            $satirlar,
        );

        foreach (array_chunk($kayitlar, 500) as $parca) {
            MykMeslek::upsert($parca, ['yeterlilik_kodu'], ['yeterlilik_adi', 'belge_zorunluluk_tarihi', 'updated_at']);
        }
    }
}
