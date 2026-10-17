<?php

namespace App\Filament\Pages;

use App\Support\KullaniciAyarlari;
use Filament\Pages\Dashboard;

/**
 * Panelin giriş sayfası — "Panel" yerine "Ana Sayfa" adıyla (isgsuite
 * "Ana sayfa — görev durumum"). Widget'lar: karşılama bandı, görev
 * durumu panosu, bu ayın ziyaretleri, portföy özeti.
 *
 * Kullanıcı telefon ana ekranını Saha Ekranı yaptıysa (Ayarlar > Telefon ana
 * ekranı) bu sayfa oraya yönlendirir; pano menüden açılabilir kalır.
 */
class AnaSayfa extends Dashboard
{
    protected static ?string $title = 'Ana Sayfa';

    protected static ?string $navigationLabel = 'Ana Sayfa';

    public function mount(): void
    {
        if (KullaniciAyarlari::sahaAnaEkrani()) {
            $this->redirect(SahaEkrani::getUrl(), navigate: true);
        }
    }

    /** Telefon görünümü kuralları bu sınıfa bağlı (tasarim.css — yalnız evrak uyumu + ziyaret takvimi). */
    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'mehse-ana-sayfa'];
    }
}
