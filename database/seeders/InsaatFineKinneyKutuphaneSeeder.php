<?php

namespace Database\Seeders;

use App\Support\FineKinneyKutuphaneIceAktarici;
use Illuminate\Database\Seeder;

/**
 * İnşaat Fine-Kinney risk analizinin (1671 madde) Risk Kütüphanesine yüklenmesi.
 *
 * Kaynak database/data/insaat-risk-fine-kinney.php (kullanıcının Excel'inden
 * çıkarıldı). Her "Faaliyet Alanı" bir TehlikeKategorisi olur; maddeler O/F/Ş ile
 * Tehlike olarak eklenir. Risk Sihirbazı manuel seçimde iş kalemine göre süzülür.
 * Idempotent (FineKinneyKutuphaneIceAktarici::kaydet → updateOrCreate).
 *
 * Not: aynı veri [[RiskSablonuInsaatFineKinneySeeder]] ile ayrıca paylaşımlı bir
 * RiskSablonu olarak da tutulur (toplu uygulama için); kütüphane ise seçerek
 * kullanım içindir.
 */
class InsaatFineKinneyKutuphaneSeeder extends Seeder
{
    public function run(): void
    {
        $maddeler = require database_path('data/insaat-risk-fine-kinney.php');

        $satirlar = array_map(fn (array $m): array => [
            'kategori' => $m['bolum'] ?? null,          // Excel'de "Faaliyet Alanı (Ana Kategori)"
            'faaliyet' => $m['faaliyet'] ?? null,       // "Alt Faaliyet / Bölüm"
            'tehlike' => $m['tehlike'] ?? null,
            'risk' => $m['risk'] ?? null,
            'mevcut_onlem' => $m['oneri'] ?? null,      // "Alınması Gereken Önleyici ve Düzeltici Tedbirler"
            'mevzuat' => $m['mevzuat'] ?? null,
            'olasilik' => $m['olasilik'] ?? null,
            'frekans' => $m['frekans'] ?? null,
            'siddet' => $m['siddet'] ?? null,
        ], $maddeler);

        $sonuc = FineKinneyKutuphaneIceAktarici::kaydet($satirlar);

        $this->command?->info(
            "Risk Kütüphanesi: {$sonuc['basarili']} inşaat Fine-Kinney tehlikesi ".
            "({$sonuc['yeniKategori']} yeni kategori)."
        );
    }
}
