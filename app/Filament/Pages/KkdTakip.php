<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KkdStokKarti;
use App\Models\KkdZimmet;
use App\Support\KkdStok;
use App\Support\KkdTakipUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * KKD Takip (isgsuite "KKD Takip") — personel bazlı kalıcı KKD zimmet sicili
 * (yenileme / SKT takibi, iade, yenileme) + firma KKD stok kartları (giriş,
 * zimmet çıkışı, iade, fire). Teslim tutanağı mevcut KKD Zimmet Formu
 * şablonundan, personelin teslimdeki tüm KKD'leriyle üretilir.
 */
class KkdTakip extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.kkd-takip';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'KKD';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'kkd-takip';

    protected static ?string $title = 'KKD Takip';

    protected static ?string $navigationLabel = 'KKD Takip (Zimmet & Stok)';

    public ?int $firmaId = null;

    public ?string $arama = null;

    public ?string $durumFiltre = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hesaplanan veriler
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<int, KkdZimmet> */
    #[Computed]
    public function tumZimmetler(): Collection
    {
        return $this->firma
            ? $this->firma->kkdZimmetleri()->with('stokKarti')->latest('teslim_tarihi')->latest('id')->get()
            : collect();
    }

    /** @return Collection<int, KkdZimmet> */
    #[Computed]
    public function zimmetler(): Collection
    {
        $aranan = mb_strtolower(trim((string) $this->arama));

        return $this->tumZimmetler
            ->when($this->durumFiltre, fn (Collection $c) => $c->filter(
                fn (KkdZimmet $z) => in_array($this->durumFiltre, ['aktif', 'yaklasan', 'gecikmis'], true)
                    ? $z->aktifMi() && ($this->durumFiltre === 'aktif' || $z->takipDurumu() === $this->durumFiltre)
                    : $z->durum === $this->durumFiltre,
            ))
            ->when($aranan !== '', fn (Collection $c) => $c->filter(
                fn (KkdZimmet $z) => str_contains(
                    mb_strtolower(implode(' ', [$z->zimmet_no, $z->personel_ad_soyad, $z->tur, $z->marka, $z->model, $z->seri_no, $z->bolum])),
                    $aranan,
                ),
            ))
            ->values();
    }

    /** @return Collection<int, KkdStokKarti> */
    #[Computed]
    public function stokKartlari(): Collection
    {
        return $this->firma?->kkdStokKartlari()->orderBy('tur')->get() ?? collect();
    }

    /** @return array{aktif: int, yaklasan: int, gecikmis: int, dusuk_stok: int} */
    #[Computed]
    public function ozet(): array
    {
        $aktifler = $this->tumZimmetler->filter->aktifMi();

        return [
            'aktif' => $aktifler->count(),
            'yaklasan' => $aktifler->filter(fn (KkdZimmet $z) => $z->takipDurumu() === 'yaklasan')->count(),
            'gecikmis' => $aktifler->filter(fn (KkdZimmet $z) => $z->takipDurumu() === 'gecikmis')->count(),
            'dusuk_stok' => $this->stokKartlari->filter->dusukStokMu()->count(),
        ];
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->tumZimmetler, $this->zimmetler, $this->stokKartlari, $this->ozet);
    }

    public function updatedArama(): void
    {
        unset($this->zimmetler);
    }

    public function updatedDurumFiltre(): void
    {
        unset($this->zimmetler);
    }

    private function yenile(): void
    {
        unset($this->tumZimmetler, $this->zimmetler, $this->stokKartlari, $this->ozet);
    }

    private function zimmet(int $id): KkdZimmet
    {
        return $this->firma->kkdZimmetleri()->findOrFail($id);
    }

    /** Modal formu için model değerleri — tarihler Y-m-d. */
    private function formDegerleri(\Illuminate\Database\Eloquent\Model $m): array
    {
        return collect($m->getAttributes())
            ->map(fn ($v, string $k) => $m->getAttribute($k) instanceof \DateTimeInterface ? $m->getAttribute($k)->format('Y-m-d') : $m->getAttribute($k))
            ->all();
    }
    private function stokKarti(int $id): KkdStokKarti
    {
        return $this->firma->kkdStokKartlari()->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Form parçaları
    |--------------------------------------------------------------------------
    */

    /** @return array<string, string> */
    private function kategoriSecenekleri(): array
    {
        return collect(config('isg.kkd.kategoriler'))->map(fn (array $k) => $k['ad'])->all();
    }

    /** @return array<int, string> kategori boşsa tüm katalog */
    private function turOnerileri(?string $kategori): array
    {
        return collect(config('isg.kkd.kategoriler'))
            ->when($kategori, fn ($c) => $c->only([$kategori]))
            ->flatMap(fn (array $k) => collect($k['maddeler'])->pluck('ad'))
            ->values()
            ->all();
    }

    /** Kartın bilgilerini zimmet formuna kopyalar (boş bırakılan alanlar dolar). */
    private function kartiFormaAktar(?int $kartId, Set $set): void
    {
        $kart = $kartId ? $this->stokKartlari->firstWhere('id', $kartId) : null;

        if (! $kart) {
            return;
        }

        $set('kategori', $kart->kategori);
        $set('tur', $kart->tur);
        $set('marka', $kart->marka);
        $set('model', $kart->model);
        $set('beden', $kart->beden);
        $set('raf_omru', $kart->raf_omru);
        $set('son_kullanma', $kart->son_kullanma?->toDateString());
        $set('yenileme_tarihi', $kart->yenileme_tarihi?->toDateString());
    }

    /** @return array<int, mixed> */
    private function zimmetSemasi(bool $duzenleme): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('calisan_id')
                    ->label('Personel (kayıtlı çalışan)')
                    ->options(fn () => $this->firma?->calisanlar()->orderBy('ad_soyad')->pluck('ad_soyad', 'id')->all() ?? [])
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $c = $state ? Calisan::find($state) : null;
                        if ($c) {
                            $set('personel_ad_soyad', $c->ad_soyad);
                            $set('bolum', $c->sube ?: $c->gorev);
                        }
                    }),
                TextInput::make('personel_ad_soyad')->label('Personel Ad Soyad')->required()->maxLength(255),
                TextInput::make('bolum')->label('Bölüm / Görev')->maxLength(255),
                DatePicker::make('teslim_tarihi')->label('Teslim Tarihi')->required(),
                Select::make('kategori')
                    ->label('KKD Kategorisi')
                    ->options(fn () => $this->kategoriSecenekleri())
                    ->live(),
                TextInput::make('tur')
                    ->label('KKD Türü')
                    ->required()
                    ->maxLength(255)
                    ->datalist(fn (Get $get) => $this->turOnerileri($get('kategori')))
                    ->helperText('Listeden seçin ya da yazın'),
                Select::make('kkd_stok_karti_id')
                    ->label('Stoktan düş (isteğe bağlı)')
                    ->placeholder('Stok kartı seçmeden devam et')
                    ->options(fn (Get $get) => $this->stokKartlari
                        ->when($get('kategori'), fn ($c, $k) => $c->where('kategori', $k))
                        ->mapWithKeys(fn (KkdStokKarti $k) => [$k->id => $k->etiket().' (mevcut: '.$k->mevcut.')'])
                        ->all())
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $this->kartiFormaAktar($state ? (int) $state : null, $set))
                    ->disabled($duzenleme)
                    ->dehydrated(! $duzenleme),
                TextInput::make('adet')
                    ->label('Adet')
                    ->numeric()->integer()->minValue(1)
                    ->required()
                    ->disabled($duzenleme)
                    ->dehydrated(! $duzenleme),
                TextInput::make('marka')->label('Marka')->maxLength(255),
                TextInput::make('model')->label('Model')->maxLength(255),
                TextInput::make('beden')->label('Beden')->maxLength(255),
                TextInput::make('seri_no')->label('Seri No')->maxLength(255),
                TextInput::make('raf_omru')->label('Raf Ömrü')->placeholder('Örn. 5 yıl')->maxLength(255),
                TextInput::make('garanti')->label('Garanti')->placeholder('Örn. 2 yıl')->maxLength(255),
                DatePicker::make('son_kullanma')->label('Son Kullanma (SKT)'),
                DatePicker::make('yenileme_tarihi')->label('Yenileme / Kontrol Tarihi'),
                Select::make('durum')
                    ->label('Durum')
                    ->options(config('isg.kkd_takip.durumlar'))
                    ->required()
                    ->visible(! $duzenleme),
                TextInput::make('teslim_eden')->label('Teslim Eden')->maxLength(255),
            ]),
            Textarea::make('risk_alani')->label('Risk / Kullanım Alanı')->rows(2),
            Textarea::make('aciklama')->label('Açıklama')->rows(2),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Zimmet aksiyonları
    |--------------------------------------------------------------------------
    */

    public function yeniZimmetAction(): Action
    {
        return Action::make('yeniZimmet')
            ->label('Yeni Zimmet')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni KKD Zimmet Kaydı')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('Zimmeti Kaydet')
            ->fillForm(fn (): array => [
                'teslim_tarihi' => now()->toDateString(),
                'adet' => 1,
                'durum' => 'teslim_edildi',
                'teslim_eden' => Filament::auth()->user()?->name,
            ])
            ->schema(fn () => $this->zimmetSemasi(false))
            ->action(function (array $data): void {
                $z = $this->firma->kkdZimmetleri()->create($data);

                if ($z->aktifMi()) {
                    KkdStok::zimmetCikisi($z->load('stokKarti'));
                }

                $this->yenile();

                Notification::make()->title('Zimmet kaydedildi')->body($z->zimmet_no)->success()->send();
            });
    }

    public function zimmetDuzenleAction(): Action
    {
        return Action::make('zimmetDuzenle')
            ->label('Düzenle')
            ->modalHeading('KKD Zimmet Kaydını Düzenle')
            ->modalDescription('Adet ve stok kartı sonradan değiştirilemez; yanlışsa kaydı silip yeniden girin.')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (array $arguments): array => $this->formDegerleri($this->zimmet($arguments['id'])))
            ->schema(fn () => $this->zimmetSemasi(true))
            ->action(function (array $data, array $arguments): void {
                $this->zimmet($arguments['id'])->update($data);
                $this->yenile();

                Notification::make()->title('Zimmet güncellendi')->success()->send();
            });
    }

    /** Teslimdeki kaydı iade / kayıp / hasarlı olarak kapatır. */
    public function durumDegistirAction(): Action
    {
        return Action::make('durumDegistir')
            ->label('İade / Durum')
            ->modalHeading('Zimmet Durumunu Değiştir')
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments): array => [
                'durum' => 'iade_edildi',
                'iade_tarihi' => now()->toDateString(),
                'stoga_geri' => (bool) $this->zimmet($arguments['id'])->kkd_stok_karti_id,
            ])
            ->schema(fn (array $arguments) => [
                Select::make('durum')
                    ->label('Yeni Durum')
                    ->options(collect(config('isg.kkd_takip.durumlar'))->only(['iade_edildi', 'kayip', 'hasarli'])->all())
                    ->required()
                    ->live(),
                DatePicker::make('iade_tarihi')->label('Tarih')->required(),
                Toggle::make('stoga_geri')
                    ->label('Stoğa geri ekle')
                    ->visible(fn (Get $get) => $get('durum') === 'iade_edildi' && $this->zimmet($arguments['id'])->kkd_stok_karti_id),
            ])
            ->action(function (array $data, array $arguments): void {
                $z = $this->zimmet($arguments['id']);

                if (! $z->aktifMi()) {
                    return;
                }

                $z->update(['durum' => $data['durum'], 'iade_tarihi' => $data['iade_tarihi']]);

                if ($data['durum'] === 'iade_edildi' && ($data['stoga_geri'] ?? false)) {
                    KkdStok::iade($z->load('stokKarti'));
                }

                $this->yenile();

                Notification::make()->title($z->zimmet_no.' — '.$z->durumEtiketi())->success()->send();
            });
    }

    /** Eski KKD'yi "Yenilendi" yapıp aynı personele yeni zimmet açar. */
    public function zimmetYenileAction(): Action
    {
        return Action::make('zimmetYenile')
            ->label('Yenile')
            ->modalHeading('KKD Yenileme — yeni zimmet')
            ->modalDescription('Eski kayıt "Yenilendi" olarak kapanır, aynı personele aynı KKD için yeni zimmet açılır.')
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments): array {
                $z = $this->zimmet($arguments['id']);

                return [
                    'teslim_tarihi' => now()->toDateString(),
                    'kkd_stok_karti_id' => $z->kkd_stok_karti_id,
                    'yenileme_tarihi' => null,
                    'son_kullanma' => null,
                    'seri_no' => null,
                ];
            })
            ->schema([
                DatePicker::make('teslim_tarihi')->label('Teslim Tarihi')->required(),
                Select::make('kkd_stok_karti_id')
                    ->label('Stoktan düş (isteğe bağlı)')
                    ->placeholder('Stok kartı seçmeden devam et')
                    ->options(fn () => $this->stokKartlari->mapWithKeys(fn (KkdStokKarti $k) => [$k->id => $k->etiket().' (mevcut: '.$k->mevcut.')'])->all()),
                TextInput::make('seri_no')->label('Yeni Seri No'),
                DatePicker::make('son_kullanma')->label('Yeni SKT'),
                DatePicker::make('yenileme_tarihi')->label('Sonraki Yenileme / Kontrol'),
            ])
            ->action(function (array $data, array $arguments): void {
                $eski = $this->zimmet($arguments['id']);

                $yeni = $eski->replicate(['zimmet_no', 'iade_tarihi']);
                $yeni->fill([
                    ...$data,
                    'durum' => 'teslim_edildi',
                    'teslim_eden' => Filament::auth()->user()?->name ?? $eski->teslim_eden,
                    'aciklama' => trim(($eski->aciklama ? $eski->aciklama."\n" : '').$eski->zimmet_no.' yenilemesi'),
                ]);
                $yeni->save();

                if ($eski->aktifMi()) {
                    $eski->update(['durum' => 'yenilendi', 'iade_tarihi' => $data['teslim_tarihi']]);
                }
                KkdStok::zimmetCikisi($yeni->load('stokKarti'));

                $this->yenile();

                Notification::make()->title('KKD yenilendi')->body($eski->zimmet_no.' → '.$yeni->zimmet_no)->success()->send();
            });
    }

    public function zimmetSil(int $id): void
    {
        $z = $this->zimmet($id);

        // Hatalı girilip silinen teslim stoktan düşülmüşse geri eklenir.
        if ($z->aktifMi()) {
            KkdStok::iade($z->load('stokKarti'), 'Zimmet silindi: '.$z->zimmet_no);
        }

        $z->delete();
        $this->yenile();
    }

    public function zimmetFormu(int $id)
    {
        return KkdTakipUretici::personelZimmetFormu($this->zimmet($id));
    }

    /*
    |--------------------------------------------------------------------------
    | Stok aksiyonları
    |--------------------------------------------------------------------------
    */

    /** @return array<int, mixed> */
    private function stokSemasi(bool $duzenleme): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('kategori')->label('KKD Kategorisi')->options(fn () => $this->kategoriSecenekleri())->live(),
                TextInput::make('tur')
                    ->label('KKD Türü')
                    ->required()
                    ->maxLength(255)
                    ->datalist(fn (Get $get) => $this->turOnerileri($get('kategori'))),
                TextInput::make('marka')->label('Marka')->maxLength(255),
                TextInput::make('model')->label('Model')->maxLength(255),
                TextInput::make('beden')->label('Beden')->maxLength(255),
                TextInput::make('mevcut')
                    ->label('İlk Stok Adedi')
                    ->numeric()->integer()->minValue(0)
                    ->visible(! $duzenleme),
                TextInput::make('asgari')->label('Asgari Stok')->numeric()->integer()->minValue(0),
                TextInput::make('raf_omru')->label('Raf Ömrü')->maxLength(255),
                DatePicker::make('son_kullanma')->label('Son Kullanma'),
                DatePicker::make('yenileme_tarihi')->label('Yenileme / Kontrol'),
            ]),
            Textarea::make('aciklama')->label('Açıklama')->rows(2),
        ];
    }

    public function yeniStokKartiAction(): Action
    {
        return Action::make('yeniStokKarti')
            ->label('Yeni Stok Kartı')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni KKD Stok Kartı')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitActionLabel('Stok Kartını Kaydet')
            ->fillForm(fn (): array => ['mevcut' => 0, 'asgari' => 0])
            ->schema(fn () => $this->stokSemasi(false))
            ->action(function (array $data): void {
                $ilk = (int) ($data['mevcut'] ?? 0);
                $kart = $this->firma->kkdStokKartlari()->create([...$data, 'mevcut' => 0, 'asgari' => (int) ($data['asgari'] ?? 0)]);

                if ($ilk > 0) {
                    KkdStok::giris($kart, $ilk, 'İlk stok');
                }

                $this->yenile();

                Notification::make()->title('Stok kartı oluşturuldu')->body($kart->etiket())->success()->send();
            });
    }

    public function stokDuzenleAction(): Action
    {
        return Action::make('stokDuzenle')
            ->label('Düzenle')
            ->modalHeading('Stok Kartını Düzenle')
            ->modalDescription('Mevcut adet yalnız stok hareketiyle (giriş / fire) değişir.')
            ->modalWidth(Width::TwoExtraLarge)
            ->fillForm(fn (array $arguments): array => $this->formDegerleri($this->stokKarti($arguments['id'])))
            ->schema(fn () => $this->stokSemasi(true))
            ->action(function (array $data, array $arguments): void {
                $this->stokKarti($arguments['id'])->update([...$data, 'asgari' => (int) ($data['asgari'] ?? 0)]);
                $this->yenile();

                Notification::make()->title('Stok kartı güncellendi')->success()->send();
            });
    }

    public function stokHareketAction(): Action
    {
        return Action::make('stokHareket')
            ->label('Giriş / Fire')
            ->modalHeading(fn (array $arguments) => 'Stok Hareketi — '.$this->stokKarti($arguments['id'])->etiket())
            ->modalWidth(Width::Large)
            ->fillForm(fn (): array => ['tip' => 'giris', 'tarih' => now()->toDateString()])
            ->schema([
                Select::make('tip')
                    ->label('Hareket')
                    ->options(collect(config('isg.kkd_takip.hareket_tipleri'))->only(['giris', 'fire'])->all())
                    ->required(),
                TextInput::make('miktar')->label('Miktar')->numeric()->integer()->minValue(1)->required(),
                DatePicker::make('tarih')->label('Tarih')->required(),
                TextInput::make('aciklama')->label('Açıklama')->placeholder('Örn. fatura no, hurda nedeni'),
            ])
            ->action(function (array $data, array $arguments): void {
                $kart = $this->stokKarti($arguments['id']);
                $miktar = (int) $data['miktar'];

                $data['tip'] === 'fire'
                    ? KkdStok::fire($kart, $miktar, $data['aciklama'] ?? null, $data['tarih'])
                    : KkdStok::giris($kart, $miktar, $data['aciklama'] ?? null, $data['tarih']);

                $this->yenile();

                Notification::make()->title('Stok güncellendi')->body($kart->etiket().' — mevcut: '.$kart->fresh()->mevcut)->success()->send();
            });
    }

    public function stokGecmisiAction(): Action
    {
        return Action::make('stokGecmisi')
            ->label('Geçmiş')
            ->modalHeading(fn (array $arguments) => 'Stok Hareketleri — '.$this->stokKarti($arguments['id'])->etiket())
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Kapat')
            ->modalContent(fn (array $arguments) => view('filament.pages.partials.kkd-stok-gecmisi', [
                'hareketler' => $this->stokKarti($arguments['id'])->hareketler()->with('zimmet')->get(),
            ]));
    }

    public function stokSil(int $id): void
    {
        $this->stokKarti($id)->delete();
        $this->yenile();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->firma !== null && $this->tumZimmetler->isNotEmpty())
                ->action(fn () => KkdTakipUretici::excel($this->firma, $this->zimmetler)),
        ];
    }
}
