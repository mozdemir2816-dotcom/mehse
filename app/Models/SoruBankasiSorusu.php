<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Soru Bankası sorusu — kalıcı, kaynaklı, onaylı İSG sınav sorusu. Eğitim
 * Soruları ve Uzaktan Eğitim sınavları yalnızca `durum = onaylandi` soruları
 * havuzdan çeker.
 */
class SoruBankasiSorusu extends Model
{
    protected $table = 'soru_bankasi_sorulari';

    protected $guarded = ['id'];

    protected $casts = [
        'secenekler' => 'array',
        'dogru_index' => 'integer',
        'onay_tarihi' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sistem havuzu (user_id NULL) + verilen uzmanın kendi soruları. */
    public function scopeErisilebilir(Builder $q, ?int $userId): Builder
    {
        return $q->where(fn (Builder $s) => $s->whereNull('user_id')->orWhere('user_id', $userId));
    }

    public function scopeOnayli(Builder $q): Builder
    {
        return $q->where('durum', 'onaylandi');
    }

    public function dogruSecenek(): ?string
    {
        return $this->secenekler[$this->dogru_index] ?? null;
    }

    public function sektorEtiketi(): string
    {
        return $this->sektor_anahtari
            ? (config('isg.risk_ai.sektorler.'.$this->sektor_anahtari.'.ad') ?? $this->sektor_anahtari)
            : 'Genel';
    }

    public function konuEtiketi(): string
    {
        return config('isg.soru_bankasi.konular.'.$this->konu, $this->konu ?? '—');
    }

    public function zorlukEtiketi(): string
    {
        return config('isg.soru_bankasi.zorluklar.'.$this->zorluk, $this->zorluk ?? '—');
    }

    public function durumEtiketi(): string
    {
        return config('isg.soru_bankasi.durumlar.'.$this->durum, $this->durum ?? '—');
    }

    /**
     * Eğitim Soruları / LMS sınav biçimine indirger.
     *
     * @return array{soru: string, secenekler: array<int, string>, dogru_index: int, kaynak: ?string, aciklama: ?string}
     */
    public function sinavBicimi(): array
    {
        return [
            'soru' => $this->soru,
            'secenekler' => $this->secenekler,
            'dogru_index' => $this->dogru_index,
            'kaynak' => $this->kaynak,
            'aciklama' => $this->aciklama,
        ];
    }
}
