<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Firma Takip — bir firmanın bir Kontrol Merkezi kriteri için elle girilen
 * vade tarihi (isgpratik "Firma Checklist" referansı). Bkz. `PortfoyKarne::
 * firmaChecklistDetay()`.
 */
class FirmaChecklistVadesi extends Model
{
    protected $table = 'firma_checklist_vadeleri';

    protected $guarded = ['id'];

    protected $casts = [
        'vade_tarihi' => 'date',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
}
