<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Toolbox Konuşması (İş Başı Konuşması) — uzmanın önceden hazırladığı, sahada
 * kısa güvenlik brifingi olarak kullanılan metinlerin kişisel kütüphanesi.
 * Firma bağımsızdır; tek dosya (Word/PDF/Excel) + başlık olarak saklanır.
 */
class ToolboxKonusmasi extends Model
{
    protected $table = 'toolbox_konusmalari';

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boyutEtiketi(): string
    {
        if ($this->boyut <= 0) {
            return '—';
        }

        $kb = $this->boyut / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1).' MB' : number_format($kb, 0).' KB';
    }
}
