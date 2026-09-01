<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * Henüz kurulmamış modüller için ortak "hazırlanıyor" iskele sayfası.
 * Sol menü isgpratik 1-3.jpg'ye göre tam görünür; her modül ilgili ekran
 * görüntüsü gelince kendi sayfasıyla değiştirilir.
 */
abstract class HazirlanryorPage extends Page
{
    protected string $view = 'filament.pages.hazirlaniyor';

    /** Bu modülün hangi isgpratik ekranlarını takip edeceği. */
    protected static ?string $planNotu = null;

    /** Nav'da [AI] rozeti gösterilsin mi? */
    protected static bool $aiModulu = false;

    public static function getNavigationBadge(): ?string
    {
        return static::$aiModulu ? 'AI' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::$aiModulu ? 'info' : null;
    }

    public function getViewData(): array
    {
        return [
            'planNotu' => static::$planNotu,
            'aiModulu' => static::$aiModulu,
        ];
    }
}
