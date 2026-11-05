<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\DofOlustur as Sayfa;
use App\Models\DofRaporu;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "DÖF Raporları" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class DofRaporlariSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'dofRaporlari';

    protected static ?string $title = 'DÖF Raporları';

    protected static function kayitModeli(): string
    {
        return DofRaporu::class;
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
