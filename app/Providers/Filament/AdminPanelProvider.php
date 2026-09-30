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
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Tasarım sistemi (2026-09-14): kullanıcının verdiği birebir token seti
     * (lacivert sidebar/topbar, #2563EB birincil aksiyon, vb.) uygulandı —
     * önceki mor/amber-lacivert grup renklendirmesi (Md.1-10) bu tam
     * değişimle KALDIRILDI. Detaylar resources/css/filament/admin/theme.css'te.
     *
     * Sidebar artık ikon-şeridine daralmadığı (sabit 200px) için Filament
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarWidth('200px')
            ->colors([
                'primary' => Color::Blue,
            ])
            // Tasarım sistemi (2026-09-14): gövde — DM Sans, veri/belge no — JetBrains Mono.
            ->font('DM Sans', provider: GoogleFontProvider::class)
            ->monoFont('JetBrains Mono', provider: GoogleFontProvider::class)
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                        // Tek seferlik geçiş: panel açık temaya çevrildi, ama tarayıcıda daha önce
                        // kaydedilmiş "theme: dark" tercihi defaultThemeMode'u geçersiz kılıyordu
                        // (bkz. filament/resources/js/dark-mode.js — localStorage.getItem('theme')
                        // her zaman server varsayılanının önüne geçer). Alpine henüz başlamadan
                        // (alpine:init'ten ÖNCE, <head> en başında) localStorage'ı bir kereliğine
                        // "light"a zorluyoruz; sonrasında kullanıcı yine istediği temayı seçip
                        // kaydedebilir, bu script bir daha araya girmez.
                        (function () {
                            try {
                                if (!localStorage.getItem('mehse_acik_tema_geciti_20260904')) {
                                    localStorage.setItem('theme', 'light');
                                    localStorage.setItem('mehse_acik_tema_geciti_20260904', '1');
                                }
                            } catch (e) {}
                        })();

                        // Görsel tema (klasik / material / windows11 / saha) — ilk boyamadan
                        // önce <html data-mehse-tema> basılır ki sayfa önce lacivert açılıp
                        // sonra renk değiştirmesin. Seçici: filament.components.tema-secici.
                        window.mehseTemaUygula = function (tema) {
                            var gecerli = ['klasik', 'material', 'windows11', 'saha'];
                            if (gecerli.indexOf(tema) === -1) tema = 'klasik';
                            document.documentElement.dataset.mehseTema = tema;
                            try { localStorage.setItem('mehse_tema', tema); } catch (e) {}
                        };
                        (function () {
                            var tema = 'klasik';
                            try { tema = localStorage.getItem('mehse_tema') || 'klasik'; } catch (e) {}
                            window.mehseTemaUygula(tema);
                        })();
                    </script>
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700&display=swap">
                    HTML),
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
            // İSG dosyası klasör yapısına göre (isgpratik "İSG Klasöründe Olması Gerekenler")
            ->navigationGroups($this->navGruplari())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
