<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\PkdKaydi;
use App\Support\PkdUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
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
 * Patlamadan Korunma Dokümanı (PKD) Sicili — isgsuite "PKD Sicili". İşyeri,
 * bölüm ve proses bazında PKD künyesi; zone sınıfları, tutuşturucu kaynaklar,
 * korunma önlemleri, revizyon / gözden geçirme takibi ve doküman dosyası.
 * Menüde ayrı görünmez; Kimyasal Yönetimi sayfasından açılır.
 */
class PkdSicili extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.pkd-sicili';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|UnitEnum|null $navigationGroup = 'Risk Değerlendirmesi';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'pkd-sicili';

    protected static ?string $title = 'PKD Sicili — Patlamadan Korunma Dokümanı';

    public ?int $firmaId = null;

    public ?string $arama = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()->where('user_id', Filament::auth()->id())->orderBy('unvan')->pluck('unvan', 'id')->all();
    }

    /** @return Collection<int, PkdKaydi> */
    #[Computed]
    public function tumKayitlar(): Collection
    {
        return PkdKaydi::query()
            ->whereIn('firma_id', array_keys($this->firmalar))
            ->when($this->firmaId, fn ($q) => $q->where('firma_id', $this->firmaId))
            ->with('firma')
            ->orderBy('dokuman_no')
            ->get();
    }

    /** @return Collection<int, PkdKaydi> */
    #[Computed]
    public function kayitlar(): Collection
    {
        $aranan = mb_strtolower(trim((string) $this->arama));

        return $aranan === ''
            ? $this->tumKayitlar
            : $this->tumKayitlar->filter(fn (PkdKaydi $p) => str_contains(
                mb_strtolower(implode(' ', [$p->dokuman_no, $p->bolum, $p->proses, $p->tehlikeli_maddeler, $p->firma?->unvan])),
                $aranan,
            ))->values();
    }

    /** @return array{toplam: int, aktif: int, dosyali: int, dosyasiz: int, takip: int, gecikmis: int} */
    #[Computed]
    public function ozet(): array
    {
        $k = $this->tumKayitlar;
        $acik = $k->where('durum', '!=', 'arsiv');

        return [
            'toplam' => $k->count(),
            'aktif' => $k->where('durum', 'aktif')->count(),
            'dosyali' => $acik->filter->dosyaVarMi()->count(),
            'dosyasiz' => $acik->reject->dosyaVarMi()->count(),
            'takip' => $acik->filter->takipGerekiyorMu()->count(),
            'gecikmis' => $acik->filter(fn (PkdKaydi $p) => $p->incelemeDurumu() === 'gecikmis')->count(),
        ];
    }

    public function updated(string $alan): void
    {
        if (in_array($alan, ['firmaId', 'arama'], true)) {
            $this->yenile();
        }
    }

    private function yenile(): void
    {
        unset($this->tumKayitlar, $this->kayitlar, $this->ozet);
    }

    private function kayit(int $id): PkdKaydi
    {
        return PkdKaydi::query()->whereIn('firma_id', array_keys($this->firmalar))->findOrFail($id);
    }

    /** @return array<int, mixed> */
    private function sema(bool $yeni): array
    {
        return [
            Section::make('Doküman künyesi')->description('PKD\'nin hangi işyeri, alan ve revizyona ait olduğu.')->schema([
                Grid::make(2)->schema([
                    Select::make('firma_id')->label('Firma')->options(fn () => $this->firmalar)->searchable()->required()->visible($yeni),
                    TextInput::make('dokuman_no')->label('PKD doküman no')->placeholder('Örn. PKD-2026-001')->required()->maxLength(255),
                    TextInput::make('bolum')->label('Bölüm / tehlikeli alan')->placeholder('Örn. Solvent deposu')->required()->maxLength(255),
                    TextInput::make('proses')->label('Proses / faaliyet')->placeholder('Örn. Dolum ve transfer hattı')->maxLength(255),
                    Select::make('ortam_turu')->label('Ortam türü')->options(config('isg.pkd.ortam_turleri'))->required(),
                    TextInput::make('revizyon_no')->label('Revizyon no')->maxLength(255),
                    DatePicker::make('dokuman_tarihi')->label('Doküman tarihi'),
                    DatePicker::make('sonraki_gozden_gecirme')->label('Sonraki gözden geçirme'),
                    Select::make('durum')->label('İş akışı durumu')->options(config('isg.pkd.durumlar'))->required(),
                ]),
            ]),
            Section::make('Patlayıcı ortam değerlendirmesi')->schema([
                Textarea::make('tehlikeli_maddeler')->label('Tehlikeli maddeler / karışımlar')->helperText('Yanıcı sıvı, gaz, buhar veya tozları belirtin.')->rows(2),
                CheckboxList::make('zonelar')->label('Tehlikeli bölge sınıfları')->options(config('isg.pkd.zonelar'))->columns(3),
                CheckboxList::make('tutusturucular')->label('Muhtemel tutuşturucu kaynaklar')->options(config('isg.pkd.tutusturucular'))->columns(2),
                CheckboxList::make('onlemler')->label('Kontrol ve korunma önlemleri')->options(config('isg.pkd.onlemler'))->columns(2),
            ]),
            Section::make('Sorumluluk ve onay')->schema([
                Grid::make(3)->schema([
                    TextInput::make('sorumlu')->label('Sorumlu kişi')->maxLength(255),
                    TextInput::make('hazirlayan')->label('Hazırlayan')->maxLength(255),
                    TextInput::make('onaylayan')->label('Onaylayan')->maxLength(255),
                ]),
                Textarea::make('notlar')->label('Notlar ve aksiyonlar')->rows(3),
            ]),
        ];
    }

    public function yeniPkdAction(): Action
    {
        return Action::make('yeniPkd')
            ->label('Yeni PKD Kaydı')
            ->icon('heroicon-o-plus')
            ->modalHeading('Yeni PKD Kaydı')
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel('PKD Kaydını Oluştur')
            ->fillForm(fn (): array => [
                'firma_id' => $this->firmaId,
                'ortam_turu' => 'gaz_buhar_sis',
                'revizyon_no' => '1.0',
                'durum' => 'taslak',
                'dokuman_tarihi' => now()->toDateString(),
                'hazirlayan' => Filament::auth()->user()?->name,
            ])
            ->schema(fn () => $this->sema(true))
            ->action(function (array $data): void {
                abort_unless(array_key_exists((int) $data['firma_id'], $this->firmalar), 403);

                $p = PkdKaydi::create($data);
                $this->yenile();

                Notification::make()->title('PKD kaydı oluşturuldu')->body($p->dokuman_no)->success()->send();
            });
    }

    public function pkdDuzenleAction(): Action
    {
        return Action::make('pkdDuzenle')
            ->label('Düzenle')
            ->modalHeading(fn (array $arguments) => 'PKD Kaydı — '.$this->kayit($arguments['id'])->dokuman_no)
            ->modalWidth(Width::FourExtraLarge)
            ->fillForm(function (array $arguments): array {
                $p = $this->kayit($arguments['id']);

                return [
                    ...$p->only(['dokuman_no', 'bolum', 'proses', 'ortam_turu', 'revizyon_no', 'durum', 'tehlikeli_maddeler', 'sorumlu', 'hazirlayan', 'onaylayan', 'notlar']),
                    'dokuman_tarihi' => $p->dokuman_tarihi?->toDateString(),
                    'sonraki_gozden_gecirme' => $p->sonraki_gozden_gecirme?->toDateString(),
                    'zonelar' => $p->zonelar ?? [],
                    'tutusturucular' => $p->tutusturucular ?? [],
                    'onlemler' => $p->onlemler ?? [],
                ];
            })
            ->schema(fn () => $this->sema(false))
            ->action(function (array $data, array $arguments): void {
                $this->kayit($arguments['id'])->update($data);
                $this->yenile();

                Notification::make()->title('PKD kaydı güncellendi')->success()->send();
            });
    }

    public function dosyaYukleAction(): Action
    {
        return Action::make('dosyaYukle')
            ->label('Dosya yükle')
            ->modalHeading(fn (array $arguments) => 'PKD Dosyası — '.$this->kayit($arguments['id'])->dokuman_no)
            ->modalDescription('Önceki dosya varsa yenisiyle değiştirilir.')
            ->schema([
                FileUpload::make('dosya')
                    ->label('PKD dosyası (PDF / Word)')
                    ->disk('public')
                    ->directory('pkd')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(30720)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $p = $this->kayit($arguments['id']);

                if ($p->dosya_yolu && $p->dosya_yolu !== $data['dosya'] && Storage::disk('public')->exists($p->dosya_yolu)) {
                    Storage::disk('public')->delete($p->dosya_yolu);
                }

                $p->update(['dosya_yolu' => $data['dosya'], 'dosya_adi' => basename($data['dosya'])]);
                $this->yenile();

                Notification::make()->title('PKD dosyası yüklendi')->success()->send();
            });
    }

    public function dosyaIndir(int $id)
    {
        $p = $this->kayit($id);

        if (! $p->dosya_yolu || ! Storage::disk('public')->exists($p->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($p->dosya_yolu, $p->dosya_adi);
    }

    /** PKD künye / özet formu (PDF) — dosyaya eklenecek kapak ve kontrol listesi. */
    public function dokumanOlustur(int $id)
    {
        return PkdUretici::kunyePdf($this->kayit($id));
    }

    public function sil(int $id): void
    {
        $this->kayit($id)->delete();
        $this->yenile();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('merkez')
                ->label('Kimyasal Yönetimi')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => KimyasalYonetimi::getUrl(array_filter(['firma' => $this->firmaId]))),

            Action::make('excel')
                ->label('Excel Rapor')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->visible(fn () => $this->kayitlar->isNotEmpty())
                ->action(fn () => PkdUretici::excel($this->kayitlar)),
        ];
    }
}
