{{--
    Giriş formunun altı (AdminPanelProvider AUTH_LOGIN_FORM_AFTER) — isgsuite.tr
    "Başvuru seçenekleri" kartı. mehse'de tek kayıt türü var: bireysel uzman hesabı
    (App\Filament\Auth\KayitOl; hesap yetkisiz açılır, sahip hesap yetki tanımlar).
--}}
@if (filament()->hasRegistration())
    <div class="mehse-giris-secenekler">
        <div class="mehse-giris-secenekler-baslik">Hesabınız yok mu?</div>
        <a href="{{ filament()->getRegistrationUrl() }}" class="mehse-giris-secenek">
            <span class="mehse-giris-secenek-ikon"><x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5" /></span>
            <span class="mehse-giris-secenek-metin">
                <strong>İş Güvenliği Uzmanı</strong>
                <small>Bireysel hesap oluşturun</small>
            </span>
            <x-filament::icon icon="heroicon-m-arrow-right" class="mehse-giris-secenek-ok h-4 w-4" />
        </a>
    </div>
@endif
