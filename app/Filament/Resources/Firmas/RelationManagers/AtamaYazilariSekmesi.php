<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\AtamaYazilari as Sayfa;
use App\Models\AtamaYazisi;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Atama Yazıları" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class AtamaYazilariSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'atamaYazilari';

    protected static ?string $title = 'Atama Yazıları';

    protected static function kayitModeli(): string
    {
        return AtamaYazisi::class;
    }

    protected function modulUrl(Model $kayit): ?string
    {
        return Sayfa::getUrl(['firma' => $kayit->firma_id]);
    }

    protected function yeniUrl(): ?string
    {
        return Sayfa::getUrl(['firma' => $this->getOwnerRecord()->getKey()]);
    }
}
