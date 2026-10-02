<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IsIzinFormu;
use App\Models\Taseron;
use App\Models\TaseronBelgesi;
use App\Models\TaseronCalisani;
use App\Support\TaseronUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Taşeron / Alt İşveren Yönetimi (isgsuite "Taşeron Yönetimi") — işyeri
 * bazında alt işveren ve taşeron firmaları: sözleşme, çalışan (ana personel
 * sayısına eklenmez), belge + geçerlilik ve iş izni bağları; uygunluk eksikleri
 * ve Uygunluk Raporu PDF'i.
 */
class TaseronYonetimi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.taseron-yonetimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = 'Diğer Belge & Yazışma';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'taseron-yonetimi';

    protected static ?string $title = 'Taşeron / Alt İşveren Yönetimi';

    protected static ?string $navigationLabel = 'Taşeron / Alt İşveren';

    /** Liste filtresi (boş = tüm işyerleri). */
    public ?int $firmaId = null;

    public bool $pasifleriGoster = false;

    /** "Yönet" ile açılan taşeron. */
    public ?int $seciliId = null;

    // --- Çalışan ekleme formu ---
    public ?string $calisanAd = null;

    public ?string $calisanGorev = null;

    public ?string $calisanTc = null;

    public ?string $calisanIseGiris = null;

    public ?string $calisanEgitim = null;

    public ?string $calisanSaglik = null;

    public ?int $baglanacakIzinId = null;

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

    /** @return Collection<int, Taseron> */
    #[Computed]
    public function taseronlar(): Collection
    {
        return Taseron::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->when(! $this->pasifleriGoster, fn ($q) => $q->where('aktif', true))
            ->with(['firma', 'calisanlar', 'belgeler'])
            ->orderByDesc('aktif')
            ->orderBy('unvan')
            ->get();
    }

    #[Computed]
    public function secili(): ?Taseron
    {
        return $this->seciliId
            ? Taseron::query()
                ->whereIn('firma_id', array_keys($this->firmalar))
                ->with(['firma', 'calisanlar', 'belgeler', 'isIzinleri'])
                ->find($this->seciliId)
            : null;
    }

    /** Seçili taşeronun işyerindeki, henüz bağlanmamış iş izinleri. */
    #[Computed]
    public function baglanabilirIzinler(): array
    {
        if (! $this->secili) {
            return [];
        }

        $bagli = $this->secili->isIzinleri->pluck('id')->all();

        return IsIzinFormu::query()
            ->where('firma_id', $this->secili->firma_id)
            ->whereNotIn('id', $bagli)
            ->latest('baslangic')->latest('id')
            ->get()
            ->mapWithKeys(fn (IsIzinFormu $f) => [$f->id => trim(($f->izin_no ?: '#'.$f->id).' — '.($f->calisma_alani ?: 'Alan belirtilmemiş')
                .($f->baslangic ? ' ('.$f->baslangic->format('d.m.Y').')' : ''))])
            ->all();
    }

    private function yenile(): void
    {
        unset($this->taseronlar, $this->secili, $this->baglanabilirIzinler);
    }

    private function taseron(int $id): Taseron
    {
        return Taseron::query()->whereIn('firma_id', array_keys($this->firmalar))->findOrFail($id);
    }

    public function updatedFirmaId(): void
    {
        $this->yenile();
    }

    public function updatedPasifleriGoster(): void
    {
        $this->yenile();
    }

    public function yonet(int $id): void
    {
        $this->seciliId = $this->taseron($id)->id;
        $this->reset('calisanAd', 'calisanGorev', 'calisanTc', 'calisanIseGiris', 'calisanEgitim', 'calisanSaglik', 'baglanacakIzinId');
        $this->yenile();
    }

    public function kapat(): void
    {
        $this->seciliId = null;
        $this->yenile();
    }

    /*
    |--------------------------------------------------------------------------
    | Taşeron kaydı
    |--------------------------------------------------------------------------
    */

    /** @return array<int, mixed> */
    private function taseronSemasi(bool $yeni): array
    {
        return [
            Section::make('Firma bilgileri')->schema([
                Grid::make(2)->schema([
                    Select::make('firma_id')
                        ->label('İşyeri (asıl işveren)')
                        ->options(fn () => $this->firmalar)
                        ->searchable()
                        ->required()
                        ->visible($yeni),
                    Select::make('tur')->label('Tür')->options(config('isg.taseron.turler'))->required(),
                    TextInput::make('unvan')->label('Taşeron / alt işveren ünvanı')->required()->maxLength(255),
                    TextInput::make('faaliyet')->label('Yaptığı iş / faaliyet')->placeholder('Örn. Elektrik tesisatı, temizlik, kaba inşaat')->maxLength(255),
                    TextInput::make('vergi_no')->label('Vergi no')->maxLength(255),
                    TextInput::make('sgk_sicil_no')->label('SGK işyeri sicil no (alt işveren)')->maxLength(255),
                    Select::make('tehlike_sinifi')
                        ->label('Tehlike sınıfı')
                        ->options(config('isg.tehlike_siniflari'))
                        ->placeholder('İşyerininki kullanılsın')
                        ->helperText('Çalışan eğitim / sağlık raporu geçerliliği buna göre hesaplanır.'),
                ]),
            ]),
            Section::make('Sözleşme ve iletişim')->schema([
                Grid::make(2)->schema([
                    TextInput::make('sozlesme_no')->label('Sözleşme no')->maxLength(255),
                    DatePicker::make('sozlesme_baslangic')->label('Sözleşme başlangıç'),
                    DatePicker::make('sozlesme_bitis')->label('Sözleşme bitiş')->afterOrEqual('sozlesme_baslangic'),
                    TextInput::make('yetkili')->label('Yetkili')->maxLength(255),
                    TextInput::make('telefon')->label('Telefon')->tel()->maxLength(255),
                    TextInput::make('eposta')->label('E-posta')->email()->maxLength(255),
                    TextInput::make('isg_uzmani')->label('Taşeronun İSG uzmanı')->maxLength(255),
                    TextInput::make('isyeri_hekimi')->label('Taşeronun işyeri hekimi')->maxLength(255),
                ]),
            ]),
            Textarea::make('ilk_calisanlar')
                ->label('İlk çalışanlar (isteğe bağlı)')
                ->helperText('Her satıra bir kişi: "Ad Soyad - Görev". Çalışanlar ana personel listesine eklenmez.')
                ->rows(3)
                ->visible($yeni),
            Textarea::make('notlar')->label('Notlar')->rows(2),
        ];
    }

    public function yeniTaseronAction(): Action
    {
        return Action::make('yeniTaseron')
            ->label('Yeni Taşeron / Alt İşveren')
            ->icon('heroicon-o-plus')
            ->modalHeading('Taşeron / Alt İşveren Kaydı')
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn (): array => ['firma_id' => $this->firmaId, 'tur' => 'alt_isveren'])
            ->schema(fn () => $this->taseronSemasi(true))
            ->action(function (array $data): void {
                abort_unless(array_key_exists((int) $data['firma_id'], $this->firmalar), 403);

                $ilk = $data['ilk_calisanlar'] ?? null;
                unset($data['ilk_calisanlar']);

                $t = Taseron::create($data);

                foreach (preg_split('/\r?\n/', (string) $ilk) as $satir) {
                    [$ad, $gorev] = array_pad(array_map('trim', preg_split('/\s+[-–]\s+/u', trim($satir), 2)), 2, null);
                    if (filled($ad)) {
                        $t->calisanlar()->create(['ad_soyad' => $ad, 'gorev' => $gorev ?: null]);
                    }
                }

                $this->seciliId = $t->id;
                $this->yenile();

                Notification::make()->title($t->unvan.' kaydedildi')->body($t->turEtiketi().' — '.$t->firma->unvan)->success()->send();
            });
    }

    public function taseronDuzenleAction(): Action
    {
        return Action::make('taseronDuzenle')
            ->label('Bilgileri Düzenle')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Taşeron / Alt İşveren Bilgileri')
            ->modalWidth(Width::FourExtraLarge)
            ->fillForm(function (): array {
                $t = $this->taseron($this->seciliId);

                return collect($t->getAttributes())
                    ->map(fn ($v, string $k) => $t->getAttribute($k) instanceof \DateTimeInterface ? $t->getAttribute($k)->format('Y-m-d') : $v)
                    ->all();
            })
            ->schema(fn () => $this->taseronSemasi(false))
            ->action(function (array $data): void {
                $this->taseron($this->seciliId)->update($data);
                $this->yenile();

                Notification::make()->title('Bilgiler güncellendi')->success()->send();
            });
    }

    public function aktiflikDegistir(): void
    {
        $t = $this->taseron($this->seciliId);
        $t->update(['aktif' => ! $t->aktif]);
        $this->yenile();

        Notification::make()->title($t->unvan.($t->aktif ? ' aktifleştirildi' : ' pasife alındı'))->success()->send();
    }

    public function taseronSil(): void
    {
        $t = $this->taseron($this->seciliId);
        $t->delete();
        $this->seciliId = null;
        $this->yenile();

        Notification::make()->title($t->unvan.' silindi')->success()->send();
    }

    public function uygunlukRaporu()
    {
        return TaseronUretici::uygunlukPdf($this->taseron($this->seciliId));
    }

    /*
    |--------------------------------------------------------------------------
    | Çalışanlar
    |--------------------------------------------------------------------------
    */

    public function calisanEkle(): void
    {
        $t = $this->taseron($this->seciliId);

        if (blank($this->calisanAd)) {
            Notification::make()->title('Ad soyad zorunlu')->danger()->send();

            return;
        }

        $t->calisanlar()->create([
            'ad_soyad' => trim($this->calisanAd),
            'gorev' => $this->calisanGorev,
            'tc_maskeli' => TaseronCalisani::maskele($this->calisanTc),
            'ise_giris' => $this->calisanIseGiris ?: null,
            'isg_egitim_tarihi' => $this->calisanEgitim ?: null,
            'saglik_raporu_tarihi' => $this->calisanSaglik ?: null,
        ]);

        $this->reset('calisanAd', 'calisanGorev', 'calisanTc', 'calisanIseGiris', 'calisanEgitim', 'calisanSaglik');
        $this->yenile();
    }

    private function calisan(int $id): TaseronCalisani
    {
        return $this->taseron($this->seciliId)->calisanlar()->findOrFail($id);
    }

    public function calisanAktiflik(int $id): void
    {
        $c = $this->calisan($id);
        $c->update(['aktif' => ! $c->aktif]);
        $this->yenile();
    }

    public function calisanSil(int $id): void
    {
        $this->calisan($id)->delete();
        $this->yenile();
    }

    public function calisanDuzenleAction(): Action
    {
        return Action::make('calisanDuzenle')
            ->label('Düzenle')
            ->modalHeading('Taşeron Çalışanı')
            ->modalWidth(Width::TwoExtraLarge)
            ->fillForm(function (array $arguments): array {
                $c = $this->calisan($arguments['id']);

                return [
                    'ad_soyad' => $c->ad_soyad,
                    'gorev' => $c->gorev,
                    'tc_maskeli' => $c->tc_maskeli,
                    'ise_giris' => $c->ise_giris?->toDateString(),
                    'isg_egitim_tarihi' => $c->isg_egitim_tarihi?->toDateString(),
                    'saglik_raporu_tarihi' => $c->saglik_raporu_tarihi?->toDateString(),
                ];
            })
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('ad_soyad')->label('Ad soyad')->required(),
                    TextInput::make('gorev')->label('Görev'),
                    TextInput::make('tc_maskeli')->label('Kimlik no (maskelenir)'),
                    DatePicker::make('ise_giris')->label('İşe giriş'),
                    DatePicker::make('isg_egitim_tarihi')->label('Son İSG eğitimi'),
                    DatePicker::make('saglik_raporu_tarihi')->label('Son sağlık raporu'),
                ]),
            ])
            ->action(function (array $data, array $arguments): void {
                $data['tc_maskeli'] = TaseronCalisani::maskele($data['tc_maskeli'] ?? null);
                $this->calisan($arguments['id'])->update($data);
                $this->yenile();
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Belgeler
    |--------------------------------------------------------------------------
    */

    public function belgeEkleAction(): Action
    {
        return Action::make('belgeEkle')
            ->label('Belge Ekle')
            ->icon('heroicon-o-paper-clip')
            ->modalHeading('Taşeron Belgesi')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Grid::make(2)->schema([
                    Select::make('tur')
                        ->label('Belge türü')
                        ->options(collect(config('isg.taseron.belge_turleri'))->map(fn (array $b) => $b['ad'])->all())
                        ->required(),
                    TextInput::make('baslik')->label('Belge başlığı (isteğe bağlı)'),
                    DatePicker::make('gecerlilik_sonu')->label('Geçerlilik sonu')->helperText('Süresiz belgede boş bırakın'),
                    TextInput::make('notu')->label('Not'),
                ]),
                FileUpload::make('dosya')
                    ->label('Dosya')
                    ->disk('public')
                    ->directory(fn () => 'taseron/'.$this->taseron($this->seciliId)->firma_id)
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png',
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(20480),
            ])
            ->action(function (array $data): void {
                $t = $this->taseron($this->seciliId);
                $yol = $data['dosya'] ?? null;

                $t->belgeler()->create([
                    'tur' => $data['tur'],
                    'baslik' => $data['baslik'] ?: null,
                    'gecerlilik_sonu' => $data['gecerlilik_sonu'] ?: null,
                    'notu' => $data['notu'] ?: null,
                    'dosya_yolu' => $yol,
                    'dosya_adi' => $yol ? basename($yol) : null,
                    'boyut' => $yol && Storage::disk('public')->exists($yol) ? Storage::disk('public')->size($yol) : null,
                ]);

                $this->yenile();

                Notification::make()->title('Belge eklendi')->success()->send();
            });
    }

    private function belge(int $id): TaseronBelgesi
    {
        return $this->taseron($this->seciliId)->belgeler()->findOrFail($id);
    }

    public function belgeIndir(int $id)
    {
        $b = $this->belge($id);

        if (! $b->dosya_yolu || ! Storage::disk('public')->exists($b->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($b->dosya_yolu, $b->dosya_adi);
    }

    public function belgeSil(int $id): void
    {
        $this->belge($id)->delete();
        $this->yenile();
    }

    /*
    |--------------------------------------------------------------------------
    | İş izni bağları — iş izni kaydını değiştirmez, yalnız bağ kurar.
    |--------------------------------------------------------------------------
    */

    public function izinBagla(): void
    {
        $t = $this->taseron($this->seciliId);

        if (! $this->baglanacakIzinId || ! array_key_exists($this->baglanacakIzinId, $this->baglanabilirIzinler)) {
            Notification::make()->title('Bir iş izni seçin')->danger()->send();

            return;
        }

        $t->isIzinleri()->syncWithoutDetaching([$this->baglanacakIzinId]);
        $this->baglanacakIzinId = null;
        $this->yenile();

        Notification::make()->title('İş izni taşerona bağlandı')->success()->send();
    }

    public function izinBagKaldir(int $izinId): void
    {
        $this->taseron($this->seciliId)->isIzinleri()->detach($izinId);
        $this->yenile();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liste_excel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->taseronlar->isNotEmpty())
                ->action(fn () => TaseronUretici::listeExcel($this->taseronlar)),
        ];
    }
}
