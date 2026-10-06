<?php

namespace App\Providers;

use App\Models\EgitimGirisi;
use Filament\Actions\Action;
use Filament\Pages\BasePage;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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

        // Tüm açılır pencerelerde (modal) başlık ve Kaydet/İptal şeridi sabit:
        // uzun formda kaydırınca da Kaydet ekranda kalır (kullanıcı isteği
        // 06.10.2026 — Çalışan Ekle, Kurul vb. "Kaydet aşağıda kalıyor").
        // Formlu sayfalarda da (Firma/Çalışan düzenle...) alt düğmeler yapışkan.
        Action::configureUsing(fn (Action $aksiyon) => $aksiyon->stickyModalHeader()->stickyModalFooter());
        BasePage::stickyFormActions();

        // Uzaktan eğitim portalına (kullanıcı kodu / e-posta) her giriş günlüğe düşer.
        Event::listen(Login::class, function (Login $e): void {
            if ($e->guard === 'calisan') {
                EgitimGirisi::kaydet((int) $e->user->getAuthIdentifier());
            }
        });
    }
}
