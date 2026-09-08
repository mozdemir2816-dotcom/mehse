<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TehlikeKutuphanesiSeeder::class,
            NaceKoduSeeder::class,
            MykMeslekSeeder::class,
            RiskSablonuInsaatFineKinneySeeder::class,
        ]);
    }
}
