<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\IsbasiEgitim as Sayfa;
use App\Models\IsbasiEgitimTutanagi;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "İşbaşı Eğitimleri" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class IsbasiEgitimleriSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'isbasiEgitimTutanaklari';

    protected static ?string $title = 'İşbaşı Eğitimleri';

    protected static function kayitModeli(): string
    {
        return IsbasiEgitimTutanagi::class;
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
