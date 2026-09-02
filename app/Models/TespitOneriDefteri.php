<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tespit ve Öneri Defteri — isgpratik 65-66.jpg. Firma başına bir kayıt;
 * madde listesi zamanla biriktirilir.
 */
class TespitOneriDefteri extends Model
{
    use HasFactory;

    protected $table = 'tespit_oneri_defterleri';

    protected $guarded = ['id'];

    protected $casts = [
        'maddeler' => 'array',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public static function firmaIcin(Firma $firma): self
    {
        $defter = static::firstOrNew(['firma_id' => $firma->id]);

        if (! $defter->exists) {
            $defter->maddeler = [];
            $defter->save();
        }

        return $defter;
    }
}
