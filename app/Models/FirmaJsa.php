<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JSA kütüphanesindeki bir analizin bir firmaya atanması (firma_jsa pivotu,
 * ama Raporlar/Evrak ZIP akışına girsin diye ilk sınıf model). "Kazı ile
 * hazırladığım JSA'yı Düzyaka + başka firmalara da ekleyeyim; ileride o
 * firmanın evrakını toplu indirince orada çıksın" ihtiyacı için.
 *
 * @property-read Firma $firma
 * @property-read JsaSablonu $jsaSablonu
 */
class FirmaJsa extends Model
{
    protected $table = 'firma_jsa';

    protected $guarded = ['id'];

    /** Raporlar listesi baslikEtiketi() için JSA başlığını hazır getir. */
    protected $with = ['jsaSablonu:id,baslik'];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }

    public function jsaSablonu(): BelongsTo
    {
        return $this->belongsTo(JsaSablonu::class);
    }

    /** config isg.raporlar.kaynaklar → tip_metod: satırın "tip" etiketi. */
    public function baslikEtiketi(): string
    {
        return 'JSA: '.($this->jsaSablonu?->baslik ?? '—');
    }
}
