<?php

namespace App\Filament\Pages;

use App\Support\ZiyaretTakvimi;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Saha Takvimi — portföy genelinde, tüm firmaların Ziyaret Programı'ndaki
 * tarihli kayıtlarını tek aylık takvimde gösteren salt-okunur görünüm
 * (App\Support\ZiyaretTakvimi). Önceden Profilim > Firma Ziyaretleri
 * sekmesindeydi, ayrı sayfaya taşındı.
 */
class SahaTakvimi extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.saha-takvimi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Genel';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'saha-takvimi';

    protected static ?string $title = 'Saha Takvimi';

    protected static ?string $navigationLabel = 'Saha Takvimi';

    #[Url(as: 'tarih')]
    public string $seciliTarih = '';

    #[Url(as: 'ay')]
    public string $gosterilenAy = '';

    public function mount(): void
    {
        $this->seciliTarih = $this->seciliTarih ?: now()->toDateString();
        $this->gosterilenAy = $this->gosterilenAy ?: now()->format('Y-m');
    }

    /** @return array<string, array<int, array{firma: \App\Models\Firma, amac: ?string, sure_saat: ?float, durum: string, program_id: int, ay_index: int, satir_index: int}>> */
    #[Computed]
    public function gunlukGruplar(): array
    {
        return ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id());
    }

    /** @return array{ziyaret: int, firma: int} */
    #[Computed]
    public function ozet(): array
    {
        return ZiyaretTakvimi::ozet(Filament::auth()->id());
    }

    /** @return array<int, array{firma: \App\Models\Firma, amac: ?string, sure_saat: ?float, durum: string}> */
    #[Computed]
    public function gunlukListe(): array
    {
        return $this->gunlukGruplar[$this->seciliTarih] ?? [];
    }

    /** O ay için gün → ['tamamlandi' => int, 'planlandi' => int] rozet sayıları. */
    #[Computed]
    public function ayRozetleri(): array
    {
        $ayBaslangic = Carbon::parse($this->gosterilenAy.'-01');
        $rozetler = [];

        for ($gun = 1; $gun <= $ayBaslangic->daysInMonth; $gun++) {
            $tarih = $ayBaslangic->copy()->day($gun)->toDateString();
            $girdiler = $this->gunlukGruplar[$tarih] ?? [];

            if (! $girdiler) {
                continue;
            }

            $rozetler[$tarih] = [
                'tamamlandi' => collect($girdiler)->where('durum', 'tamamlandi')->count(),
                'planlandi' => collect($girdiler)->where('durum', 'planlandi')->count(),
            ];
        }

        return $rozetler;
    }

    public function tarihSec(string $tarih): void
    {
        $this->seciliTarih = $tarih;
    }

    public function ayDegistir(int $fark): void
    {
        $this->gosterilenAy = Carbon::parse($this->gosterilenAy.'-01')->addMonths($fark)->format('Y-m');
    }

    public function buguneGit(): void
    {
        $this->gosterilenAy = now()->format('Y-m');
        $this->seciliTarih = now()->toDateString();
    }
}
