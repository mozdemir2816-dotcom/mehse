<?php

namespace App\Models;

use App\Support\RiskDegerlendirmesiUretici;
use App\Support\RiskSkorlama;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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
        'sablon_verisi' => 'array',
        'haric_tehlikeler' => 'array',
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

            // Hastane formu seçili firmada (Fine-Kinney) yeni değerlendirme hastane şablonunda açılır.
            if (! $rd->exists && blank($rd->cikti_sablonu) && $rd->yontem === 'fine_kinney' && $firma?->risk_cikti_sablonu === 'hastane') {
                $rd->cikti_sablonu = 'hastane';
            }

            $rd->belge_no ??= static::belgeNoUret();

            // İçerik değiştiyse (pdf_* alanları hariç) önbellekteki PDF artık
            // güncel değil — RiskPdfUret komutu yeniden üretsin diye temizle.
            if ($rd->exists && $rd->isDirty() && ! $rd->isDirty(['pdf_talep_edildi_at', 'pdf_hazir_at', 'pdf_yolu'])) {
                $rd->pdfOnbellegiTemizle();
            }
        });
    }

    /** Bursa Şehir Hastanesi formu: çıktı (Excel/PDF) hastanenin kendi şablonunda üretilir. */
    public function hastaneMi(): bool
    {
        return $this->cikti_sablonu === 'hastane';
    }

    public static function belgeNoUret(): string
    {
        return \App\Support\BelgeNumarasi::sonraki(static::class, 'belge_no', 'RD-'.now()->year.'-');
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
        return RiskSkorlama::ucEksenliMi($this->yontem);
    }

    public function gecerlilikGecti(): bool
    {
        return $this->gecerlilik_tarihi && $this->gecerlilik_tarihi->isPast();
    }

    /** Durumlar (13.11.2026): firma/birim başına tek "Yürürlükte"; eskiler "Arşiv". */
    public const DURUMLAR = ['taslak' => 'Taslak', 'yayinlandi' => 'Yürürlükte', 'arsiv' => 'Arşiv'];

    public const DURUM_RENKLERI = ['taslak' => 'gray', 'yayinlandi' => 'success', 'arsiv' => 'warning'];

    /**
     * Bu değerlendirmeyi yürürlüğe alır; aynı firma + aynı birim/proje için yürürlükteki
     * diğer değerlendirmeler arşive alınır (hastane gibi birim birim değerlendirmeler birbirini etkilemez).
     */
    public function yururlugeAl(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function (): void {
            static::query()->where('firma_id', $this->firma_id)->whereKeyNot($this->getKey())
                ->where('durum', 'yayinlandi')
                ->where(fn ($q) => filled($this->proje_adi) ? $q->where('proje_adi', $this->proje_adi) : $q->whereNull('proje_adi')->orWhere('proje_adi', ''))
                ->update(['durum' => 'arsiv']);
            $this->update(['durum' => 'yayinlandi']);
        });
    }

    /**
     * Firmanın geçerli (esas alınan) değerlendirmesi: yürürlükte işaretli olan; yoksa arşiv
     * dışındaki en son rapor tarihli olan.
     */
    public static function gecerliOlan(int $firmaId): ?self
    {
        return static::query()->where('firma_id', $firmaId)->where('durum', 'yayinlandi')->latest('rapor_tarihi')->latest('id')->first()
            ?? static::query()->where('firma_id', $firmaId)->where('durum', '!=', 'arsiv')->latest('rapor_tarihi')->latest('id')->first();
    }

    /**
     * Risk Değerlendirmesi Yönetmeliği md.12 — rapor tarihinden sonra yenilemeyi gerektiren olaylar:
     * iş kazası / ramak kala / meslek hastalığı, işyerine yeni iş ekipmanı girmesi.
     *
     * @return array<int, string>
     */
    public function revizyonGerekceleri(): array
    {
        $tarih = $this->rapor_tarihi;
        $firma = $this->firma;
        if (! $tarih || ! $firma) {
            return [];
        }

        $gerekce = [];
        $kaza = $firma->isKazasiRaporlari()->whereDate('kaza_tarihi', '>', $tarih)->count()
            + $firma->olayKayitlari()->where('olay_tipi', 'is_kazasi')->whereDate('olay_tarihi', '>', $tarih)->count();
        $ramak = $firma->olayKayitlari()->where('olay_tipi', 'ramak_kala')->whereDate('olay_tarihi', '>', $tarih)->count();
        $mh = $firma->meslekHastaligiBildirimleri()->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(tani_tarihi, ogrenme_tarihi, created_at)'), '>', $tarih)->count();
        $ekipman = $firma->isEkipmanlari()->where('aktif', true)->whereDate('created_at', '>', $tarih)->count();

        if ($kaza) {
            $gerekce[] = $kaza.' iş kazası';
        }
        if ($ramak) {
            $gerekce[] = $ramak.' ramak kala olay';
        }
        if ($mh) {
            $gerekce[] = $mh.' meslek hastalığı bildirimi';
        }
        if ($ekipman) {
            $gerekce[] = $ekipman.' yeni iş ekipmanı';
        }

        return $gerekce;
    }

    /**
     * Arka planda üretilmiş PDF hâlâ güncel mi — talep edildikten sonra
     * içerik değişmemiş VE dosya diskte gerçekten var mı.
     */
    public function pdfHazirMi(bool $imzali = true): bool
    {
        return $this->pdf_yolu
            && $this->pdf_hazir_at
            && (! $this->pdf_talep_edildi_at || $this->pdf_hazir_at->gte($this->pdf_talep_edildi_at))
            && Storage::disk('local')->exists($this->pdfDosyasi($imzali));
    }

    /** Önbellekteki PDF'in seçilen nüshası (imzalı = pdf_yolu, imzasız yanında ayrı dosya). */
    public function pdfDosyasi(bool $imzali = true): string
    {
        return $imzali ? (string) $this->pdf_yolu : RiskDegerlendirmesiUretici::onbellekYolu($this, false);
    }

    /**
     * İçerik değiştiğinde (madde eklendi/silindi/puan değişti vb.) önbellekteki
     * PDF artık güncel değildir — dosyayı sil, alanları sıfırla. Kaydetmez;
     * çağıran taraf (kendi saving hook'u ya da açıkça saveQuietly) kaydeder.
     */
    public function pdfOnbellegiTemizle(): void
    {
        if ($this->pdf_yolu) {
            Storage::disk('local')->delete([$this->pdf_yolu, $this->pdfDosyasi(false)]);
        }

        $this->pdf_yolu = null;
        $this->pdf_hazir_at = null;
        $this->pdf_talep_edildi_at = null;
    }
}
