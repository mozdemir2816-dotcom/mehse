<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Arşiv dosyası / doküman — Profilim > Arşiv (isgpratik 144.jpg) ve
 * Doküman Yönetimi (isgsuite) aynı tabloyu kullanır: firma başına dosya +
 * kategori, başlık, açıklama, başlangıç / geçerlilik sonu, versiyon, durum.
 */
class ArsivDosya extends Model
{
    protected $table = 'arsiv_dosyalari';

    protected $guarded = ['id'];

    protected $attributes = ['aktif' => true];

    protected $casts = [
        'boyut' => 'integer',
        'baslangic_tarihi' => 'date',
        'gecerlilik_sonu' => 'date',
        'aktif' => 'boolean',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function boyutEtiketi(): string
    {
        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }

    /** Listede görünen ad: başlık, yoksa dosya adı. */
    public function etiket(): string
    {
        return $this->baslik ?: (string) $this->dosya_adi;
    }

    public function kategoriEtiketi(): string
    {
        return config('isg.dokuman.kategoriler.'.($this->kategori ?: 'diger'), $this->kategori ?: 'Diğer');
    }

    /** @return 'suresiz'|'gecerli'|'yaklasan'|'dolmus' */
    public function gecerlilikDurumu(): string
    {
        if (! $this->gecerlilik_sonu) {
            return 'suresiz';
        }

        $kalan = (int) Carbon::today()->diffInDays($this->gecerlilik_sonu, false);

        return match (true) {
            $kalan < 0 => 'dolmus',
            $kalan <= (int) config('isg.dokuman.yaklasan_gun', 30) => 'yaklasan',
            default => 'gecerli',
        };
    }

    public function durumEtiketi(): string
    {
        if (! $this->aktif) {
            return 'Pasif';
        }

        return ['suresiz' => 'Aktif', 'gecerli' => 'Aktif', 'yaklasan' => 'Süresi yaklaşıyor', 'dolmus' => 'Süresi doldu'][$this->gecerlilikDurumu()];
    }
}
