@php
    $kullanici = $this->kullanici();
    $kart = 'border:1px solid rgb(107 114 128 / .22);border-radius:.75rem;padding:1.1rem 1.2rem';
    $baslik = 'font-weight:700;font-size:.95rem;margin-bottom:.7rem';
    $soluk = 'font-size:.78rem;color:rgb(107 114 128)';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
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
                            <span style="font-size:.75rem;padding:.05rem .45rem;border-radius:999px;background:rgb(16 185 129 / .12);color:#047857;font-weight:600">bu cihaz</span>
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

    {{-- DENETİM KAYITLARI (AUDIT LOG) + 7 GÜNLÜK AKTİVİTE GRAFİĞİ (F7) --}}
    @php
        $hacim = $this->denetim7Gun;
        $maksHacim = max(1, (int) collect($hacim)->max('sayi'));
        $kayitlar = $this->denetimKayitlari;
    @endphp
    <div style="{{ $kart }}">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem">
            <div>
                <div style="{{ $baslik }};margin-bottom:.15rem">Denetim Kayıtları (Audit Log)</div>
                <div style="{{ $soluk }}">Kim, ne zaman, hangi firma ve modülde işlem yaptı — son 7 gün aktivite hacmi ve işlem geçmişi.</div>
            </div>
            <div style="display:flex;gap:.45rem;flex-wrap:wrap">
                <select wire:model.live="denetimModul" style="font-size:.78rem;padding:.32rem .55rem;border:1px solid rgb(107 114 128 / .35);border-radius:.45rem;background:transparent">
                    <option value="">Tüm Modüller</option>
                    @foreach ($this->denetimModulleri as $mod)
                        <option value="{{ $mod }}">{{ $mod }}</option>
                    @endforeach
                </select>
                <select wire:model.live="denetimIslem" style="font-size:.78rem;padding:.32rem .55rem;border:1px solid rgb(107 114 128 / .35);border-radius:.45rem;background:transparent">
                    <option value="">Tüm İşlemler</option>
                    @foreach (\App\Models\DenetimKaydi::ISLEMLER as $k => $ad)
                        <option value="{{ $k }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 7 GÜNLÜK ÇUBUK GRAFİK --}}
        @if (count($hacim))
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.5rem;align-items:end;height:86px;padding:.5rem .6rem;border:1px solid rgb(107 114 128 / .15);border-radius:.6rem;margin-bottom:.9rem">
                @foreach ($hacim as $gun)
                    @php $yukseklik = max(6, (int) round(($gun['sayi'] / $maksHacim) * 52)); @endphp
                    <div style="display:flex;flex-direction:column;align-items:center;gap:.2rem">
                        <span style="font-size:.68rem;font-weight:700;color:#2563eb">{{ $gun['sayi'] }}</span>
                        <div style="width:100%;max-width:34px;height:{{ $yukseklik }}px;background:#2563eb;border-radius:4px 4px 0 0;opacity:.85"></div>
                        <span style="font-size:.68rem;color:rgb(107 114 128)">{{ $gun['etiket'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- SON İŞLEMLER TABLOSU --}}
        @if ($kayitlar->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid rgb(107 114 128 / .25);color:rgb(107 114 128)">
                            <th style="padding:.4rem">Tarih</th>
                            <th style="padding:.4rem">Kullanıcı</th>
                            <th style="padding:.4rem">Modül / İşlem</th>
                            <th style="padding:.4rem">Firma</th>
                            <th style="padding:.4rem">Özet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kayitlar as $k)
                            <tr style="border-bottom:1px solid rgb(107 114 128 / .12)">
                                <td style="padding:.4rem;white-space:nowrap;color:rgb(107 114 128)">{{ $k->created_at?->format('d.m.Y H:i') }}</td>
                                <td style="padding:.4rem;white-space:nowrap;font-weight:600">{{ $k->kullanici_adi ?: 'Sistem' }}</td>
                                <td style="padding:.4rem;white-space:nowrap">
                                    <span style="font-weight:600">{{ $k->modul }}</span> ·
                                    <span style="font-size:.72rem;padding:.05rem .4rem;border-radius:999px;background:rgb(37 99 235 / .1);color:#1d4ed8;font-weight:600">{{ \App\Models\DenetimKaydi::ISLEMLER[$k->islem] ?? $k->islem }}</span>
                                </td>
                                <td style="padding:.4rem">{{ $k->firma?->unvan ?: '—' }}</td>
                                <td style="padding:.4rem">{{ $k->ozet }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="{{ $soluk }}">Henüz denetim kaydı yok. Firma, Risk Değerlendirmesi, DÖF veya Periyodik Kontrol işlemlerinde kayıtlar otomatik oluşur.</p>
        @endif
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
