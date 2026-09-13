<?php

use App\Support\SayfaKatalogu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sayfa_yetkileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sayfa_anahtari');
            $table->timestamps();

            $table->unique(['user_id', 'sayfa_anahtari']);
        });

        // Geçiş: sahip olmayan mevcut kullanıcılar hiçbir şey kaybetmesin diye
        // bugüne kadar zaten erişebildikleri tüm sayfalara açık şekilde izinlendirilir.
        // Bundan sonra kayıt olacak yeni kullanıcılar bu satırları almaz (varsayılan: kapalı).
        $digerKullaniciIdleri = DB::table('users')->where('rol', '!=', 'sahip')->pluck('id');
        $simdi = now();

        $satirlar = [];
        foreach ($digerKullaniciIdleri as $userId) {
            foreach (array_keys(SayfaKatalogu::tumSayfalar()) as $anahtar) {
                $satirlar[] = [
                    'user_id' => $userId,
                    'sayfa_anahtari' => $anahtar,
                    'created_at' => $simdi,
                    'updated_at' => $simdi,
                ];
            }
        }

        foreach (array_chunk($satirlar, 500) as $parca) {
            DB::table('sayfa_yetkileri')->insert($parca);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sayfa_yetkileri');
    }
};
