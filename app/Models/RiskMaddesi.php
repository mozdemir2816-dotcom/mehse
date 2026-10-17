<?php

namespace App\Models;

use App\Support\RiskSkorlama;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Risk değerlendirmesindeki tek satır. `saving` hook'u puan/düzey ve rezidüel
 * puan/düzeyi yönteme göre hesaplar.
 */
class RiskMaddesi extends Model
{
    use HasFactory;

    protected $table = 'risk_maddeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'etkilenen_calisan' => 'boolean',
        'etkilenen_diger' => 'boolean',
        'olasilik' => 'float',
        'frekans' => 'float',
        'siddet' => 'float',
        'puan' => 'float',
        'son_olasilik' => 'float',
        'son_frekans' => 'float',
        'son_siddet' => 'float',
        'son_puan' => 'float',
        'hazop' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (RiskMaddesi $m): void {
            $yontem = $m->riskDegerlendirmesi?->yontem ?? 'matris_5x5';

            $mevcut = RiskSkorlama::hesapla($yontem, $m->olasilik, $m->siddet, $m->frekans);
            $m->puan = $mevcut['puan'] ?: null;
            $m->duzey = $mevcut['puan'] ? $mevcut['duzey'] : null;

            // Önlem sonrası olasılık/şiddet/frekans girilmemişse varsayılan:
            // önlem genelde olasılığı düşürür (varsayılan "1" — çok düşük),
            // şiddet ve maruz kalma frekansı önlemden ETKİLENMEZ, öneri
            // öncesindeki değerle aynı kabul edilir.
            if ($m->olasilik !== null && $m->son_olasilik === null) {
                $m->son_olasilik = 1;
            }
            if ($m->siddet !== null && $m->son_siddet === null) {
                $m->son_siddet = $m->siddet;
            }
            if (RiskSkorlama::ucEksenliMi($yontem) && $m->frekans !== null && $m->son_frekans === null) {
                $m->son_frekans = $m->frekans;
            }

            $son = RiskSkorlama::hesapla($yontem, $m->son_olasilik, $m->son_siddet, $m->son_frekans);
            $m->son_puan = $son['puan'] ?: null;
            $m->son_duzey = $son['puan'] ? $son['duzey'] : null;

            // Boş sorumlu / termin kayıtta da varsayılanla dolar — ekranda ve
            // çıktılarda aynı görünsün (kullanıcı 15.10.2026).
            if (blank($m->sorumlu)) {
                $m->sorumlu = self::VARSAYILAN_SORUMLU;
            }
            if (blank($m->termin)) {
                $m->termin = self::VARSAYILAN_TERMIN;
            }
        });

        // Madde eklenip/değişip/silinince üst rapordaki önbelleklenmiş PDF
        // artık güncel değil — RiskDegerlendirmesi::pdfOnbellegiTemizle().
        $temizle = function (RiskMaddesi $m): void {
            $rd = $m->riskDegerlendirmesi;

            if ($rd && $rd->pdf_yolu) {
                $rd->pdfOnbellegiTemizle();
                $rd->saveQuietly();
            }
        };
        static::saved($temizle);
        static::deleted($temizle);
    }

    public function riskDegerlendirmesi(): BelongsTo
    {
        return $this->belongsTo(RiskDegerlendirmesi::class);
    }

    public const VARSAYILAN_SORUMLU = 'İşveren / İşveren Vekili';

    public const VARSAYILAN_TERMIN = 'SÜREKLİ';

    /**
     * Çıktılarda (PDF/Excel/Word) gösterilen sorumlu: boşsa varsayılan; "İşveren/Vekili",
     * "İŞVEREN/", "İşveren İşveren Vekili" gibi yazımlar tek biçime çevrilir, ardındaki
     * ek kişi korunur ("İşveren/Vekili Çalışan" → "İşveren / İşveren Vekili, Çalışan").
     * Kayıt değişmez (kullanıcı 14.10.2026).
     */
    public function sorumluMetni(): string
    {
        $sorumlu = trim((string) $this->sorumlu);
        if ($sorumlu === '') {
            return self::VARSAYILAN_SORUMLU;
        }

        // Türkçe küçük harf: İ/I tek karakter kalır, mb_ konumları asıl metinle aynı.
        $kucuk = mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], $sorumlu));
        if (! preg_match('#^işveren(?:\s*/\s*(?:işveren\s+)?(?:vekili)?|\s+işveren\s+vekili|\s+vekili)?(?=[\s,/]|$)#u', $kucuk, $e)) {
            return $sorumlu;
        }

        $kalan = trim(mb_substr($sorumlu, mb_strlen($e[0])), " \t,/");

        return self::VARSAYILAN_SORUMLU.($kalan !== '' ? ', '.$kalan : '');
    }

    /** Çıktılardaki açıklama: boşsa ilk risk düzeyinin karar metni (config bantlar.eylem). */
    public function aciklamaMetni(): string
    {
        if (filled($this->aciklama)) {
            return (string) $this->aciklama;
        }

        return $this->puan
            ? RiskSkorlama::bant($this->riskDegerlendirmesi?->yontem ?? 'matris_5x5', (float) $this->puan)['eylem']
            : '';
    }

    public function rengi(): string
    {
        $yontem = $this->riskDegerlendirmesi?->yontem ?? 'matris_5x5';

        return RiskSkorlama::bant($yontem, (float) $this->puan)['renk'];
    }

    /**
     * PDF'te dar sütuna sığan dikey düzey yazısı — her harf ayrı satırda
     * (transform:rotate yerine; dompdf'te rotate edilen kutu, satırın
     * kendi hücresine göre kayabiliyor, harf harf alt alta yazmak tabloya
     * bağlı kalıp satırla her zaman hizalı kalır). Tam ad harf harf çok
     * uzun sütun oluşturuyordu → iki harflik kısaltma (ÇY, YK…), açıklaması
     * PDF'te tablonun üstünde (kullanıcı kararı 14.10.2026).
     */
    public function duzeyDikey(): ?string
    {
        return $this->dikey($this->duzey);
    }

    public function sonDuzeyDikey(): ?string
    {
        return $this->dikey($this->son_duzey);
    }

    private function dikey(?string $duzey): ?string
    {
        $kisa = RiskSkorlama::kisaltma($this->riskDegerlendirmesi?->yontem ?? 'matris_5x5', $duzey);

        return $kisa ? implode("\n", mb_str_split($kisa)) : null;
    }
}
