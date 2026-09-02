<?php

namespace Database\Factories;

use App\Models\IsgProfesyoneli;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IsgProfesyoneli>
 */
class IsgProfesyoneliFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tip' => 'igu',
            'ad_soyad' => fake()->name(),
            'unvan' => 'A Sınıfı İş Güvenliği Uzmanı',
            'aktif' => true,
        ];
    }
}
