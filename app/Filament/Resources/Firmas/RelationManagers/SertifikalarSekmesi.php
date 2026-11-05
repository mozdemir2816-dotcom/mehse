<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\SertifikaOlustur as Sayfa;
use App\Models\Sertifika;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Sertifikalar" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class SertifikalarSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'sertifikalar';

    protected static ?string $title = 'Sertifikalar';

    protected static function kayitModeli(): string
    {
        return Sertifika::class;
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
