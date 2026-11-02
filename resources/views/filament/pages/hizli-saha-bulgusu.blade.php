@php
    $inp = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.7rem 1rem';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.45rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $hata = fn (string $alan) => $errors->first($alan);
    $skor = $olasilik * $siddet;
    $otoOncelik = \App\Models\SahaBulgusu::skordanOncelik($skor);
    $gecerliOncelik = $oncelik ?: $otoOncelik;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sahada gördüğünüz her uygunsuzluk burada <strong>bir kez</strong> kaydedilir: elle, fotoğraftan ya da kısa açıklamadan AI ile,
        veya başka bir yapay zekaya hazırlattığınız Word/Excel dosyasından ("Word/Excel'den Aktar"). DÖF raporu için listeden bulguları
        seçip "Seçilenleri DÖF'e Aktar"ı kullanın. Kapsamlı kontrol listesi için
        <a href="{{ \App\Filament\Pages\SahaDenetimi::getUrl() }}" style="color:rgb(124 58 237);text-decoration:underline">Saha Denetimi</a>.
    </p>

    @php $o = $this->ozet; @endphp
    {{-- KPI çubuğu: Toplam · Açık (kritik / termini geçen rozetleriyle) · Kapatılan · Başarı oranı --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
        <div style="{{ $kutu }};border-radius:1rem">
            <div style="font-size:1.4rem;font-weight:800">{{ $o['toplam'] }}</div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">Toplam bulgu</div>
        </div>
        <div style="{{ $kutu }};border-radius:1rem">
            <div style="font-size:1.4rem;font-weight:800;color:{{ $o['acik'] > 0 ? '#d97706' : 'inherit' }}">{{ $o['acik'] }}</div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">Açık</div>
            @if ($o['kritik'] > 0 || $o['gecikmis'] > 0)
                <div style="display:flex;gap:.3rem;flex-wrap:wrap;margin-top:.3rem">
                    @if ($o['kritik'] > 0)<span style="font-size:.68rem;font-weight:700;padding:.05rem .5rem;border-radius:9999px;background:rgb(220 38 38 / .12);color:#b91c1c">{{ $o['kritik'] }} kritik</span>@endif
                    @if ($o['gecikmis'] > 0)<span style="font-size:.68rem;font-weight:700;padding:.05rem .5rem;border-radius:9999px;background:rgb(220 38 38 / .12);color:#b91c1c">{{ $o['gecikmis'] }} termini geçen</span>@endif
                </div>
            @endif
        </div>
        <div style="{{ $kutu }};border-radius:1rem">
            <div style="font-size:1.4rem;font-weight:800;color:{{ $o['kapandi'] > 0 ? '#16a34a' : 'inherit' }}">{{ $o['kapandi'] }}</div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">Kapatılan</div>
        </div>
        <div style="{{ $kutu }};border-radius:1rem">
            <div style="font-size:1.4rem;font-weight:800;color:{{ $o['basari'] >= 75 ? '#16a34a' : ($o['basari'] >= 40 ? '#d97706' : '#b91c1c') }}">%{{ $o['basari'] }}</div>
            <div style="font-size:.75rem;color:rgb(107 114 128)">Başarı oranı</div>
            <div style="height:5px;border-radius:9999px;background:rgb(107 114 128 / .2);margin-top:.3rem;overflow:hidden"><div style="height:100%;width:{{ max($o['basari'], 1) }}%;background:#16a34a"></div></div>
        </div>
    </div>

    {{-- TEK DOKUNUŞLA AI BULGU GİRİŞİ: fotoğraf (sol) + hızlı not (sağ) + tek AI düğmesi --}}
    <x-filament::section icon="heroicon-o-sparkles" icon-color="primary">
        <x-slot name="heading">Tek Dokunuşla AI Bulgu Girişi</x-slot>
        <x-slot name="description">Fotoğraf çekin ya da gördüğünüzü yazın; yapay zekâ tehlikeyi, olasılık/şiddet skorunu ve yasal dayanağı forma yazar. Kaydetmeden önce kontrol edin.</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:1rem;align-items:start">
            <div>
                <label style="{{ $lbl }}">Fotoğraf ({{ count($kaydedilenFotolar) + count($yeniFotograflar) }}/{{ config('isg.saha_bulgu.max_foto') }})</label>
                <label style="position:relative;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:.25rem;min-height:7.5rem;margin-top:.3rem;border:2px dashed rgb(107 114 128 / .45);border-radius:1rem;cursor:pointer;text-align:center;padding:.8rem;font-size:.85rem;color:rgb(107 114 128)">
                    <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" aria-label="Fotoğraf yükle"
                        style="position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer">
                    <span style="font-size:1.6rem">📷</span>
                    <span><strong>Fotoğraf çek veya sürükle-bırak</strong></span>
                    <span style="font-size:.72rem">En fazla {{ config('isg.saha_bulgu.max_foto') }} fotoğraf</span>
                </label>
                <div wire:loading wire:target="yeniFotograflar" style="font-size:.78rem;color:rgb(107 114 128)">Yükleniyor…</div>
                @if ($hata('yeniFotograflar.*'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('yeniFotograflar.*') }}</div>@endif

                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem">
                    @php $isaretDugme = 'position:absolute;left:0;right:0;bottom:0;background:rgb(0 0 0 / .6);color:#fff;border:none;border-radius:0 0 .4rem .4rem;font-size:.68rem;padding:.15rem 0;cursor:pointer'; @endphp
                    @foreach ($kaydedilenFotolar as $i => $yol)
                        @php $fotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($yol); @endphp
                        <div style="position:relative">
                            <img src="{{ $fotoUrl }}" style="width:80px;height:80px;object-fit:cover;border-radius:.4rem">
                            <button type="button" x-data x-on:click="$dispatch('foto-isaretle', { src: @js($fotoUrl), tur: 'kayitli', index: {{ $i }} })" style="{{ $isaretDugme }}">✎ İşaretle</button>
                        </div>
                    @endforeach
                    @foreach ($yeniFotograflar as $i => $f)
                        @php $fotoUrl = $f->temporaryUrl(); @endphp
                        <div style="position:relative">
                            <img src="{{ $fotoUrl }}" style="width:80px;height:80px;object-fit:cover;border-radius:.4rem">
                            <button type="button" wire:click="fotoSil({{ $i }})" aria-label="Fotoğrafı kaldır" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:.7rem;cursor:pointer">✕</button>
                            <button type="button" x-data x-on:click="$dispatch('foto-isaretle', { src: @js($fotoUrl), tur: 'yeni', index: {{ $i }} })" style="{{ $isaretDugme }}">✎ İşaretle</button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label style="{{ $lbl }}" for="hizli-not">Hızlı not</label>
                <textarea id="hizli-not" wire:model="aciklamaMetni" rows="4" placeholder="Örn. 3. kat döşeme kenarında korkuluk yok, işçiler kenarda çalışıyor" style="{{ $inp }}"></textarea>

                <x-filament::button icon="heroicon-o-sparkles" wire:click="aiDegerlendir" wire:loading.attr="disabled" wire:target="aiDegerlendir,aiIleDoldur,aciklamadanDoldur"
                    style="width:100%;min-height:3rem;margin-top:.6rem">
                    <span wire:loading.remove wire:target="aiDegerlendir,aiIleDoldur,aciklamadanDoldur">✨ Yapay Zekâ ile Değerlendir</span>
                    <span wire:loading wire:target="aiDegerlendir,aiIleDoldur,aciklamadanDoldur">Değerlendiriliyor…</span>
                </x-filament::button>
                @if ($kaynak === 'ai')<div style="font-size:.78rem;color:rgb(124 58 237);margin-top:.4rem">AI taslağı forma yazıldı — aşağıdaki alanları kontrol edip kaydedin.</div>@endif
                @if ($aiEkTespitler)
                    <x-filament::button color="warning" icon="heroicon-o-plus" wire:click="kalanTespitleriEkle" style="margin-top:.5rem">Kalan {{ count($aiEkTespitler) }} tespiti de ekle</x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-map-pin" icon-color="primary">
        <x-slot name="heading">Yer</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
            <div>
                <label style="{{ $lbl }}">İşyeri *</label>
                <select wire:model.live="firmaId" style="{{ $inp }}">
                    <option value="">İşyeri seçin</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
                @if ($hata('firmaId'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('firmaId') }}</div>@endif
            </div>
            <div>
                <label style="{{ $lbl }}">Bölüm / saha alanı</label>
                <input type="text" wire:model="bolum" list="bolum-onerileri" placeholder="Örn. Pres hattı" style="{{ $inp }}">
                <datalist id="bolum-onerileri">
                    @foreach ($this->bolumOnerileri as $b)<option value="{{ $b }}">@endforeach
                </datalist>
            </div>
            <div>
                <label style="{{ $lbl }}">Gözlem konumu</label>
                <input type="text" wire:model="gozlemKonumu" placeholder="Örn. Üretim alanı, pres önü" style="{{ $inp }}">
            </div>
        </div>
        <div x-data="{ hata: '' }" style="margin-top:.75rem;display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;font-size:.8rem">
            <x-filament::button size="sm" color="gray" icon="heroicon-o-map-pin"
                x-on:click="hata = ''; if (! navigator.geolocation) { hata = 'Tarayıcı konum desteklemiyor.'; return; }
                    navigator.geolocation.getCurrentPosition(p => $wire.konumAyarla(p.coords.latitude, p.coords.longitude), e => hata = 'Konum alınamadı: ' + e.message, { enableHighAccuracy: true, timeout: 15000 })">
                Konum al
            </x-filament::button>
            @if ($enlem !== null)
                <span>📍 {{ $enlem }}, {{ $boylam }}</span>
            @else
                <span style="color:rgb(107 114 128)">Konum isteğe bağlıdır.</span>
            @endif
            <span x-show="hata" x-text="hata" style="color:#dc2626"></span>
        </div>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
        <x-slot name="heading">Tehlike ve Uygunsuzluk</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem">
            <div>
                <label style="{{ $lbl }}">Tehlike kategorisi</label>
                <select wire:model="kategori" style="{{ $inp }}">
                    <option value="">Kategori seçin</option>
                    @foreach (config('isg.saha_tehlike_kategorileri') as $i => $k)
                        <option value="{{ $k }}">{{ $i + 1 }}. {{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Tehlike</label>
                <input type="text" wire:model="tehlike" placeholder="Örn. Korkuluksuz platform kenarı" style="{{ $inp }}">
            </div>
        </div>
        <div style="margin-top:1rem">
            <label style="{{ $lbl }}">Uygunsuzluk / risk tanımı *</label>
            <textarea wire:model="uygunsuzluk" rows="3" placeholder="Ne gördünüz, hangi çalışanı veya süreci etkiliyor?" style="{{ $inp }}"></textarea>
            @if ($hata('uygunsuzluk'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('uygunsuzluk') }}</div>@endif
        </div>
        <div style="margin-top:1rem">
            <label style="{{ $lbl }}">Mevcut önlemler</label>
            <textarea wire:model="mevcutOnlemler" rows="2" placeholder="Varsa mevcut kontrol / bariyer" style="{{ $inp }}"></textarea>
        </div>
        <div style="margin-top:1rem">
            <label style="{{ $lbl }}">Yasal gerekçe / dayanak</label>
            <input type="text" wire:model="yasalGerekce" placeholder="Örn. Yapı İşlerinde İSG Yönetmeliği Ek-4 / TS EN 13374" style="{{ $inp }}">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:1rem;align-items:end;margin-top:1rem">
            <div>
                <label style="{{ $lbl }}">Olasılık</label>
                <select wire:model.live="olasilik" style="{{ $inp }}">
                    @foreach (config('isg.saha_bulgu.olasilik') as $d => $ad)<option value="{{ $d }}">{{ $d }} · {{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Şiddet</label>
                <select wire:model.live="siddet" style="{{ $inp }}">
                    @foreach (config('isg.saha_bulgu.siddet') as $d => $ad)<option value="{{ $d }}">{{ $d }} · {{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">Öncelik</label>
                <select wire:model.live="oncelik" style="{{ $inp }}">
                    <option value="">Otomatik ({{ \App\Models\SahaBulgusu::ONCELIKLER[$otoOncelik] }})</option>
                    @foreach (\App\Models\SahaBulgusu::ONCELIKLER as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div style="text-align:center;padding:.35rem .8rem;border-radius:.6rem;color:#fff;background:{{ \App\Models\SahaBulgusu::ONCELIK_RENK[$gecerliOncelik] }}">
                <div style="font-size:1.4rem;font-weight:800;line-height:1">{{ $skor }}</div>
                <div style="font-size:.7rem">{{ \App\Models\SahaBulgusu::ONCELIKLER[$gecerliOncelik] }}</div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-wrench" icon-color="primary">
        <x-slot name="heading">Düzeltici Faaliyet</x-slot>
        <label style="{{ $lbl }}">Aksiyon açıklaması</label>
        <textarea wire:model="aksiyon" rows="2" placeholder="Kapatmak için yapılması gereken iş" style="{{ $inp }}"></textarea>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem">
            <div><label style="{{ $lbl }}">Sorumlu kişi</label><input type="text" wire:model="sorumlu" style="{{ $inp }}"></div>
            <div><label style="{{ $lbl }}">Termin</label><input type="date" wire:model="termin" style="{{ $inp }}"></div>
        </div>
    </x-filament::section>

    {{-- KAYDET & DAĞIT: bulgu nereye gitsin --}}
    <x-filament::section icon="heroicon-o-paper-airplane" icon-color="gray">
        <x-slot name="heading">Kaydet ve Dağıt</x-slot>
        <x-slot name="description">Bulgu her durumda Saha Bulguları'na kaydedilir; ek olarak seçtiğiniz yerlere de gider.</x-slot>

        <div style="display:flex;flex-wrap:wrap;gap:.6rem">
            @foreach ([
                ['dagitDof', "DÖF Listesine Ekle", 'Kayıttan sonra DÖF Oluştur ekranı bulguyla açılır'],
                ['dagitDefter', 'Onaylı Deftere Yaz', 'Firmanın Tespit-Öneri defterine madde olarak eklenir'],
            ] as [$alan, $ad, $ipucu])
                <label title="{{ $ipucu }}" style="display:inline-flex;align-items:center;gap:.55rem;min-height:2.9rem;padding:0 1rem;border:1px solid rgb(107 114 128 / .35);border-radius:9999px;cursor:pointer;font-size:.85rem;font-weight:600;{{ $this->{$alan} ? 'background:rgb(2 132 199 / .1);border-color:#0284C7;color:#0369A1' : '' }}">
                    <input type="checkbox" wire:model.live="{{ $alan }}" style="width:1.2rem;height:1.2rem">
                    {{ $ad }}
                </label>
            @endforeach
            <label title="İşe başlama tebliği ayrı bir kayıttır; Ön Bilgilendirme / Tebliğ sekmesinden hazırlanır" style="display:inline-flex;align-items:center;gap:.55rem;min-height:2.9rem;padding:0 1rem;border:1px dashed rgb(107 114 128 / .35);border-radius:9999px;font-size:.85rem;color:rgb(107 114 128)">
                <a href="{{ \App\Filament\Pages\OnBilgilendirmeler::getUrl(['firma' => $firmaId]) }}" style="color:inherit;text-decoration:underline">İşe Başlama Tebliği → ayrı sekme</a>
            </label>
        </div>

        <div style="margin-top:1rem">
            <x-filament::button icon="heroicon-o-check" wire:click="kaydet" style="min-height:3rem;padding-inline:1.5rem">Bulguyu Kaydet</x-filament::button>
        </div>
    </x-filament::section>

    {{-- LİSTE --}}
    <x-filament::section icon="heroicon-o-queue-list" icon-color="gray">
        <x-slot name="heading">Saha Bulguları {{ $firmaId ? '' : '— tüm işyerleri' }} ({{ $this->bulgular->count() }})</x-slot>
        <x-slot name="afterHeader">
            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                @if (count($secili))
                    <x-filament::button size="sm" color="warning" icon="heroicon-o-arrow-right" wire:click="dofeAktar">Seçilenleri DÖF'e Aktar ({{ count($secili) }})</x-filament::button>
                @endif
                <select wire:model.live="listeDurum" style="padding:.35rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                    <option value="acik">Açık + devam eden</option>
                    <option value="ertelendi">Ertelenen</option>
                    <option value="kapandi">Kapanan</option>
                    <option value="">Tümü</option>
                </select>
            </div>
        </x-slot>

        @if ($this->bulgular->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:960px">
                    <tr>@foreach (['', 'Bulgu', 'İşyeri / Bölüm', 'Uygunsuzluk', 'Risk / Durum', 'Sorumlu / Termin', 'Kapatma', ''] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @foreach ($this->bulgular as $b)
                        <tr wire:key="bulgu-{{ $b->id }}">
                            <td style="{{ $td }}"><input type="checkbox" wire:model.live="secili" value="{{ $b->id }}" title="DÖF'e aktarmak için seç" style="width:1.1rem;height:1.1rem"></td>
                            <td style="{{ $td }};white-space:nowrap"><strong>{{ $b->bulgu_no }}</strong><div style="font-size:.72rem;color:rgb(107 114 128)">{{ $b->created_at?->format('d.m.Y') }}{{ match ($b->kaynak) { 'ai' => ' · AI', 'dosya' => ' · dosya', default => '' } }}@if (count($b->fotograflar ?? [])) · 📷 {{ count($b->fotograflar) }}@endif</div></td>
                            <td style="{{ $td }}">{{ $b->firma?->unvan }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ collect([$b->bolum, $b->gozlem_konumu])->filter()->implode(' · ') }}@if ($b->konumLinki()) · <a href="{{ $b->konumLinki() }}" target="_blank" style="color:rgb(124 58 237)">harita</a>@endif</div></td>
                            <td style="{{ $td }};max-width:22rem">{{ \Illuminate\Support\Str::limit($b->uygunsuzluk, 110) }}@if ($b->kategori)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $b->kategori }}</div>@endif</td>
                            <td style="{{ $td }};white-space:nowrap">
                                <span style="display:inline-block;color:#fff;background:{{ $b->oncelikRengi() }};border-radius:9999px;padding:.15rem .65rem;font-weight:700;font-size:.74rem">{{ $b->skor() }} · {{ $b->oncelikEtiketi() }}</span>
                                @if ($b->durum !== 'acik')
                                    <div style="margin-top:.25rem"><span style="display:inline-block;font-size:.68rem;font-weight:700;padding:.05rem .5rem;border-radius:9999px;{{ $b->durum === 'kapandi' ? 'background:rgb(22 163 74 / .12);color:#15803d' : 'background:rgb(217 119 6 / .14);color:#b45309' }}">{{ \App\Models\SahaBulgusu::DURUMLAR[$b->durum] ?? $b->durum }}</span></div>
                                @endif
                            </td>
                            <td style="{{ $td }};white-space:nowrap">{{ $b->sorumlu ?: '—' }}<div style="font-size:.72rem;color:{{ $b->terminGectiMi() ? '#dc2626' : 'rgb(107 114 128)' }}">{{ $b->termin?->format('d.m.Y') }}{{ $b->terminGectiMi() ? ' · geçti' : '' }}</div></td>
                            <td style="{{ $td }};min-width:16rem">
                                @if ($b->durum !== 'kapandi')
                                    <div style="display:flex;gap:.35rem;align-items:center">
                                        <input type="text" wire:model="kapatNotlari.{{ $b->id }}" wire:keydown.enter="hizliKapat({{ $b->id }})" placeholder="Kapatma notu…" aria-label="Kapatma notu"
                                            style="flex:1;min-width:0;min-height:2.5rem;padding:.35rem .6rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                                        <x-filament::button size="sm" color="success" wire:click="hizliKapat({{ $b->id }})" style="min-height:2.5rem">Kapat</x-filament::button>
                                    </div>
                                @else
                                    <span style="font-size:.74rem;color:rgb(107 114 128)">{{ $b->kapanis_tarihi?->format('d.m.Y') }}@if ($b->kapanis_notu) · {{ \Illuminate\Support\Str::limit($b->kapanis_notu, 60) }}@endif</span>
                                @endif
                            </td>
                            <td style="{{ $td }};text-align:right;white-space:nowrap">
                                @if ($b->durum !== 'kapandi')
                                    <x-filament::button size="xs" color="success" outlined wire:click="whatsappHatirlat({{ $b->id }})" title="İşverene WhatsApp hatırlatması">📲 WhatsApp</x-filament::button>
                                @endif
                                {{-- Diğer işlemler tek `···` menüsünde (eskiden satır başına 6 renkli düğme) --}}
                                <x-filament::dropdown placement="bottom-end">
                                    <x-slot name="trigger">
                                        <x-filament::icon-button icon="heroicon-m-ellipsis-horizontal" label="Diğer işlemler" color="gray" size="lg" />
                                    </x-slot>
                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.list.item icon="heroicon-m-document-arrow-down" wire:click="pdf({{ $b->id }})">Tutanak Al</x-filament::dropdown.list.item>
                                        @if ($b->durum !== 'kapandi')
                                            <x-filament::dropdown.list.item icon="heroicon-m-arrow-right" wire:click="dofeAktar({{ $b->id }})">DÖF'e Aktar</x-filament::dropdown.list.item>
                                            @if ($b->durum === 'acik')
                                                <x-filament::dropdown.list.item icon="heroicon-m-play" wire:click="durumDegistir({{ $b->id }}, 'devam_ediyor')">Devam ediyor</x-filament::dropdown.list.item>
                                                <x-filament::dropdown.list.item icon="heroicon-m-clock" wire:click="durumDegistir({{ $b->id }}, 'ertelendi')">Ertele</x-filament::dropdown.list.item>
                                            @else
                                                <x-filament::dropdown.list.item icon="heroicon-m-arrow-uturn-left" wire:click="yenidenAc({{ $b->id }})">Açığa al</x-filament::dropdown.list.item>
                                            @endif
                                            <x-filament::dropdown.list.item icon="heroicon-m-check-circle" wire:click="mountAction('kapat', { id: {{ $b->id }} })">Tarihli kapat…</x-filament::dropdown.list.item>
                                        @else
                                            <x-filament::dropdown.list.item icon="heroicon-m-arrow-uturn-left" wire:click="yenidenAc({{ $b->id }})">Yeniden aç</x-filament::dropdown.list.item>
                                        @endif
                                        <x-filament::dropdown.list.item icon="heroicon-m-trash" color="danger" wire:click="sil({{ $b->id }})" wire:confirm="Bulgu ve fotoğrafları silinsin mi?">Sil</x-filament::dropdown.list.item>
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <p style="font-size:.83rem;color:rgb(107 114 128)">Bu filtrede saha bulgusu yok.</p>
        @endif
    </x-filament::section>

    @include('filament.pages.partials.foto-isaretle')
</x-filament-panels::page>
