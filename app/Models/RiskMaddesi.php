<?php

namespace App\Models;

use App\Support\HastaneRisk;
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
            // Hastane şablonu: puanlar dosyadaki gibi korunur, boş alanlar varsayılanla doldurulmaz.
            if ($m->riskDegerlendirmesi?->hastaneMi()) {
                HastaneRisk::maddeyiHesapla($m);

                return;
            }

            $yontem = $m->riskDegerlendirmesi?->yontem ?? 'matris_5x5';

            $mevcut = RiskSkorlama::hesapla($yontem, $m->olasilik, $m->siddet, $m->frekans);
            $m->puan = $mevcut['puan'] ?: null;
            $m->duzey = $mevcut['puan'] ? $mevcut['duzey'] : null;

            // Önlem sonrası olasılık/şiddet/frekans girilmemişse varsayılan:
            // önlem genelde olasılığı düşürür (varsayılan "1" — çok düşük),
            // şiddet ve maruz kalma frekansı önlemden ETKİLENMEZ, öneri
            // öncesindeki değerle aynı kabul edilir.
            // Fine-Kinney'de ilk olasılık zaten 1 veya altındaysa (0,2 / 0,5 / 1) "1" önlem sonrası
            // puanı YÜKSELTİYORDU (ör. 0,5×3×15=22,5 → 1×3×15=45); bu durumda bir alt basamağa iner.
            if ($m->siddet !== null && $m->son_siddet === null) {
                $m->son_siddet = $m->siddet;
            }
            if (RiskSkorlama::ucEksenliMi($yontem) && $m->frekans !== null && $m->son_frekans === null) {
                $m->son_frekans = $m->frekans;
            }
            if ($m->olasilik !== null && $m->son_olasilik === null) {
                $m->son_olasilik = self::varsayilanSonOlasilik(
                    $yontem, (float) $m->olasilik,
                    $m->son_frekans !== null ? (float) $m->son_frekans : null,
                    $m->son_siddet !== null ? (float) $m->son_siddet : null,
                );
            }

            $son = RiskSkorlama::hesapla($yontem, $m->son_olasilik, $m->son_siddet, $m->son_frekans);
            $m->son_puan = $son['puan'] ?: null;
            $m->son_duzey = $son['puan'] ? $son['duzey'] : null;

            // Boş sorumlu / termin kayıtta da varsayılanla dolar — ekranda ve
            // çıktılarda aynı görünsün (kullanıcı 15.10.2026).
            if (blank($m->sorumlu)) {
                $m->sorumlu = self::VARSAYILAN_SORUMLU;
            }
            // Termin risk düzeyine göre (02.11.2026: ÇY → Derhal, YK → 1 Ay, ÖN/OD → 3 Ay, düşük → Sürekli / Rutin).
            // Sistemin koyduğu termin puan değişince yeniden hesaplanır — puansız eklenen madde "SÜREKLİ"
            // alıp sonra Çok Yüksek çıksa da "SÜREKLİ" kalıyordu. Elle yazılan termine dokunulmaz.
            if (blank($m->termin) || ($m->exists && self::otomatikTerminMi($m->termin) && $m->isDirty(['olasilik', 'frekans', 'siddet', 'duzey']))) {
                $m->termin = self::varsayilanTermin($yontem, $m->duzey, $m->tehlike.' '.$m->oneri);
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

        // Puansız kütüphane maddesi ilk kez puanlanınca puan kütüphaneye öğrenilir.
        static::saved(fn (RiskMaddesi $m) => \App\Support\KutuphanePuanOgrenme::ogren($m));

        // 12.11.2026: kütüphanesiz yeni madde kütüphaneye bağlanır / eklenir; dolan alan aynı kütüphane
        // maddesini kullanan diğer analizlerin boş alanına yayılır (AnalizKutuphanesi).
        static::saved(function (RiskMaddesi $m): void {
            if (! $m->tehlike_id) {
                if ($m->wasRecentlyCreated) {
                    \App\Support\AnalizKutuphanesi::maddeyiEsitle($m);
                }

                return;
            }
            if ($m->wasRecentlyCreated || $m->wasChanged(['risk', 'mevcut_onlem', 'oneri', 'mevzuat', 'sorumlu', 'olasilik', 'frekans', 'siddet'])) {
                \App\Support\AnalizKutuphanesi::kardeslereYay($m);
            }
        });
    }

    public function riskDegerlendirmesi(): BelongsTo
    {
        return $this->belongsTo(RiskDegerlendirmesi::class);
    }

    public const VARSAYILAN_SORUMLU = 'İşveren / İşveren Vekili';

    public const VARSAYILAN_TERMIN = 'Sürekli / Rutin';

    /** Sistemin daha önce otomatik koyduğu termin metinleri (elle yazılmış sayılmaz). */
    /** Kullanıcının elle yazdığı 'Sürekli' gibi değerler burada YOK — onlar korunur. */
    public const OTOMATIK_TERMINLER = ['SÜREKLİ', 'Sürekli / Rutin', 'Derhal / 3 Gün', 'Derhal', '1 Ay', '3 Ay'];

    public static function otomatikTerminMi(?string $termin): bool
    {
        return in_array(trim((string) $termin), self::OTOMATIK_TERMINLER, true);
    }

    /**
     * Çıktıda (PDF / Excel / Word) gösterilen termin: elle yazılmışsa olduğu gibi; boş ya da sistemin
     * otomatik koyduğu bir değerse güncel risk düzeyine göre yeniden (eski kayıtlarda ÇY maddeye
     * "SÜREKLİ" basılıyordu — 02.11.2026).
     */
    public function terminMetni(?string $yontem = null): string
    {
        if (filled($this->termin) && ! self::otomatikTerminMi($this->termin)) {
            return (string) $this->termin;
        }

        $yontem ??= $this->riskDegerlendirmesi?->yontem ?? 'fine_kinney';

        return $this->duzey
            ? self::varsayilanTermin($yontem, $this->duzey, $this->tehlike.' '.$this->oneri)
            : ((string) $this->termin ?: self::VARSAYILAN_TERMIN);
    }

    /** Fine-Kinney önlem sonrası varsayılan hedef: puan bu değerin ALTINA (Olası Risk ve altı) iner — AI üreticiyle aynı. */
    public const HEDEF_FK_SON_PUAN_ALTI = 70;

    /**
     * Önlem sonrası olasılık girilmemişse. 5x5: 1. Fine-Kinney: ölçekte en az bir alt basamak, gerekirse
     * önlem sonrası puan 70'in altına inene kadar daha aşağı (en düşük 0,1). Önceden sabit "1" idi:
     * 0,5×3×15=22,5 → 45 (puan YÜKSELİYORDU) ve 3×6×40=720 → 240 (önlemden sonra hâlâ Yüksek) çıkıyordu
     * (FOM MAKİNA RD-2026-122/123 bulguları, tüm firmalar için genel kural — kullanıcı 01.11.2026).
     */
    public static function varsayilanSonOlasilik(string $yontem, float $ilk, ?float $frekans = null, ?float $siddet = null): float
    {
        if ($yontem !== 'fine_kinney') {
            return 1;
        }

        $olcek = array_map('floatval', array_keys(config('isg.risk_fine_kinney.olasilik', [])));
        rsort($olcek);
        $alt = array_values(array_filter($olcek, fn ($o) => $o < $ilk));
        if (! $alt) {
            return $olcek ? end($olcek) : $ilk;
        }

        $carpan = ($frekans ?? 1) * ($siddet ?? 1);
        foreach ($alt as $o) {
            if ($frekans === null || $siddet === null || $o * $carpan < self::HEDEF_FK_SON_PUAN_ALTI) {
                return $o;
            }
        }

        return end($alt);
    }

    /**
     * Boş termin, düzey kısaltmasına göre (config isg.risk_termin_duzey): ÇY → Derhal, YK → 1 Ay, ÖN / OD → 3 Ay;
     * Olası ve Kabul Edilebilir → "Sürekli / Rutin". 02.11.2026: eğitim / talimat maddesi düzeyi ne olursa olsun
     * "SÜREKLİ" alıyordu (Çok Yüksek riskli maddede bile) — o istisna kaldırıldı. $metin geriye uyum için.
     */
    public static function varsayilanTermin(string $yontem, ?string $duzey, ?string $metin = null): string
    {
        $kisa = RiskSkorlama::kisaltma($yontem, $duzey);

        return config('isg.risk_termin_duzey.'.$kisa) ?? self::VARSAYILAN_TERMIN;
    }

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
    /** Excel/Word "Öneri" hücresi: öneri + alt satırda "Mevzuat: …" (12.11.2026; PDF'te aynı yerde). */
    public function oneriVeMevzuat(): string
    {
        return trim((string) $this->oneri.(filled($this->mevzuat) ? "
Mevzuat: ".trim($this->mevzuat) : ''));
    }

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
        if ($this->riskDegerlendirmesi?->hastaneMi()) {
            return '#'.(HastaneRisk::renk($this->duzey ?: HastaneRisk::harf($this->puan)) ?? '6b7280');
        }

        $yontem = $this->riskDegerlendirmesi?->yontem ?? 'matris_5x5';

        return RiskSkorlama::bant($yontem, (float) $this->puan)['renk'];
    }

    /** PDF düzey sütunundaki iki harflik kısaltma (ÇY, YK, ÖN, OL, KE); açıklaması tablonun üstünde. */
    public function duzeyKisa(): ?string
    {
        return RiskSkorlama::kisaltma($this->riskDegerlendirmesi?->yontem ?? 'matris_5x5', $this->duzey);
    }

    public function sonDuzeyKisa(): ?string
    {
        return RiskSkorlama::kisaltma($this->riskDegerlendirmesi?->yontem ?? 'matris_5x5', $this->son_duzey);
    }
}
