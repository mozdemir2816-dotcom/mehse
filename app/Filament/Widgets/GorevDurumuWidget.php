<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AcilDurumPlani;
use App\Filament\Pages\DofOlustur;
use App\Filament\Pages\EgitimYenilemeTakibi;
use App\Filament\Pages\KkdTakip;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\OrtamOlcumleri;
use App\Models\User;
use App\Support\EgitimTakibi;
use App\Support\GorevDurumu;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Ana Sayfa — görev durumu (isgsuite): aylık görevlendirme süresi, hızlı
 * çalışma akışları, görev araması, Günü geçen / Yaklaşan / Yapılmayan /
 * Yapılan sayaçları ve dört sütunlu görev panosu + düz metin durum raporu.
 */
class GorevDurumuWidget extends Widget
{
    protected static ?int $sort = -5;

    protected string $view = 'filament.widgets.gorev-durumu-widget';

    protected int|string|array $columnSpan = 'full';

    public string $arama = '';

    /** Pano sekmesi: kritik (günü geçen + yaklaşan, varsayılan) | yapilmayan | yapilan. */
    public string $pano = 'kritik';

    private function kullanici(): User
    {
        /** @var User */
        return Filament::auth()->user();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function tumGorevler(): Collection
    {
        return GorevDurumu::gorevlerOnbellekli($this->kullanici());
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function gorevler(): Collection
    {
        $aranan = EgitimTakibi::adAnahtari($this->arama);

        return $aranan === ''
            ? $this->tumGorevler
            : $this->tumGorevler->filter(fn (array $g) => str_contains(
                EgitimTakibi::adAnahtari(implode(' ', [$g['firma'], $g['baslik'], $g['aciklama'], $g['modul'], $g['dayanak']])),
                $aranan,
            ))->values();
    }

    /**
     * Portföy şeridi (Pano teması, 13.11.2026): her aktif firma bir çubuk, en kötü görev durumuna göre:
     * gecikti (günü geçen var) · yaklasiyor (yaklaşan var) · tamam.
     *
     * @return array<int, array{id: int, ad: string, durum: string}>
     */
    #[Computed]
    public function portfoySeridi(): array
    {
        $durumlar = $this->tumGorevler->groupBy('firma_id')->map(fn (Collection $g) => match (true) {
            $g->contains('durum', 'gecikmis') => 'gecikti',
            $g->contains('durum', 'yaklasan') => 'yaklasiyor',
            default => 'tamam',
        });

        return \App\Models\Firma::query()->where('user_id', $this->kullanici()->id)->where('aktif', true)
            ->orderBy('unvan')->get(['id', 'unvan', 'kisa_ad'])
            ->map(fn (\App\Models\Firma $f) => [
                'id' => $f->id,
                'unvan' => (string) $f->unvan,
                'ad' => (string) ($f->kisa_ad ?: $f->unvan),
                'durum' => $durumlar[$f->id] ?? 'tamam',
            ])->all();
    }

    /** Şeritte bir firmaya tıklanınca görevler o firmaya süzülür; aynı firmaya tekrar tıklamak süzgeci kaldırır. */
    public function firmaSec(int $id): void
    {
        $f = collect($this->portfoySeridi)->firstWhere('id', $id);
        if (! $f) {
            return;
        }

        $this->arama = $this->arama === $f['unvan'] ? '' : $f['unvan'];
        unset($this->gorevler, $this->ozet);

        if ($this->arama !== '' && $f['durum'] === 'tamam') {
            $this->pano = 'yapilmayan';
        }
    }

    #[Computed]
    public function ozet(): array
    {
        return GorevDurumu::ozet($this->gorevler);
    }

    #[Computed]
    public function sure(): array
    {
        return GorevDurumu::sureOzeti($this->kullanici());
    }

    /** @return array<int, array{ad: string, url: string}> */
    public function hizliAkislar(): array
    {
        return [
            ['ad' => 'Risk ve DÖF', 'ikon' => 'heroicon-o-exclamation-triangle', 'url' => DofOlustur::getUrl()],
            ['ad' => 'Eğitim uygunluğu', 'ikon' => 'heroicon-o-academic-cap', 'url' => EgitimYenilemeTakibi::getUrl()],
            ['ad' => 'KKD yenilemeleri', 'ikon' => 'heroicon-o-shield-check', 'url' => KkdTakip::getUrl()],
            ['ad' => 'Acil durum planı', 'ikon' => 'heroicon-o-fire', 'url' => AcilDurumPlani::getUrl()],
            ['ad' => 'Ortam ölçümleri', 'ikon' => 'heroicon-o-beaker', 'url' => OrtamOlcumleri::getUrl()],
            ['ad' => 'Kontrol Merkezi', 'ikon' => 'heroicon-o-squares-2x2', 'url' => KontrolMerkezi::getUrl()],
        ];
    }

    public function panoSec(string $pano): void
    {
        $this->pano = in_array($pano, ['kritik', 'yapilmayan', 'yapilan'], true) ? $pano : 'kritik';
    }

    public function yenile(): void
    {
        GorevDurumu::onbellegiTemizle($this->kullanici());
        unset($this->tumGorevler, $this->gorevler, $this->ozet, $this->sure);
    }

    public function updatedArama(): void
    {
        unset($this->gorevler, $this->ozet);
    }

    public function durumRaporu()
    {
        $metin = GorevDurumu::durumRaporu($this->kullanici());

        return response()->streamDownload(fn () => print ("\xEF\xBB\xBF".$metin), 'gorev-durumu-'.now()->format('Y-m-d').'.txt', ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
