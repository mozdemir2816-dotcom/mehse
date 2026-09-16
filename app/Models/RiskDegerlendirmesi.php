<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Firma risk değerlendirmesi (rapor). Firma künyesi kayıt anında snapshot'lanır;
 * geçerlilik tarihi tehlike sınıfına göre otomatik hesaplanır.
 */
class RiskDegerlendirmesi extends Model
{
    use HasFactory;

    protected $table = 'risk_degerlendirmeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'rapor_tarihi' => 'date',
        'gecerlilik_tarihi' => 'date',
        'ekip' => 'array',
        'pdf_talep_edildi_at' => 'datetime',
        'pdf_hazir_at' => 'datetime',
    ];

    /**
     * Bu sayıdan çok maddeli raporlarda PDF, tıklama anında (senkron) değil
     * arka planda (cron ile) üretilir — paylaşımlı hosting'in istek süresi
     * sınırı büyük raporlarda indirmeyi "boş sayfa" ile kesiyordu.
     * Bkz. RiskDegerlendirmesiUretici, App\Console\Commands\RiskPdfUret.
     */
    public const PDF_ARKA_PLAN_ESIGI = 200;

    protected static function booted(): void
    {
        static::saving(function (RiskDegerlendirmesi $rd): void {
            $firma = $rd->firma;

            if ($firma) {
                $rd->firma_unvan ??= $firma->unvan;
                $rd->firma_sgk_sicil_no ??= $firma->sgk_sicil_no;
                $rd->firma_nace ??= trim($firma->nace_kodu.' '.$firma->nace_aciklama);
                $rd->tehlike_sinifi ??= $firma->tehlike_sinifi;
                $rd->firma_adres ??= $firma->adres;
                // Bu değerlendirmeye özel İGU seçilmediyse firmanın atanmış İGU'su esas alınır.
                $rd->igu_id ??= $firma->igu_id;

                if ($rd->rapor_tarihi && ! $rd->gecerlilik_tarihi) {
                    $yil = config('isg.risk_gecerlilik_yili.'.$firma->tehlike_sinifi, 4);
                    $rd->gecerlilik_tarihi = Carbon::parse($rd->rapor_tarihi)->addYears($yil);
                }
            }

            $rd->belge_no ??= static::belgeNoUret();

            // İçerik değiştiyse (pdf_* alanları hariç) önbellekteki PDF artık
            // güncel değil — RiskPdfUret komutu yeniden üretsin diye temizle.
            if ($rd->exists && $rd->isDirty() && ! $rd->isDirty(['pdf_talep_edildi_at', 'pdf_hazir_at', 'pdf_yolu'])) {
                $rd->pdfOnbellegiTemizle();
            }
        });
    }

    public static function belgeNoUret(): string
    {
        $yil = now()->year;
        $sira = static::whereYear('created_at', $yil)->count() + 1;

        return sprintf('RD-%d-%03d', $yil, $sira);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** Bu değerlendirmeyi hazırlayan İGU — firmanın atanmış İGU'sundan farklı olabilir. */
    public function igu(): BelongsTo
    {
        return $this->belongsTo(IsgProfesyoneli::class);
    }

    public function maddeler(): HasMany
    {
        return $this->hasMany(RiskMaddesi::class)->orderBy('sira');
    }

    public function yontemEtiketi(): string
    {
        return config('isg.risk_yontemleri.'.$this->yontem, $this->yontem);
    }

    public function fineKinneyMi(): bool
    {
        return $this->yontem === 'fine_kinney';
    }

    /** Üç eksenli (Olasılık × Frekans/Saptanabilirlik × Şiddet) yöntem mi — bkz. RiskSkorlama::ucEksenliMi(). */
    public function ucEksenliMi(): bool
    {
        return \App\Support\RiskSkorlama::ucEksenliMi($this->yontem);
    }

    public function gecerlilikGecti(): bool
    {
        return $this->gecerlilik_tarihi && $this->gecerlilik_tarihi->isPast();
    }

    /**
     * Arka planda üretilmiş PDF hâlâ güncel mi — talep edildikten sonra
     * içerik değişmemiş VE dosya diskte gerçekten var mı.
     */
    public function pdfHazirMi(): bool
    {
        return $this->pdf_yolu
            && $this->pdf_hazir_at
            && (! $this->pdf_talep_edildi_at || $this->pdf_hazir_at->gte($this->pdf_talep_edildi_at))
            && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->pdf_yolu);
    }

    /**
     * İçerik değiştiğinde (madde eklendi/silindi/puan değişti vb.) önbellekteki
     * PDF artık güncel değildir — dosyayı sil, alanları sıfırla. Kaydetmez;
     * çağıran taraf (kendi saving hook'u ya da açıkça saveQuietly) kaydeder.
     */
    public function pdfOnbellegiTemizle(): void
    {
        if ($this->pdf_yolu) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($this->pdf_yolu);
        }

        $this->pdf_yolu = null;
        $this->pdf_hazir_at = null;
        $this->pdf_talep_edildi_at = null;
    }
}
