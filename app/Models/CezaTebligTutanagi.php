<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İSG Ceza ve Tebliğ Tutanağı — isgpratik 79-80.jpg.
 */
class CezaTebligTutanagi extends Model
{
    use HasFactory;

    protected $table = 'ceza_teblig_tutanaklari';

    protected $guarded = ['id'];

    protected $casts = [
        'tutanak_tarihi' => 'date',
        'ise_giris_tarihi' => 'date',
        'olay_tarihi' => 'date',
        'teblig_tarihi' => 'date',
        'taniklar' => 'array',
        'ihlaller' => 'array',
        'fotograflar' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CezaTebligTutanagi $t): void {
            $t->tutanak_no ??= 'CT-'.now()->format('Y').'-'.str_pad((string) (static::count() + 1), 3, '0', STR_PAD_LEFT);
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function istihdamSekliEtiketi(): string
    {
        return match ($this->istihdam_sekli) {
            'alt_isveren' => 'Alt İşveren (Taşeron) Çalışanı',
            'gecici_gorevli' => 'Geçici Görevli Çalışan',
            default => 'Kadrolu Çalışan',
        };
    }

    public function yaptirimEtiketi(): string
    {
        return config('isg.ceza_teblig.yaptirimlar.'.$this->yaptirim.'.ad', $this->yaptirim) ?? '—';
    }
}
