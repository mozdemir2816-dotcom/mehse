<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Makine tipi başına güvenlik afişi (App\Support\EkipmanAfisKutuphanesi).
 *
 * @property array<int, string>|null $yapin
 * @property array<int, string>|null $yapmayin
 * @property array<int, string>|null $kkd
 */
class EkipmanAfisi extends Model
{
    protected $table = 'ekipman_afisleri';

    protected $guarded = ['id'];

    protected $casts = [
        'yapin' => 'array',
        'yapmayin' => 'array',
        'kkd' => 'array',
        'yz_taslak' => 'boolean',
        'onaylandi_at' => 'datetime',
    ];

    public function gorselVarMi(): bool
    {
        return $this->gorsel_yolu && Storage::disk('public')->exists($this->gorsel_yolu);
    }

    public function yaziVarMi(): bool
    {
        return filled($this->yapin) && filled($this->yapmayin);
    }

    public function hazirMi(): bool
    {
        return $this->gorselVarMi() && $this->yaziVarMi();
    }

    public function onayBekliyorMu(): bool
    {
        return $this->yz_taslak && $this->onaylandi_at === null;
    }

    /** Admin önizlemesi: /storage sembolik bağlantısı eksik hostingde de çalışan oturumlu rota. */
    public function gorselAdresi(): ?string
    {
        return $this->gorselVarMi()
            ? route('mehse.storage.dosya', ['yol' => $this->gorsel_yolu]).'?v='.$this->updated_at?->timestamp
            : null;
    }
}
