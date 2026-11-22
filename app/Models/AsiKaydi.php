<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Çalışan Aşı Takip kaydı (Tetanoz, Hepatit B, Grip vb. — K1).
 */
class AsiKaydi extends Model
{
    protected $table = 'asi_kayitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'yapilma_tarihi' => 'date',
        'sonraki_doz_tarihi' => 'date',
    ];

    public const TURLER = [
        'Tetanoz' => 'Tetanoz (Td)',
        'Hepatit B' => 'Hepatit B',
        'Grip' => 'Mevsimsel Grip',
        'Hepatit A' => 'Hepatit A',
        'Kuduz' => 'Kuduz',
        'Diğer' => 'Diğer',
    ];

    public const DOZLAR = [
        '1. Doz' => '1. Doz',
        '2. Doz' => '2. Doz',
        '3. Doz' => '3. Doz',
        'Rapel' => 'Rapel (Hatırlatma)',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function calisan(): BelongsTo
    {
        return $this->belongsTo(Calisan::class);
    }

    public function sonrakiDurum(): string
    {
        if (! $this->sonraki_doz_tarihi) {
            return 'tamam';
        }
        $bugun = Carbon::today();
        if ($this->sonraki_doz_tarihi->lt($bugun)) {
            return 'gecikti';
        }
        if ($this->sonraki_doz_tarihi->lte($bugun->copy()->addDays(30))) {
            return 'yaklasan';
        }

        return 'planli';
    }
}
