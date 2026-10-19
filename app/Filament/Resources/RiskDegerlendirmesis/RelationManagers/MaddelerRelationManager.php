<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\RelationManagers;

use App\Console\Commands\RiskOneriTamamla;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Support\GeminiRiskPuanTamamlayici;
use App\Support\NaceGrubu;
use App\Support\RiskKutuphanesi;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
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

    protected function yontem(): string
    {
        return $this->getOwnerRecord()->yontem;
    }

    /** Değerlendirmenin firma NACE'sinden sabit grup (config/risk_nace.php); yoksa null. */
    protected function firmaNaceGrubu(): ?string
    {
        $rd = $this->getOwnerRecord();

        return NaceGrubu::bul($rd->firma_nace ?: $rd->firma?->nace_kodu);
    }

    protected function ucEksenli(): bool
    {
        return RiskSkorlama::ucEksenliMi($this->yontem());
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

    /** Mevcut Önlem ve Önerilen önlem ikisi de boşsa AI'dan kısa bir öneri alıp yazar; doluysa dokunmaz. */
    private function onlemVerAiIle(RiskMaddesi $madde): bool
    {
        if (filled($madde->mevcut_onlem) || filled($madde->oneri)) {
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
        $yontem = $this->yontem();
        $ucEksenli = $this->ucEksenli();
        // HAZOP puansızdır: puan alanları (olasılık / şiddet / önlem sonrası) gösterilmez.
        $hazop = $this->yontem() === 'hazop';
        $etiket = RiskSkorlama::eksenEtiketleri($yontem);
        $yontemBasligi = config('isg.risk_yontemleri.'.$yontem, $yontem);

        return $schema->components([
            // HAZOP senaryosunun Excel alanları (kaynak düzenle aynı): düzenlemede görünsün.
            Section::make('HAZOP senaryosu')->columns(2)->visible($hazop)->schema([
                TextInput::make('hazop.dugum')->label('Düğüm'),
                TextInput::make('hazop.parametre')->label('Parametre'),
                TextInput::make('hazop.kilavuz')->label('Kılavuz kelime'),
                TextInput::make('hazop.sapma')->label('Sapma'),
                Textarea::make('hazop.neden')->label('Olası nedenler')->rows(2),
                Textarea::make('hazop.sonuc')->label('Olası sonuçlar')->rows(2),
                Textarea::make('hazop.korumalar')->label('Doğrulanacak korumalar')->rows(2),
                Textarea::make('hazop.oneri')->label('Öneri / aksiyon')->rows(2),
                TextInput::make('hazop.oncelik')->label('Öncelik'),
                TextInput::make('hazop.kapanis')->label('Kapanış kanıtı'),
                TextInput::make('hazop.durum')->label('Durum'),
            ]),

            // Modal sayfaya sığsın diye iki sütun: solda tanım, sağda puan + önlem.
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Tanım')->columns(2)->compact()->schema([
                    TextInput::make('sira')->label('Sıra')->numeric()
                        ->default(fn () => (int) ($this->getOwnerRecord()->maddeler()->max('sira') ?? 0) + 1),
                    // isgsuite "Bölümler": bu değerlendirmede kullanılan + önerilen bölüm adları
                    TextInput::make('bolum')->label('Bölüm / Ünite')
                        ->datalist(fn () => $this->getOwnerRecord()->maddeler()->whereNotNull('bolum')->distinct()->pluck('bolum')
                            ->merge(config('risk_nace.bolum_onerileri', []))->unique()->values()->all()),
                    TextInput::make('faaliyet')->label('Faaliyet')->columnSpanFull(),
                    Textarea::make('tehlike')->label('Tehlike')->rows(2)->required()->columnSpanFull(),
                    Textarea::make('risk')->label('Risk / tehlikeli durum')->rows(2)->columnSpanFull(),
                    Textarea::make('mevcut_onlem')->label('Mevcut önlemler')->rows(2)->columnSpanFull(),
                ]),

                Group::make([
                    Section::make("Mevcut Risk ({$yontemBasligi})")
                        ->columns(3)
                        ->compact()
                        ->visible(! $hazop)
                        ->schema(array_values(array_filter([
                            Select::make('olasilik')->label($etiket['olasilik'])
                                ->options(RiskSkorlama::olcek($yontem, 'olasilik'))
                                ->native(false)->required(),
                            $ucEksenli ? Select::make('frekans')->label($etiket['frekans'])
                                ->options(RiskSkorlama::olcek($yontem, 'frekans'))->native(false)->required() : null,
                            Select::make('siddet')->label($etiket['siddet'])
                                ->options(RiskSkorlama::olcek($yontem, 'siddet'))
                                ->native(false)->required(),
                        ]))),

                    Section::make('Maruziyet')->columns(2)->compact()->schema([
                        Toggle::make('etkilenen_calisan')->label('Çalışanlar etkilenir')->default(true),
                        Toggle::make('etkilenen_diger')->label('Taşeron / ziyaretçi etkilenir'),
                        // Tehlike ve Risk Analitiği: tür boşsa metinden tahmin edilir; dominans = puan × maruz kişi
                        Select::make('tehlike_turu')->label('Tehlike türü')->placeholder('Otomatik (metinden tahmin)')
                            ->options(collect(config('risk_nace.tehlike_turleri'))->map(fn ($t) => $t['ad'])->all()),
                        TextInput::make('maruz_kisi')->label('Maruz kalan kişi sayısı')->numeric()->minValue(0)->maxValue(100000),
                    ]),

                    Section::make('Planlanan Önlem & Rezidüel Risk')
                        ->columns(2)
                        ->compact()
                        ->schema(array_values(array_filter([
                            Textarea::make('oneri')->label('Önerilen önlem')->rows(2)->columnSpanFull(),
                            TextInput::make('sorumlu')->label('Sorumlu'),
                            TextInput::make('termin')->label('Termin')->placeholder('gg.aa.yyyy veya "Sürekli"'),
                            Select::make('durum')->label('Durum')->options(config('isg.risk_madde_durumlari'))->default('acik'),
                            Select::make('son_olasilik')->label('Önlem sonrası '.$etiket['olasilik'])
                                ->options(RiskSkorlama::olcek($yontem, 'olasilik'))->native(false)
                                ->default(1)
                                ->visible(! $hazop)
                                ->helperText('Boş bırakılırsa önlem sonrası varsayılan olarak 1 kabul edilir.'),
                            $ucEksenli ? Select::make('son_frekans')->label('Önlem sonrası '.$etiket['frekans'])
                                ->options(RiskSkorlama::olcek($yontem, 'frekans'))->native(false) : null,
                            Select::make('son_siddet')->label('Önlem sonrası '.$etiket['siddet'])
                                ->options(RiskSkorlama::olcek($yontem, 'siddet'))->native(false)
                                ->visible(! $hazop)
                                ->helperText('Boş bırakılırsa öneri öncesindeki '.$etiket['siddet'].' değeriyle aynı kabul edilir.'),
                        ]))),
                ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sira')->label('#')->sortable()->width(48),
                TextColumn::make('bolum')->label('Bölüm')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('tehlike')->label('Tehlike')->searchable()->wrap()->limit(80),
                TextColumn::make('hazop.kilavuz')->label('Kılavuz')->placeholder('—')
                    ->visible(fn (): bool => $this->yontem() === 'hazop'),
                TextColumn::make('hazop.sonuc')->label('Olası sonuçlar')->wrap()->limit(60)->placeholder('—')
                    ->visible(fn (): bool => $this->yontem() === 'hazop'),
                TextColumn::make('puan')->label('Puan')->badge()->alignCenter()
                    ->color(fn (RiskMaddesi $r) => static::rozetRengi($r->rengi()))
                    ->formatStateUsing(fn ($state, RiskMaddesi $r) => $state ? "{$state} · {$r->duzey}" : '—')
                    ->visible(fn (): bool => $this->yontem() !== 'hazop'),
                TextColumn::make('son_puan')->label('Rezidüel')->alignCenter()->placeholder('—')
                    ->formatStateUsing(fn ($state, RiskMaddesi $r) => $state ? "{$state} · {$r->son_duzey}" : '—')
                    ->visible(fn (): bool => $this->yontem() !== 'hazop'),
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
                CreateAction::make()->label('Risk Maddesi Ekle')->modalWidth(Width::SixExtraLarge)
                    ->stickyModalHeader()->stickyModalFooter(),
                Action::make('kutuphanedenAktar')
                    ->label('Kütüphaneden Toplu Ekle')->icon('heroicon-o-book-open')->color('gray')
                    ->modalDescription('Birden fazla tehlike seçip tek seferde ekleyebilirsiniz (ör. yeni makine/ekipman geldiğinde ilgili tüm maddeler). Puan/önlem AI önerisi burada çalışmaz — eklendikten sonra "Eksik Puan/Önlemleri AI ile Tamamla" ile toplu tamamlayabilirsiniz.')
                    ->schema([
                        Select::make('tehlike_idler')->label('Tehlikeler')
                            ->options(fn () => Tehlike::query()->with('kategori')
                                ->whereHas('kategori', fn ($q) => $q->uygun($this->yontem(), $this->firmaNaceGrubu()))
                                ->get()
                                ->mapWithKeys(fn (Tehlike $t) => [$t->id => "[{$t->kategori->ad}] ".Str::limit($t->tehlike, 70)]))
                            ->multiple()
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data): void {
                        $tehlikeler = Tehlike::whereIn('id', $data['tehlike_idler'])->get()->keyBy('id');
                        $sira = (int) ($this->getOwnerRecord()->maddeler()->max('sira') ?? 0);
                        $satirlar = [];

                        foreach ($data['tehlike_idler'] as $id) {
                            $t = $tehlikeler->get($id);

                            if (! $t) {
                                continue;
                            }

                            // Kütüphane maddesinin tüm alanları (HAZOP, O/F/Ş, son puanlar, tehlike_id) taşınır.
                            $sira++;
                            $satirlar[] = array_merge(
                                collect(RiskKutuphanesi::maddeyeCevir($t, $this->getOwnerRecord()->yontem))->except('anahtar', 'kaynak')->all(),
                                [
                                    'risk_degerlendirmesi_id' => $this->getOwnerRecord()->id,
                                    'sira' => $sira,
                                    'durum' => 'acik',
                                ],
                            );
                        }

                        if ($satirlar === []) {
                            Notification::make()->title('Eklenecek tehlike bulunamadı')->danger()->send();

                            return;
                        }

                        foreach ($satirlar as $satir) {
                            RiskMaddesi::create($satir);
                        }
                        $this->getOwnerRecord()->pdfOnbellegiTemizle();
                        $this->getOwnerRecord()->saveQuietly();

                        Notification::make()
                            ->title(count($satirlar).' risk maddesi eklendi')
                            ->body('Puanları girmek veya AI ile tamamlamak için "Eksik Puan/Önlemleri AI ile Tamamla" butonunu kullanabilirsiniz.')
                            ->success()->send();
                    }),
                Action::make('bosOnerileriDoldur')
                    ->label('Boş Önerileri AI ile Doldur')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    // Arka planda çalıştığı için kalan sayı rozetle izlenir (tablo da kendiliğinden yenilenir).
                    ->badge(fn () => $this->bosOneriSayisi() ?: null)
                    ->tooltip(fn () => $this->bosOneriSayisi() ? $this->bosOneriSayisi().' maddenin önerisi hâlâ boş' : 'Tüm önerileri dolu')
                    ->visible(fn () => GeminiRiskPuanTamamlayici::aktifMi())
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Bu rapordaki önerisi (düzeltici/önleyici faaliyet) boş '.RiskOneriTamamla::bosOneriSorgusu()->where('risk_degerlendirmesi_id', $this->getOwnerRecord()->id)->count().' madde sıraya alınır; arka planda dakikada 25 madde yapay zekâ ile doldurulur. Dolu önerilere dokunulmaz. Sayfayı birkaç dakika sonra yenileyin.')
                    ->action(function (): void {
                        $bos = RiskOneriTamamla::bosOneriSorgusu()->where('risk_degerlendirmesi_id', $this->getOwnerRecord()->id)->count();

                        if (! $bos) {
                            Notification::make()->title('Önerisi boş madde yok')->success()->send();

                            return;
                        }

                        RiskOneriTamamla::oncelikliyeAl($this->getOwnerRecord()->id);

                        Notification::make()
                            ->title($bos.' madde sıraya alındı')
                            ->body('Yaklaşık '.max(1, (int) ceil($bos / 25)).'-'.max(2, (int) ceil($bos / 25) * 2).' dakika içinde dolar; sayfayı yenileyerek takip edin.')
                            ->success()->send();
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
                        $fk = $this->ucEksenli();

                        $eksikSorgu = $this->getOwnerRecord()->maddeler()
                            ->where(function ($q) use ($fk) {
                                // Önlem eksik = mevcut önlem VE önerilen önlem ikisi de boş (sihirbazla aynı kural).
                                $q->whereNull('olasilik')->orWhereNull('siddet')
                                    ->orWhere(fn ($o) => $o->where(fn ($a) => $a->whereNull('mevcut_onlem')->orWhere('mevcut_onlem', ''))
                                        ->where(fn ($b) => $b->whereNull('oneri')->orWhere('oneri', '')));

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
                EditAction::make()->modalWidth(Width::SixExtraLarge)
                    ->stickyModalHeader()->stickyModalFooter(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('sira')
            ->poll(fn () => $this->bosOneriSayisi() ? '15s' : null)
            ->reorderable('sira');
    }

    /** Bu değerlendirmede önerisi (düzeltici/önleyici faaliyet) hâlâ boş madde sayısı. */
    private function bosOneriSayisi(): int
    {
        return RiskOneriTamamla::bosOneriSorgusu()->where('risk_degerlendirmesi_id', $this->getOwnerRecord()->id)->count();
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
