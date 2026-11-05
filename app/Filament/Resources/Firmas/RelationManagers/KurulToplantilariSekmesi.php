<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\KurulToplantisi as Sayfa;
use App\Models\KurulToplantisi;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Kurul Toplantıları" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class KurulToplantilariSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'kurulToplantilari';

    protected static ?string $title = 'Kurul Toplantıları';

    protected static function kayitModeli(): string
    {
        return KurulToplantisi::class;
    }

    protected function modulUrl(Model $kayit): ?string
    {
        return Sayfa::getUrl(['firma' => $kayit->firma_id, 'toplanti' => $kayit->getKey()]);
    }

    protected function yeniUrl(): ?string
    {
        return Sayfa::getUrl(['firma' => $this->getOwnerRecord()->getKey()]);
    }
}
