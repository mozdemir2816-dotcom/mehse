<?php

namespace App\Providers\Filament;

use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
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
     * Tasarım Md. 1: nötr (gri) skala saf griden mora hafif kaçırılmış — aynı Gray
     * paletinin lightness/chroma eğrisi, sadece hue Violet ailesine (~290°) kaydırılmış
     * ve düşük çakralık (chroma) biraz artırılmış (Gray'in ~0.03'ü yerine ~0.04) —
     * belirgin mor değil, "seçilmiş" bir nötr.
     */
    private const GrayVioletTint = [
        50 => 'oklch(0.985 0.006 290)',
        100 => 'oklch(0.967 0.008 290)',
        200 => 'oklch(0.928 0.012 290)',
        300 => 'oklch(0.872 0.018 290)',
        400 => 'oklch(0.707 0.032 290)',
        500 => 'oklch(0.551 0.038 290)',
        600 => 'oklch(0.446 0.042 290)',
        700 => 'oklch(0.373 0.046 290)',
        800 => 'oklch(0.278 0.044 290)',
        900 => 'oklch(0.21 0.045 290)',
        950 => 'oklch(0.13 0.038 290)',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(isSimple: false)
            ->brandName('mehse İSG')
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => Color::Violet,
                'gray' => self::GrayVioletTint,
            ])
            // Tasarım Md. 2: gövde/arayüz — Public Sans, veri/belge no — JetBrains Mono.
            ->font('Public Sans', provider: GoogleFontProvider::class)
            ->monoFont('JetBrains Mono', provider: GoogleFontProvider::class)
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                        // Tek seferlik geçiş: panel açık temaya (Md.1-4 tasarımı) çevrildi, ama
                        // tarayıcıda daha önce kaydedilmiş "theme: dark" tercihi defaultThemeMode'u
                        // geçersiz kılıyordu (bkz. filament/resources/js/dark-mode.js —
                        // localStorage.getItem('theme') her zaman server varsayılanının önüne geçer).
                        // Alpine henüz başlamadan (alpine:init'ten ÖNCE, <head> en başında) localStorage'ı
                        // bir kereliğine "light"a zorluyoruz; sonrasında kullanıcı yine istediği temayı
                        // seçip kaydedebilir, bu script bir daha araya girmez.
                        (function () {
                            try {
                                if (!localStorage.getItem('mehse_acik_tema_geciti_20260904')) {
                                    localStorage.setItem('theme', 'light');
                                    localStorage.setItem('mehse_acik_tema_geciti_20260904', '1');
                                }
                            } catch (e) {}
                        })();
                    </script>
                    HTML),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&display=swap" rel="stylesheet">
                    <style>
                        /* Tasarım Md. 2: başlıklarda Archivo — Public Sans'tan ayrışan, biraz daha resmi bir ağırlık. */
                        .fi-header-heading, .fi-section-header-heading, .fi-modal-heading,
                        .fi-simple-page .fi-simple-page-heading, .fi-widget h1, .fi-widget h2, .fi-widget h3 {
                            font-family: Archivo, 'Public Sans', sans-serif !important;
                            font-weight: 700;
                        }
                        /* Tasarım Md. 4: seçili/hover tablo satırında turkuaz vurgu (mor tamamen tek renk kalmasın). */
                        .fi-ta-row.fi-selected, .fi-ta-row[data-selected="true"] {
                            background-color: rgb(20 184 166 / .10) !important;
                            box-shadow: inset 2px 0 0 rgb(20 184 166) !important;
                        }
                    </style>
                    HTML),
            )
            ->navigationGroups([
                'Yönetim',
                'Risk Yönetimi',
                'Formlar & Belgeler',
                'Planlama & Arşiv',
            ])
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
