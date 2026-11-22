<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

use App\Filament\Concerns\SinirliErisim;
/**
 * Ücretsiz E-Reçetem — BİLGİ SAYFASI. mehse'de gerçek e-Reçete/MEDULA
 * entegrasyonu yok (bu işlem işyeri hekiminin kendi doktor e-imzası ve
 * SGK yetkilendirmesiyle resmi Sağlık Bakanlığı sistemleri üzerinden
 * yapılır); süreç, uygunluk ve SSS burada anlatılır.
 */
class EReetem extends Page
{
    use \App\Filament\Concerns\SinirliErisim;

    protected string $view = 'filament.pages.e-recetem';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';

    protected static string|UnitEnum|null $navigationGroup = 'Planlama & Arşiv';

    /** Menüde gizli — ana sayfasının sekme şeridinden açılır (App\Support\SayfaAileleri). */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'e-recetem';

    protected static ?string $title = 'Ücretsiz E-Reçetem';

    protected static ?string $navigationLabel = 'Ücretsiz E-Reçetem';

    public function isyeriHekimiHatirlat(): void
    {
        $firmalar = \App\Models\Firma::query()
            ->where('user_id', \Filament\Facades\Filament::auth()->id())
            ->where('aktif', true)
            ->get(['id', 'unvan', 'isyeri_hekimi_id']);

        $toplam = $firmalar->count();
        $atanmis = $firmalar->whereNotNull('isyeri_hekimi_id')->count();
        $eksikler = $firmalar->whereNull('isyeri_hekimi_id');

        if ($toplam === 0) {
            Notification::make()
                ->title('Aktif Firma Kaydı Bulunamadı')
                ->body('Önce Firmalar modülünden işyeri kaydı ekleyip işyeri hekimi ataması yapabilirsiniz.')
                ->warning()
                ->send();

            return;
        }

        if ($eksikler->isEmpty()) {
            Notification::make()
                ->title("Tüm Aktif Firmalarda Hekim Atanmış ({$atanmis}/{$toplam})")
                ->body('e-Reçete düzenleme yetkisi doğrudan atanmış işyeri hekimlerinize aittir; hekiminiz Sağlık Bakanlığı e-Reçetem portalından reçete düzenleyebilir.')
                ->success()
                ->send();

            return;
        }

        $ornek = $eksikler->take(4)->pluck('unvan')->implode(', ');
        $fazla = $eksikler->count() > 4 ? ' ve '.($eksikler->count() - 4).' firma daha' : '';

        Notification::make()
            ->title("İşyeri Hekimi Atama Durumu: {$atanmis}/{$toplam} Firma")
            ->body("{$eksikler->count()} aktif firmada işyeri hekimi eksik ({$ornek}{$fazla}). İSG Profesyonelleri / Firma düzenleme ekranından hekim ataması yapabilirsiniz.")
            ->warning()
            ->send();
    }
}
