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

        // PhpWord varsayılanda metni XML-kaçışsız yazar: firma adında / metinde
        // "&" ya da "<" olunca .docx bozuk çıkar, Word açamaz. Hiçbir üretici ham
        // XML basmadığı için kaçış genel olarak açık (Kurul/JSA/Talimat Word).
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        // Tüm açılır pencerelerde (modal) başlık ve Kaydet/İptal şeridi sabit:
        // uzun formda kaydırınca da Kaydet ekranda kalır (kullanıcı isteği
        // 06.10.2026 — Çalışan Ekle, Kurul vb. "Kaydet aşağıda kalıyor").
        // Formlu sayfalarda da (Firma/Çalışan düzenle...) alt düğmeler yapışkan.
        Action::configureUsing(fn (Action $aksiyon) => $aksiyon->stickyModalHeader()->stickyModalFooter());
        BasePage::stickyFormActions();

        // Personel / firma / İGU-hekim bilgisi düzeltilince, bilginin kopyalandığı
        // evraklar (tutanak, atama yazısı, iş izni...) da güncellenir (06.10.2026).
        \App\Models\Calisan::updated(fn ($c) => \App\Support\BagliKayitGuncelleyici::calisanGuncellendi($c));
        \App\Models\Firma::updated(fn ($f) => \App\Support\BagliKayitGuncelleyici::firmaGuncellendi($f));
        \App\Models\IsgProfesyoneli::updated(fn ($p) => \App\Support\BagliKayitGuncelleyici::profesyonelGuncellendi($p));
        // Tablo içi "Düzenle" pencerelerinde kaydetme sonrası "X evrak güncellendi"
        // (düzenleme sayfaları için bkz. KaydetUstte::afterSave).
        \Filament\Actions\EditAction::configureUsing(fn ($a) => $a->after(fn () => \App\Support\BagliKayitGuncelleyici::bildir()));

        // Uzaktan eğitim portalına (kullanıcı kodu / e-posta) her giriş günlüğe düşer.
        Event::listen(Login::class, function (Login $e): void {
            if ($e->guard === 'calisan') {
                EgitimGirisi::kaydet((int) $e->user->getAuthIdentifier());
            }
        });
    }
}
