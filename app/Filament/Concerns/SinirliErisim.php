<?php

namespace App\Filament\Concerns;

use App\Support\SayfaKatalogu;

/**
 * Sayfa/Resource bazlı kişisel yetkilendirme. Sahip hesap her zaman erişir; diğerleri
 * sadece KullaniciYonetimi ekranından kendilerine açıkça tanımlanmış sayfalara girebilir.
 * Anahtar, sınıf adının kebab-case hali (bkz. SayfaKatalogu::anahtar) — aynı kural hem
 * Page hem Resource sınıflarında (class_basename ile) çalışır.
 */
trait SinirliErisim
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->aktif) {
            return false;
        }

        return $user->sayfaErisimiVarMi(SayfaKatalogu::anahtar(class_basename(static::class)));
    }
}
