<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SinirliErisim;
use App\Models\Calisan;
use App\Models\Firma;
use App\Support\GeciciGorevlendirmeUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Geçici Görevlendirme & Dış Saha Görev Emri — 4 yasal şablonda çoklu çalışan
 * destekli resmi görevlendirme yazısı ve İSG taahhütnamesi PDF üreticisi.
 */
class GeciciGorevlendirme extends Page
{
    use SinirliErisim;

    protected string $view = 'filament.pages.gecici-gorevlendirme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|UnitEnum|null $navigationGroup = 'Belgeler & Yazışmalar';

    /** Menüde gizli — Resmi Belge & Kurul Yönetim Merkezi sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'gecici-gorevlendirme';

    protected static ?string $title = 'Geçici Görevlendirme';

    protected static ?string $navigationLabel = 'Geçici Görevlendirme';

    #[Url(as: 'firma')]
    public ?int $firmaId = null;

    public string $sablon = 'santiye';

    public string $gorevYeri = '';

    public string $gorevKonusu = '';

    public string $baslangicTarihi = '';

    public ?string $bitisTarihi = null;

    public ?string $harcirahNotu = null;

    public ?string $aracPlaka = null;

    public bool $tcMaskele = false;

    public ?string $canliImza = null;

    /** @var array<int, int> */
    public array $secilenCalisanIdler = [];

    /** @var array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    public array $manuelCalisanlar = [];

    public ?string $yeniAdSoyad = null;

    public ?string $yeniTc = null;

    public ?string $yeniGorev = null;

    public function mount(): void
    {
        $this->baslangicTarihi = now()->toDateString();

        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
        }
    }

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
    public function firma(): ?Firma
    {
        return $this->firmaId
            ? Firma::where('user_id', Filament::auth()->id())->find($this->firmaId)
            : null;
    }

    #[Computed]
    public function calisanlar(): Collection
    {
        if (! $this->firma) {
            return collect();
        }

        return $this->firma->calisanlar()->where('aktif', true)->orderBy('ad_soyad')->get();
    }

    public function updatedFirmaId(): void
    {
        $this->secilenCalisanIdler = [];
    }

    public function tumCalisanlariSec(bool $sec): void
    {
        $this->secilenCalisanIdler = $sec ? $this->calisanlar->pluck('id')->all() : [];
    }

    public function manuelCalisanEkle(): void
    {
        $this->validate(['yeniAdSoyad' => 'required|string|max:190']);

        $this->manuelCalisanlar[] = [
            'ad_soyad' => trim((string) $this->yeniAdSoyad),
            'tc' => $this->yeniTc ? trim($this->yeniTc) : null,
            'gorev' => $this->yeniGorev ? trim($this->yeniGorev) : null,
        ];

        $this->reset('yeniAdSoyad', 'yeniTc', 'yeniGorev');
    }

    public function manuelCalisanSil(int $index): void
    {
        unset($this->manuelCalisanlar[$index]);
        $this->manuelCalisanlar = array_values($this->manuelCalisanlar);
    }

    /** @return array<int, array{ad_soyad: string, tc: ?string, gorev: ?string}> */
    public function seciliCalisanListesi(): array
    {
        $kayitli = $this->calisanlar
            ->whereIn('id', $this->secilenCalisanIdler)
            ->map(fn (Calisan $c) => [
                'ad_soyad' => $c->ad_soyad,
                'tc' => $c->tc,
                'gorev' => $c->gorev,
            ])
            ->values()
            ->all();

        return [...$kayitli, ...$this->manuelCalisanlar];
    }

    public function pdfIndir()
    {
        if (! $this->firma) {
            Notification::make()->title('Önce firma seçin')->danger()->send();

            return null;
        }

        if (blank($this->gorevYeri) || blank($this->gorevKonusu)) {
            Notification::make()->title('Görev yeri ve görev konusu zorunludur')->warning()->send();

            return null;
        }

        $liste = $this->seciliCalisanListesi();
        if (! $liste) {
            Notification::make()->title('En az bir çalışan seçin veya elle ekleyin')->warning()->send();

            return null;
        }

        return GeciciGorevlendirmeUretici::pdf($this->firma, [
            'sablon' => $this->sablon,
            'gorev_yeri' => trim($this->gorevYeri),
            'gorev_konusu' => trim($this->gorevKonusu),
            'baslangic_tarihi' => $this->baslangicTarihi ?: now()->toDateString(),
            'bitis_tarihi' => $this->bitisTarihi,
            'harcirah_notu' => $this->harcirahNotu,
            'arac_plaka' => $this->aracPlaka,
            'tc_maskele' => $this->tcMaskele,
            'canli_imza' => $this->canliImza,
            'calisanlar' => $liste,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdfIndir')
                ->label('Görevlendirme PDF İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn () => $this->pdfIndir()),
        ];
    }
}
