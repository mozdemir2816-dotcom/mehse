<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Radio;

/**
 * Evrak üretim aksiyonlarında paylaşılan "İmzalı / İmzasız (matbu)" seçimi.
 * Sistemde kayıtlı kaşe/imza görselini gömen üreticilerde gerçek görsel
 * bloğunu gizler; görseli olmayan (zaten metinsel/boş) üreticilerde şu an
 * için yalnızca arayüz tutarlılığı amacıyla sorulur.
 */
final class ImzaSecenegi
{
    public static function alan(): Radio
    {
        return Radio::make('imzali')
            ->label('Belge nasıl üretilsin?')
            ->options([
                '1' => 'İmzalı / kaşeli (sistemde kayıtlı görsellerle)',
                '0' => 'İmzasız (matbu — elle imzalanacak boş form)',
            ])
            ->default('1')
            ->inline()
            ->required();
    }

    public static function secili(array $data): bool
    {
        return ($data['imzali'] ?? '1') === '1';
    }
}
