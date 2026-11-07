<?php

use App\Models\EgitimPaketi;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Tüm işyerleri için zorunlu "Genel İSG Eğitimi" paketi. İlk video Modül 1 (Genel Konular)
 * altında; sekiz saate tamamlayacak ek videolar panelden eklenir ve sıralanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ad = 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)';
        $kullanici = User::query()->orderBy('id')->first();

        if (! $kullanici || EgitimPaketi::where('ad', $ad)->exists()) {
            return;
        }

        $paket = EgitimPaketi::create([
            'user_id' => $kullanici->id,
            'ad' => $ad,
            'aciklama' => 'Tüm işyerlerinin alması gereken zorunlu temel İSG eğitimi konuları.',
            'gecme_puani' => 60,
            'video_zorunlu_yuzde' => 90,
            'sinav_soru_sayisi' => 20,
            'aktif' => true,
            'paylasildi' => true,
        ]);

        $paket->dersler()->create([
            'modul' => 1,
            'sira' => 1,
            'baslik' => 'Temel İş Sağlığı ve Güvenliği Eğitimi | Genel Konular',
            'video_url' => 'https://www.youtube.com/watch?v=607t0w4AS5E',
        ]);
    }

    public function down(): void
    {
        EgitimPaketi::where('ad', 'Zorunlu Temel İSG Eğitimi (Tüm İşyerleri)')->delete();
    }
};
