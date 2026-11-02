<?php

namespace App\Filament\Resources\RiskDegerlendirmesis\RelationManagers;

use App\Console\Commands\RiskOneriTamamla;
use App\Console\Commands\RiskPuanTamamla;
use App\Models\RiskMaddesi;
use App\Models\Tehlike;
use App\Models\TehlikeKategorisi;
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

    public function form(Schema $schema): Schema
    {
        $yontem = $this->yontem();
        $ucEksenli = $this->ucEksenli();
        // HAZOP puansızdır: puan alanları (olasılık / şiddet / önlem sonrası) gösterilmez.
        $hazop = $this->yontem() === 'hazop';
        // Hastane formu: puanlar dosyadaki ölçekte (Ş 40 = ölüm, ara değerler) olduğu gibi tutulur → sayısal giriş.
        $hastane = $this->getOwnerRecord()->hastaneMi();
        $puan = fn (string $alan, string $etiket, bool $zorunlu = false) => TextInput::make($alan)->label($etiket)->numeric()->step('any')->minValue(0)->required($zorunlu);
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
                    Textarea::make('etki_alani')->label('Etki alanı')->rows(2)->columnSpanFull()->visible($hastane),
                    Textarea::make('mevcut_onlem')->label('Mevcut önlemler')->rows(2)->columnSpanFull(),
                ]),

                Group::make([
                    Section::make("Mevcut Risk ({$yontemBasligi})")
                        ->columns(3)
                        ->compact()
                        ->visible(! $hazop)
                        ->schema(array_values(array_filter([
                            $hastane ? $puan('olasilik', 'Olasılık (O)', true) : Select::make('olasilik')->label($etiket['olasilik'])
                                ->options(RiskSkorlama::olcek($yontem, 'olasilik'))
                                ->native(false)->required(),
                            $hastane ? $puan('frekans', 'Frekans (F)', true) : ($ucEksenli ? Select::make('frekans')->label($etiket['frekans'])
                                ->options(RiskSkorlama::olcek($yontem, 'frekans'))->native(false)->required() : null),
                            $hastane ? $puan('siddet', 'Şiddet (Ş)', true) : Select::make('siddet')->label($etiket['siddet'])
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
                            Textarea::make('aciklama')->label('Açıklama')->rows(2)->columnSpanFull()->visible($hastane),
                            TextInput::make('sorumlu')->label('Sorumlu'),
                            TextInput::make('termin')->label('Termin')->placeholder('gg.aa.yyyy veya "Sürekli"'),
                            Select::make('durum')->label('Durum')->options(config('isg.risk_madde_durumlari'))->default('acik'),
                            $hastane ? $puan('son_olasilik', 'Son risk: Olasılık (O)') : Select::make('son_olasilik')->label('Önlem sonrası '.$etiket['olasilik'])
                                ->options(RiskSkorlama::olcek($yontem, 'olasilik'))->native(false)
                                ->default(1)
                                ->visible(! $hazop)
                                ->helperText('Boş bırakılırsa önlem sonrası varsayılan olarak 1 kabul edilir.'),
                            $hastane ? $puan('son_frekans', 'Son risk: Frekans (F)') : ($ucEksenli ? Select::make('son_frekans')->label('Önlem sonrası '.$etiket['frekans'])
                                ->options(RiskSkorlama::olcek($yontem, 'frekans'))->native(false) : null),
                            $hastane ? $puan('son_siddet', 'Son risk: Şiddet (Ş)') : Select::make('son_siddet')->label('Önlem sonrası '.$etiket['siddet'])
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
                        // 01.11.2026: ekipman/klasör seçince tüm maddeleri gelir (yeni makine geldiğinde tek tek seçmeye gerek yok).
                        Select::make('kategori_idler')->label('Ekipman / klasörün tüm maddeleri')
                            ->options(fn () => TehlikeKategorisi::query()->uygun($this->yontem(), $this->firmaNaceGrubu())
                                ->orWhere(fn ($q) => $q->whereNull('yontem')->where(fn ($w) => $w->where('anahtar', 'like', 'ekipman_%')->orWhere('anahtar', 'like', 'atolye_%')))
                                ->withCount('tehlikeler')->orderBy('ad')->get()
                                ->filter(fn (TehlikeKategorisi $k) => $k->tehlikeler_count > 0)
                                ->mapWithKeys(fn (TehlikeKategorisi $k) => [$k->id => $k->ad.' ('.$k->tehlikeler_count.' madde)']))
                            ->multiple()->searchable()
                            ->helperText('Analizde zaten bulunan kütüphane maddeleri tekrar eklenmez.'),
                        Select::make('tehlike_idler')->label('Tek tek tehlikeler')
                            ->options(fn () => Tehlike::query()->with('kategori')
                                ->whereHas('kategori', fn ($q) => $q->uygun($this->yontem(), $this->firmaNaceGrubu()))
                                ->get()
                                ->mapWithKeys(fn (Tehlike $t) => [$t->id => "[{$t->kategori->ad}] ".Str::limit($t->tehlike, 70)]))
                            ->multiple()
                            ->searchable()
                            ->requiredWithout('kategori_idler'),
                    ])
                    ->action(function (array $data): void {
                        $idler = collect($data['tehlike_idler'] ?? [])
                            ->merge(Tehlike::whereIn('tehlike_kategorisi_id', $data['kategori_idler'] ?? [])->orderBy('tehlike_kategorisi_id')->orderBy('kod')->pluck('id'))
                            ->map(fn ($id) => (int) $id)->unique()
                            // Analizde zaten olan kütüphane maddesi mükerrer eklenmez.
                            ->diff($this->getOwnerRecord()->maddeler()->whereNotNull('tehlike_id')->pluck('tehlike_id')->map(fn ($id) => (int) $id))
                            ->values();
                        $tehlikeler = Tehlike::whereIn('id', $idler)->get()->keyBy('id');
                        $sira = (int) ($this->getOwnerRecord()->maddeler()->max('sira') ?? 0);
                        $satirlar = [];

                        foreach ($idler as $id) {
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
                            Notification::make()->title('Eklenecek yeni tehlike yok')->body('Seçilenler analizde zaten var.')->warning()->send();

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
                    ->label('Eksik Alanları AI ile Tamamla')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->badge(fn () => $this->eksikPuanSayisi() ?: null)
                    ->visible(fn () => GeminiRiskPuanTamamlayici::aktifMi() && in_array($this->getOwnerRecord()->yontem, ['fine_kinney', 'matris_5x5'], true))
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Eksik alanı olan '.$this->eksikPuanSayisi().' madde yapay zekâ ile tamamlanır: risk tanımı, mevcut önlem, öneri (düzeltici/önleyici faaliyet), mevzuat dayanağı, rol bazlı sorumlu, Olasılık / '.($this->ucEksenli() ? 'Frekans / ' : '').'Şiddet ve önlem sonrası puanlar. Termin risk düzeyine göre (Derhal / 1 Ay / 3 Ay / Sürekli-Rutin) atanır. Yalnız BOŞ alanlar dolar, yazdıklarınıza dokunulmaz. Dolan alanlar Risk Kütüphanesindeki karşılığına da kaydedilir — bir sonraki analizde dolu gelir. İlk '.RiskPuanTamamla::PARTI.' madde hemen, kalanı arka planda dakikada '.RiskPuanTamamla::PARTI.' madde işlenir.')
                    ->action(function (): void {
                        @set_time_limit(app()->runningInConsole() ? 0 : 300);
                        GeminiRiskPuanTamamlayici::devreyiSifirla();
                        $rd = $this->getOwnerRecord();
                        $toplam = $this->eksikPuanSayisi();
                        RiskPuanTamamla::denemeleriSifirla($rd->id);

                        if (! $toplam) {
                            Notification::make()->title('Eksik alanı olan madde yok')->success()->send();

                            return;
                        }

                        // 25 madde tek istekte — madde başına istek ücretsiz kotayı birkaç maddede bitiriyordu.
                        $tamamlanan = RiskPuanTamamla::isle($rd);
                        $kalan = $this->eksikPuanSayisi();

                        if ($kalan > 0) {
                            RiskPuanTamamla::oncelikliyeAl($rd->id);
                        }
                        $rd->pdfOnbellegiTemizle();
                        $rd->saveQuietly();

                        $bildirim = Notification::make()->title($tamamlanan
                            ? "✓ {$tamamlanan} adet maddenin eksik alanları (öneri, mevzuat, ".($this->ucEksenli() ? 'Fine-Kinney ' : '').'puan, sorumlu, termin) yapay zekâ ile mevzuata uygun olarak tamamlandı ve kütüphaneye kaydedildi'
                            : 'Yapay zekâ şu an yanıt vermedi');

                        match (true) {
                            $tamamlanan && $kalan => $bildirim->body("Kalan {$kalan} madde arka planda tamamlanıyor (dakikada ".RiskPuanTamamla::PARTI." madde); tablo kendiliğinden yenilenir.")->success(),
                            $tamamlanan > 0 => $bildirim->success(),
                            default => $bildirim->body('Kota / yoğunluk olabilir. '.$kalan.' madde kuyruğa alındı; arka planda denenecek.')->warning(),
                        };
                        $bildirim->send();
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
            ->poll(fn () => $this->bosOneriSayisi() || RiskPuanTamamla::kuyruktaMi($this->getOwnerRecord()->id) ? '15s' : null)
            ->reorderable('sira');
    }

    /** Eksik alanı olan madde sayısı (RiskAlanTamamlama) — "Eksik Alanlar" rozeti ve sayaç. */
    private function eksikPuanSayisi(): int
    {
        return in_array($this->getOwnerRecord()->yontem, ['fine_kinney', 'matris_5x5'], true)
            ? RiskPuanTamamla::eksikSorgu($this->getOwnerRecord())->count()
            : 0;
    }

    /** Bu değerlendirmede önerisi (düzeltici/önleyici faaliyet) hâlâ boş madde sayısı. */
    private function bosOneriSayisi(): int
    {
        return RiskOneriTamamla::bosOneriSorgusu()->where('risk_degerlendirmesi_id', $this->getOwnerRecord()->id)->count();
    }

    private static function rozetRengi(string $hex): string
    {
        return match (strtolower($hex)) {
            '#7f1d1d', '#dc2626', '#ff0000' => 'danger',
            '#f59e0b', '#ffc000', '#ffff00' => 'warning',
            '#84cc16', '#22c55e', '#92d050' => 'success',
            '#00b0f0' => 'info',
            default => 'gray',
        };
    }
}
