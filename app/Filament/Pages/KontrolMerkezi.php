<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Support\PortfoyKarne;
use App\Support\YasalTakvim;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
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
            ->pluck('unvan', 'id')
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
