<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

/**
 * Telefonda işe yaramayan düğmeleri gizler (13.10.2026, kullanıcı isteği:
 * "telefonda kullanılmayan bir seçenek olmasın").
 *
 * Excel şablonu indirip doldurmak, Excel / İSG-KATİP listesi yüklemek telefonda
 * yapılmıyor; bu aksiyonların düğmesine `mehse-masaustu` sınıfı eklenir ve
 * mobil.css 1024px altında gizler. Aksiyonun kendisi kaldırılmaz — masaüstünde
 * aynen durur, testler ve yetki etkilenmez.
 */
class MobilGorunum
{
    public const SINIF = 'mehse-masaustu';

    /** Yalnız masaüstünde gösterilen aksiyon adları (Action::make('...')). */
    public const MASAUSTU_AKSIYONLARI = [
        'excelSablon',
        'excelYukle',
        'excelYukleEgitim',
        'excelYukleCalisma',
        'excelSektorSablonu',
        'sablonIndir',
        'egitimSablonIndir',
        'topluYukle',
        'katipAktar',
        'katipSec',
        'tablodanAktar',
        'fineKinneyYukle',
    ];

    public static function kaydet(): void
    {
        Action::configureUsing(function (Action $aksiyon): void {
            if (in_array($aksiyon->getName(), self::MASAUSTU_AKSIYONLARI, true)) {
                $aksiyon->extraAttributes(['class' => self::SINIF], merge: true);
            }
        });

        // Tüm alt aksiyonları masaüstüne özgü olan grup (Firmalar > "İSG-KATİP")
        // da telefonda gizlenir.
        ActionGroup::configureUsing(function (ActionGroup $grup): void {
            // getActions() alt aksiyonlara varsayılan görünüm atıyor; ham listeyi oku.
            $adlar = array_map(
                fn ($a) => $a instanceof Action ? $a->getName() : null,
                (fn () => $this->actions)->call($grup),
            );

            if ($adlar !== [] && array_diff($adlar, self::MASAUSTU_AKSIYONLARI) === []) {
                $grup->extraAttributes(['class' => self::SINIF], merge: true);
            }
        });
    }
}
