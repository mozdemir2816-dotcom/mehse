<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeftisPaylasimi extends Model
{
    protected $table = 'teftis_paylasimlari';

    protected $guarded = ['id'];

    protected $casts = [
        'bitis_tarihi' => 'datetime',
        'son_erisim_at' => 'datetime',
        'aktif' => 'boolean',
        'erisim_kayitlari' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function gecerliMi(): bool
    {
        return $this->aktif && $this->bitis_tarihi && $this->bitis_tarihi->isFuture();
    }
}
