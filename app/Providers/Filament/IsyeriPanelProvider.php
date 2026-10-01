<?php

namespace App\Providers\Filament;

use App\Filament\Isyeri\Auth\IsyeriLogin;
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
 * İşyeri (işveren) paneli — firmaya tanımlanan kalıcı e-posta + şifre ile
 * (IsyeriHesabi, `isyeri` guard) girilir; yalnız o firmanın evrakları
 * salt-okunur görüntülenir/indirilir. Tema dosyası portal ile ortak (ayrı
 * Vite girdisi gerekmesin diye); sayfa stilleri satır içi.
 */
class IsyeriPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('isyeri')
            ->path('isyeri')
            ->authGuard('isyeri')
            ->login(IsyeriLogin::class)
            ->brandName('mehse')
            ->viteTheme('resources/css/filament/portal/theme.css')
            ->defaultThemeMode(ThemeMode::Light)
            ->topNavigation()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->font('DM Sans', provider: GoogleFontProvider::class)
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): HtmlString => new HtmlString('<span class="portal-brand-tag">İşyeri Girişi</span>'),
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <div class="portal-auth-brand">
                        <strong>mehse</strong>
                        <span>İşyeri Evrak Görüntüleme</span>
                    </div>
                    HTML),
            )
            ->discoverPages(in: app_path('Filament/Isyeri/Pages'), for: 'App\Filament\Isyeri\Pages')
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
