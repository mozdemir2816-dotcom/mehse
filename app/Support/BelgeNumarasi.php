<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Belge numarası üretimi (13.11.2026): eskiden "kayıt sayısı + 1" kullanılıyordu; bir kayıt
 * silinince sonraki yeni kayıt mevcut bir numarayı tekrar alıyordu (ör. RD-2026-044 iki firmada).
 * Artık aynı önekteki en büyük sıra + 1 alınır (silinmiş kayıtlar dahil).
 */
class BelgeNumarasi
{
    /**
     * @param  class-string<Model>  $model
     * @param  string  $onek  ör. 'EGT-2026-'
     */
    public static function sonraki(string $model, string $kolon, string $onek, int $hane = 3, ?callable $kapsam = null): string
    {
        $sorgu = $model::query()->withoutGlobalScopes();
        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $sorgu->withTrashed();
        }
        if ($kapsam) {
            $kapsam($sorgu);
        }

        $enBuyuk = $sorgu->where($kolon, 'like', addcslashes($onek, '%_').'%')
            ->pluck($kolon)
            ->map(fn ($no) => substr((string) $no, strlen($onek)))
            ->filter(fn ($sira) => ctype_digit($sira))
            ->map(fn ($sira) => (int) $sira)
            ->max() ?? 0;

        return $onek.str_pad((string) ($enBuyuk + 1), $hane, '0', STR_PAD_LEFT);
    }
}
