<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Ücretsiz E-Reçetem — BİLGİ SAYFASI. mehse'de gerçek e-Reçete/MEDULA
 * entegrasyonu yok (bu işlem işyeri hekiminin kendi doktor e-imzası ve
 * SGK yetkilendirmesiyle resmi Sağlık Bakanlığı sistemleri üzerinden
 * yapılır); süreç, uygunluk ve SSS burada anlatılır.
 */
class EReetem extends Page
{
    protected string $view = 'filament.pages.e-recetem';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';

    protected static string|UnitEnum|null $navigationGroup = 'Formlar & Belgeler';

    protected static ?int $navigationSort = 27;

    protected static ?string $slug = 'e-recetem';

    protected static ?string $title = 'Ücretsiz E-Reçetem';

    protected static ?string $navigationLabel = 'Ücretsiz E-Reçetem';

    public function isyeriHekimiHatirlat(): void
    {
        Notification::make()
            ->title('İşyeri Hekimi Ataması')
            ->body('Firma kaydınızda işyeri hekimi atanmışsa, e-reçete düzenleme yetkisi doğrudan hekiminize aittir. Atama yoksa "İSG Profesyonelleri" modülünden ekleyebilirsiniz.')
            ->info()
            ->send();
    }
}
