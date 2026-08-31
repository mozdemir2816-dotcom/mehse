<?php

namespace Database\Factories;

use App\Models\Calisan;
use App\Models\Firma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calisan>
 */
class CalisanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'firma_id' => Firma::factory(),
            'ad_soyad' => fake()->name(),
            'gorev' => fake()->jobTitle(),
            'ise_giris' => fake()->dateTimeBetween('-5 years', 'now'),
            'aktif' => true,
        ];
    }
}
