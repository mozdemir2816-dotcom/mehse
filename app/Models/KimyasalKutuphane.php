<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kimyasal Kütüphanesi kaydı — firma envanterine (KimyasalUrun) kopyalanır.
 * `user_id` boşsa hazır kayıt; doluysa uzmanın kendi kaydı (düzenlenebilir/silinebilir).
 */
class KimyasalKutuphane extends Model
{
    protected $table = 'kimyasal_kutuphanesi';

    protected $guarded = ['id'];

    protected $casts = [
        'ghs' => 'array',
    ];

    /** Hazır kayıtlar + verilen uzmanın kendi kayıtları. */
    public function scopeErisilebilir(Builder $q, ?int $userId): Builder
    {
        return $q->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId));
    }

    public function hazirMi(): bool
    {
        return $this->user_id === null;
    }

    /** @return array<int, string> */
    public function ghsKodlari(): array
    {
        return collect($this->ghs ?? [])->map(fn ($k) => strtoupper($k))->all();
    }

    /** Envantere eklenecek alanlar. */
    public function envanterVerisi(): array
    {
        return $this->only(['urun_adi', 'cas_no', 'tedarikci', 'fiziksel_hal', 'kullanim_alani', 'depolama', 'ghs', 'aciklama']);
    }
}
