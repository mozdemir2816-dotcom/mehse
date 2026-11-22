{{--
    Sidebar alt kartı (AdminPanelProvider SIDEBAR_FOOTER): oturumdaki uzman,
    ünvanı ve Ayarlar kısayolu. Stiller: theme.css "Sidebar alt kartı".
--}}
@php
    /** @var \App\Models\User|null $kullanici */
    $kullanici = filament()->auth()->user();
@endphp

@if ($kullanici instanceof \App\Models\User)
    @php
        $parcalar = preg_split('/\s+/u', trim((string) $kullanici->name)) ?: [];
        $basHarfler = mb_strtoupper(
            str_replace('i', 'İ', mb_substr($parcalar[0] ?? '?', 0, 1).mb_substr(count($parcalar) > 1 ? end($parcalar) : '', 0, 1))
        );
        $ayarlarAktif = request()->routeIs(\App\Filament\Pages\Ayarlar::getRouteName());
        $aktifFirmaId = \App\Support\KomutaFirma::oturumdaki();
        $aktifFirma = $aktifFirmaId
            ? \App\Models\Firma::query()->where('user_id', $kullanici->id)->find($aktifFirmaId, ['id', 'unvan', 'kisa_ad', 'nace_kodu', 'tehlike_sinifi'])
            : null;
        $canliKonumAktif = (bool) (($kullanici->ayarlar ?? [])['canli_konum']['aktif'] ?? false);
        $sesliHedefler = [
            ['anahtar' => ['ana sayfa', 'anasayfa', 'özet'], 'url' => \App\Filament\Pages\AnaSayfa::getUrl()],
            ['anahtar' => ['kontrol', 'komuta'], 'url' => \App\Filament\Pages\KontrolMerkezi::getUrl()],
            ['anahtar' => ['bulgu', 'hızlı bulgu', 'saha'], 'url' => \App\Filament\Pages\HizliSahaBulgusu::getUrl()],
            ['anahtar' => ['ziyaret', 'takvim', 'rota'], 'url' => \App\Filament\Pages\ZiyaretProgrami::getUrl()],
            ['anahtar' => ['döf', 'dof'], 'url' => \App\Filament\Pages\DofOlustur::getUrl()],
            ['anahtar' => ['risk'], 'url' => \App\Filament\Pages\RiskSihirbazi::getUrl()],
            ['anahtar' => ['eğitim', 'egitim', 'sertifika'], 'url' => \App\Filament\Pages\EgitimKatilim::getUrl()],
            ['anahtar' => ['yıllık', 'plan'], 'url' => \App\Filament\Pages\YillikPlan\YillikCalismaPlani::getUrl()],
            ['anahtar' => ['sağlık', 'saglik', 'muayene', 'kaza'], 'url' => \App\Filament\Pages\SaglikGozetimi::getUrl()],
            ['anahtar' => ['acil', 'yangın', 'kroki'], 'url' => \App\Filament\Pages\AcilDurumPlani::getUrl()],
            ['anahtar' => ['kurul'], 'url' => \App\Filament\Pages\KurulToplantisi::getUrl()],
            ['anahtar' => ['ekipman', 'periyodik'], 'url' => \App\Filament\Pages\PeriyodikKontrol::getUrl()],
            ['anahtar' => ['kkd'], 'url' => \App\Filament\Pages\KkdTakip::getUrl()],
            ['anahtar' => ['arşiv', 'arsiv', 'doküman'], 'url' => \App\Filament\Pages\DokumanYonetimi::getUrl()],
            ['anahtar' => ['firma'], 'url' => \App\Filament\Resources\Firmas\FirmaResource::getUrl('index')],
            ['anahtar' => ['çalışan', 'personel'], 'url' => \App\Filament\Resources\Calisans\CalisanResource::getUrl('index')],
            ['anahtar' => ['cari', 'tahsilat', 'finans'], 'url' => \App\Filament\Pages\CariHesap::getUrl()],
            ['anahtar' => ['ayar'], 'url' => \App\Filament\Pages\Ayarlar::getUrl()],
        ];
    @endphp

    <div class="mehse-sidebar-alt">
        {{-- Sabit Aktif İşyeri Bağlamı Kartı (E6) --}}
        @if ($aktifFirma)
            <a href="{{ \App\Filament\Resources\Firmas\FirmaResource::getUrl('edit', ['record' => $aktifFirma->id]) }}"
                style="display:block;margin-bottom:.45rem;padding:.45rem .6rem;border-radius:10px;background:rgb(255 255 255/.08);border:1px solid rgb(255 255 255/.14);text-decoration:none;color:inherit;font-size:.72rem;line-height:1.3">
                <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;font-weight:700">📍 Aktif İşyeri Bağlamı</div>
                <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:.1rem" title="{{ $aktifFirma->secimEtiketi() }}">{{ $aktifFirma->secimEtiketi() }}</div>
                <div style="opacity:.78;font-size:.66rem">{{ $aktifFirma->nace_kodu ?: 'NACE —' }} · {{ $aktifFirma->tehlikeSinifiEtiketi() }}</div>
            </a>
        @endif

        {{-- Sesli Navigasyon (E6) + Opsiyonel Canlı Konum Paylaşımı (E9) --}}
        <div
            x-data="{
                dinliyor: false,
                sesDurum: '',
                konumAktif: @js($canliKonumAktif),
                hedefler: @js($sesliHedefler),
                sesliAc() {
                    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SR) { this.sesDurum = 'Tarayıcı ses tanımayı desteklemiyor.'; return; }
                    const rec = new SR();
                    rec.lang = 'tr-TR';
                    this.dinliyor = true;
                    this.sesDurum = 'Dinleniyor… (örn. Risk, Bulgu, DÖF, Ziyaret)';
                    rec.onresult = (e) => {
                        const metin = (e.results[0][0].transcript || '').toLocaleLowerCase('tr');
                        const bulunan = this.hedefler.find((h) => h.anahtar.some((k) => metin.includes(k)));
                        if (bulunan) {
                            this.sesDurum = 'Açılıyor: ' + metin;
                            window.location.href = bulunan.url;
                        } else {
                            this.sesDurum = 'Anlaşılamadı: ' + metin;
                        }
                    };
                    rec.onerror = () => { this.dinliyor = false; this.sesDurum = 'Mikrofon izni alınamadı.'; };
                    rec.onend = () => { this.dinliyor = false; };
                    rec.start();
                },
                konumDegistir() {
                    const yeni = !this.konumAktif;
                    const gonder = (lat, lng) => {
                        fetch(@js(route('mehse.konum.bildir')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                            body: JSON.stringify({ aktif: yeni, enlem: lat, boylam: lng }),
                            credentials: 'same-origin',
                        }).then(() => { this.konumAktif = yeni; });
                    };
                    if (yeni && navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (p) => gonder(p.coords.latitude, p.coords.longitude),
                            () => gonder(null, null)
                        );
                    } else {
                        gonder(null, null);
                    }
                }
            }"
            style="display:flex;gap:.35rem;margin-bottom:.45rem;flex-wrap:wrap"
        >
            <button type="button" x-on:click="sesliAc()" title="Mikrofonla sayfa aç (örn. Risk, Bulgu, Ziyaret)"
                style="flex:1;padding:.28rem .45rem;border-radius:8px;border:1px solid rgb(255 255 255/.18);background:rgb(255 255 255/.08);color:inherit;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap">
                <span x-text="dinliyor ? '🔴 Dinliyor…' : '🎤 Sesli Aç'"></span>
            </button>
            <button type="button" x-on:click="konumDegistir()" title="OSGB yöneticisiyle canlı saha konumunu paylaş (isteğe bağlı)"
                :style="konumAktif ? 'border-color:#22c55e;background:rgb(34 197 94/.2);color:#bbf7d0' : 'border-color:rgb(255 255 255/.18);background:rgb(255 255 255/.08);color:inherit'"
                style="flex:1;padding:.28rem .45rem;border-radius:8px;border:1px solid;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap">
                <span x-text="konumAktif ? '📍 Konum Açık' : '📍 Konum Kapalı'"></span>
            </button>
            <div x-show="sesDurum" x-text="sesDurum" style="width:100%;font-size:.66rem;opacity:.85"></div>
        </div>

        <div class="mehse-sidebar-alt-kart">
            <span class="mehse-sidebar-alt-avatar" aria-hidden="true">{{ $basHarfler }}</span>
            <span class="mehse-sidebar-alt-kisi">
                <strong title="{{ $kullanici->name }}">{{ $kullanici->name }}</strong>
                <small>{{ $kullanici->unvanEtiketi() }}</small>
            </span>
            <a
                href="{{ \App\Filament\Pages\SahaEkrani::getUrl() }}"
                class="mehse-sidebar-alt-ayar @if (request()->routeIs(\App\Filament\Pages\SahaEkrani::getRouteName())) fi-active @endif"
                title="Telefon Saha Uygulaması"
                aria-label="Telefon Saha Uygulaması"
            >
                <x-filament::icon icon="heroicon-o-device-phone-mobile" class="mehse-sidebar-alt-ayar-ikon" />
            </a>
            <a
                href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}"
                class="mehse-sidebar-alt-ayar @if ($ayarlarAktif) fi-active @endif"
                title="Ayarlar"
                aria-label="Ayarlar"
            >
                <x-filament::icon icon="heroicon-o-cog-6-tooth" class="mehse-sidebar-alt-ayar-ikon" />
            </a>
        </div>
    </div>
@endif
