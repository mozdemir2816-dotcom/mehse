<?php

namespace App\Filament\Pages\Concerns;

use App\Support\ZiyaretTakvimi;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Portföy ajandası (eski Saha Takvimi): tüm firmaların Ziyaret Programı'ndaki tarihli kayıtları tek aylık takvimde.
 * Saha & Ziyaret Planlama Merkezi'nin "Genel Ajanda" görünümü kullanır (App\Support\ZiyaretTakvimi).
 */
trait PortfoyAjandasi
{
    #[Url(as: 'ajanda_tarih')]
    public string $seciliTarih = '';

    #[Url(as: 'ajanda_ay')]
    public string $gosterilenAy = '';

    protected function ajandaBaslat(): void
    {
        $this->seciliTarih = $this->seciliTarih ?: now()->toDateString();
        $this->gosterilenAy = $this->gosterilenAy ?: now()->format('Y-m');
    }

    /** @return array<string, array<int, array<string, mixed>>> tarih => ziyaretler */
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

    /** Portföyün aylık İSG-KATİP doluluğu: tüm aktif firmaların yasal süreleri toplamı / aylık kapasite (Ana Sayfa ile aynı hesap). */
    #[Computed]
    public function katipDoluluk(): array
    {
        return \App\Support\GorevDurumu::sureOzeti(Filament::auth()->user());
    }

    /** @return array<int, array<string, mixed>> */
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
