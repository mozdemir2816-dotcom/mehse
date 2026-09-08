<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Onaylı Defter Nüshası — Tespit ve Öneri Defteri'nin imzalı/onaylı, İSG-KATİP'e
 * yüklenen nüshasının taranmış hâli. `nusha_no` firma + defter türü başına
 * otomatik sıralanır (1, 2, 3...).
 */
class OnayliDefterNushasi extends Model
{
    use HasFactory;

    protected $table = 'onayli_defter_nushalari';

    protected $guarded = ['id'];

    protected $casts = [
        'onay_tarihi' => 'date',
        'boyut' => 'integer',
        'nusha_no' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (OnayliDefterNushasi $n): void {
            $n->nusha_no ??= static::sonrakiNo($n->firma_id, $n->defter_turu ?: 'tespit_oneri');
        });
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function tespitOneriDefteri(): BelongsTo
    {
        return $this->belongsTo(TespitOneriDefteri::class);
    }

    /** Firma + defter türü için bir sonraki nüsha numarası. */
    public static function sonrakiNo(int $firmaId, string $defterTuru): int
    {
        return (int) static::where('firma_id', $firmaId)
            ->where('defter_turu', $defterTuru)
            ->max('nusha_no') + 1;
    }

    public function defterTuruEtiketi(): string
    {
        return config('isg.onayli_defter.turleri.'.$this->defter_turu, $this->defter_turu);
    }

    public function baslik(): string
    {
        return $this->defterTuruEtiketi().' — '.$this->nusha_no.'. Nüsha';
    }

    public function boyutEtiketi(): string
    {
        if ($this->boyut <= 0) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }
}
