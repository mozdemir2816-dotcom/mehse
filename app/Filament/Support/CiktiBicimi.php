<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Radio;

/** Yıldız Grup sertifikasında Excel / PDF çıktı biçimi seçimi (varsayılan Excel). */
final class CiktiBicimi
{
    public static function alan(): Radio
    {
        return Radio::make('bicim')
            ->label('Çıktı biçimi')
            ->options(['xlsx' => 'Excel (.xlsx)', 'pdf' => 'PDF'])
            ->default('xlsx')
            ->inline()
            ->required();
    }

    public static function secili(array $data): string
    {
        return ($data['bicim'] ?? 'xlsx') === 'pdf' ? 'pdf' : 'xlsx';
    }
}
