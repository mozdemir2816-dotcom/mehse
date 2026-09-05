<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\RelationManagers;

use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Support\RiskSkorlama;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaddelerRelationManager extends RelationManager
{
    protected static string $relationship = 'maddeler';

    protected static ?string $title = 'Risk Maddeleri';

    protected function fineKinney(): bool
    {
        return $this->getOwnerRecord()->yontem === 'fine_kinney';
    }

    public function form(Schema $schema): Schema
    {
        $fk = $this->fineKinney();

        return $schema->components([
            Section::make('Tanım')->columns(2)->schema([
                TextInput::make('sira')->label('Sıra')->numeric()
                    ->default(fn () => (int) ($this->getOwnerRecord()->maddeler()->max('sira') ?? 0) + 1),
                TextInput::make('bolum')->label('Bölüm / Ünite'),
                TextInput::make('faaliyet')->label('Faaliyet')->columnSpanFull(),
                Textarea::make('tehlike')->label('Tehlike')->rows(2)->required()->columnSpanFull(),
                Textarea::make('risk')->label('Risk / tehlikeli durum')->rows(2)->columnSpanFull(),
                Textarea::make('mevcut_onlem')->label('Mevcut önlemler')->rows(2)->columnSpanFull(),
                Toggle::make('etkilenen_calisan')->label('Çalışanlar etkilenir')->default(true),
                Toggle::make('etkilenen_diger')->label('Taşeron / ziyaretçi etkilenir'),
            ]),

            Section::make($fk ? 'Mevcut Risk (Fine-Kinney)' : 'Mevcut Risk (5×5)')
                ->columns(3)
                ->schema(array_values(array_filter([
                    Select::make('olasilik')->label('Olasılık')
                        ->options(RiskSkorlama::olcek($fk ? 'fine_kinney' : 'matris_5x5', 'olasilik'))
                        ->native(false)->required(),
                    $fk ? Select::make('frekans')->label('Frekans (maruz kalma)')
                        ->options(RiskSkorlama::olcek('fine_kinney', 'frekans'))->native(false)->required() : null,
                    Select::make('siddet')->label('Şiddet')
                        ->options(RiskSkorlama::olcek($fk ? 'fine_kinney' : 'matris_5x5', 'siddet'))
                        ->native(false)->required(),
                ]))),

            Section::make('Planlanan Önlem & Rezidüel Risk')
                ->columns(3)
                ->collapsed()
                ->schema(array_values(array_filter([
                    Textarea::make('oneri')->label('Önerilen önlem')->rows(2)->columnSpan(3),
                    TextInput::make('sorumlu')->label('Sorumlu'),
                    TextInput::make('termin')->label('Termin')->placeholder('gg.aa.yyyy veya "Sürekli"'),
                    Select::make('durum')->label('Durum')->options(config('isg.risk_madde_durumlari'))->default('acik'),
                    Select::make('son_olasilik')->label('Önlem sonrası Olasılık')
                        ->options(RiskSkorlama::olcek($fk ? 'fine_kinney' : 'matris_5x5', 'olasilik'))->native(false)
                        ->default(1)
                        ->helperText('Boş bırakılırsa önlem sonrası varsayılan olarak 1 kabul edilir.'),
                    $fk ? Select::make('son_frekans')->label('Önlem sonrası Frekans')
                        ->options(RiskSkorlama::olcek('fine_kinney', 'frekans'))->native(false) : null,
                    Select::make('son_siddet')->label('Önlem sonrası Şiddet')
                        ->options(RiskSkorlama::olcek($fk ? 'fine_kinney' : 'matris_5x5', 'siddet'))->native(false)
                        ->helperText('Boş bırakılırsa öneri öncesindeki Şiddet değeriyle aynı kabul edilir.'),
                ]))),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sira')->label('#')->sortable()->width(48),
                TextColumn::make('bolum')->label('Bölüm')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('tehlike')->label('Tehlike')->searchable()->wrap()->limit(80),
                TextColumn::make('puan')->label('Puan')->badge()->alignCenter()
                    ->color(fn (RiskMaddesi $r) => static::rozetRengi($r->rengi()))
                    ->formatStateUsing(fn ($state, RiskMaddesi $r) => $state ? "{$state} · {$r->duzey}" : '—'),
                TextColumn::make('son_puan')->label('Rezidüel')->alignCenter()->placeholder('—')
                    ->formatStateUsing(fn ($state, RiskMaddesi $r) => $state ? "{$state} · {$r->son_duzey}" : '—'),
                TextColumn::make('termin')->label('Termin')->placeholder('—')->toggleable(),
                TextColumn::make('durum')->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state) => config('isg.risk_madde_durumlari.'.$state, $state))
                    ->color(fn (string $state) => match ($state) {
                        'kapali' => 'success', 'devam' => 'warning', default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('durum')->label('Durum')->options(config('isg.risk_madde_durumlari')),
            ])
            ->headerActions([
                CreateAction::make()->label('Risk Maddesi Ekle'),
                Action::make('kutuphanedenAktar')
                    ->label('Kütüphaneden aktar')->icon('heroicon-o-book-open')->color('gray')
                    ->schema([
                        Select::make('tehlike_id')->label('Tehlike')
                            ->options(fn () => Tehlike::query()->with('kategori')->get()
                                ->mapWithKeys(fn (Tehlike $t) => [$t->id => "[{$t->kategori->ad}] ".\Illuminate\Support\Str::limit($t->tehlike, 70)]))
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data): void {
                        $t = Tehlike::findOrFail($data['tehlike_id']);
                        $this->getOwnerRecord()->maddeler()->create([
                            'sira' => (int) ($this->getOwnerRecord()->maddeler()->max('sira') ?? 0) + 1,
                            'bolum' => $t->bolum,
                            'faaliyet' => $t->faaliyet,
                            'tehlike' => $t->tehlike,
                            'risk' => $t->risk,
                            'mevcut_onlem' => $t->mevcut_onlem,
                            'durum' => 'acik',
                        ]);
                        Notification::make()->title('Risk maddesi eklendi (puanı girin)')->success()->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('sira')
            ->reorderable('sira');
    }

    private static function rozetRengi(string $hex): string
    {
        return match ($hex) {
            '#7f1d1d', '#dc2626' => 'danger',
            '#f59e0b' => 'warning',
            '#84cc16', '#22c55e' => 'success',
            default => 'gray',
        };
    }
}
