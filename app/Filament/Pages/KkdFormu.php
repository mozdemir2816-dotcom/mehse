<?php

namespace App\Filament\Pages;

use App\Models\Calisan;
use App\Models\Firma;
use App\Models\KkdZimmetFormu as KkdZimmetFormuModel;
use App\Support\KkdZimmetFormuUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * KKD Zimmet Formu — isgpratik 71-75.jpg. Çoklu çalışan × çoklu KKD seçimi
 * (6 kategori) → her çalışan için ayrı teslim tutanağı sayfası.
 */
class KkdFormu extends Page
{
    protected string $view = 'filament.pages.kkd-formu';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'kkd-formu';

    protected static ?string $title = 'KKD Zimmet Formu';

    protected static ?string $navigationLabel = 'KKD Formu';

    public ?int $firmaId = null;

    public ?string $teslimTarihi = null;

    public ?string $periyodikKontrolTarihi = null;

    public ?string $teslimEden = null;

    /** @var array<int, int> */
    public array $secilenCalisanIdler = [];

    public ?string $manuelAdSoyad = null;

    public ?string $manuelTc = null;

    public ?string $manuelDepartman = null;

    /** @var array<int, array{ad_soyad: string, tc: ?string, departman: ?string}> */
    public array $manuelCalisanlar = [];

    /** @var array<int, string> "kategori|ad" biçiminde seçilen KKD anahtarları */
    public array $secilenKkdler = [];

    public function mount(): void
    {
        $this->teslimTarihi = now()->toDateString();

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

    /** @return Collection<int, Calisan> */
    #[Computed]
    public function calisanlar(): Collection
    {
        return $this->firma?->calisanlar()->orderBy('ad_soyad')->get() ?? collect();
    }

    #[Computed]
    public function kategoriler(): array
    {
        return config('isg.kkd.kategoriler');
    }

    /** @return Collection<int, KkdZimmetFormuModel> */
    #[Computed]
    public function gecmisFormlar(): Collection
    {
        return $this->firma?->kkdZimmetFormlari()->latest()->get() ?? collect();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->calisanlar, $this->gecmisFormlar);
        $this->secilenCalisanIdler = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Çalışan seçimi
    |--------------------------------------------------------------------------
    */

    public function calisanToggle(int $id): void
    {
        $this->secilenCalisanIdler = in_array($id, $this->secilenCalisanIdler, true)
            ? array_values(array_diff($this->secilenCalisanIdler, [$id]))
            : [...$this->secilenCalisanIdler, $id];
    }

    public function manuelCalisanEkle(): void
    {
        if (blank($this->manuelAdSoyad)) {
            return;
        }

        $this->manuelCalisanlar[] = [
            'ad_soyad' => $this->manuelAdSoyad,
            'tc' => $this->manuelTc,
            'departman' => $this->manuelDepartman,
        ];

        $this->reset('manuelAdSoyad', 'manuelTc', 'manuelDepartman');
    }

    public function manuelCalisanSil(int $index): void
    {
        unset($this->manuelCalisanlar[$index]);
        $this->manuelCalisanlar = array_values($this->manuelCalisanlar);
    }

    /*
    |--------------------------------------------------------------------------
    | KKD seçimi
    |--------------------------------------------------------------------------
    */

    public function kkdToggle(string $kategori, string $ad): void
    {
        $anahtar = $kategori.'|'.$ad;

        $this->secilenKkdler = in_array($anahtar, $this->secilenKkdler, true)
            ? array_values(array_diff($this->secilenKkdler, [$anahtar]))
            : [...$this->secilenKkdler, $anahtar];
    }

    public function kategoriTumunuSec(string $kategori): void
    {
        $maddeler = collect($this->kategoriler()[$kategori]['maddeler'] ?? [])->pluck('ad');
        $yeniAnahtarlar = $maddeler->map(fn ($ad) => $kategori.'|'.$ad)->all();

        $this->secilenKkdler = array_values(array_unique([...$this->secilenKkdler, ...$yeniAnahtarlar]));
    }

    public function kkdSecimiTemizle(): void
    {
        $this->secilenKkdler = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Kaydet & PDF
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array{ad_soyad: string, tc: ?string, departman: ?string}> */
    private function calisanlariTopla(): array
    {
        $firmaCalisanlari = $this->calisanlar
            ->whereIn('id', $this->secilenCalisanIdler)
            ->map(fn (Calisan $c) => ['ad_soyad' => $c->ad_soyad, 'tc' => $c->tc, 'departman' => $c->gorev])
            ->values()
            ->all();

        return [...$firmaCalisanlari, ...$this->manuelCalisanlar];
    }

    /** @return array<int, array{ad: string, standart: string, kategori: string}> */
    private function kkdleriTopla(): array
    {
        $kategoriler = $this->kategoriler();

        return collect($this->secilenKkdler)
            ->map(function (string $anahtar) use ($kategoriler) {
                [$kategori, $ad] = explode('|', $anahtar, 2);
                $madde = collect($kategoriler[$kategori]['maddeler'] ?? [])->firstWhere('ad', $ad);

                return $madde ? ['ad' => $ad, 'standart' => $madde['standart'], 'kategori' => $kategoriler[$kategori]['ad']] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function kaydet(): ?KkdZimmetFormuModel
    {
        if (! $this->firma || ! $this->teslimTarihi) {
            Notification::make()->title('Firma ve teslim tarihi zorunlu')->danger()->send();

            return null;
        }

        $calisanlar = $this->calisanlariTopla();
        $kkdler = $this->kkdleriTopla();

        if (! $calisanlar || ! $kkdler) {
            Notification::make()->title('En az bir çalışan ve bir KKD seçin')->danger()->send();

            return null;
        }

        $form = new KkdZimmetFormuModel([
            'firma_id' => $this->firma->id,
            'teslim_tarihi' => $this->teslimTarihi,
            'periyodik_kontrol_tarihi' => $this->periyodikKontrolTarihi,
            'teslim_eden' => $this->teslimEden,
            'calisanlar' => $calisanlar,
            'kkdler' => $kkdler,
        ]);
        $form->save();

        unset($this->gecmisFormlar);

        return $form;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF Oluştur (Kaydet)')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->firma !== null)
                ->action(function () {
                    $form = $this->kaydet();

                    return $form ? KkdZimmetFormuUretici::pdf($form) : null;
                }),
        ];
    }

    public function gecmisPdf(int $id)
    {
        $form = $this->firma?->kkdZimmetFormlari()->find($id);

        return $form ? KkdZimmetFormuUretici::pdf($form) : null;
    }

    public function gecmisSil(int $id): void
    {
        $this->firma?->kkdZimmetFormlari()->find($id)?->delete();
        unset($this->gecmisFormlar);
    }
}
