<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ecza Dolabı / İlk Yardım Çantası stok & SKT takibi (K1).
 */
class EczaDolabiKalemi extends Model
{
    protected $table = 'ecza_dolabi_kalemleri';

    protected $guarded = ['id'];

    protected $casts = [
        'miktar' => 'integer',
        'kritik_miktar' => 'integer',
        'skt' => 'date',
    ];

    public const KATEGORILER = [
        'pansuman' => 'Pansuman / Sargı',
        'ilac' => 'Temel İlaç / Antiseptik',
        'cihaz' => 'Tıbbi Cihaz / Ölçüm',
        'sarf' => 'Sarf Malzemesi',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    /** 'suresi_gecmis' | 'skt_yaklasan' | 'kritik_stok' | 'uygun' */
    public function durum(): string
    {
        $bugun = Carbon::today();
        if ($this->skt && $this->skt->lt($bugun)) {
            return 'suresi_gecmis';
        }
        if ($this->skt && $this->skt->lte($bugun->copy()->addDays(30))) {
            return 'skt_yaklasan';
        }
        if ($this->miktar <= $this->kritik_miktar) {
            return 'kritik_stok';
        }

        return 'uygun';
    }

    public function durumEtiketi(): string
    {
        return match ($this->durum()) {
            'suresi_gecmis' => 'SKT Geçmiş',
            'skt_yaklasan' => 'SKT Yaklaşıyor',
            'kritik_stok' => 'Kritik Stok',
            default => 'Uygun',
        };
    }
}
