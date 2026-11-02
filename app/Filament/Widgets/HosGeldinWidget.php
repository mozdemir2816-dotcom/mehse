<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AcilDurumKrokisi;
use App\Filament\Pages\HizliSahaBulgusu;
use App\Filament\Pages\KontrolMerkezi;
use App\Filament\Pages\RiskSihirbazi;
use App\Filament\Pages\SahaDenetimi;
use App\Filament\Resources\Firmas\FirmaResource;
use App\Models\Calisan;
use App\Models\Firma;
use App\Models\User;
use App\Support\BulguHavuzu;
use App\Support\GorevDurumu;
use App\Support\PortfoyKarne;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * Ana sayfa kokpiti (02.11.2026): tek şeritte selamlama + unvan rozeti + hızlı işlemler + evrak uyumu
 * halkası; altında tek satır 4 kart — Aktif Portföy, İSG-KATİP kapasitesi, Sözleşme durumu, Kritik DÖF.
 * Eski ayrık Portföy Özeti (3 kutu) ve görev panosundaki kapasite kartı / açıklama satırı buraya taşındı.
 */
class HosGeldinWidget extends Widget
{
    protected static ?int $sort = -10;

    protected string $view = 'filament.widgets.hos-geldin-widget';

    protected int|string|array $columnSpan = 'full';

    /** Sayfanın en üstü: tembel yükleme yok — açılışta boş şerit / titreme olmasın. */
    protected static bool $isLazy = false;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var User $kullanici */
        $kullanici = Filament::auth()->user();
        $saat = (int) now()->format('G');

        $ozet = PortfoyKarne::ozet($kullanici->id);
        $aktifFirmalar = Firma::query()->where('user_id', $kullanici->id)->where('aktif', true);

        return [
            'selam' => match (true) {
                $saat < 6 => 'İyi geceler',
                $saat < 12 => 'Günaydın',
                $saat < 18 => 'İyi günler',
                default => 'İyi akşamlar',
            },
            'ad' => trim((string) $kullanici->name),
            'unvan' => config('isg.uzman_unvanlari.'.$kullanici->unvan),
            'tarih' => now()->locale('tr')->translatedFormat('j F Y, l'),
            'firma' => (int) $ozet['firma'],
            'uyum' => (int) $ozet['uyum_yuzde'],
            'eylemler' => [
                ['ad' => 'Firma Ekle', 'ikon' => 'heroicon-o-plus', 'url' => FirmaResource::getUrl('index', ['action' => 'create'])],
                ['ad' => 'Risk Sihirbazı', 'ikon' => 'heroicon-o-sparkles', 'url' => RiskSihirbazi::getUrl()],
                ['ad' => 'Saha Denetimi', 'ikon' => 'heroicon-o-clipboard-document-list', 'url' => SahaDenetimi::getUrl()],
                ['ad' => 'Acil Durum / Kroki', 'ikon' => 'heroicon-o-map', 'url' => AcilDurumKrokisi::getUrl()],
            ],
            'kontrolUrl' => KontrolMerkezi::getUrl(),
            'portfoy' => [
                'firma' => (clone $aktifFirmalar)->count(),
                'calisan' => Calisan::query()->whereHas('firma', fn ($q) => $q->where('user_id', $kullanici->id))->where('aktif', true)->count(),
                'url' => FirmaResource::getUrl('index'),
            ],
            'sure' => GorevDurumu::sureOzeti($kullanici),
            'sozlesme' => [
                'geciken' => (clone $aktifFirmalar)->whereNotNull('sozlesme_bitis')->whereDate('sozlesme_bitis', '<', today())->count(),
                'yaklasan' => (clone $aktifFirmalar)->whereNotNull('sozlesme_bitis')->whereDate('sozlesme_bitis', '>=', today())
                    ->whereDate('sozlesme_bitis', '<=', today()->addDays(30))->count(),
                'url' => FirmaResource::getUrl('index'),
            ],
            'dof' => $this->dofOzeti((clone $aktifFirmalar)->pluck('id')->all()),
        ];
    }

    /**
     * Açık saha bulgusu / DÖF maddeleri: termini geçen ve yaklaşan (görev panosuyla aynı kaynak ve gün eşiği).
     *
     * @param  array<int, int>  $firmaIdler
     * @return array{geciken: int, yaklasan: int, url: string}
     */
    private function dofOzeti(array $firmaIdler): array
    {
        $bugun = today();
        $sinir = today()->addDays(GorevDurumu::yaklasanGun());
        $geciken = $yaklasan = 0;

        foreach ($firmaIdler as $id) {
            foreach (BulguHavuzu::aksiyonlar($id) as $a) {
                if (! $a['acik'] || ! $a['termin']) {
                    continue;
                }
                $termin = $a['termin']->copy()->startOfDay();
                if ($termin->lt($bugun)) {
                    $geciken++;
                } elseif ($termin->lte($sinir)) {
                    $yaklasan++;
                }
            }
        }

        return ['geciken' => $geciken, 'yaklasan' => $yaklasan, 'url' => HizliSahaBulgusu::getUrl()];
    }
}
