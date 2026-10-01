@php
    $kullanici = $this->kullanici();
    $kart = 'border:1px solid rgb(107 114 128 / .22);border-radius:.75rem;padding:1.1rem 1.2rem';
    $baslik = 'font-weight:700;font-size:.95rem;margin-bottom:.7rem';
    $soluk = 'font-size:.78rem;color:rgb(107 114 128)';
@endphp

<x-filament-panels::page>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;align-items:start">

        {{-- ŞİFRE DEĞİŞTİR --}}
        <div style="{{ $kart }}">
            <div style="{{ $baslik }}">Şifre Değiştir</div>
            <form wire:submit="sifreDegistir">
                {{ $this->form }}
                <div style="margin-top:1rem;display:flex;justify-content:flex-end">
                    <x-filament::button type="submit" icon="heroicon-o-check">Şifreyi Kaydet</x-filament::button>
                </div>
            </form>
            <p style="{{ $soluk }};margin-top:.8rem">
                Yalnızca kendi şifrenizi değiştirebilirsiniz; hiçbir yönetici başkasının şifresini göremez.
                Şifre değişince diğer cihazlardaki oturumlarınız kapanır.
            </p>
        </div>

        {{-- İKİ ADIMLI DOĞRULAMA --}}
        <div style="{{ $kart }}">
            <div style="{{ $baslik }}">İki Adımlı Doğrulama (MFA)</div>
            @if ($kullanici->mfaAcikMi())
                <p style="color:#047857;font-weight:600">✓ Durum: Açık</p>
                <p style="{{ $soluk }};margin-top:.3rem">
                    Girişte şifrenize ek olarak telefonunuzdaki doğrulama uygulamasının 6 haneli kodu istenir.
                    Kurtarma kodu kalan: {{ count($kullanici->getAppAuthenticationRecoveryCodes() ?? []) }}
                </p>
            @else
                <p style="color:#b45309;font-weight:600">Durum: Kapalı</p>
                <p style="{{ $soluk }};margin-top:.3rem">
                    Şifreniz ele geçse bile hesabınıza girilemesin diye önerilir. Google Authenticator veya
                    Microsoft Authenticator gibi bir uygulama ile birkaç dakikada kurulur.
                </p>
            @endif
            <div style="margin-top:.9rem">
                <x-filament::button tag="a" :href="filament()->getProfileUrl()" :color="$kullanici->mfaAcikMi() ? 'gray' : 'primary'" icon="heroicon-o-device-phone-mobile">
                    {{ $kullanici->mfaAcikMi() ? 'MFA Ayarlarını Yönet' : 'MFA Kur' }}
                </x-filament::button>
            </div>
            <p style="{{ $soluk }};margin-top:.6rem">Kurulum, Profil sayfasındaki "Doğrulama uygulaması" bölümünden yapılır.</p>
        </div>

        {{-- OTURUMLAR --}}
        <div style="{{ $kart }}">
            <div style="{{ $baslik }}">Açık Oturumlar</div>
            @forelse ($this->oturumlar as $o)
                <div style="display:flex;gap:.6rem;align-items:center;padding:.45rem 0;border-bottom:1px solid rgb(107 114 128 / .12);font-size:.85rem">
                    <span style="flex:1">
                        {{ $o['cihaz'] }}
                        @if ($o['bu_cihaz'])
                            <span style="font-size:.7rem;padding:.05rem .45rem;border-radius:999px;background:rgb(16 185 129 / .12);color:#047857;font-weight:600">bu cihaz</span>
                        @endif
                        <br><span style="{{ $soluk }}">{{ $o['ip'] ?: 'IP yok' }}</span>
                    </span>
                    <span style="{{ $soluk }};white-space:nowrap">{{ $o['son']->diffForHumans() }}</span>
                </div>
            @empty
                <p style="{{ $soluk }}">Oturum bilgisi görüntülenemiyor.</p>
            @endforelse
            <p style="{{ $soluk }};margin-top:.8rem">Şüpheli bir giriş görürseniz veya bir cihazınızı kaybettiyseniz diğer tüm oturumları kapatın.</p>
            <div style="margin-top:.6rem">{{ $this->tumCihazlardanCikisAction }}</div>
        </div>

        {{-- GÜVENLİK NOTLARI --}}
        <div style="{{ $kart }}">
            <div style="{{ $baslik }}">Güvenlik Notları</div>
            <ul style="font-size:.85rem;line-height:1.6;padding-left:1.1rem;list-style:disc">
                <li>Yeni şifre en az 10 karakter olmalıdır.</li>
                <li>Şifrenizi yalnızca siz değiştirebilirsiniz; yöneticiler başkasının şifresini göremez.</li>
                <li>İki adımlı doğrulamayı açmanız önerilir — özellikle ekip/OSGB yöneticisi hesaplarında.</li>
                <li>Ortak bilgisayarda işiniz bitince oturumu kapatın.</li>
                <li>İşyeri (işveren) giriş şifreleri bu hesaptan ayrıdır; Firmalar → "İşyeri Girişi"nden yönetilir.</li>
            </ul>
        </div>
    </div>

    {{-- HUKUKİ ONAYLAR --}}
    <div style="{{ $kart }}">
        <div style="{{ $baslik }};margin-bottom:.2rem">Hukuki Onaylar</div>
        <p style="{{ $soluk }};margin-bottom:.8rem">Metni okuyup sürüm bazlı onaylayın. Metin güncellenirse yeniden onayınız istenir.</p>

        @forelse ($this->yasalMetinler as $anahtar => $m)
            <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;border:1px solid rgb(107 114 128 / .18);border-radius:.6rem;padding:.75rem .9rem;margin-bottom:.6rem">
                <div style="flex:1;min-width:14rem">
                    <div style="font-weight:600">{{ $m['baslik'] }}</div>
                    <div style="{{ $soluk }}">{{ $m['revizyon'] ?: 'Sürüm belirtilmemiş' }}</div>
                    @if ($m['onay'] && ! $m['guncel'])
                        <div style="font-size:.75rem;color:#b45309;margin-top:.2rem">
                            Önceki sürümü ({{ $m['onay']['revizyon'] }}) onaylamıştınız — metin güncellendi.
                        </div>
                    @endif
                    <button type="button" x-on:click="$dispatch('open-modal', { id: 'yasal-{{ $anahtar }}' })"
                        style="margin-top:.4rem;font-size:.75rem;padding:.2rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer">
                        Tam metni göster
                    </button>
                </div>
                @if ($m['guncel'])
                    <span style="color:#047857;font-weight:600;font-size:.85rem">
                        ✓ Onaylandı · {{ \Illuminate\Support\Carbon::parse($m['onay']['onay_at'])->format('d.m.Y H:i') }}
                    </span>
                @else
                    <x-filament::button wire:click="yasalOnayla('{{ $anahtar }}')">Okudum, onaylıyorum</x-filament::button>
                @endif
            </div>
        @empty
            <p style="{{ $soluk }}">Tanımlı yasal metin yok.</p>
        @endforelse
    </div>

    @include('filament.components.yasal-modallar')

</x-filament-panels::page>
