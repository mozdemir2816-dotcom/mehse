<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\SahaDenetimi as Sayfa;
use App\Models\SahaDenetimi;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Saha Denetimleri" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class SahaDenetimleriSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'sahaDenetimleri';

    protected static ?string $title = 'Saha Denetimleri';

    protected static function kayitModeli(): string
    {
        return SahaDenetimi::class;
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
