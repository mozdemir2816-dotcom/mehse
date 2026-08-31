<?php

namespace Database\Factories;

use App\Models\Firma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Firma>
 */
class FirmaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'unvan' => fake()->company().' A.Ş.',
            'tehlike_sinifi' => fake()->randomElement(['az_tehlikeli', 'tehlikeli', 'cok_tehlikeli']),
            'nace_kodu' => fake()->numerify('##.##'),
            'calisan_sayisi' => fake()->numberBetween(1, 200),
            'il' => fake()->city(),
            'aktif' => true,
        ];
    }
}
