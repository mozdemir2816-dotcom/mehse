<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşverene İdari Para Cezası (İPC) Tebliği — Ceza ve Tebliğ Tutanağı'nın
 * (isgpratik 79-80.jpg) 2. sekmesi. Çalışana değil, devlet tarafından
 * işverene kesilen idari para cezasının tebliğini belgeler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipc_tebligleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->cascadeOnDelete();

            $table->string('belge_no')->nullable();
            $table->date('teblig_tarihi')->nullable();
            $table->date('denetim_tarihi')->nullable();
            $table->string('tespit_eden_kurum')->nullable();
            $table->string('mufettis_adi')->nullable();

            $table->json('ihlaller')->nullable(); // [{baslik, aciklama}]
            $table->text('serbest_ihlal_metni')->nullable();

            $table->decimal('ceza_tutari', 12, 2)->nullable();
            $table->decimal('pesin_odeme_tutari', 12, 2)->nullable();
            $table->boolean('odeme_yapildi')->default(false);
            $table->boolean('itiraz_edildi')->default(false);
            $table->text('itiraz_notu')->nullable();

            $table->string('hazirlayan')->nullable();
            $table->string('hazirlayan_kase')->nullable();

            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipc_tebligleri');
    }
};
