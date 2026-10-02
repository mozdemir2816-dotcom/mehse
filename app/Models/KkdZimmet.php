<?php

namespace App\Models;

use App\Support\KullaniciAyarlari;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * KKD zimmet kaydı (isgsuite "KKD Takip") — bir personele teslim edilen tek
 * KKD kalemi. Teslim durumundaki kayıtlarda yenileme / SKT takibi yapılır:
 * vade = yenileme tarihi ile son kullanma tarihinden erken olanı.
 */
class KkdZimmet extends Model
{
    protected $table = 'kkd_zimmetleri';

    protected $guarded = ['id'];

    protected $casts = [
        'teslim_tarihi' => 'date',
        'son_kullanma' => 'date',
        'yenileme_tarihi' => 'date',
        'iade_tarihi' => 'date',
        'adet' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (KkdZimmet $z): void {
            $z->zimmet_no ??= 'KKD-Z-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 4, '0', STR_PAD_LEFT);
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

    public function stokKarti(): BelongsTo
    {
        return $this->belongsTo(KkdStokKarti::class, 'kkd_stok_karti_id');
    }

    public function aktifMi(): bool
    {
        return $this->durum === 'teslim_edildi';
    }

    public function vade(): ?Carbon
    {
        return collect([$this->son_kullanma, $this->yenileme_tarihi])->filter()->sort()->first();
    }

    /** Vadeye kalan gün (geçmişse negatif); vade yoksa null. */
    public function kalanGun(): ?int
    {
        $vade = $this->vade();

        return $vade ? (int) now()->startOfDay()->diffInDays($vade->copy()->startOfDay(), false) : null;
    }

    /** Teslimdeki kayıt için: gecikmis | yaklasan | aktif. Diğerleri kendi durumu. */
    public function takipDurumu(): string
    {
        if (! $this->aktifMi()) {
            return $this->durum;
        }

        $kalan = $this->kalanGun();

        return match (true) {
            $kalan === null => 'aktif',
            $kalan < 0 => 'gecikmis',
            $kalan <= KullaniciAyarlari::esik('kkd') => 'yaklasan',
            default => 'aktif',
        };
    }

    public function takipEtiketi(): string
    {
        return config('isg.kkd_takip.takip_durumlari.'.$this->takipDurumu())
            ?? config('isg.kkd_takip.durumlar.'.$this->durum, $this->durum);
    }

    public function durumEtiketi(): string
    {
        return config('isg.kkd_takip.durumlar.'.$this->durum, $this->durum);
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.kkd.kategoriler.'.$this->kategori.'.ad', $this->kategori ?? '—');
    }

    public function markaModel(): string
    {
        return collect([$this->marka, $this->model])->filter()->implode(' / ');
    }

    /** Katalogdaki TS EN standardı (tür adı katalogda varsa). */
    public function standart(): ?string
    {
        foreach (config('isg.kkd.kategoriler', []) as $kategori) {
            foreach ($kategori['maddeler'] as $madde) {
                if ($madde['ad'] === $this->tur) {
                    return $madde['standart'];
                }
            }
        }

        return null;
    }
}
