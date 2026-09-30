{{--
    Giriş formunun altı (AdminPanelProvider AUTH_LOGIN_FORM_AFTER) — isgsuite.tr
    "Başvuru seçenekleri": bireysel İGU kaydı (hesap anında açılır, yetkiyi sahip
    verir — App\Filament\Auth\KayitOl) ve OSGB başvurusu (sahip onayı bekler —
    App\Filament\Auth\OsgbBasvuru).
--}}
@php
    $secenekler = array_filter([
        filament()->hasRegistration() ? ['url' => filament()->getRegistrationUrl(), 'ikon' => 'heroicon-o-shield-check', 'baslik' => 'İş Güvenliği Uzmanı', 'alt' => 'Bireysel kayıt'] : null,
        ['url' => \App\Filament\Auth\OsgbBasvuru::getUrl(), 'ikon' => 'heroicon-o-building-office-2', 'baslik' => 'OSGB Başvurusu', 'alt' => 'Ortak Sağlık Güvenlik Birimi'],
    ]);
@endphp

<div class="mehse-giris-secenekler">
    <div class="mehse-giris-secenekler-baslik">Başvuru seçenekleri</div>
    <div class="flex flex-col gap-2">
        @foreach ($secenekler as $s)
            <a href="{{ $s['url'] }}" class="mehse-giris-secenek">
                <span class="mehse-giris-secenek-ikon"><x-filament::icon :icon="$s['ikon']" class="h-5 w-5" /></span>
                <span class="mehse-giris-secenek-metin">
                    <strong>{{ $s['baslik'] }}</strong>
                    <small>{{ $s['alt'] }}</small>
                </span>
                <x-filament::icon icon="heroicon-m-arrow-right" class="mehse-giris-secenek-ok h-4 w-4" />
            </a>
        @endforeach
    </div>
</div>
