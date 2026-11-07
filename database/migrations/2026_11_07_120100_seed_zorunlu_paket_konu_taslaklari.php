<?php

use App\Models\EgitimPaketi;
use Illuminate\Database\Migrations\Migration;

/**
 * Zorunlu Temel İSG Eğitimi paketine Yönetmelik Ek-1'in Sağlık (2) ve Teknik (3) konu başlıkları,
 * video bekleyen taslak ders olarak eklenir. Genel Konular (1) mevcut videoyla kalır.
 * Video linki bağlanınca ders çalışana görünür.
 */
return new class extends Migration
{
    public const PAKET = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)';

    /** @return array<int, array<int, string>> modül => konu başlıkları (sıra = dizi sırası) */
    public static function konular(): array
    {
        return [
            2 => [
                'Meslek hastalıklarının sebepleri',
                'Hastalıktan korunma prensipleri ve korunma tekniklerinin uygulanması',
                'Biyolojik ve psikososyal risk etmenleri',
                'İlk yardım',
                'Bağımlılık yapıcı maddelerin zararları ve teknoloji bağımlılığı',
            ],
            3 => [
                'Kimyasal, fiziksel ve ergonomik risk etmenleri',
                'Elle kaldırma ve taşıma',
                'Parlama, patlama',
                'Yangın ve yangından korunma',
                'İş ekipmanlarının güvenli kullanımı',
                'Ekranlı araçlarla çalışma',
                'Elektrik, tehlikeleri, riskleri ve önlemleri',
                'İş kazalarının sebepleri ve korunma prensipleri ile tekniklerinin uygulanması',
                'Sağlık ve güvenlik işaretleri',
                'Kişisel koruyucu donanım kullanımı',
                'İş sağlığı ve güvenliği genel kuralları ve güvenlik kültürü',
                'Acil durumlar, tahliye ve kurtarma',
            ],
        ];
    }

    public function up(): void
    {
        $paket = EgitimPaketi::where('ad', self::PAKET)->first();

        if (! $paket) {
            return;
        }

        foreach (self::konular() as $modul => $basliklar) {
            if ($paket->tumDersler()->where('modul', $modul)->exists()) {
                continue;
            }

            foreach ($basliklar as $i => $baslik) {
                $paket->tumDersler()->create(['modul' => $modul, 'sira' => $i + 1, 'baslik' => $baslik, 'video_url' => null]);
            }
        }
    }

    public function down(): void
    {
        EgitimPaketi::where('ad', self::PAKET)->first()?->tumDersler()->whereNull('video_url')->delete();
    }
};
