<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tespit ve Öneri Defteri — isgpratik 65-66.jpg. Firma başına TEK kayıt
 * (AcilDurumPlani ile aynı desen); maddeler zamanla eklenip biriktirilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tespit_oneri_defterleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->unique()->constrained('firmalar')->cascadeOnDelete();

            $table->text('genel_not')->nullable();
            $table->json('maddeler')->nullable(); // [{tespit, oneri, dayanak, oncelik}]

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tespit_oneri_defterleri');
    }
};
