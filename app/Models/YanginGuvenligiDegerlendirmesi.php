<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yangın Güvenliği Genel Durum Değerlendirmesi — firma başına bir kayıt. Bina
 * kullanım türü + bölümler + faaliyet/depolama risklerinden bina yangın tehlike
 * sınıfı (düşük/orta/yüksek) belirlenir; tespitler kütüphaneden seçilir.
 */
class YanginGuvenligiDegerlendirmesi extends Model
{
    use HasFactory;

    protected $table = 'yangin_guvenligi_degerlendirmeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'ozel_kullanimlar' => 'array',
        'bolumler' => 'array',
        'riskler' => 'array',
        'tespitler' => 'array',
        'kat_sayisi' => 'integer',
        'kullanici_yuku' => 'integer',
        'taban_alani_m2' => 'decimal:2',
        'sinif_elle' => 'boolean',
        'degerlendirme_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (YanginGuvenligiDegerlendirmesi $d): void {
            if (! $d->sinif_elle) {
                $d->belirlenen_tehlike_sinifi = $d->hesaplaTehlikeSinifi();
            }
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $kayit = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $kayit->exists) {
            $kayit->save();
        }

        return $kayit;
    }

    /**
     * Seçili risklerin config'teki 'sinif' değerlerinin en yükseği + kullanım
     * türü / bölüm etkisi → bina yangın tehlike sınıfı.
     */
    public function hesaplaTehlikeSinifi(): string
    {
        $agirlik = ['dusuk' => 1, 'orta' => 2, 'yuksek' => 3];
        $katalog = config('isg.yangin_guvenligi.risk_kutuphanesi', []);

        $enYuksek = 1;

        foreach ($this->riskler ?? [] as $risk) {
            $puan = $agirlik[$katalog[$risk] ?? 'dusuk'] ?? 1;
            $enYuksek = max($enYuksek, $puan);
        }

        // Depolama / endüstriyel yapı ve yüksek kullanıcı yükü en az "orta"ya çeker.
        if (in_array($this->kullanim_turu, ['endustriyel', 'depolama'], true) && $enYuksek < 2) {
            $enYuksek = 2;
        }

        if (($this->kullanici_yuku ?? 0) >= 1000 && $enYuksek < 2) {
            $enYuksek = 2;
        }

        return array_flip($agirlik)[$enYuksek] ?? 'dusuk';
    }

    public function tehlikeSinifiEtiketi(): string
    {
        return config('isg.yangin_guvenligi.tehlike_siniflari.'.$this->belirlenen_tehlike_sinifi, '—');
    }

    public function kullanimTuruEtiketi(): string
    {
        return config('isg.yangin_guvenligi.kullanim_turleri.'.$this->kullanim_turu, $this->kullanim_turu ?? '—');
    }

    public function doluMu(): bool
    {
        return filled($this->kullanim_turu) || filled($this->riskler) || filled($this->bolumler);
    }
}
