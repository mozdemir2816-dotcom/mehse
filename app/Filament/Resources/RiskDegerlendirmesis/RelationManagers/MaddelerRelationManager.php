<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\RelationManagers;

use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Support\GeminiRiskPuanTamamlayici;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MaddelerRelationManager extends RelationManager
{
    protected static string $relationship = 'maddeler';

    protected static ?string $title = 'Risk Maddeleri';

    /** "Eksik Puan/Önlemleri AI ile Tamamla" tek çalıştırmada en fazla bu kadar madde işler. */
    private const AI_TOPLU_LIMIT = 40;

    protected function fineKinney(): bool
    {
        return $this->getOwnerRecord()->yontem === 'fine_kinney';
    }

    /** AI'dan puan alıp maddeye yazar; öneri gelmezse (AI kapalı/hatalı) dokunmadan false döner. */
    private function puanlaAiIle(RiskMaddesi $madde): bool
    {
        $oneri = GeminiRiskPuanTamamlayici::oner(
            $madde->tehlike,
            $madde->risk,
            $madde->bolum,
            $madde->faaliyet,
            $this->getOwnerRecord()->yontem,
        );

        if (! $oneri) {
            return false;
        }

        // Yalnızca gerçekten boş olan eksen(ler) yazılır — örn. Fine-Kinney'de
        // yalnız Frekans eksikse, kullanıcının elle girdiği Olasılık/Şiddet
        // AI önerisiyle EZİLMEZ.
        $doldurulacak = collect($oneri)
            ->only(collect(['olasilik', 'siddet', 'frekans'])->filter(fn ($eksen) => blank($madde->{$eksen}))->all())
            ->all();

        if (! $doldurulacak) {
            return false;
        }

        $madde->forceFill($doldurulacak)->save();

        return true;
    }

    /** Mevcut Önlem metni boşsa AI'dan kısa bir öneri alıp yazar; doluysa dokunmaz. */
    private function onlemVerAiIle(RiskMaddesi $madde): bool
    {
        if (filled($madde->mevcut_onlem)) {
            return false;
        }

        $onlem = GeminiRiskPuanTamamlayici::onlemOner($madde->tehlike, $madde->risk, $madde->bolum, $madde->faaliyet);

        if (! $onlem) {
            return false;
        }

        $madde->forceFill(['mevcut_onlem' => $onlem])->save();

        return true;
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
                                ->mapWithKeys(fn (Tehlike $t) => [$t->id => "[{$t->kategori->ad}] ".Str::limit($t->tehlike, 70)]))
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data): void {
                        $t = Tehlike::findOrFail($data['tehlike_id']);
                        $madde = $this->getOwnerRecord()->maddeler()->create([
                            'sira' => (int) ($this->getOwnerRecord()->maddeler()->max('sira') ?? 0) + 1,
                            'bolum' => $t->bolum,
                            'faaliyet' => $t->faaliyet,
                            'tehlike' => $t->tehlike,
                            'risk' => $t->risk,
                            'mevcut_onlem' => $t->mevcut_onlem,
                            'durum' => 'acik',
                        ]);

                        $puanlandi = $this->puanlaAiIle($madde);
                        $onlemVerildi = $this->onlemVerAiIle($madde);

                        $baslik = match (true) {
                            $puanlandi && $onlemVerildi => 'Risk maddesi eklendi, AI ile puanlandı ve önlem önerisi eklendi',
                            $puanlandi => 'Risk maddesi eklendi ve AI ile puanlandı',
                            $onlemVerildi => 'Risk maddesi eklendi ve AI önlem önerisi eklendi (puanı girin)',
                            default => 'Risk maddesi eklendi (puanı girin)',
                        };

                        Notification::make()->title($baslik)->success()->send();
                    }),
                Action::make('eksikPuanlariTamamla')
                    ->label('Eksik Puan/Önlemleri AI ile Tamamla')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->visible(fn () => GeminiRiskPuanTamamlayici::aktifMi())
                    ->requiresConfirmation()
                    ->modalDescription('Olasılık/Şiddet puanı veya Mevcut Önlem metni boş olan maddeler için Gemini\'den öneri istenir (her çalıştırmada en fazla '.self::AI_TOPLU_LIMIT.' madde; kalanlar için tekrar çalıştırın). Puan önerisi yalnız ölçekteki değerlerden seçilir, yine de gözden geçirin.')
                    ->action(function (): void {
                        @set_time_limit(300);
                        GeminiRiskPuanTamamlayici::devreyiSifirla();
                        $fk = $this->fineKinney();

                        $eksikSorgu = $this->getOwnerRecord()->maddeler()
                            ->where(function ($q) use ($fk) {
                                $q->whereNull('olasilik')->orWhereNull('siddet')
                                    ->orWhereNull('mevcut_onlem')->orWhere('mevcut_onlem', '');

                                if ($fk) {
                                    $q->orWhereNull('frekans');
                                }
                            });

                        $toplamEksik = (clone $eksikSorgu)->count();
                        $eksikler = $eksikSorgu->orderBy('sira')->limit(self::AI_TOPLU_LIMIT)->get();

                        if ($eksikler->isEmpty()) {
                            Notification::make()->title('Eksik puan/önlem içeren madde yok')->success()->send();

                            return;
                        }

                        $tamamlanan = 0;

                        foreach ($eksikler as $m) {
                            if (GeminiRiskPuanTamamlayici::devreKesikMi()) {
                                break;
                            }

                            $puanEksik = blank($m->olasilik) || blank($m->siddet) || ($fk && blank($m->frekans));
                            $puanlandi = $puanEksik && $this->puanlaAiIle($m);
                            $onlemVerildi = $this->onlemVerAiIle($m);

                            if ($puanlandi || $onlemVerildi) {
                                $tamamlanan++;
                            }
                        }

                        $kalan = max(0, $toplamEksik - $tamamlanan);

                        $body = match (true) {
                            GeminiRiskPuanTamamlayici::devreKesikMi() => 'Gemini yanıt vermiyor (kota / hız sınırı olabilir); birkaç dakika sonra tekrar deneyin.'.($kalan ? " Kalan: {$kalan} madde." : ''),
                            $kalan > 0 => "{$kalan} madde hâlâ eksik — aksiyonu tekrar çalıştırın.",
                            default => null,
                        };

                        Notification::make()
                            ->title("{$tamamlanan} madde AI ile tamamlandı")
                            ->body($body)
                            ->success()
                            ->send();
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
