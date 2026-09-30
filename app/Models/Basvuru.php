<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kayıt / başvuru kaydı — bireysel İGU kaydı (anında hesap) veya OSGB
 * başvurusu (sahip onayı ile hesap). Bkz. App\Support\BasvuruIslemleri.
 */
class Basvuru extends Model
{
    protected $table = 'basvurular';

    protected $guarded = ['id'];

    protected $casts = [
        'onaylar' => 'array',
        'incelendi_at' => 'datetime',
    ];

    public const TIPLER = [
        'uzman' => 'İş Güvenliği Uzmanı (bireysel)',
        'osgb' => 'OSGB',
    ];

    public const DURUMLAR = [
        'beklemede' => 'Beklemede',
        'onaylandi' => 'Onaylandı',
        'reddedildi' => 'Reddedildi',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inceleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inceleyen_id');
    }

    public function tipEtiketi(): string
    {
        return self::TIPLER[$this->tip] ?? $this->tip;
    }

    public function durumEtiketi(): string
    {
        return self::DURUMLAR[$this->durum] ?? $this->durum;
    }

    /** Başvuru ekranındaki başlık: OSGB adı ya da kişi adı. */
    public function baslik(): string
    {
        return $this->osgb_adi ?: $this->ad_soyad;
    }
}
