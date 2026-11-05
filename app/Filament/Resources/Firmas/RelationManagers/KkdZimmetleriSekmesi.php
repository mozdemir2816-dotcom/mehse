<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\KkdTakip as Sayfa;
use App\Models\KkdZimmetFormu;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "KKD Zimmet Formları" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class KkdZimmetleriSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'kkdZimmetFormlari';

    protected static ?string $title = 'KKD Zimmet Formları';

    protected static function kayitModeli(): string
    {
        return KkdZimmetFormu::class;
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
