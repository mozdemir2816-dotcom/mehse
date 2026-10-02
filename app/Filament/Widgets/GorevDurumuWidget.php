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

    private function kullanici(): User
    {
        /** @var User */
        return Filament::auth()->user();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function tumGorevler(): Collection
    {
        return GorevDurumu::gorevler($this->kullanici());
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
            ['ad' => 'Risk ve DÖF', 'url' => DofOlustur::getUrl()],
            ['ad' => 'Eğitim uygunluğu', 'url' => EgitimYenilemeTakibi::getUrl()],
            ['ad' => 'KKD yenilemeleri', 'url' => KkdTakip::getUrl()],
            ['ad' => 'Acil durum planı', 'url' => AcilDurumPlani::getUrl()],
            ['ad' => 'Ortam ölçümleri', 'url' => OrtamOlcumleri::getUrl()],
            ['ad' => 'Kontrol Merkezi', 'url' => KontrolMerkezi::getUrl()],
        ];
    }

    public function yenile(): void
    {
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
