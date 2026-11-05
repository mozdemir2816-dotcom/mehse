<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Pages\EgitimKatilim as Sayfa;
use App\Models\EgitimKatilim;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Eğitim Kayıtları" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class EgitimKayitlariSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'egitimKatilimlari';

    protected static ?string $title = 'Eğitim Kayıtları';

    protected static function kayitModeli(): string
    {
        return EgitimKatilim::class;
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
