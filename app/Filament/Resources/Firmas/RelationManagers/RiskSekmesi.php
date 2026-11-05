<?php

namespace App\Filament\Resources\Firmas\RelationManagers;

use App\Filament\Resources\RiskDegerlendirmesis\RiskDegerlendirmesiResource;
use App\Models\RiskDegerlendirmesi;
use Illuminate\Database\Eloquent\Model;

/** Firma sayfasında "Risk Analizleri" sekmesi — firmaya ait tüm kayıtlar, PDF ve modül kısayolu. */
class RiskSekmesi extends KayitSekmesiRelationManager
{
    protected static string $relationship = 'riskDegerlendirmeleri';

    protected static ?string $title = 'Risk Analizleri';

    protected static function kayitModeli(): string
    {
        return RiskDegerlendirmesi::class;
    }

    protected function modulUrl(Model $kayit): ?string
    {
        return RiskDegerlendirmesiResource::getUrl('edit', ['record' => $kayit->getKey()]);
    }

    protected function yeniUrl(): ?string
    {
        return RiskDegerlendirmesiResource::getUrl('create');
    }
}
