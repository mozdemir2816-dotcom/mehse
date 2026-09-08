<?php

namespace App\Filament\Resources\Tehlikes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TehlikeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('tehlike_kategorisi_id')->label('Kategori')
                    ->relationship('kategori', 'ad')
                    ->createOptionForm([
                        TextInput::make('ad')->label('Kategori adı')->required(),
                        TextInput::make('anahtar')->label('Anahtar (slug)')->required(),
                    ])
                    ->searchable()->preload()->required(),
                TextInput::make('kod')->label('Kod'),
                TextInput::make('bolum')->label('Önerilen bölüm'),
                TextInput::make('faaliyet')->label('Faaliyet'),
                Textarea::make('tehlike')->label('Tehlike')->rows(2)->required()->columnSpanFull(),
                Textarea::make('risk')->label('Risk / tehlikeli durum')->rows(2)->columnSpanFull(),
                Textarea::make('mevcut_onlem')->label('Önerilen kontrol tedbiri')->rows(2)->columnSpanFull(),
                TextInput::make('mevzuat')->label('Mevzuat dayanağı')->columnSpanFull(),
            ]),
            Section::make('Önerilen Puanlar')
                ->description('Sektörel bir analizden (Fine-Kinney / 5x5) aktarıldıysa dolu gelir; Risk Sihirbazı manuel seçimde forma önceden yazılır. Boş bırakılabilir.')
                ->columns(3)
                ->collapsed(fn ($record) => $record === null || ($record->olasilik === null && $record->siddet === null))
                ->schema([
                    TextInput::make('olasilik')->label('Olasılık')->numeric()->step('0.1'),
                    TextInput::make('frekans')->label('Frekans (yalnız Fine-Kinney)')->numeric()->step('0.1'),
                    TextInput::make('siddet')->label('Şiddet')->numeric()->step('0.1'),
                ]),
        ]);
    }
}
