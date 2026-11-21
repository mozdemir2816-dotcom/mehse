<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ekipman afiş kütüphanesi (21.11.2026) — makine tipi başına tek afiş: yazısız görsel
 * (Gemini'de üretilir) + YAPIN / YAPMAYIN / KKD yazıları. Firmaya özgü değildir; afiş PDF'i
 * basılırken firma logosu ve ekipmanın QR'ı eklenir. yz_taslak: yazılar yapay zekâdan geldi,
 * onaylandi_at dolana kadar "uzman onayı bekliyor" etiketi taşır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ekipman_afisleri', function (Blueprint $table) {
            $table->id();
            $table->string('anahtar', 80)->unique();
            $table->string('ad');
            $table->string('grup')->nullable();
            $table->string('gorsel_yolu')->nullable();
            $table->json('yapin')->nullable();
            $table->json('yapmayin')->nullable();
            $table->json('kkd')->nullable();
            $table->boolean('yz_taslak')->default(true);
            $table->timestamp('onaylandi_at')->nullable();
            $table->foreignId('onaylayan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ekipman_afisleri');
    }
};
