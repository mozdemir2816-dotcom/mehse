<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TehlikeKategorisi extends Model
{
    use HasFactory;

    protected $table = 'tehlike_kategorileri';

    protected $guarded = ['id'];

    protected $casts = [
        'ekipman_tipleri' => 'array',
        'nace_gruplari' => 'array',
    ];

    public function tehlikeler(): HasMany
    {
        return $this->hasMany(Tehlike::class);
    }

    /**
     * Firmanın yöntemi ve NACE grubuna uyan kategoriler. "ortak" etiketli kategoriler
     * her NACE grubunda görünür (vinç, kompresör gibi ortak ekipmanlar).
     */
    public function scopeUygun(Builder $sorgu, string $yontem, ?string $naceGrubu): Builder
    {
        return $sorgu->where('yontem', $yontem)
            ->where(function (Builder $q) use ($naceGrubu) {
                $q->whereJsonContains('nace_gruplari', 'ortak');

                if ($naceGrubu) {
                    $q->orWhereJsonContains('nace_gruplari', $naceGrubu);
                }
            });
    }
}
