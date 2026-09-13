<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Paylaşımlı hosting'de public/ kökü bootstrap/app.php'de usePublicPath() ile
        // taşındı (bkz. o dosyadaki yorum); dompdf paketi bunu kullanmıyor, kendi
        // config'inde ayrıca base_path('public') deniyor — orada public/ olmadığı için
        // "Cannot resolve public path" atıyordu. Gerçek public_path()'i besliyoruz.
        config(['dompdf.public_path' => public_path()]);
    }
}
