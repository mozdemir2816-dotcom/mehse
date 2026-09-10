<?php

namespace App\Filament\Pages;

use App\Models\AcilDurumPlani as PlanModel;
use App\Models\Firma;
use App\Support\AcilDurumPlaniUretici;
use App\Support\AcilDurumWordUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Acil Durum Eylem Planı — isgpratik 19-21, 146-154.jpg. Firma seçilir, konu
 * sayfaları + destek ekipleri + kapak çerçevesi ayarlanır → PDF üretilir.
 * Ayrıca 7 acil durum afişi (A3/A4 talimat) indirilir.
 */
class AcilDurumPlani extends Page
{
    protected string $view = 'filament.pages.acil-durum-plani';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|UnitEnum|null $navigationGroup = 'Acil Durum & Yangın';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'acil-durum-plani';

    protected static ?string $title = 'Acil Durum Eylem Planı';

    protected static ?string $navigationLabel = 'Acil Durum Planı';

    public ?int $firmaId = null;

    // Form durumu (seçili plana bağlanır)
    public ?string $dokumanNo = null;

    public ?string $raporTarihi = null;

    public string $kapakCercevesi = 'klasik';

    public ?string $revizyonNo = null;

    public ?string $toplanmaYeri = null;

    public ?string $disaridanEtkileyebilecekIsyerleri = null;

    /** @var array<int, string> seçili konu anahtarları */
    public array $konular = [];

    /** @var array<string, string> ekip anahtarı => virgülle ayrık isimler */
    public array $ekipMetni = [];

    // Afiş seçimi
    public string $afisTipi = 'yangin';

    public string $afisEbat = 'a4';

    /** Firmalar listesinden "Acil Durum Planı" satır aksiyonuyla ?firma= ile gelinir. */
    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->pluck('unvan', 'id')
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    public function plan(): ?PlanModel
    {
        return $this->firma ? PlanModel::firmaIcin($this->firma) : null;
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma);

        $plan = $this->plan();

        if (! $plan) {
            return;
        }

        $this->dokumanNo = $plan->dokuman_no;
        $this->raporTarihi = $plan->rapor_tarihi?->toDateString() ?? now()->toDateString();
        $this->kapakCercevesi = $plan->kapak_cercevesi ?: 'klasik';
        $this->revizyonNo = $plan->revizyon_no;
        $this->toplanmaYeri = $plan->toplanma_yeri;
        $this->disaridanEtkileyebilecekIsyerleri = $plan->disaridan_etkileyebilecek_isyerleri;
        $this->konular = $plan->konular ?? [];
        $this->ekipMetni = collect(config('isg.acil_durum.ekipler'))
            ->mapWithKeys(fn ($ad, $k) => [$k => implode(', ', $plan->ekipListesi()[$k] ?? [])])
            ->all();
    }

    public function konuToggle(string $anahtar): void
    {
        $this->konular = in_array($anahtar, $this->konular, true)
            ? array_values(array_diff($this->konular, [$anahtar]))
            : [...$this->konular, $anahtar];
    }

    public function tumKonular(bool $sec): void
    {
        $this->konular = $sec
            ? collect(config('isg.acil_durum.konular'))->pluck('anahtar')->all()
            : [];
    }

    public function kaydet(): void
    {
        $plan = $this->plan();

        if (! $plan) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return;
        }

        $plan->forceFill([
            'dokuman_no' => $this->dokumanNo,
            'rapor_tarihi' => $this->raporTarihi,
            'gecerlilik_tarihi' => null, // saving hook yeniden hesaplar
            'kapak_cercevesi' => $this->kapakCercevesi,
            'revizyon_no' => $this->revizyonNo,
            'toplanma_yeri' => $this->toplanmaYeri,
            'disaridan_etkileyebilecek_isyerleri' => $this->disaridanEtkileyebilecekIsyerleri,
            'konular' => $this->konular ?: null,
            'ekipler' => collect($this->ekipMetni)
                ->map(fn ($metin) => array_values(array_filter(array_map('trim', explode(',', (string) $metin)))))
                ->all(),
        ])->save();

        Notification::make()->title('Acil durum planı kaydedildi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Plan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $this->kaydet();

                    return AcilDurumPlaniUretici::pdf($this->plan());
                }),

            Action::make('word')
                ->label('Word (Orijinal Şablon)')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->tooltip('Referans belgenin birebir kopyası; yalnızca firmaya özel bilgiler değişir, geri kalan metin/biçim aynen korunur.')
                ->action(function () {
                    $this->kaydet();

                    return AcilDurumWordUretici::docx($this->plan());
                }),

            Action::make('krokiPlani')
                ->label('Kroki Planı Hazırla')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->url(fn () => AcilDurumKrokisi::getUrl(['firma' => $this->firma->id])),

            Action::make('tahliyePlani')
                ->label('Tahliye Planı Görseli')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('İşyerinin kat/bölüm krokisi üzerine kaçış yolları, toplanma yeri ve acil durum ekipmanlarının işaretlendiği tahliye planı — İşyerlerinde Acil Durumlar Hakkında Yönetmelik gereği zorunludur.')
                ->fillForm(fn (): array => ['tahliye_plani_gorseli' => $this->plan()?->tahliye_plani_gorseli])
                ->schema([
                    FileUpload::make('tahliye_plani_gorseli')
                        ->label('Tahliye planı / kroki görseli')
                        ->image()->imageEditor()
                        ->disk('public')->directory('acil-durum-tahliye')->maxSize(4096),
                ])
                ->action(function (array $data): void {
                    $this->plan()?->forceFill($data)->save();
                    Notification::make()->title('Tahliye planı görseli kaydedildi')->success()->send();
                }),
        ];
    }

    public function afisIndir()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        return AcilDurumPlaniUretici::afis($this->firma, $this->afisTipi, $this->afisEbat);
    }
}
