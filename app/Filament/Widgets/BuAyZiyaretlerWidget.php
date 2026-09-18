<?php

namespace App\Filament\Widgets;

use App\Models\ZiyaretProgrami;
use App\Support\ZiyaretTakvimi;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

/**
 * Panel ana sayfası — bu ayın ziyaret takvimi + o ay gidilmesi planlanan
 * firmaların toplu listesi. Kaynak ZiyaretTakvimi::gunlukGruplar() ile
 * Profilim > Firma Ziyaretleri ve Ziyaret Programı sayfalarıyla AYNIdır,
 * ayrı bir sorgu/gruplama tekrarlanmaz.
 */
class BuAyZiyaretlerWidget extends Widget
{
    protected string $view = 'filament.widgets.bu-ay-ziyaretler-widget';

    protected int|string|array $columnSpan = 'full';

    public string $gosterilenAy = '';

    public ?string $seciliTarih = null;

    public function mount(): void
    {
        $this->gosterilenAy = now()->format('Y-m');
    }

    #[Computed]
    public function gunlukGruplar(): array
    {
        return ZiyaretTakvimi::gunlukGruplar(Filament::auth()->id());
    }

    /**
     * Gösterilen aya ait ziyaretler, tarihe göre sıralı — bir gün seçiliyse
     * sadece o güne süzülür, aksi halde ayın tamamı listelenir.
     *
     * @return array<int, array{firma: mixed, amac: ?string, sure_saat: mixed, durum: string, program_id: int, ay_index: int, satir_index: int, tarih: string}>
     */
    #[Computed]
    public function ayinZiyaretleri(): array
    {
        $sonuc = [];

        foreach ($this->gunlukGruplar as $tarih => $oGunkuZiyaretler) {
            if (! str_starts_with($tarih, $this->gosterilenAy)) {
                continue;
            }

            if ($this->seciliTarih && $tarih !== $this->seciliTarih) {
                continue;
            }

            foreach ($oGunkuZiyaretler as $z) {
                $sonuc[] = $z + ['tarih' => $tarih];
            }
        }

        usort($sonuc, fn (array $a, array $b) => $a['tarih'] <=> $b['tarih']);

        return $sonuc;
    }

    public function ayDegistir(int $fark): void
    {
        $this->gosterilenAy = Carbon::parse($this->gosterilenAy.'-01')->addMonths($fark)->format('Y-m');
        $this->seciliTarih = null;
        unset($this->ayinZiyaretleri);
    }

    public function gunSec(string $tarih): void
    {
        $this->seciliTarih = $this->seciliTarih === $tarih ? null : $tarih;
        unset($this->ayinZiyaretleri);
    }

    public function durumDegistir(int $programId, int $ayIndex, int $satirIndex): void
    {
        ZiyaretProgrami::query()
            ->whereHas('firma', fn ($q) => $q->where('user_id', Filament::auth()->id()))
            ->find($programId)
            ?->durumIlerlet($ayIndex, $satirIndex);

        unset($this->gunlukGruplar, $this->ayinZiyaretleri);
    }
}
