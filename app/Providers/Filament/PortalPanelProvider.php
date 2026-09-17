<?php

namespace App\Providers\Filament;

use App\Filament\Portal\Auth\PortalLogin;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Uzaktan Eğitim Portalı — çalışanların (Calisan modeli, `calisan` guard) e-posta
 * + şifre ile girip kendilerine atanmış eğitimleri izlediği ayrı panel.
 */
class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('egitim')
            ->authGuard('calisan')
            ->login(PortalLogin::class)
            ->brandName('mehse')
            ->viteTheme('resources/css/filament/portal/theme.css')
            ->defaultThemeMode(ThemeMode::Light)
            ->topNavigation()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->font('DM Sans', provider: GoogleFontProvider::class)
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): HtmlString => new HtmlString('<span class="portal-brand-tag">Uzaktan Eğitim</span>'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <div class="portal-auth-brand">
                        <strong>mehse</strong>
                        <span>Uzaktan Eğitim Portalı</span>
                    </div>
                    HTML),
            )
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->pages([])
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
