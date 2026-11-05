<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\IsKazasiRaporu as Sayfa;
use App\Models\IsKazasiRaporu;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "İş Kazaları" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class IsKazalariSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'isKazasiRaporlari';

    protected static ?string $title = 'İş Kazaları';

    protected static function kayitModeli(): string
    {
        return IsKazasiRaporu::class;
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
