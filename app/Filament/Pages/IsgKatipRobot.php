<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * İSG-KATİP Robot — isgpratik 7-9.jpg. İSG-KATİP portalını otomatikleştiren Chrome
 * eklentisinin BİLGİ SAYFASI. mehse'de gerçek eklenti / portal erişimi yok:
 * gereksinimler + kurulum rehberi + bot kataloğu + günlük hak sayacı gösterilir,
 * "Kurmak için tıklayın" bildirim döndürür.
 */
class IsgKatipRobot extends Page
{
    protected string $view = 'filament.pages.isg-katip-robot';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'isg-katip-robot';

    protected static ?string $title = 'İSG-KATİP Robot';

    protected static ?string $navigationLabel = 'İSG-KATİP Robot';

    /** Eklenti gerçekte kurulu değil — daima false (stub). */
    public bool $eklentiBagli = false;

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
