<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table): void {
            $table->timestamp('pdf_talep_edildi_at')->nullable()->after('durum');
            $table->timestamp('pdf_hazir_at')->nullable()->after('pdf_talep_edildi_at');
            $table->string('pdf_yolu')->nullable()->after('pdf_hazir_at');
        });
    }

    public function down(): void
    {
        Schema::table('risk_degerlendirmeleri', function (Blueprint $table): void {
            $table->dropColumn(['pdf_talep_edildi_at', 'pdf_hazir_at', 'pdf_yolu']);
        });
    }
};
