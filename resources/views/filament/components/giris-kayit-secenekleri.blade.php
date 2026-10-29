{{--
    Giriş formunun altı (AdminPanelProvider AUTH_LOGIN_FORM_AFTER) — isgsuite.tr
    "Başvuru seçenekleri": bireysel İGU kaydı (hesap anında açılır, yetkiyi sahip
    verir — App\Filament\Auth\KayitOl) ve OSGB başvurusu (sahip onayı bekler —
    App\Filament\Auth\OsgbBasvuru). Dikeyde taşmasın diye "veya" çizgisi + yan yana
    iki kompakt düğme; teklif/demo isteyenler için tanıtım sayfası bağlantısı.
--}}
@php
    $secenekler = array_filter([
        filament()->hasRegistration() ? ['url' => filament()->getRegistrationUrl(), 'ikon' => 'heroicon-o-shield-check', 'baslik' => 'İş Güvenliği Uzmanı', 'alt' => 'Bireysel kayıt'] : null,
        ['url' => \App\Filament\Auth\OsgbBasvuru::getUrl(), 'ikon' => 'heroicon-o-building-office-2', 'baslik' => 'OSGB Başvurusu', 'alt' => 'Kurumsal başvuru'],
    ]);
@endphp

<div class="mehse-giris-secenekler">
    <div class="mehse-giris-ayirac"><span>veya başvurun</span></div>
    <div class="mehse-giris-secenek-izgara">
        @foreach ($secenekler as $s)
            <a href="{{ $s['url'] }}" class="mehse-giris-secenek">
                <span class="mehse-giris-secenek-ikon"><x-filament::icon :icon="$s['ikon']" class="h-5 w-5" /></span>
                <span class="mehse-giris-secenek-metin">
                    <strong>{{ $s['baslik'] }}</strong>
                    <small>{{ $s['alt'] }}</small>
                </span>
            </a>
        @endforeach
    </div>
    <a href="{{ url('/') }}#teklif" class="mehse-giris-demo">Demo / teklif talep et <span aria-hidden="true">→</span></a>
</div>
