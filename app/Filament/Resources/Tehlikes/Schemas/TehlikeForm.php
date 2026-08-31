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
        ]);
    }
}
