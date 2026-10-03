<?php

namespace App\Models;

use App\Support\ArsivKurali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Arşiv > Belge Şablonları — kullanıcının {{isyeri.unvan}} gibi yer
 * tutucular içeren kendi Word (.docx) / Excel (.xlsx) şablonu. Yer tutucular
 * yüklenirken taranır (BelgeSablonMotoru); sistemde karşılığı olanlar
 * otomatik dolar, kalanlar üretim formunda sorulur.
 */
class BelgeSablonu extends Model
{
    protected $table = 'belge_sablonlari';

    protected $guarded = ['id'];

    protected $casts = ['yer_tutucular' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategoriEtiketi(): string
    {
        return $this->kategori ? ArsivKurali::kategori($this->kategori)['ad'] : 'Tüm kategoriler';
    }
}
