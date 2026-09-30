<?php

namespace App\Providers\Filament;

use App\Filament\Auth\KayitOl;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Tasarım sistemi (2026-09-14): kullanıcının verdiği birebir token seti
     * (lacivert sidebar/topbar, #2563EB birincil aksiyon, vb.) uygulandı —
     * önceki mor/amber-lacivert grup renklendirmesi (Md.1-10) bu tam
     * değişimle KALDIRILDI. Detaylar resources/css/filament/admin/theme.css'te.
     *
     * Sidebar artık ikon-şeridine daralmadığı (sabit genişlik, 240px) için Filament
     * grup İKONU ile madde ikonlarının aynı anda kullanılmasına izin vermiyor
     * ("Either the group or its items can have icons, but not both") — madde
     * bazlı ikonlar (her Resource/Page kendi navigationIcon'unu tanımlıyor)
     * daha faydalı olduğundan grup ikonları kaldırıldı, yalnız sıralama kaldı.
     */
    private const NavGrupSirasi = [
        'Yönetim',
        'Risk Değerlendirmesi',
        'Acil Durum & Yangın',
        'Eğitimler',
        'Çalışan & Kurul',
        'Sağlık Gözetimi',
        'Saha Kontrolleri',
        'Periyodik Kontrol & Ölçüm',
        'KKD',
        'İş Kazaları & Olaylar',
        'Planlama & Arşiv',
        'Diğer Belge & Yazışma',
    ];

    /**
     * @return array<NavigationGroup>
     */
    private function navGruplari(): array
    {
        return array_map(
            fn (string $etiket): NavigationGroup => NavigationGroup::make($etiket),
            self::NavGrupSirasi,
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(KayitOl::class)
            ->profile(isSimple: false)
            ->brandName('mehse İSG')
            // Kalkan rozetli marka bloğu (sidebar başlığı, mobil topbar, giriş ekranı).
            ->brandLogo(fn () => view('filament.components.marka'))
            ->brandLogoHeight('2.5rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultThemeMode(ThemeMode::Light)
            // 200px → 240px (2026-09-30): Türkçe menü adları ("Periyodik Kontrol &
            // Ölçüm", "İş Kazaları & Olaylar") 200px'te iki satıra kırılıyordu.
            ->sidebarWidth('240px')
            ->colors([
                'primary' => Color::Blue,
            ])
            // Tasarım sistemi (2026-09-14): gövde — DM Sans, veri/belge no — JetBrains Mono.
            ->font('DM Sans', provider: GoogleFontProvider::class)
            ->monoFont('JetBrains Mono', provider: GoogleFontProvider::class)
            // <head> başı: koyu mod geçişi + görsel tema/yoğunluk/yazı boyutu (hesaba
            // kayıtlı ayarlar ilk boyamadan önce <html data-mehse-*> olarak basılır) +
            // tema fontları. Bkz. resources/views/filament/components/head-ayarlar.blade.php
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => view('filament.components.head-ayarlar'),
            )
            // Görsel tema seçici — topbar'da kullanıcı avatarının solunda.
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.components.tema-secici'),
            )
            // Mobil alt navigasyon çubuğu (tasarım sistemi 2026-09-14) — yalnız
            // küçük ekranda görünür (bkz. theme.css .fi-mobil-alt-nav); 5 sekme
            // kullanıcıyla birlikte seçildi: Kontrol Merkezi, Firmalar, Risk
            // Değerlendirme, Saha Denetimi, Tespit & Öneri Defteri.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.mobil-alt-nav'),
            )
            // Sidebar altı: oturumdaki uzman + Ayarlar kısayolu.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.components.sidebar-alt'),
            )
            // İSG dosyası klasör yapısına göre (isgpratik "İSG Klasöründe Olması Gerekenler")
            ->navigationGroups($this->navGruplari())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Filament'in AccountWidget'ı yerine HosGeldinWidget (karşılama bandı) kullanılıyor.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
