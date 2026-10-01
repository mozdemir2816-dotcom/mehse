<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        // Yeni şifreler (kayıt, profil, Güvenlik sayfası) en az 10 karakter — mevcut
        // şifreler etkilenmez, yalnız değiştirilirken uygulanır.
        Password::defaults(fn () => Password::min(10));

        // Paylaşımlı hosting'de public/ kökü bootstrap/app.php'de usePublicPath() ile
        // taşındı (bkz. o dosyadaki yorum); dompdf paketi bunu kullanmıyor, kendi
        // config'inde ayrıca base_path('public') deniyor — orada public/ olmadığı için
        // "Cannot resolve public path" atıyordu. Gerçek public_path()'i besliyoruz.
        config(['dompdf.public_path' => public_path()]);
    }
}
