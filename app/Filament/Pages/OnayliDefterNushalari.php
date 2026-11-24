<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\OnayliDefterNushasi;
use App\Support\TespitOneriDefteriUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Onaylı Defter Nüshaları — mehse'de yazılan Tespit ve Öneri Defteri'nin
 * imzalanmış / onaylı, İSG-KATİP'e yüklenen nüshalarının taranmış hâli.
 * Firma + defter türü başına nüsha numarası otomatik artar (1, 2, 3...).
 */
class OnayliDefterNushalari extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    use WithFileUploads;

    protected string $view = 'filament.pages.onayli-defter-nushalari';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'onayli-defter-nushalari';

    protected static ?string $title = 'Onaylı Defter Nüshaları';

    protected static ?string $navigationLabel = 'Onaylı Defter Nüshaları';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public string $defterTuru = 'tespit_oneri';

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma') ?: \App\Support\KomutaFirma::baslangic($this->firmalar)) {
            $this->firmaId = $firmaId;
        }
    }

    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    /** @return Collection<string, Collection<int, OnayliDefterNushasi>> defter türü etiketi => nüshalar */
    #[Computed]
    public function nushalar(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return $this->firma->onayliDefterNushalari()
            ->orderBy('defter_turu')
            ->orderBy('nusha_no')
            ->get()
            ->groupBy(fn (OnayliDefterNushasi $n) => $n->defterTuruEtiketi());
    }

    #[Computed]
    public function sonrakiNo(): int
    {
        return $this->firma
            ? OnayliDefterNushasi::sonrakiNo($this->firma->id, $this->defterTuru)
            : 1;
    }

    /** Seçili firma + defter türü için yükleme periyodu durumu (bekliyor/gecerli/yaklasan/dolmus). */
    #[Computed]
    public function durum(): ?string
    {
        return $this->firma
            ? OnayliDefterNushasi::durum($this->firma->id, $this->defterTuru)
            : null;
    }

    #[Computed]
    public function vadeTarihi(): ?Carbon
    {
        return $this->firma
            ? OnayliDefterNushasi::vadeTarihi($this->firma->id, $this->defterTuru)
            : null;
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
        unset($this->firma, $this->nushalar, $this->durum, $this->vadeTarihi);
    }

    public function updatedDefterTuru(): void
    {
        unset($this->nushalar, $this->durum, $this->vadeTarihi);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tespitOneriPdf')
                ->label('Defteri İndir')->tooltip('Tespit-Öneri Defterini İndir (imzalanmak üzere)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => filled($this->firma?->tespitOneriDefteri?->maddeler))
                ->action(fn () => TespitOneriDefteriUretici::pdf($this->firma->tespitOneriDefteri)),

            Action::make('nushaYukle')
                ->label('Onaylı Nüsha Yükle')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => $this->firma !== null)
                ->modalDescription('İmzalı defter nüshasının taranmış hâlini (PDF/görsel) yükleyin.')
                ->modalSubmitActionLabel('Kaydet')
                // Telefonda uzun formu sonuna kadar kaydırmadan kaydedebilmek için
                // başlık ve Kaydet/İptal şeridi hep görünür kalır.
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->schema([
                    FileUpload::make('dosya')
                        ->label('Taranmış nüsha')
                        ->disk('public')
                        ->directory(fn () => 'onayli-defter/'.$this->firmaId)
                        ->preserveFilenames()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(20480)
                        ->required(),
                    Grid::make(['default' => 2])->schema([
                        Select::make('defter_turu')
                            ->label('Defter türü')
                            ->options(config('isg.onayli_defter.turleri'))
                            ->default(fn () => $this->defterTuru)
                            ->native(false)
                            ->required()
                            ->live(),
                        TextInput::make('nusha_no_onizleme')
                            ->label('Nüsha no')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state, $get) => OnayliDefterNushasi::sonrakiNo(
                                $this->firma->id,
                                $get('defter_turu') ?: 'tespit_oneri',
                            ).'. Nüsha'),
                        DatePicker::make('onay_tarihi')->label('Onay tarihi')->default(now()),
                        TextInput::make('donem')->label('Dönem')->placeholder('örn. 2026/1. çeyrek'),
                        TextInput::make('seri_sayfa_no')->label('Seri / Sayfa No')->placeholder('örn. Seri A · Sayfa 1-5'),
                        TextInput::make('onay_makami')->label('Onay Makamı')->placeholder('Noter / Çalışma ve İş Kurumu İl Müd.'),
                    ]),
                    Textarea::make('aciklama')->label('Açıklama (opsiyonel)')->rows(1),
                ])
                ->action(function (array $data): void {
                    $firma = Firma::where('user_id', Filament::auth()->id())->find($this->firmaId);

                    if (! $firma) {
                        Notification::make()->title('Önce bir firma seçin')->danger()->send();

                        return;
                    }

                    $yol = $data['dosya'];
                    $turu = $data['defter_turu'] ?: 'tespit_oneri';
                    $aciklamaParcalari = array_filter([
                        filled($data['seri_sayfa_no'] ?? null) ? 'Seri/Sayfa: '.trim((string) $data['seri_sayfa_no']) : null,
                        filled($data['onay_makami'] ?? null) ? 'Onay Makamı: '.trim((string) $data['onay_makami']) : null,
                        filled($data['aciklama'] ?? null) ? trim((string) $data['aciklama']) : null,
                    ]);

                    $nusha = OnayliDefterNushasi::create([
                        'firma_id' => $firma->id,
                        'tespit_oneri_defteri_id' => $turu === 'tespit_oneri' ? $firma->tespitOneriDefteri?->id : null,
                        'defter_turu' => $turu,
                        'onay_tarihi' => $data['onay_tarihi'] ?: null,
                        'donem' => $data['donem'] ?: null,
                        'dosya_adi' => basename($yol),
                        'dosya_yolu' => $yol,
                        'boyut' => Storage::disk('public')->exists($yol) ? Storage::disk('public')->size($yol) : 0,
                        'aciklama' => $aciklamaParcalari !== [] ? implode(' · ', $aciklamaParcalari) : null,
                    ]);

                    unset($this->nushalar, $this->durum, $this->vadeTarihi);
                    Notification::make()->title($nusha->baslik().' kaydedildi')->success()->send();
                }),
        ];
    }

    public function nushaIndir(int $id)
    {
        $nusha = $this->firma?->onayliDefterNushalari()->find($id);

        if (! $nusha || ! $nusha->dosya_yolu || ! Storage::disk('public')->exists($nusha->dosya_yolu)) {
            Notification::make()->title('Dosya bulunamadı')->danger()->send();

            return null;
        }

        return Storage::disk('public')->download($nusha->dosya_yolu, $nusha->dosya_adi);
    }

    public function nushaSil(int $id): void
    {
        $nusha = $this->firma?->onayliDefterNushalari()->find($id);

        if (! $nusha) {
            return;
        }

        if ($nusha->dosya_yolu && Storage::disk('public')->exists($nusha->dosya_yolu)) {
            Storage::disk('public')->delete($nusha->dosya_yolu);
        }

        $nusha->delete();
        unset($this->nushalar, $this->durum, $this->vadeTarihi);
        Notification::make()->title('Nüsha silindi')->success()->send();
    }
}
