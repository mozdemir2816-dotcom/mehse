<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kontrol Kartı için sistemde olmayan alan/ekipman şablonu (14.11.2026) —
 * Gemini'nin "yeni alan" kontrol kartı cevabı yüklenince kendiliğinden oluşur.
 * Yapısı config/kontrol_kartlari.php'deki şablonla aynıdır (maddeler: kod,
 * baslik, kontrol, tespit, ..., once [O,F,Ş], sonra [O,F,Ş]).
 */
class KontrolKartiSablonu extends Model
{
    protected $table = 'kontrol_karti_sablonlari';

    protected $guarded = ['id'];

    protected $casts = [
        'maddeler' => 'array',
    ];

    /** @return array{ad: string, aciklama: ?string, maddeler: array} config şablonuyla aynı biçim */
    public function sablonDizisi(): array
    {
        return ['ad' => $this->ad, 'aciklama' => $this->aciklama, 'maddeler' => $this->maddeler ?? []];
    }
}
