<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İş İzin Formu (Permit to Work) — isgpratik 76-78.jpg.
 */
class IsIzinFormu extends Model
{
    use HasFactory;

    protected $table = 'is_izin_formlari';

    protected $guarded = ['id'];

    protected $casts = [
        'baslangic' => 'datetime',
        'bitis' => 'datetime',
        'izin_turleri' => 'array',
        'guvenlik_onlemleri' => 'array',
        'gerekli_kkdler' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (IsIzinFormu $f): void {
            $f->izin_no ??= 'PTW-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** @return array<int, string> seçili izin türlerinin etiketleri */
    public function izinTurEtiketleri(): array
    {
        return collect($this->izin_turleri ?? [])
            ->map(fn ($t) => config('isg.is_izin.turler.'.$t, $t))
            ->all();
    }
}
