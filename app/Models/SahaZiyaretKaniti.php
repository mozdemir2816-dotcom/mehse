<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SahaZiyaretKaniti extends Model
{
    protected $table = 'saha_ziyaret_kanitlari';

    protected $guarded = ['id'];

    protected $casts = [
        'ziyaret_tarihi' => 'date',
        'giris_at' => 'datetime',
        'cikis_at' => 'datetime',
        'qr_dogrulandi' => 'boolean',
        'imza_onay' => 'boolean',
        'sure_dk' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function gpsVarMi(): bool
    {
        return $this->giris_enlem !== null && $this->giris_boylam !== null;
    }

    public function kanitPuani(): int
    {
        return ($this->qr_dogrulandi ? 1 : 0)
            + ($this->gpsVarMi() ? 1 : 0)
            + (filled($this->defter_dosyasi) ? 1 : 0)
            + ($this->imza_onay ? 1 : 0);
    }
}
