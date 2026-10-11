<?php

namespace App\Filament\Concerns;

use App\Support\SayfaAileleri;

/**
 * Sayfa ailesinin menüde görünen ana sayfası (App\Support\SayfaAileleri) —
 * ailenin menüden gizli sekmelerinden biri açıkken de sol menüde vurgulu kalır.
 */
trait AileAnaSayfasi
{
    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return SayfaAileleri::aktifRotalar(static::class) ?: parent::getNavigationItemActiveRoutePattern();
    }
}
