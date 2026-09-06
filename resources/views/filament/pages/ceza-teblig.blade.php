@php
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Çalışana yönelik ceza/ihtar tutanağını ve işverene idari para cezası bilgilendirme
        tebliğini — resmi PDF çıktısı ile — hazırlayın.
    </p>

    {{-- FİRMA (paylaşılan) --}}
    <div style="{{ $kutu }}">
        <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
        <select wire:model.live="firmaId"
            style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </div>

    {{-- SEKME SEÇİCİ --}}
    <div style="display:flex;gap:.5rem;border-bottom:1px solid rgb(107 114 128 / .25);padding-bottom:.5rem">
        @foreach (['tutanak' => 'Ceza ve Tebliğ Tutanağı', 'ipc' => 'İşverene İPC Tebliği'] as $anahtar => $etiket)
            @php $secili = $aktifSekme === $anahtar; @endphp
            <button type="button" wire:click="$set('aktifSekme', '{{ $anahtar }}')"
                style="padding:.5rem 1rem;border-radius:.5rem .5rem 0 0;cursor:pointer;font-size:.85rem;font-weight:600;
                    border:1px solid {{ $secili ? $kirmizi : 'transparent' }};border-bottom:none;
                    background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }};
                    color:{{ $secili ? $kirmizi : 'inherit' }}">
                {{ $etiket }}
            </button>
        @endforeach
    </div>

    @if ($aktifSekme === 'tutanak')

    {{-- 1. FİRMA & ÇALIŞAN --}}
    <x-filament::section icon="heroicon-o-scale" icon-color="danger">
        <x-slot name="heading">1. Çalışan Bilgileri</x-slot>

        <div style="margin-bottom:1rem;max-width:260px">
            <label style="font-weight:600;font-size:.82rem">Tutanak Tarihi</label>
            <input type="date" wire:model="tutanakTarihi"
                style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
        </div>

        @if ($this->firma)
            @if ($this->calisanlar->isNotEmpty())
                <div style="margin-bottom:.75rem">
                    <label style="font-weight:600;font-size:.82rem">Hızlı Çalışan Seç</label>
                    <select wire:model.live="calisanHizliSecId"
                        style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                        <option value="">Firmaya kayıtlı çalışan yok / manuel gir</option>
                        @foreach ($this->calisanlar as $c)
                            <option value="{{ $c->id }}">{{ $c->ad_soyad }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Adı Soyadı <span style="color:#ef4444">*</span></label>
                    <input type="text" wire:model="calisanAdSoyad"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">T.C. Kimlik No</label>
                    <input type="text" wire:model="calisanTc"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Görevi</label>
                    <input type="text" wire:model="calisanGorev"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Bölümü</label>
                    <input type="text" wire:model="calisanBolum"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">İşe Giriş Tarihi</label>
                    <input type="date" wire:model="iseGirisTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>

            <div style="margin-top:.75rem">
                <label style="font-weight:600;font-size:.8rem">İstihdam Şekli</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem;margin-top:.3rem">
                    @foreach (['kadrolu' => 'Kadrolu Çalışan', 'alt_isveren' => 'Alt İşveren (Taşeron) Çalışan', 'gecici_gorevli' => 'Geçici Görevli Çalışan'] as $anahtar => $etiket)
                        @php $secili = $istihdamSekli === $anahtar; @endphp
                        <button type="button" wire:click="$set('istihdamSekli', '{{ $anahtar }}')"
                            style="padding:.5rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;text-align:center;
                                border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                            {{ $etiket }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->firma)
        {{-- 2. OLAY BİLGİLERİ --}}
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">2. Olay Bilgileri</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Olay Tarihi</label>
                    <input type="date" wire:model="olayTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Olay Saati</label>
                    <input type="time" wire:model="olaySaati"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Olay Yeri</label>
                    <input type="text" wire:model="olayYeri" placeholder="Örn: Üretim sahası"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
            </div>
            <div style="margin-top:.75rem">
                <label style="font-weight:600;font-size:.8rem">Olay Açıklaması</label>
                <textarea wire:model="olayAciklamasi" rows="2" placeholder="Olayın gelişimi, yapılan uyarılar ve çalışanın davranışı..."
                    style="width:100%;margin-top:.2rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
            </div>

            <div style="margin-top:.75rem">
                <label style="font-weight:600;font-size:.8rem">Tanıklar</label>
                <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;margin-top:.3rem;margin-bottom:.5rem">
                    <input type="text" wire:model="yeniTanikAd" placeholder="Adı Soyadı"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <input type="text" wire:model="yeniTanikGorev" placeholder="Görevi (örn. Vardiya Amiri)"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="tanikEkle">+ Ekle</x-filament::button>
                </div>
                @foreach ($taniklar as $i => $tk)
                    <div style="display:flex;justify-content:space-between;font-size:.8rem;padding:.2rem .4rem">
                        <span>{{ $tk['ad_soyad'] }} — {{ $tk['gorev'] ?: '—' }}</span>
                        <button type="button" wire:click="tanikSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 3. İHLAL EDİLEN KURALLAR --}}
        <x-filament::section icon="heroicon-o-x-circle" icon-color="danger">
            <x-slot name="heading">3. İhlal Edilen Kurallar ({{ count($ihlaller) }} seçili)</x-slot>
            <x-slot name="description">Katalogdan seçin; listede olmayan durumlar için serbest madde ekleyin</x-slot>

            @foreach ($this->ihlalKatalogu as $kategori => $maddeler)
                <div style="margin-bottom:.6rem">
                    <div style="font-weight:700;font-size:.78rem;color:{{ $kirmizi }};margin-bottom:.3rem;text-transform:uppercase">{{ $kategori }}</div>
                    <div style="display:flex;flex-direction:column;gap:.3rem">
                        @foreach ($maddeler as $m)
                            @php $secili = $this->ihlalSeciliMi($m['madde']); @endphp
                            <button type="button" wire:click="ihlalToggle('{{ addslashes($m['madde']) }}', '{{ addslashes($m['dayanak']) }}')"
                                style="text-align:left;padding:.4rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                    border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                                {{ $secili ? '☑' : '☐' }} {{ $m['madde'] }}
                                <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $m['dayanak'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div style="{{ $kutu }};margin-top:.5rem">
                <div style="font-weight:600;font-size:.8rem;margin-bottom:.4rem">Serbest Madde Ekle</div>
                <textarea wire:model="serbestIhlalMetni" rows="2" placeholder="İhlal açıklaması (tutanağa yazılacak cümle)"
                    style="width:100%;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem;margin-bottom:.4rem"></textarea>
                <div style="display:grid;grid-template-columns:1fr auto;gap:.5rem">
                    <input type="text" wire:model="serbestIhlalDayanak" placeholder="Yasal dayanak (boşsa: işyeri iç yönetmeliği)"
                        style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                    <x-filament::button size="sm" wire:click="serbestIhlalEkle">+ Ekle</x-filament::button>
                </div>
            </div>

            @if ($ihlaller)
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;margin-top:.75rem">
                    @foreach ($ihlaller as $i => $ih)
                        <tr>
                            <td style="padding:.25rem .5rem">{{ $ih['madde'] }}</td>
                            <td style="padding:.25rem .5rem;text-align:right">
                                <button type="button" wire:click="ihlalSil({{ $i }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </x-filament::section>

        {{-- 4. YAPTIRIM --}}
        <x-filament::section icon="heroicon-o-shield-exclamation" icon-color="danger">
            <x-slot name="heading">4. Uygulanan Yaptırım</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.5rem">
                @foreach ($this->yaptirimlar as $anahtar => $y)
                    @php $secili = $yaptirim === $anahtar; @endphp
                    <button type="button" wire:click="$set('yaptirim', '{{ $anahtar }}')"
                        style="text-align:left;padding:.6rem;border-radius:.5rem;cursor:pointer;font-size:.82rem;
                            border:2px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                            background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                        <div style="font-weight:700">{{ $y['ad'] }}</div>
                        <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $y['aciklama'] }}</div>
                    </button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- 5. TEBLİĞ / TEBELLÜĞ --}}
        <x-filament::section icon="heroicon-o-calendar" icon-color="danger">
            <x-slot name="heading">5. Tebliğ / Tebellüğ</x-slot>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                <div>
                    <label style="font-weight:600;font-size:.8rem">Tebliğ Tarihi</label>
                    <input type="date" wire:model="tebligTarihi"
                        style="margin-top:.2rem;width:100%;padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                </div>
                <div>
                    <label style="font-weight:600;font-size:.8rem">Çalışanın İmza Durumu</label>
                    <div style="display:flex;gap:.5rem;margin-top:.3rem">
                        @foreach (['imzaladi' => 'İmzaladı / Teslim Aldı', 'imtina_etti' => 'İmzadan İmtina Etti'] as $anahtar => $etiket)
                            @php $secili = $imzaDurumu === $anahtar; @endphp
                            <button type="button" wire:click="$set('imzaDurumu', '{{ $anahtar }}')"
                                style="flex:1;padding:.45rem;border-radius:.4rem;cursor:pointer;font-size:.78rem;
                                    border:1px solid {{ $secili ? $kirmizi : 'rgb(107 114 128 / .3)' }};
                                    background:{{ $secili ? 'rgb(239 68 68 / .08)' : 'transparent' }}">
                                {{ $etiket }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- 6. FOTOĞRAFLAR --}}
        <x-filament::section icon="heroicon-o-camera" icon-color="danger">
            <x-slot name="heading">6. Fotoğraflar ({{ count($yeniFotograflar) }})</x-slot>

            @if ($yeniFotograflar)
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
                    @foreach ($yeniFotograflar as $i => $dosya)
                        <div style="position:relative">
                            <img src="{{ $dosya->temporaryUrl() }}" style="width:70px;height:70px;object-fit:cover;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3)">
                            <button type="button" wire:click="fotoSil({{ $i }})"
                                style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:.7rem;cursor:pointer;line-height:1">✕</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" style="font-size:.82rem">
            <p style="font-size:.75rem;color:rgb(107 114 128);margin-top:.3rem">
                Olay yerinin veya ihlalin fotoğraf kanıtı — PDF'in sonuna kanıt sayfası olarak eklenir.
            </p>
        </x-filament::section>

        {{-- 7. GEÇMİŞ TUTANAKLAR --}}
        @if ($this->gecmisTutanaklar->isNotEmpty())
            <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                <x-slot name="heading">Geçmiş Tutanaklar</x-slot>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    @foreach ($this->gecmisTutanaklar as $t)
                        <tr>
                            <td style="padding:.3rem .5rem">{{ $t->tutanak_no }} — {{ $t->calisan_ad_soyad }}</td>
                            <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="gecmisPdf({{ $t->id }})">PDF</x-filament::button>
                                <x-filament::button size="xs" color="danger" wire:click="gecmisSil({{ $t->id }})">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif

    @endif{{-- /aktifSekme tutanak --}}

    @if ($aktifSekme === 'ipc')
        @if ($this->firma)
            {{-- 1. DENETİM BİLGİLERİ --}}
            <x-filament::section icon="heroicon-o-magnifying-glass" icon-color="warning">
                <x-slot name="heading">1. Denetim Bilgileri</x-slot>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Tebliğ Tarihi</label>
                        <input type="date" wire:model="tebligTarihiIpc"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Denetim Tarihi</label>
                        <input type="date" wire:model="denetimTarihiIpc"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Tespit Eden Kurum</label>
                        <input type="text" wire:model="tespitEdenKurum" placeholder="Örn: Çalışma ve Sosyal Güvenlik Bakanlığı"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:.82rem">Müfettiş Adı</label>
                        <input type="text" wire:model="mufettisAdi"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                </div>
            </x-filament::section>

            {{-- 2. İHLAL EDİLEN HÜKÜMLER --}}
            <x-filament::section icon="heroicon-o-x-circle" icon-color="warning">
                <x-slot name="heading">2. İhlal Edilen Hükümler ({{ count($ihlallerIpc) }} seçili)</x-slot>
                <x-slot name="description">6331 sayılı Kanun m.26 kapsamındaki kategorilerden seçin; tutar TL cinsinden aşağıda ayrıca girilir.</x-slot>

                <div style="display:flex;flex-direction:column;gap:.3rem">
                    @foreach ($this->ipcMaddeKatalogu as $m)
                        @php $secili = $this->ihlalIpcSeciliMi($m['baslik']); @endphp
                        <button type="button" wire:click="ihlalIpcToggle('{{ addslashes($m['baslik']) }}', '{{ addslashes($m['aciklama']) }}')"
                            style="text-align:left;padding:.4rem .6rem;border-radius:.4rem;cursor:pointer;font-size:.8rem;
                                border:1px solid {{ $secili ? '#b45309' : 'rgb(107 114 128 / .3)' }};
                                background:{{ $secili ? 'rgb(180 83 9 / .08)' : 'transparent' }}">
                            {{ $secili ? '☑' : '☐' }} {{ $m['baslik'] }}
                            <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $m['aciklama'] }}</div>
                        </button>
                    @endforeach
                </div>

                <div style="{{ $kutu }};margin-top:.75rem">
                    <label style="font-weight:600;font-size:.8rem">Ek Açıklama (serbest metin)</label>
                    <textarea wire:model="serbestIhlalMetniIpc" rows="2" placeholder="Tebligatta yer alan ek açıklama"
                        style="width:100%;margin-top:.3rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
                </div>
            </x-filament::section>

            {{-- 3. CEZA TUTARI VE ÖDEME --}}
            <x-filament::section icon="heroicon-o-banknotes" icon-color="warning">
                <x-slot name="heading">3. Ceza Tutarı ve Ödeme</x-slot>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                    <div>
                        <label style="font-weight:600;font-size:.82rem">İdari Para Cezası Tutarı (TL)</label>
                        <input type="number" step="0.01" min="0" wire:model.live="cezaTutari"
                            style="margin-top:.3rem;width:100%;padding:.5rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    </div>
                    <div style="{{ $kutu }};text-align:center">
                        <div style="font-size:.78rem;color:rgb(107 114 128)">Peşin Ödeme Tutarı (%25 indirimli)</div>
                        <div style="font-size:1.2rem;font-weight:800;color:#b45309">{{ $this->pesinOdemeTutari() !== null ? number_format($this->pesinOdemeTutari(), 2, ',', '.').' TL' : '—' }}</div>
                    </div>
                </div>

                <div style="display:flex;gap:1.5rem;margin-top:1rem">
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem">
                        <input type="checkbox" wire:model="odemeYapildi"> Ödeme Yapıldı
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem">
                        <input type="checkbox" wire:model.live="itirazEdildi"> İtiraz Edildi
                    </label>
                </div>
                @if ($itirazEdildi)
                    <textarea wire:model="itirazNotu" rows="2" placeholder="İtiraz notu / dilekçe özeti"
                        style="width:100%;margin-top:.5rem;padding:.5rem .7rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-family:inherit;font-size:.82rem"></textarea>
                @endif

                <div style="{{ $kutu }};margin-top:1rem;background:rgb(107 114 128 / .06);font-size:.75rem;color:rgb(75 85 99)">
                    Bu karara karşı tebliğ tarihinden itibaren 15 gün içinde yetkili Sulh Ceza Hakimliği'ne itiraz
                    edilebilir (5326 s. Kabahatler Kanunu m.27). Cezanın 15 gün içinde peşin ödenmesi halinde
                    %25 indirim uygulanır (Kabahatler Kanunu m.17/6).
                </div>
            </x-filament::section>

            {{-- 4. GEÇMİŞ TEBLİĞLER --}}
            @if ($this->gecmisIpcTebligleri->isNotEmpty())
                <x-filament::section icon="heroicon-o-clock" icon-color="gray">
                    <x-slot name="heading">Geçmiş İPC Tebliğleri</x-slot>
                    <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                        @foreach ($this->gecmisIpcTebligleri as $t)
                            <tr>
                                <td style="padding:.3rem .5rem">{{ $t->belge_no }} — {{ $t->teblig_tarihi?->format('d.m.Y') }}</td>
                                <td style="padding:.3rem .5rem;text-align:right;white-space:nowrap">
                                    <x-filament::button size="xs" color="gray" wire:click="gecmisIpcPdf({{ $t->id }})">PDF</x-filament::button>
                                    <x-filament::button size="xs" color="danger" wire:click="gecmisIpcSil({{ $t->id }})">Sil</x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </x-filament::section>
            @endif
        @else
            <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
        @endif
    @endif
</x-filament-panels::page>
