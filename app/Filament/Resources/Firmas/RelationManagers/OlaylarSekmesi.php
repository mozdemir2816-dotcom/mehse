<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\OlayKayitlari as Sayfa;
use App\Models\OlayKaydi;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Olay / Ramak Kala" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class OlaylarSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'olayKayitlari';

    protected static ?string $title = 'Olay / Ramak Kala';

    protected static function kayitModeli(): string
    {
        return OlayKaydi::class;
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
