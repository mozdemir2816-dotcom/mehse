<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\IseOzguEgitimKonusu;
use App\Support\GeminiIseOzguEgitimUretici;
use App\Support\IseOzguEgitimKutuphanesi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * İşe Özgü Eğitim Konuları — yıllık eğitim planının 4. bölümünü ("İşe ve
 * İşyerine Özgü Riskler") besleyen kütüphane. Konu, NACE ön eki ve/veya iş
 * kalemiyle eşlenir; plan oluşturulurken firmanın iş kalemine (yoksa NACE
 * koduna) uyan konular otomatik gelir. Sistem konuları düzenlenebilir
 * (kendi kopyanız geçerli olur) ya da gizlenebilir.
 */
class IseOzguEgitimKonulari extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.ise-ozgu-egitim-konulari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar ve Kütüphaneler';

    /** Menüde gizli — Sistem & Ayarlar Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'ise-ozgu-egitim-konulari';

    protected static ?string $title = 'İşe Özgü Eğitim Konuları';

    protected static ?string $navigationLabel = 'İşe Özgü Eğitim Konuları';

    public string $arama = '';

    public string $kaynakFiltre = '';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public function mount(): void
    {
        $id = request()->integer('firma') ?: request()->integer('firmaId') ?: \App\Support\KomutaFirma::baslangic($this->firmalar);
        $this->firmaId = $id && array_key_exists($id, $this->firmalar) ? $id : null;
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->secenekler()->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId && array_key_exists($this->firmaId, $this->firmalar) ? Firma::find($this->firmaId) : null;
    }

    /** @return Collection<string, array<string, mixed>> */
    #[Computed]
    public function konular(): Collection
    {
        $q = mb_strtolower(trim($this->arama));
        $onerilen = $this->firma ? IseOzguEgitimKutuphanesi::firmaIcin($this->firma)->keys()->all() : null;

        return IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id())
            ->filter(fn ($k, $a) => ($onerilen === null || in_array($a, $onerilen, true))
                && match ($this->kaynakFiltre) {
                    'kendi' => ! $k['sistem'] || $k['duzenlendi'],
                    'kalem' => str_starts_with($a, 'kalem_'),
                    'nace' => ! str_starts_with($a, 'kalem_') && ! str_starts_with($a, 'ozel_'),
                    default => true,
                }
                && ($q === '' || str_contains(mb_strtolower($k['ad'].' '.$k['hedef'].' '.$k['kaynak'].' '.IseOzguEgitimKutuphanesi::naceGoster($k['nace'])), $q)));
    }

    #[Computed]
    public function gizlenenler(): Collection
    {
        return IseOzguEgitimKutuphanesi::gizlenenler(Filament::auth()->id());
    }

    public function updated(string $alan = ''): void
    {
        if ($alan === 'firmaId') {
            \App\Support\KomutaFirma::sec($this->firmaId);
        }
        unset($this->firma, $this->konular, $this->gizlenenler);
    }

    /** Plan sayfasındaki "Kütüphaneye kaydet" ile ortak form. */
    public static function konuSemasi(): array
    {
        $kalemler = collect(config('isg.kkd_matris.is_kalemleri', []))->flatten(1)
            ->filter(fn ($k) => filled($k['anahtar'] ?? null))->pluck('ad', 'anahtar')->all();

        return [
            TextInput::make('ad')->label('Eğitimin adı')->required()->maxLength(255),
            Textarea::make('hedef')->label('Eğitimin hedefi')->rows(3),
            Grid::make(2)->schema([
                TextInput::make('egitici')->label('Eğitici')->default(IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI)->maxLength(255),
                TextInput::make('nace')->label('NACE kapsamı')->placeholder('Örn. 41, 43.21')
                    ->helperText('Alt faaliyetleri kapsar: "43.21" → 43.21 ile başlayan tüm kodlar.'),
            ]),
            Select::make('is_kalemleri')->label('İş kalemleri (yapılan işe göre)')->multiple()->options($kalemler)
                ->helperText('Firmada bu iş kalemlerinden biri seçiliyse konu otomatik önerilir.'),
            TextInput::make('is_kolu')->label('İş kolu / çalışan grubu (isteğe bağlı)')->maxLength(255)
                ->helperText('Doluysa konu yalnız bu iş koluyla düzenlenen Eğitim Katılım formlarına gelir (ör. Kazı Ekibi); firma geneli plan önerisine girmez.'),
        ];
    }

    private function veri(array $data): array
    {
        return [
            'ad' => trim($data['ad']),
            'hedef' => filled($data['hedef'] ?? null) ? trim($data['hedef']) : null,
            'egitici' => filled($data['egitici'] ?? null) ? trim($data['egitici']) : null,
            'nace_onekleri' => IseOzguEgitimKutuphanesi::naceHazirla($data['nace'] ?? null),
            'is_kalemleri' => array_values($data['is_kalemleri'] ?? []) ?: null,
            'is_kolu' => filled($data['is_kolu'] ?? null) ? trim($data['is_kolu']) : null,
        ];
    }

    public function yeniKonuAction(): Action
    {
        return Action::make('yeniKonu')
            ->label('Yeni Konu')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni işe özgü eğitim konusu')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(fn () => ['egitici' => IseOzguEgitimKutuphanesi::VARSAYILAN_EGITICI])
            ->schema(fn () => static::konuSemasi())
            ->action(function (array $data): void {
                IseOzguEgitimKonusu::create([...$this->veri($data), 'user_id' => Filament::auth()->id()]);
                $this->updated();
                Notification::make()->success()->title('Konu kaydedildi')->send();
            });
    }

    /** Gemini API ile tek sektör, seçili sektörler veya tüm sektörler için toplu işe özgü eğitim içeriği üretir. */
    public function aiSektorKonulariUretAction(): Action
    {
        return Action::make('aiSektorKonulariUret')
            ->label('✨ API / AI ile Sektör Konuları Üret')
            ->icon('heroicon-o-sparkles')
            ->modalHeading('✨ API / Yapay Zekâ ile Sektörlere Özgü Eğitim Konuları Üret')
            ->modalDescription('Seçtiğiniz sektörler (veya sistemdeki tüm 21 sektör) için 4. Bölüm işe özgü eğitim başlıkları ve hedefleri üretilir; NACE ön ekleriyle otomatik eşlenerek Yıllık Eğitim Planı ve Eğitim Katılım formlarına entegre olur.')
            ->modalSubmitActionLabel('API ile Üret ve Kütüphaneye Ekle')
            ->schema([
                Radio::make('kapsam')
                    ->label('Üretim Kapsamı')
                    ->options([
                        'secili' => 'Seçili sektör(ler) için üret',
                        'tumu' => 'Tüm sektörler için toplu üret ('.count(GeminiIseOzguEgitimUretici::sektorSecenekleri()).' sektör)',
                    ])
                    ->default('secili')
                    ->live()
                    ->required(),
                Select::make('sektorler')
                    ->label('Sektörler')
                    ->multiple()
                    ->searchable()
                    ->options(GeminiIseOzguEgitimUretici::sektorSecenekleri())
                    ->visible(fn ($get) => ($get('kapsam') ?? 'secili') === 'secili')
                    ->required(fn ($get) => ($get('kapsam') ?? 'secili') === 'secili'),
                Grid::make(2)->schema([
                    Select::make('adet')
                        ->label('Sektör Başına Konu Adedi')
                        ->options([3 => '3 konu', 4 => '4 konu', 5 => '5 konu', 6 => '6 konu', 8 => '8 konu'])
                        ->default(5)
                        ->required(),
                    TextInput::make('is_kolu')
                        ->label('Ekip / İş Kolu Etiketi (Opsiyonel)')
                        ->placeholder('Örn: Kalıp Ekibi, Sıva Ekibi (boş = sektör geneli)'),
                ]),
                Textarea::make('ek_baglam')
                    ->label('Ek Odak / Ekipman Notu (Opsiyonel)')
                    ->rows(2)
                    ->placeholder('Örn: Saha uygulamaları, makine koruyucuları ve LOTO ağırlıklı'),
            ])
            ->action(function (array $data): void {
                $userId = (int) Filament::auth()->id();
                $sektorAnahtarlari = ($data['kapsam'] ?? 'secili') === 'tumu'
                    ? array_keys(GeminiIseOzguEgitimUretici::sektorSecenekleri())
                    : array_values((array) ($data['sektorler'] ?? []));

                if ($sektorAnahtarlari === []) {
                    Notification::make()->title('En az bir sektör seçin')->warning()->send();

                    return;
                }

                $sonuc = GeminiIseOzguEgitimUretici::sektorlerIcinTopluUret(
                    $userId,
                    $sektorAnahtarlari,
                    (int) ($data['adet'] ?? 5),
                    filled($data['is_kolu'] ?? null) ? trim((string) $data['is_kolu']) : null,
                    filled($data['ek_baglam'] ?? null) ? trim((string) $data['ek_baglam']) : null,
                );

                $this->updated();

                if ($sonuc['basarili_sektor'] === 0) {
                    Notification::make()
                        ->title('API üzerinden konu üretilemedi')
                        ->body(implode(' | ', array_slice($sonuc['hatalar'], 0, 3)))
                        ->danger()
                        ->send();

                    return;
                }

                $mesaj = "{$sonuc['basarili_sektor']} sektör için {$sonuc['toplam_eklenen']} yeni konu kütüphaneye eklendi.";
                if ($sonuc['hatalar'] !== []) {
                    $mesaj .= ' ('.count($sonuc['hatalar']).' sektör atlandı)';
                }

                Notification::make()
                    ->title('Sektör konuları API ile eklendi')
                    ->body($mesaj)
                    ->success()
                    ->send();
            });
    }

    public function konuDuzenleAction(): Action
    {
        return Action::make('konuDuzenle')
            ->modalHeading('Konuyu düzenle')
            ->modalDescription(fn (array $arguments) => str_starts_with($arguments['anahtar'] ?? '', 'ozel_') ? null : 'Sistem konusu: değişiklik yalnız sizin kütüphanenizde geçerli olur.')
            ->modalSubmitActionLabel('Kaydet')
            ->fillForm(function (array $arguments): array {
                $k = IseOzguEgitimKutuphanesi::tumu(Filament::auth()->id())->get($arguments['anahtar'] ?? '');

                return $k ? ['ad' => $k['ad'], 'hedef' => $k['hedef'], 'egitici' => $k['egitici'], 'nace' => IseOzguEgitimKutuphanesi::naceGoster($k['nace']), 'is_kalemleri' => $k['is_kalemleri'], 'is_kolu' => $k['is_kolu'] ?? null] : [];
            })
            ->schema(fn () => static::konuSemasi())
            ->action(function (array $data, array $arguments): void {
                $anahtar = $arguments['anahtar'] ?? '';
                $userId = Filament::auth()->id();

                if (str_starts_with($anahtar, 'ozel_')) {
                    IseOzguEgitimKonusu::query()->where('user_id', $userId)->whereKey((int) substr($anahtar, 5))->first()?->update($this->veri($data));
                } elseif (IseOzguEgitimKutuphanesi::sistemKonulari()->has($anahtar)) {
                    IseOzguEgitimKonusu::query()->updateOrCreate(['user_id' => $userId, 'sistem_anahtari' => $anahtar], [...$this->veri($data), 'gizli' => false]);
                }

                $this->updated();
                Notification::make()->success()->title('Konu güncellendi')->send();
            });
    }

    /** Kendi konusunu siler; sistem konusunu kütüphaneden gizler. */
    public function kaldir(string $anahtar): void
    {
        $userId = Filament::auth()->id();

        if (str_starts_with($anahtar, 'ozel_')) {
            IseOzguEgitimKonusu::query()->where('user_id', $userId)->whereKey((int) substr($anahtar, 5))->delete();
        } elseif ($s = IseOzguEgitimKutuphanesi::sistemKonulari()->get($anahtar)) {
            IseOzguEgitimKonusu::query()->updateOrCreate(['user_id' => $userId, 'sistem_anahtari' => $anahtar], ['ad' => $s['ad'], 'gizli' => true]);
        }

        $this->updated();
    }

    /** Gizlenen ya da düzenlenen sistem konusunu orijinal haline döndürür. */
    public function sistemeDondur(string $anahtar): void
    {
        IseOzguEgitimKonusu::query()->where('user_id', Filament::auth()->id())->where('sistem_anahtari', $anahtar)->delete();
        $this->updated();
    }
}
