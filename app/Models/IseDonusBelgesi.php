<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İşe Dönüş Belgesi — uzun süreli rapor / iş kazası / meslek hastalığı sonrası
 * çalışanın işe dönüşünde işyeri hekiminin uygunluk değerlendirmesi ve geçici
 * iş kısıtlamaları (6331 s.K. Md.15).
 */
class IseDonusBelgesi extends Model
{
    use HasFactory;

    protected $table = 'ise_donus_belgeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'kisitlamalar' => 'array',
        'devamsizlik_baslangic' => 'date',
        'devamsizlik_bitis' => 'date',
        'ise_donus_tarihi' => 'date',
        'kontrol_muayene_tarihi' => 'date',
        'belge_tarihi' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (IseDonusBelgesi $b): void {
            $b->belge_no ??= 'IDB-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
            $b->belge_tarihi ??= now()->toDateString();
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

    public function isKazasiRaporu(): BelongsTo
    {
        return $this->belongsTo(IsKazasiRaporu::class);
    }

    public function nedenEtiketi(): string
    {
        return config('isg.ise_donus.nedenler.'.$this->neden, $this->neden ?? '—');
    }

    public function uygunlukEtiketi(): string
    {
        return config('isg.ise_donus.uygunluk.'.$this->uygunluk, $this->uygunluk ?? '—');
    }

    public function devamsizlikGunu(): ?int
    {
        if (! $this->devamsizlik_baslangic || ! $this->devamsizlik_bitis) {
            return null;
        }

        return (int) $this->devamsizlik_baslangic->diffInDays($this->devamsizlik_bitis) + 1;
    }
}
