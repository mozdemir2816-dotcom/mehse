<?php

namespace App\Filament\Pages;

use App\Models\OzelEkipmanTipi;
use App\Support\OzelEkipmanTipleri;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Toplu Makine Paketi (21.11.2026) — sistemde olmayan makineler için kontrol formu, çalışma
 * talimatı, risk maddeleri ve afiş yazısı Gemini sohbetinde (API kotası harcanmadan) üretilip
 * tek yapıştırmayla kurulur (App\Support\OzelEkipmanTipleri). Yalnız sahip hesap.
 */
class TopluMakinePaketi extends Page
{
    protected string $view = 'filament.pages.toplu-makine-paketi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static string|UnitEnum|null $navigationGroup = 'Günlük Saha İşi';

    /** Menüde gizli — Ekipman & Ölçüm sekme şeridinden açılır (App\Support\SayfaAileleri, 'ekipman'). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'toplu-makine-paketi';

    protected static ?string $title = 'Toplu Makine Paketi';

    public string $makineler = '';

    /** @var array<int, string> */
    public array $yeni = [];

    /** @var array<int, array{ad: string, tip: string}> */
    public array $mevcut = [];

    /** @var array<int, string> */
    public array $istemler = [];

    public string $cevap = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->sahipMi();
    }

    /** @return array<int, string> */
    public function oneriler(): array
    {
        return OzelEkipmanTipleri::paketsizFirmaEkipmanlari();
    }

    public function onerileriEkle(): void
    {
        $this->makineler = trim($this->makineler."\n".implode("\n", $this->oneriler()));
    }

    public function istemleriHazirla(): void
    {
        $ayrim = OzelEkipmanTipleri::ayir($this->makineler);
        $this->yeni = $ayrim['yeni'];
        $this->mevcut = $ayrim['mevcut'];
        $this->istemler = OzelEkipmanTipleri::istemler($this->yeni);

        if ($this->yeni === []) {
            Notification::make()->title('Yeni makine yok')->body($this->mevcut ? 'Yazdığınız makinelerin paketi sistemde zaten var.' : 'Önce makine adlarını yazın.')->warning()->send();
        }
    }

    public function kur(): void
    {
        $sonuc = OzelEkipmanTipleri::kur($this->cevap);

        if ($sonuc['kurulan']) {
            $this->cevap = '';
        }

        Notification::make()
            ->title(count($sonuc['kurulan']).' makine kuruldu')
            ->body($sonuc['hatalar'] ? implode("\n", $sonuc['hatalar']) : 'Afiş resimleri için aşağıdaki "Resim istemi"ni kullanın.')
            ->{$sonuc['hatalar'] ? 'warning' : 'success'}()
            ->persistent((bool) $sonuc['hatalar'])
            ->send();
    }

    public function onayla(int $id): void
    {
        OzelEkipmanTipleri::onayla(OzelEkipmanTipi::query()->findOrFail($id), auth()->id());
        Notification::make()->title('Paket onaylandı')->body('Talimat ve afiş yazısındaki taslak etiketi kalktı.')->success()->send();
    }

    /** @return array<int, array<string, mixed>> */
    public function durumlar(): array
    {
        return OzelEkipmanTipleri::durumlar();
    }

    public function resimIstemi(int $id): string
    {
        return OzelEkipmanTipleri::resimIstemi(OzelEkipmanTipi::query()->findOrFail($id));
    }
}
