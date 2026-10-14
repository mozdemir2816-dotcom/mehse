<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Görevlendirme (atama) yazısı — isgpratik 38-44.jpg. 10 görev tipinden biri
 * için firma + üye(ler) bilgisiyle PDF üretilir.
 */
class AtamaYazisi extends Model
{
    use HasFactory;

    protected $table = 'atama_yazilari';

    protected $guarded = ['id'];

    protected $casts = [
        'tarih' => 'date',
        'gorev_baslangic' => 'date',
        'gorev_bitis' => 'date',
        'uyeler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (AtamaYazisi $a): void {
            $a->dokuman_no ??= 'ATM-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });

        // Risk değerlendirmesi PDF'inin imza sahipleri bu atamalardan gelir
        // (RiskEkibiOtomatik::imzaSahipleri) — firmanın saklı PDF'leri yenilensin.
        $pdfYenile = function (AtamaYazisi $a): void {
            if (! in_array($a->rol_anahtari, ['calisan_temsilcisi', 'bilgi_sahibi', 'risk_degerlendirme_ekibi'], true)) {
                return;
            }

            RiskDegerlendirmesi::where('firma_id', $a->firma_id)->whereNotNull('pdf_hazir_at')->get()
                ->each(function (RiskDegerlendirmesi $rd): void {
                    $rd->pdfOnbellegiTemizle();
                    $rd->saveQuietly();
                });
        };
        static::saved($pdfYenile);
        static::deleted($pdfYenile);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function rol(): array
    {
        return config('isg.atama.roller.'.$this->rol_anahtari, []);
    }

    public function rolEtiketi(): string
    {
        return $this->rol()['ad'] ?? $this->rol_anahtari;
    }

    public function ekipMi(): bool
    {
        return ($this->rol()['tip'] ?? 'tekli') === 'ekip';
    }
}
