<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzaktan eğitim giriş günlüğü — portal girişi (atama boş) ya da bir
 * eğitimin açılması (atama dolu).
 */
class EgitimGirisi extends Model
{
    protected $table = 'egitim_girisleri';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $attributes = ['izleme_sn' => 0, 'yoklama_sayisi' => 0];

    protected $casts = [
        'giris_at' => 'datetime',
        'cikis_at' => 'datetime',
        'izleme_sn' => 'integer',
        'yoklama_sayisi' => 'integer',
    ];

    /**
     * Son etkinlikten bu kadar dakika içinde aynı eğitim yeniden açılırsa
     * (sayfa yenileme, kısa ara) aynı oturum sürer, yeni giriş yazılmaz.
     */
    public const TEKRAR_DK = 30;

    /** Oturumu başlatır ya da süren oturumu döndürür. */
    public static function kaydet(int $calisanId, ?int $atamaId = null): self
    {
        $sinir = now()->subMinutes(static::TEKRAR_DK);

        $suren = static::query()
            ->where('calisan_id', $calisanId)
            ->where('egitim_atamasi_id', $atamaId)
            ->where(fn ($q) => $q->where('cikis_at', '>=', $sinir)->orWhere(fn ($q) => $q->whereNull('cikis_at')->where('giris_at', '>=', $sinir)))
            ->latest('giris_at')
            ->first();

        return $suren ?? static::create([
            'calisan_id' => $calisanId,
            'egitim_atamasi_id' => $atamaId,
            'ip' => request()->ip(),
            'giris_at' => now(),
        ]);
    }

    /** Oturumun son etkinliği = çıkış; izlenen süre eklenir (Md.12/4). */
    public function nabiz(int $izlemeArtisSn = 0, bool $yoklama = false): void
    {
        $this->cikis_at = now();
        $this->izleme_sn += max(0, $izlemeArtisSn);
        if ($yoklama) {
            $this->yoklama_sayisi++;
        }
        $this->save();
    }

    /** Oturum süresi (dk) — çıkış yoksa 0. */
    public function sureDk(): int
    {
        return $this->cikis_at ? (int) round($this->giris_at->diffInSeconds($this->cikis_at) / 60) : 0;
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function atama(): BelongsTo
    {
        return $this->belongsTo(EgitimAtamasi::class, 'egitim_atamasi_id');
    }
}
