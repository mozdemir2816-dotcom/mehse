<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * İSG-KATİP Robot — isgpratik 7-9.jpg. İSG-KATİP portalını otomatikleştiren Chrome
 * eklentisinin BİLGİ SAYFASI. mehse'de gerçek eklenti / portal erişimi yok:
 * gereksinimler + kurulum rehberi + bot kataloğu + günlük hak sayacı gösterilir,
 * "Kurmak için tıklayın" bildirim döndürür — tek istisna "hazir" işaretli bot(lar),
 * bkz. botKur(): bunlar gerçekten çalışır (IsgKatipSozlesmeleri'ne yönlendirir).
 */
class IsgKatipRobot extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.isg-katip-robot';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'Firmalar & Çalışanlar';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'isg-katip-robot';

    protected static ?string $title = 'İBYS Veri Aktarımı (XML / JSON)';

    protected static ?string $navigationLabel = 'İBYS Veri Aktarımı';

    /** Eklenti gerçekte kurulu değil — daima false (stub). */
    public bool $eklentiBagli = false;

    /** İBYS uyumlu aktarım paketi için seçili firma (K6). */
    public ?int $ibysFirmaId = null;

    #[\Livewire\Attributes\Computed]
    public function firmalar(): array
    {
        return \App\Models\Firma::query()
            ->where('user_id', \Filament\Facades\Filament::auth()->id())
            ->where('aktif', true)
            ->orderBy('unvan')
            ->secenekler()
            ->all();
    }

    public function ibysIndir(string $bicim = 'xml')
    {
        $firma = $this->ibysFirmaId
            ? \App\Models\Firma::where('user_id', \Filament\Facades\Filament::auth()->id())->find($this->ibysFirmaId)
            : null;

        if (! $firma) {
            Notification::make()->title('Önce bir firma seçin')->warning()->send();

            return null;
        }

        \App\Support\DenetimKaydedici::kaydet('İBYS Aktarım', 'indirdi', $firma->unvan.' İBYS '.strtoupper($bicim).' paketi indirildi', $firma->id);

        return $bicim === 'json'
            ? \App\Support\IbysAktarimUretici::jsonIndir($firma)
            : \App\Support\IbysAktarimUretici::xmlIndir($firma);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('eklentiIndir')
                ->label('Eklentiyi İndir')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->stubBildirim('Chrome Web Store bağlantısı yakında eklenecek.')),

            Action::make('baglantiKontrol')
                ->label('Bağlantıyı Kontrol Et')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->action(fn () => $this->stubBildirim('Eklenti henüz kurulu değil — bağlantı kurulamadı.')),
        ];
    }

    public function botKur(string $ad): void
    {
        $bot = collect(config('isg.isg_katip.botlar'))->firstWhere('ad', $ad);

        if (data_get($bot, 'hazir', false)) {
            $this->redirect(IsgKatipSozlesmeleri::getUrl());

            return;
        }

        $this->stubBildirim('"'.$ad.'" botu için Chrome eklentisi gerekli. Eklenti yayınlanınca aktifleşecek.');
    }

    private function stubBildirim(string $mesaj): void
    {
        Notification::make()
            ->title('İSG-KATİP Robot')
            ->body($mesaj)
            ->warning()
            ->send();
    }
}
