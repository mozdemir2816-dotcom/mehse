<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\FirmaEvrakPaketi;
use App\Support\FirmaEvrakZipUretici;
use App\Support\PortfoyKarne;
use App\Support\YasalTakvim;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İSG Komuta Merkezi — isgpratik 135-136.jpg. Portföy genelinde yasal uyum
 * takibi. 3 sekme: Günlük Akış / Firma Asistanı / Çalışan Asistanı.
 */
class KontrolMerkezi extends Page
{
    use \App\Filament\Concerns\AileAnaSayfasi;

    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.kontrol-merkezi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kontrol-merkezi';

    protected static ?string $title = 'İSG Komuta Merkezi';

    protected static ?string $navigationLabel = 'Kontrol Merkezi';

    public const SEKMELER = [
        'gunluk' => 'Günlük Akış',
        'firma' => 'Firma Asistanı',
        'calisan' => 'Çalışan Asistanı',
    ];

    #[Url(as: 'sekme')]
    public string $sekme = 'firma';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    /** Komuta Merkezi ortak firma seçimi (App\Support\KomutaFirma): null = Tüm Portföy. */
    public function mount(): void
    {
        $this->firmaId = \App\Support\KomutaFirma::baslangic($this->firmalar);
    }

    public function updatedFirmaId(): void
    {
        \App\Support\KomutaFirma::sec($this->firmaId);
    }

    /** Portföy kriter listesinde "eksik firmaları göster" için açık olan kriter anahtarı. */
    public ?string $acikKriter = null;

    public function sekmeSec(string $sekme): void
    {
        if (array_key_exists($sekme, self::SEKMELER)) {
            $this->sekme = $sekme;
        }
    }

    /** Bir kriter satırına tıklayınca eksik firma listesini aç/kapat. */
    public function kriterDetayAc(string $anahtar): void
    {
        $this->acikKriter = $this->acikKriter === $anahtar ? null : $anahtar;
    }

    /**
     * Açık kriteri henüz karşılamayan firmalar (id => unvan). Kullanıcı "işyeri
     * hekimi 1 firmada eksik" görünce hangi firma olduğunu tıkla-gör.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function acikKriterEksikFirmalar(): array
    {
        if ($this->acikKriter === null) {
            return [];
        }

        return PortfoyKarne::eksikFirmalar(Filament::auth()->id(), $this->acikKriter)
            ->mapWithKeys(fn (Firma $f) => [$f->id => $f->secimEtiketi()])
            ->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function firmalar(): array
    {
        return Firma::query()
            ->where('user_id', Filament::auth()->id())
            ->where('aktif', true)
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    #[Computed]
    public function ozet(): array
    {
        return PortfoyKarne::ozet(Filament::auth()->id());
    }

    /** Çalışan kaydı eksik firmalar (kayıtlı < bildirilen, TC / işe giriş boş) — çalışan listesine bağlantıyla. */
    #[Computed]
    public function calisanEksikleri(): array
    {
        $firmalar = Firma::query()->where('user_id', Filament::auth()->id())->where('aktif', true)->orderBy('unvan')->get();

        return array_map(fn (array $s) => [...$s, 'url' => \App\Filament\Resources\Calisans\CalisanResource::getUrl('index', ['tableFilters' => ['firma_id' => ['value' => $s['id']]]])],
            PortfoyKarne::calisanKaydiEksikFirmalar($firmalar));
    }

    #[Computed]
    public function kriterler(): array
    {
        return PortfoyKarne::kriterler(Filament::auth()->id());
    }

    /** Kriterler 3 mantıksal grupta (Temel Yasal Belgeler / Saha & Kurul / Sağlık & Takip). */
    #[Computed]
    public function kriterGruplari(): array
    {
        return PortfoyKarne::grupla($this->kriterler);
    }

    #[Computed]
    public function secilenFirma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function calisanKarne(): ?array
    {
        return $this->secilenFirma
            ? PortfoyKarne::calisanKarne($this->secilenFirma)
            : null;
    }

    #[Computed]
    public function ekipKontenjanTablosu(): array
    {
        return PortfoyKarne::ekipKontenjanTablosu(Filament::auth()->id(), $this->firmaId);
    }

    protected function getHeaderActions(): array
    {
        $bosFormlar = FirmaEvrakPaketi::bosFormlar();

        return [
            Action::make('hizliEvrakPaketi')
                ->label('📦 Hızlı Evrak Paketi (ZIP İndir)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->modalWidth('2xl')
                ->modalHeading('📦 Hızlı Evrak Paketi — Tek Tıkla Toplu ZIP İndir')
                ->modalDescription('Firmayı seçin; kayıtlı İSG evraklarını ve firma ünvanı basılı boş formları işaretleyip tek bir ZIP dosyası olarak indirin.')
                ->modalSubmitActionLabel('ZIP Olarak İndir')
                ->schema([
                    Select::make('firma_id')
                        ->label('Firma')
                        ->options(fn () => $this->firmalar)
                        ->default(fn () => $this->firmaId ?: array_key_first($this->firmalar))
                        ->live()
                        ->searchable()
                        ->required(),
                    Section::make('Kayıtlı Evraklar')
                        ->collapsible()
                        ->schema([
                            CheckboxList::make('kayitli')
                                ->hiddenLabel()
                                ->options(function (Get $get): array {
                                    $fId = (int) ($get('firma_id') ?: $this->firmaId);
                                    $f = $fId ? Firma::where('user_id', Filament::auth()->id())->find($fId) : null;

                                    return $f ? FirmaEvrakZipUretici::secenekler($f) : [];
                                })
                                ->bulkToggleable()
                                ->columns(1),
                        ]),
                    ...collect(FirmaEvrakPaketi::GRUPLAR)->map(fn (string $baslik, string $grup) => Section::make($baslik)
                        ->collapsible()
                        ->schema([
                            CheckboxList::make($grup)
                                ->hiddenLabel()
                                ->options($bosFormlar[$grup])
                                ->bulkToggleable()
                                ->columns(2),
                        ]))->values()->all(),
                ])
                ->action(function (array $data) {
                    $firma = Firma::where('user_id', Filament::auth()->id())->findOrFail($data['firma_id']);
                    unset($data['firma_id']);

                    if (! collect($data)->flatten()->filter()->count()) {
                        Notification::make()->title('En az bir evrak seçin')->warning()->send();

                        return null;
                    }

                    @set_time_limit(app()->runningInConsole() ? 0 : 300);

                    return FirmaEvrakPaketi::zip($firma, $data);
                }),
        ];
    }

    /** Günlük Akış penceresi (gün). */
    public const TAKVIM_GUN = 90;

    /**
     * Günlük Akış (13.11.2026): yalnız risk değerlendirmesi değil, tüm süreli yasal yükümlülükler —
     * süresi geçenler ve önümüzdeki 90 gün içinde dolanlar (App\Support\YasalTakvim).
     */
    #[Computed]
    public function yaklasanIsler(): array
    {
        return YasalTakvim::kalemler(Filament::auth()->id(), self::TAKVIM_GUN)
            ->map(fn (array $k) => [...$k, 'tarih' => $k['tarih']?->format('d.m.Y')])
            ->all();
    }
}
