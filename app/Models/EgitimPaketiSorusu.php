<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgitimPaketiSorusu extends Model
{
    use HasFactory;

    protected $table = 'egitim_paketi_sorulari';

    protected $guarded = ['id'];

    /** Sorunun kullanıldığı sınav: ön test (seviye tespiti), final sınavı ya da ikisi. */
    public const KULLANIMLAR = [
        'ortak' => 'Sınav öncesi + sonrası',
        'on_test' => 'Yalnız sınav öncesi (ön test)',
        'final' => 'Yalnız sınav sonrası (final)',
    ];

    protected $casts = [
        'secenekler' => 'array',
        'dogru_index' => 'integer',
        'modul' => 'integer',
    ];

    /** Sorunun Ek-1 konu başlığı (final sınavı konulara dengeli dağıtılır). */
    public const KONULAR = [
        1 => 'Genel Konular',
        2 => 'Sağlık Konuları',
        3 => 'Teknik Konular',
    ];

    public const ZORLUKLAR = [
        'kolay' => 'Kolay',
        'orta' => 'Orta',
        'zor' => 'Zor',
    ];

    public function paket(): BelongsTo
    {
        return $this->belongsTo(EgitimPaketi::class, 'egitim_paketi_id');
    }

    public function dogruSecenek(): ?string
    {
        return $this->secenekler[$this->dogru_index] ?? null;
    }
}
