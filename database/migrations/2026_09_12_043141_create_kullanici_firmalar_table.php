<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kullanici_firmalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'firma_id']);
        });

        // Geçiş: sahip olmayan mevcut kullanıcılar bugüne kadar tüm firmaları görebiliyordu,
        // yeni görünürlük kısıtlamasıyla bu erişimi kaybetmesinler diye tüm mevcut firmalara
        // açıkça yetkilendirilirler. Bundan sonra kayıt olacaklar bu satırları almaz.
        $digerKullaniciIdleri = DB::table('users')->where('rol', '!=', 'sahip')->pluck('id');
        $firmaIdleri = DB::table('firmalar')->pluck('id');
        $simdi = now();

        $satirlar = [];
        foreach ($digerKullaniciIdleri as $userId) {
            foreach ($firmaIdleri as $firmaId) {
                $satirlar[] = [
                    'user_id' => $userId,
                    'firma_id' => $firmaId,
                    'created_at' => $simdi,
                    'updated_at' => $simdi,
                ];
            }
        }

        foreach (array_chunk($satirlar, 500) as $parca) {
            DB::table('kullanici_firmalar')->insert($parca);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kullanici_firmalar');
    }
};
