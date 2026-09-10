<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Firma extends Model
{
    use HasFactory;

    protected $table = 'firmalar';

    protected $guarded = ['id'];

    protected $casts = [
        'sozlesme_baslangic' => 'date',
        'sozlesme_bitis' => 'date',
        'aktif' => 'boolean',
        'calisan_sayisi' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Firma $f): void {
            if (blank($f->user_id) && auth()->check()) {
                $f->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function igu(): BelongsTo
    {
        return $this->belongsTo(IsgProfesyoneli::class, 'igu_id');
    }

    public function isyeriHekimi(): BelongsTo
    {
        return $this->belongsTo(IsgProfesyoneli::class, 'isyeri_hekimi_id');
    }

    public function dsp(): BelongsTo
    {
        return $this->belongsTo(IsgProfesyoneli::class, 'dsp_id');
    }

    public function calisanlar(): HasMany
    {
        return $this->hasMany(Calisan::class);
    }

    public function riskDegerlendirmeleri(): HasMany
    {
        return $this->hasMany(RiskDegerlendirmesi::class);
    }

    public function egitimKatilimlari(): HasMany
    {
        return $this->hasMany(EgitimKatilim::class);
    }

    public function atamaYazilari(): HasMany
    {
        return $this->hasMany(AtamaYazisi::class);
    }

    public function calisanTemsilcisiSecimi(): HasOne
    {
        return $this->hasOne(CalisanTemsilcisiSecimi::class);
    }

    public function checklistVadeleri(): HasMany
    {
        return $this->hasMany(FirmaChecklistVadesi::class);
    }

    public function kurulToplantilari(): HasMany
    {
        return $this->hasMany(KurulToplantisi::class);
    }

    public function egitimSinavlari(): HasMany
    {
        return $this->hasMany(EgitimSinavi::class);
    }

    public function tespitOneriDefteri(): HasOne
    {
        return $this->hasOne(TespitOneriDefteri::class);
    }

    public function onayliDefterNushalari(): HasMany
    {
        return $this->hasMany(OnayliDefterNushasi::class);
    }

    public function periyodikKontrol(): HasOne
    {
        return $this->hasOne(PeriyodikKontrol::class);
    }

    public function ortamOlcumu(): HasOne
    {
        return $this->hasOne(OrtamOlcumu::class);
    }

    public function kkdMatrisi(): HasOne
    {
        return $this->hasOne(KkdMatrisi::class);
    }

    public function saglikGozetimi(): HasOne
    {
        return $this->hasOne(SaglikGozetimi::class);
    }

    public function kimyasalRiskDegerlendirmesi(): HasOne
    {
        return $this->hasOne(KimyasalRiskDegerlendirmesi::class);
    }

    public function kazaIstatistikleri(): HasMany
    {
        return $this->hasMany(KazaIstatistigi::class);
    }

    public function kimyasalUrunler(): HasMany
    {
        return $this->hasMany(KimyasalUrun::class);
    }

    public function afisler(): HasMany
    {
        return $this->hasMany(IsgAfis::class);
    }

    public function kkdZimmetFormlari(): HasMany
    {
        return $this->hasMany(KkdZimmetFormu::class);
    }

    public function isIzinFormlari(): HasMany
    {
        return $this->hasMany(IsIzinFormu::class);
    }

    public function cezaTebligTutanaklari(): HasMany
    {
        return $this->hasMany(CezaTebligTutanagi::class);
    }

    public function talimatlar(): HasMany
    {
        return $this->hasMany(Talimat::class);
    }

    public function yillikPlanlar(): HasMany
    {
        return $this->hasMany(YillikPlan::class);
    }

    public function isbasiEgitimTutanaklari(): HasMany
    {
        return $this->hasMany(IsbasiEgitimTutanagi::class);
    }

    public function tatbikatTutanaklari(): HasMany
    {
        return $this->hasMany(TatbikatTutanagi::class);
    }

    public function sertifikalar(): HasMany
    {
        return $this->hasMany(Sertifika::class);
    }

    public function dofRaporlari(): HasMany
    {
        return $this->hasMany(DofRaporu::class);
    }

    public function sahaAnalizleri(): HasMany
    {
        return $this->hasMany(SahaAnalizi::class);
    }

    public function sahaDenetimleri(): HasMany
    {
        return $this->hasMany(SahaDenetimi::class);
    }

    public function isKazasiRaporlari(): HasMany
    {
        return $this->hasMany(IsKazasiRaporu::class);
    }

    public function olayKayitlari(): HasMany
    {
        return $this->hasMany(OlayKaydi::class);
    }

    public function ipcTebligleri(): HasMany
    {
        return $this->hasMany(IpcTebligi::class);
    }

    public function muayeneFormlari(): HasMany
    {
        return $this->hasMany(MuayeneFormu::class);
    }

    public function ziyaretProgramlari(): HasMany
    {
        return $this->hasMany(ZiyaretProgrami::class);
    }

    public function arsivDosyalari(): HasMany
    {
        return $this->hasMany(ArsivDosya::class);
    }

    public function acilDurumPlani(): HasOne
    {
        return $this->hasOne(AcilDurumPlani::class);
    }

    public function acilDurumKrokisi(): HasOne
    {
        return $this->hasOne(AcilDurumKrokisi::class);
    }

    public function tehlikeSinifiEtiketi(): string
    {
        return config('isg.tehlike_siniflari.'.$this->tehlike_sinifi, $this->tehlike_sinifi);
    }

    /** Risk değerlendirmesi geçerlilik süresi (yıl) — tehlike sınıfına göre. */
    public function riskGecerlilikYili(): int
    {
        return config('isg.risk_gecerlilik_yili.'.$this->tehlike_sinifi, 4);
    }

    public function sozlesmeAktifMi(): bool
    {
        return $this->aktif
            && (! $this->sozlesme_bitis || $this->sozlesme_bitis->isFuture());
    }

    /**
     * Yıllık planda, atanmış uzman (firma sözleşme başlangıcı) öncesindeki
     * aylar seçilemez. Verilen yıl için seçilebilir İLK ay indeksini (0=Ocak …
     * 11=Aralık) döndürür: 0 → kısıt yok, 12 → o yılın tamamı kilitli
     * (sözleşme sonraki yıl başlıyor).
     */
    public function planKilitAyIndeksi(int $yil): int
    {
        $baslangic = $this->sozlesme_baslangic;

        if (! $baslangic || $baslangic->year < $yil) {
            return 0;
        }

        if ($baslangic->year > $yil) {
            return 12;
        }

        return $baslangic->month - 1;
    }
}
