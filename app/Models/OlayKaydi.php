<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Olay Kaydı — İSG olay defterinin tek bir satırı. İş Kazası Raporu'ndan farkı:
 * yaralanma olmasa da (ramak kala, tehlikeli durum/davranış, maddi hasar, çevre)
 * her olayı kapsar; sınıflandırma + potansiyel risk skoru + 5 Neden (5N) kök
 * neden zinciri tutar ve tek tıkla DÖF'e aktarılabilir.
 */
class OlayKaydi extends Model
{
    protected $table = 'olay_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'olay_tarihi' => 'date',
        'bildirim_tarihi' => 'date',
        'sgk_bildirim_tarihi' => 'date',
        'kayip_gun_sayisi' => 'integer',
        'potansiyel_skor' => 'integer',
        'bes_neden' => 'array',
        'etkilenen_kategorileri' => 'array',
        'kok_neden_kategorileri' => 'array',
        'taniklar' => 'array',
        'fotograflar' => 'array',
        'sgk_bildirimi_yapildi' => 'boolean',
        'kolluk_bildirimi_yapildi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (OlayKaydi $o): void {
            $o->belge_no ??= 'OLK-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
            $o->potansiyel_skor = static::skorHesapla($o->olasilik, $o->siddet);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function dofRaporu(): BelongsTo
    {
        return $this->belongsTo(DofRaporu::class);
    }

    /**
     * Potansiyel risk skoru = olasılık sırası (1-5) × şiddet sırası (1-5) → 1-25.
     * (Kayıp/ramak kala olayında "gerçekleşen" değil "olabilecek en kötü sonuç"
     * değerlendirilir — bu yüzden İş Kazası Raporu'ndaki ağırlık derecesinden ayrı.)
     */
    public static function skorHesapla(?string $olasilik, ?string $siddet): ?int
    {
        $o = array_search($olasilik, array_keys(config('isg.olay.olasiliklar', [])), true);
        $s = array_search($siddet, array_keys(config('isg.olay.siddetler', [])), true);

        return ($o !== false && $s !== false) ? ($o + 1) * ($s + 1) : null;
    }

    public function tipEtiketi(): string
    {
        return config('isg.olay.tipler.'.$this->olay_tipi, $this->olay_tipi ?? '—');
    }

    public function sonucEtiketi(): string
    {
        return config('isg.olay.sonuc_turleri.'.$this->sonuc_turu, $this->sonuc_turu ?? '—');
    }

    public function olasilikEtiketi(): string
    {
        return config('isg.olay.olasiliklar.'.$this->olasilik, $this->olasilik ?? '—');
    }

    public function siddetEtiketi(): string
    {
        return config('isg.olay.siddetler.'.$this->siddet, $this->siddet ?? '—');
    }

    public function potansiyelSeviye(): string
    {
        return match (true) {
            $this->potansiyel_skor === null => '—',
            $this->potansiyel_skor <= 4 => 'Düşük',
            $this->potansiyel_skor <= 9 => 'Orta',
            $this->potansiyel_skor <= 15 => 'Yüksek',
            default => 'Çok Yüksek',
        };
    }

    /** İş kazası / meslek hastalığı şüphesi — SGK/kolluk bildirim alanları görünür. */
    public function isKazasiMi(): bool
    {
        return in_array($this->olay_tipi, ['is_kazasi', 'meslek_hastaligi_supheli'], true);
    }

    /** @return array<int, string> */
    public function etkilenenEtiketleri(): array
    {
        return collect($this->etkilenen_kategorileri ?? [])
            ->map(fn ($k) => config('isg.olay.etkilenen_kategorileri.'.$k, $k))
            ->all();
    }

    /** @return array<int, string> */
    public function kokNedenEtiketleri(): array
    {
        return collect($this->kok_neden_kategorileri ?? [])
            ->map(fn ($k) => config('isg.is_kazasi.kok_neden_kategorileri.'.$k, $k))
            ->all();
    }

    /**
     * 5 Neden zinciri — boş adımlar atılır ("Neden 1: … → Neden 2: …" formatına
     * PDF/Excel tarafında dönüştürülür).
     *
     * @return array<int, string>
     */
    public function nedenZinciri(): array
    {
        return array_values(array_filter(
            array_map('trim', $this->bes_neden ?? []),
            fn ($n) => $n !== '',
        ));
    }
}
