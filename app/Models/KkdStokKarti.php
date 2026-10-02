<?php

namespace App\Models;

use App\Support\KullaniciAyarlari;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * KKD Stok Kartı — firmadaki bir KKD türünün (marka / model / beden) stoğu.
 * `mevcut` her hareketle (giriş / zimmet / iade / fire) güncellenir; hareket
 * geçmişi kkd_stok_hareketleri'nde tutulur (bkz. App\Support\KkdStok).
 */
class KkdStokKarti extends Model
{
    protected $table = 'kkd_stok_kartlari';

    protected $guarded = ['id'];

    protected $casts = [
        'mevcut' => 'integer',
        'asgari' => 'integer',
        'son_kullanma' => 'date',
        'yenileme_tarihi' => 'date',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function hareketler(): HasMany
    {
        return $this->hasMany(KkdStokHareketi::class)->latest('tarih')->latest('id');
    }

    public function etiket(): string
    {
        return collect([$this->tur, $this->marka, $this->model, $this->beden ? 'Beden '.$this->beden : null])
            ->filter()->implode(' · ');
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.kkd.kategoriler.'.$this->kategori.'.ad', $this->kategori ?? '—');
    }

    /** SKT / yenileme tarihlerinden erken olanı. */
    public function vade(): ?Carbon
    {
        return collect([$this->son_kullanma, $this->yenileme_tarihi])->filter()->sort()->first();
    }

    /** tukendi | dusuk | suresi_gecmis | yaklasan | yeterli */
    public function durum(): string
    {
        $vade = $this->vade();

        return match (true) {
            $this->mevcut <= 0 => 'tukendi',
            $vade !== null && $vade->isPast() && ! $vade->isToday() => 'suresi_gecmis',
            $this->asgari > 0 && $this->mevcut <= $this->asgari => 'dusuk',
            $vade !== null && now()->startOfDay()->diffInDays($vade, false) <= KullaniciAyarlari::esik('kkd') => 'yaklasan',
            default => 'yeterli',
        };
    }

    public function durumEtiketi(): string
    {
        return config('isg.kkd_takip.stok_durumlari.'.$this->durum(), $this->durum());
    }

    public function dusukStokMu(): bool
    {
        return in_array($this->durum(), ['tukendi', 'dusuk'], true);
    }
}
