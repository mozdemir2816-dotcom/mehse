<?php

namespace App\Filament\Pages;

use App\Models\Firma;
use App\Models\TespitOneriDefteri as TespitOneriDefteriModel;
use App\Support\GeminiOneriDanismani;
use App\Support\TespitOneriDefteriUretici;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

/**
 * Tespit ve Öneri Defteri — isgpratik 65-66.jpg. Hazır katalogdan tek tıkla
 * madde ekleme + serbest (opsiyonel AI destekli) tespit/öneri yazma.
 */
class TespitOneriDefteri extends Page
{
    protected string $view = 'filament.pages.tespit-oneri-defteri';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 18;

    protected static ?string $slug = 'tespit-oneri-defteri';

    protected static ?string $title = 'Tespit ve Öneri Defteri';

    protected static ?string $navigationLabel = 'Tespit Öneri Defteri';

    public ?int $firmaId = null;

    public string $konuFiltre = '';

    public string $arama = '';

    public ?string $genelNot = null;

    // Serbest tespit/öneri yazma
    public ?string $serbestTespit = null;

    public ?string $serbestOneri = null;

    public function mount(): void
    {
        if ($firmaId = request()->integer('firma')) {
            $this->firmaId = $firmaId;
            $this->updatedFirmaId();
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

    #[Computed]
    public function defter(): ?TespitOneriDefteriModel
    {
        return $this->firma ? TespitOneriDefteriModel::firmaIcin($this->firma) : null;
    }

    #[Computed]
    public function katalog(): array
    {
        $katalog = config('isg.tespit_oneri.katalog', []);

        if (blank($this->konuFiltre) && blank($this->arama)) {
            return $katalog;
        }

        $sonuc = [];

        foreach ($katalog as $kategori => $maddeler) {
            if ($this->konuFiltre && $this->konuFiltre !== $kategori) {
                continue;
            }

            $filtrelenen = $this->arama
                ? array_values(array_filter($maddeler, fn ($m) => str_contains(mb_strtolower($m['tespit']), mb_strtolower($this->arama))))
                : $maddeler;

            if ($filtrelenen) {
                $sonuc[$kategori] = $filtrelenen;
            }
        }

        return $sonuc;
    }

    #[Computed]
    public function konular(): array
    {
        return array_keys(config('isg.tespit_oneri.katalog', []));
    }

    #[Computed]
    public function aiAktif(): bool
    {
        return GeminiOneriDanismani::aktifMi();
    }

    public function updatedFirmaId(): void
    {
        unset($this->firma, $this->defter);
        $this->genelNot = $this->defter()?->genel_not;
    }

    /*
    |--------------------------------------------------------------------------
    | Madde ekleme
    |--------------------------------------------------------------------------
    */

    public function katalogdanEkle(string $tespit): void
    {
        $d = $this->defter();

        if (! $d) {
            return;
        }

        $eklenecek = collect(config('isg.tespit_oneri.katalog', []))
            ->flatten(1)
            ->firstWhere('tespit', $tespit);

        if (! $eklenecek) {
            return;
        }

        $maddeler = $d->maddeler ?? [];
        $maddeler[] = [
            'tespit' => $eklenecek['tespit'],
            'oneri' => $eklenecek['oneri'],
            'dayanak' => $eklenecek['dayanak'] ?? null,
            'oncelik' => $eklenecek['oncelik'] ?? 'orta',
        ];
        $d->update(['maddeler' => $maddeler]);

        Notification::make()->title('Madde eklendi')->success()->send();
    }

    public function aiOnerisiAl(): void
    {
        if (blank($this->serbestTespit)) {
            return;
        }

        $oneri = GeminiOneriDanismani::oner($this->serbestTespit);

        if ($oneri) {
            $this->serbestOneri = $oneri;
        } else {
            Notification::make()->title('AI önerisi alınamadı')->warning()->send();
        }
    }

    public function serbestEkle(): void
    {
        $d = $this->defter();

        if (! $d || blank($this->serbestTespit) || blank($this->serbestOneri)) {
            Notification::make()->title('Tespit ve öneri metni gerekli')->danger()->send();

            return;
        }

        $maddeler = $d->maddeler ?? [];
        $maddeler[] = [
            'tespit' => $this->serbestTespit,
            'oneri' => $this->serbestOneri,
            'dayanak' => null,
            'oncelik' => 'orta',
        ];
        $d->update(['maddeler' => $maddeler]);

        $this->reset('serbestTespit', 'serbestOneri');
        Notification::make()->title('Madde eklendi')->success()->send();
    }

    public function maddeSil(int $index): void
    {
        $d = $this->defter();

        if (! $d) {
            return;
        }

        $maddeler = $d->maddeler ?? [];
        unset($maddeler[$index]);
        $d->update(['maddeler' => array_values($maddeler)]);
    }

    public function genelNotuKaydet(): void
    {
        $this->defter()?->update(['genel_not' => $this->genelNot]);
        Notification::make()->title('Not kaydedildi')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF Çıktı Al')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn () => $this->defter() !== null)
                ->action(fn () => TespitOneriDefteriUretici::pdf($this->defter())),
        ];
    }
}
