{{--
    Sidebar alt kartı (AdminPanelProvider SIDEBAR_FOOTER): aktif işyeri bağlamı,
    sesli navigasyon, isteğe bağlı canlı konum paylaşımı ve oturumdaki uzman kartı.
    Stiller: tasarim.css "Sidebar alt kartı".
--}}
@php
    /** @var \App\Models\User|null $kullanici */
    $kullanici = filament()->auth()->user();
@endphp

@if ($kullanici instanceof \App\Models\User)
    @php
        $parcalar = preg_split('/\s+/u', trim((string) $kullanici->name)) ?: [];
        $ilkHarf = mb_substr($parcalar[0] ?? '?', 0, 1);
        $sonHarf = count($parcalar) > 1 ? mb_substr((string) end($parcalar), 0, 1) : '';
        $basHarfler = \App\Support\TurkceMetin::buyuk($ilkHarf.$sonHarf);

        $ayarlarAktif = request()->routeIs(\App\Filament\Pages\Ayarlar::getRouteName());
        $profilimErisim = \App\Filament\Pages\Profilim::canAccess();
        $profilUrl = $profilimErisim
            ? \App\Filament\Pages\Profilim::getUrl()
            : (filament()->getProfileUrl() ?: \App\Filament\Pages\Ayarlar::getUrl());
        $sahaEkraniErisim = \App\Filament\Pages\SahaEkrani::canAccess();

        // Firma modelinin global 'gorunurluk' kapsamı sahip ve paylaşılan firmaları otomatik çözer.
        $aktifFirmaId = \App\Support\KomutaFirma::oturumdaki();
        $aktifFirma = $aktifFirmaId
            ? \App\Models\Firma::query()->find($aktifFirmaId, ['id', 'unvan', 'kisa_ad', 'nace_kodu', 'tehlike_sinifi'])
            : null;
        $aktifFirmaEditUrl = $aktifFirma && \App\Filament\Resources\Firmas\FirmaResource::canAccess()
            ? \App\Filament\Resources\Firmas\FirmaResource::getUrl('edit', ['record' => $aktifFirma->id])
            : null;

        $canliKonum = (array) (($kullanici->ayarlar ?? [])['canli_konum'] ?? []);
        $canliKonumAktif = (bool) ($canliKonum['aktif'] ?? false);
        $canliKonumZaman = isset($canliKonum['guncelleme']) ? (string) $canliKonum['guncelleme'] : '';

        $kaseYuklu = ! empty($kullanici->kase_gorseli);

        // Sesli navigasyon hedefleri — yalnızca kullanıcının yetkisi olan sayfalar eklenir.
        $sesliAdaylar = [
            [\App\Filament\Pages\AnaSayfa::class, ['ana sayfa', 'anasayfa', 'özet'], fn () => \App\Filament\Pages\AnaSayfa::getUrl()],
            [\App\Filament\Pages\KontrolMerkezi::class, ['kontrol', 'komuta'], fn () => \App\Filament\Pages\KontrolMerkezi::getUrl()],
            [\App\Filament\Pages\HizliSahaBulgusu::class, ['bulgu', 'hızlı bulgu', 'saha'], fn () => \App\Filament\Pages\HizliSahaBulgusu::getUrl()],
            [\App\Filament\Pages\ZiyaretProgrami::class, ['ziyaret', 'takvim', 'rota'], fn () => \App\Filament\Pages\ZiyaretProgrami::getUrl()],
            [\App\Filament\Pages\DofOlustur::class, ['döf', 'dof'], fn () => \App\Filament\Pages\DofOlustur::getUrl()],
            [\App\Filament\Pages\TespitOneriDefteri::class, ['defter', 'tespit', 'onaylı defter'], fn () => \App\Filament\Pages\TespitOneriDefteri::getUrl()],
            [\App\Filament\Pages\IsIzinFormu::class, ['iş izni', 'izin', 'ptw'], fn () => \App\Filament\Pages\IsIzinFormu::getUrl()],
            [\App\Filament\Pages\TalimatOlustur::class, ['talimat'], fn () => \App\Filament\Pages\TalimatOlustur::getUrl()],
            [\App\Filament\Pages\RiskSihirbazi::class, ['risk'], fn () => \App\Filament\Pages\RiskSihirbazi::getUrl()],
            [\App\Filament\Pages\EgitimKatilim::class, ['eğitim', 'egitim', 'sertifika', 'sınav'], fn () => \App\Filament\Pages\EgitimKatilim::getUrl()],
            [\App\Filament\Pages\YillikPlan\YillikCalismaPlani::class, ['yıllık', 'plan'], fn () => \App\Filament\Pages\YillikPlan\YillikCalismaPlani::getUrl()],
            [\App\Filament\Pages\SaglikGozetimi::class, ['sağlık', 'saglik', 'muayene', 'kaza'], fn () => \App\Filament\Pages\SaglikGozetimi::getUrl()],
            [\App\Filament\Pages\AcilDurumPlani::class, ['acil', 'yangın', 'kroki', 'tatbikat'], fn () => \App\Filament\Pages\AcilDurumPlani::getUrl()],
            [\App\Filament\Pages\KurulToplantisi::class, ['kurul'], fn () => \App\Filament\Pages\KurulToplantisi::getUrl()],
            [\App\Filament\Pages\PeriyodikKontrol::class, ['ekipman', 'periyodik', 'ölçüm'], fn () => \App\Filament\Pages\PeriyodikKontrol::getUrl()],
            [\App\Filament\Pages\KkdTakip::class, ['kkd', 'zimmet'], fn () => \App\Filament\Pages\KkdTakip::getUrl()],
            [\App\Filament\Pages\DokumanYonetimi::class, ['arşiv', 'arsiv', 'doküman'], fn () => \App\Filament\Pages\DokumanYonetimi::getUrl()],
            [\App\Filament\Resources\Firmas\FirmaResource::class, ['firma'], fn () => \App\Filament\Resources\Firmas\FirmaResource::getUrl('index')],
            [\App\Filament\Resources\Calisans\CalisanResource::class, ['çalışan', 'personel'], fn () => \App\Filament\Resources\Calisans\CalisanResource::getUrl('index')],
            [\App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::class, ['profesyonel', 'hekim', 'uzmanlar'], fn () => \App\Filament\Resources\IsgProfesyonelis\IsgProfesyoneliResource::getUrl('index')],
            [\App\Filament\Pages\TaseronYonetimi::class, ['taşeron', 'alt işveren', 'paydaş'], fn () => \App\Filament\Pages\TaseronYonetimi::getUrl()],
            [\App\Filament\Pages\IsgKatipSozlesmeleri::class, ['katip', 'sözleşme', 'ibys'], fn () => \App\Filament\Pages\IsgKatipSozlesmeleri::getUrl()],
            [\App\Filament\Pages\Pazarlama::class, ['pazarlama', 'teklif', 'aday'], fn () => \App\Filament\Pages\Pazarlama::getUrl()],
            [\App\Filament\Pages\CariHesap::class, ['cari', 'tahsilat', 'finans'], fn () => \App\Filament\Pages\CariHesap::getUrl()],
            [\App\Filament\Pages\FaturaProfil::class, ['abonelik', 'fatura', 'paket'], fn () => \App\Filament\Pages\FaturaProfil::getUrl()],
            [\App\Filament\Pages\Profilim::class, ['profil', 'kaşe', 'imza'], fn () => \App\Filament\Pages\Profilim::getUrl()],
            [\App\Filament\Pages\Araclar::class, ['araç', 'hesapla', 'sözlük'], fn () => \App\Filament\Pages\Araclar::getUrl()],
            [\App\Filament\Pages\Ayarlar::class, ['ayar'], fn () => \App\Filament\Pages\Ayarlar::getUrl()],
            [\App\Filament\Pages\Guvenlik::class, ['güvenlik', 'şifre', 'oturum'], fn () => \App\Filament\Pages\Guvenlik::getUrl()],
            [\App\Filament\Pages\KullaniciYonetimi::class, ['yetki', 'kullanıcı'], fn () => \App\Filament\Pages\KullaniciYonetimi::getUrl()],
        ];

        $sesliHedefler = [];
        foreach ($sesliAdaylar as [$sinif, $anahtarlar, $urlUretici]) {
            if ($sinif::canAccess()) {
                $sesliHedefler[] = ['anahtar' => $anahtarlar, 'url' => $urlUretici()];
            }
        }
    @endphp

    <div class="mehse-sidebar-alt">
        {{-- Sabit Aktif İşyeri Bağlamı Kartı (E6) + Tüm Portföye Dön --}}
        @if ($aktifFirma)
            <div
                x-data="{
                    goster: true,
                    temizle() {
                        fetch(@js(route('mehse.aktif-firma.temizle')), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        }).then((r) => {
                            if (r.ok) {
                                this.goster = false;
                                const u = new URL(window.location.href);
                                if (u.searchParams.has('firma')) {
                                    u.searchParams.delete('firma');
                                    window.location.href = u.toString();
                                } else {
                                    window.location.reload();
                                }
                            }
                        });
                    }
                }"
                x-show="goster"
                style="display:flex;align-items:flex-start;gap:.35rem;margin-bottom:.45rem;padding:.45rem .55rem;border-radius:10px;background:rgb(255 255 255/.08);border:1px solid rgb(255 255 255/.14);font-size:.72rem;line-height:1.3"
            >
                @if ($aktifFirmaEditUrl)
                    <a href="{{ $aktifFirmaEditUrl }}" style="flex:1;min-width:0;text-decoration:none;color:inherit">
                @else
                    <div style="flex:1;min-width:0">
                @endif
                        <div style="font-size:.62rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7;font-weight:700">📍 Aktif İşyeri Bağlamı</div>
                        <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:.1rem" title="{{ $aktifFirma->secimEtiketi() }}">{{ $aktifFirma->secimEtiketi() }}</div>
                        <div style="opacity:.78;font-size:.66rem">{{ $aktifFirma->nace_kodu ?: 'NACE —' }} · {{ $aktifFirma->tehlikeSinifiEtiketi() }}</div>
                @if ($aktifFirmaEditUrl)
                    </a>
                @else
                    </div>
                @endif
                <button
                    type="button"
                    x-on:click.prevent="temizle()"
                    title="Aktif firma seçimini temizle (Tüm Portföy)"
                    aria-label="Aktif firma seçimini temizle"
                    style="flex-shrink:0;border:none;background:rgb(255 255 255/.12);color:inherit;width:1.25rem;height:1.25rem;border-radius:9999px;font-size:.78rem;line-height:1;cursor:pointer;display:grid;place-items:center;opacity:.8"
                >×</button>
            </div>
        @endif

        {{-- Sesli Navigasyon (E6) + Opsiyonel Canlı Konum Paylaşımı (E9) --}}
        <div
            x-data="{
                dinliyor: false,
                rec: null,
                durumMetni: '',
                durumHata: false,
                konumAktif: @js($canliKonumAktif),
                konumYukleniyor: false,
                hedefler: @js($sesliHedefler),
                mesaj(metin, hata = false) {
                    this.durumMetni = metin;
                    this.durumHata = hata;
                },
                sesliAc() {
                    if (this.dinliyor && this.rec) {
                        this.rec.stop();
                        this.dinliyor = false;
                        this.mesaj('Dinleme durduruldu.');
                        return;
                    }
                    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SR) { this.mesaj('Tarayıcı ses tanımayı desteklemiyor.', true); return; }
                    const rec = new SR();
                    rec.lang = 'tr-TR';
                    this.rec = rec;
                    this.dinliyor = true;
                    this.mesaj('Dinleniyor… (örn. Risk, Bulgu, DÖF, Profil)');
                    rec.onresult = (e) => {
                        const metin = (e.results[0][0].transcript || '').toLocaleLowerCase('tr');
                        const bulunan = this.hedefler.find((h) => h.anahtar.some((k) => metin.includes(k)));
                        if (bulunan) {
                            this.mesaj('Açılıyor: ' + metin);
                            window.location.href = bulunan.url;
                        } else {
                            this.mesaj('Anlaşılamadı veya yetki yok: ' + metin, true);
                        }
                    };
                    rec.onerror = () => { this.dinliyor = false; this.mesaj('Mikrofon izni alınamadı.', true); };
                    rec.onend = () => { this.dinliyor = false; };
                    rec.start();
                },
                konumDegistir() {
                    if (this.konumYukleniyor) return;
                    const yeni = !this.konumAktif;
                    const gonder = (lat, lng) => {
                        this.konumYukleniyor = true;
                        fetch(@js(route('mehse.konum.bildir')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                            body: JSON.stringify({ aktif: yeni, enlem: lat, boylam: lng }),
                            credentials: 'same-origin',
                        })
                            .then((r) => {
                                if (!r.ok) throw new Error('HTTP ' + r.status);
                                return r.json();
                            })
                            .then((veri) => {
                                this.konumAktif = Boolean(veri.aktif);
                                this.mesaj(
                                    this.konumAktif
                                        ? ('Konum paylaşıldı' + (veri.guncelleme ? ' · ' + veri.guncelleme : ''))
                                        : 'Konum paylaşımı kapatıldı.'
                                );
                            })
                            .catch(() => {
                                this.mesaj('Konum sunucuya kaydedilemedi.', true);
                            })
                            .finally(() => {
                                this.konumYukleniyor = false;
                            });
                    };

                    if (yeni) {
                        if (!navigator.geolocation) {
                            this.mesaj('Tarayıcı konum özelliğini desteklemiyor.', true);
                            return;
                        }
                        this.konumYukleniyor = true;
                        this.mesaj('Konum alınıyor…');
                        navigator.geolocation.getCurrentPosition(
                            (p) => gonder(p.coords.latitude, p.coords.longitude),
                            () => {
                                this.konumYukleniyor = false;
                                this.mesaj('Konum izni reddedildi veya alınamadı.', true);
                            },
                            { enableHighAccuracy: true, timeout: 10000 }
                        );
                    } else {
                        gonder(null, null);
                    }
                }
            }"
            style="display:flex;gap:.35rem;margin-bottom:.45rem;flex-wrap:wrap"
        >
            <button type="button" x-on:click="sesliAc()" title="Mikrofonla sayfa aç (örn. Risk, Bulgu, Ziyaret, Profil)"
                style="flex:1;padding:.28rem .45rem;border-radius:8px;border:1px solid rgb(255 255 255/.18);background:rgb(255 255 255/.08);color:inherit;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap">
                <span x-text="dinliyor ? '🔴 Durdur' : '🎤 Sesli Aç'"></span>
            </button>
            <button type="button" x-on:click="konumDegistir()" :disabled="konumYukleniyor"
                title="{{ $canliKonumAktif && $canliKonumZaman ? 'Son paylaşım: '.$canliKonumZaman : 'OSGB yöneticisiyle canlı saha konumunu paylaş (isteğe bağlı)' }}"
                :style="konumAktif ? 'border-color:#22c55e;background:rgb(34 197 94/.2);color:#bbf7d0' : 'border-color:rgb(255 255 255/.18);background:rgb(255 255 255/.08);color:inherit'"
                style="flex:1;padding:.28rem .45rem;border-radius:8px;border:1px solid;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap">
                <span x-text="konumYukleniyor ? '⏳ Konum…' : (konumAktif ? '📍 Konum Açık' : '📍 Konum Kapalı')"></span>
            </button>
            <div x-show="durumMetni" x-text="durumMetni" :style="durumHata ? 'color:#fca5a5' : 'opacity:.85'" style="width:100%;font-size:.66rem;line-height:1.25"></div>
        </div>

        <div class="mehse-sidebar-alt-kart">
            <a
                href="{{ $profilUrl }}"
                class="mehse-sidebar-alt-profil-link"
                title="{{ $profilimErisim ? 'Profil & Kaşe/İmza sayfasını aç' : 'Hesap profilini aç' }}{{ $kaseYuklu ? '' : ' · Kaşe henüz yüklenmemiş' }}"
            >
                <span class="mehse-sidebar-alt-avatar" aria-hidden="true">{{ $basHarfler }}</span>
                <span class="mehse-sidebar-alt-kisi">
                    <strong title="{{ $kullanici->name }}">{{ $kullanici->name }}</strong>
                    <small>{{ $kullanici->unvanEtiketi() }}</small>
                </span>
            </a>
            @if ($sahaEkraniErisim)
                <a
                    href="{{ \App\Filament\Pages\SahaEkrani::getUrl() }}"
                    class="mehse-sidebar-alt-ayar @if (request()->routeIs(\App\Filament\Pages\SahaEkrani::getRouteName())) fi-active @endif"
                    title="Telefon Saha Uygulaması"
                    aria-label="Telefon Saha Uygulaması"
                >
                    <x-filament::icon icon="heroicon-o-device-phone-mobile" class="mehse-sidebar-alt-ayar-ikon" />
                </a>
            @endif
            <a
                href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}"
                class="mehse-sidebar-alt-ayar mehse-sidebar-alt-disli @if ($ayarlarAktif) fi-active @endif"
                title="Ayarlar"
                aria-label="Ayarlar"
            >
                <x-filament::icon icon="heroicon-o-cog-6-tooth" class="mehse-sidebar-alt-ayar-ikon" />
            </a>
        </div>
    </div>
@endif
