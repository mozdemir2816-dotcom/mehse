@php
    $inp = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
    $lbl = 'font-weight:600;font-size:.82rem';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:.7rem 1rem';
    $th = 'text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3);font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:rgb(107 114 128)';
    $td = 'padding:.45rem .5rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $hata = fn (string $alan) => $errors->first($alan);
    $skor = $olasilik * $siddet;
    $skorRenk = fn (int $s) => match (true) { $s >= 16 => '#b91c1c', $s >= 10 => '#d97706', $s >= 5 => '#ca8a04', default => '#16a34a' };
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sahada gördüğünüz tek bir uygunsuzluğu hızla kaydedin: yer, tehlike, risk skoru, aksiyon ve fotoğraf kanıtı.
        Fotoğraf eklediyseniz "AI ile doldur" bir taslak üretir; kontrol edip kaydedin. Kapsamlı kontrol listesi için
        <a href="{{ \App\Filament\Pages\SahaDenetimi::getUrl() }}" style="color:rgb(124 58 237);text-decoration:underline">Saha Denetimi</a>,
        çok fotoğraflı rapor için <a href="{{ \App\Filament\Pages\AiSahaAnalizi::getUrl() }}" style="color:rgb(124 58 237);text-decoration:underline">AI Saha Analizi</a>.
    </p>

    @php $o = $this->ozet; @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.6rem">
        @foreach ([['Açık bulgu', $o['acik'], '#d97706'], ['Kritik (≥15)', $o['kritik'], '#b91c1c'], ['Termini geçen', $o['gecikmis'], '#b91c1c'], ['Kapanan', $o['kapandi'], '#16a34a']] as [$ad, $sayi, $renk])
            <div style="{{ $kutu }}">
                <div style="font-size:1.35rem;font-weight:700;color:{{ $sayi > 0 ? $renk : 'inherit' }}">{{ $sayi }}</div>
                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $ad }}</div>
            </div>
        @endforeach
    </div>

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

        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:1rem;align-items:end;margin-top:1rem">
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
            <div style="text-align:center;padding:.35rem .8rem;border-radius:.6rem;color:#fff;background:{{ $skorRenk($skor) }}">
                <div style="font-size:1.4rem;font-weight:800;line-height:1">{{ $skor }}</div>
                <div style="font-size:.7rem">{{ \App\Models\SahaBulgusu::seviye($skor) }}</div>
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

    <x-filament::section icon="heroicon-o-camera" icon-color="gray">
        <x-slot name="heading">Fotoğraf Kanıtı ({{ count($kaydedilenFotolar) + count($yeniFotograflar) }}/{{ config('isg.saha_bulgu.max_foto') }})</x-slot>
        <input type="file" wire:model="yeniFotograflar" multiple accept="image/*" style="font-size:.85rem">
        <div wire:loading wire:target="yeniFotograflar" style="font-size:.78rem;color:rgb(107 114 128)">Yükleniyor…</div>
        @if ($hata('yeniFotograflar.*'))<div style="color:#dc2626;font-size:.75rem">{{ $hata('yeniFotograflar.*') }}</div>@endif

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem">
            @foreach ($kaydedilenFotolar as $yol)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($yol) }}" style="width:80px;height:80px;object-fit:cover;border-radius:.4rem">
            @endforeach
            @foreach ($yeniFotograflar as $i => $f)
                <div style="position:relative">
                    <img src="{{ $f->temporaryUrl() }}" style="width:80px;height:80px;object-fit:cover;border-radius:.4rem">
                    <button type="button" wire:click="fotoSil({{ $i }})" style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:.7rem;cursor:pointer">✕</button>
                </div>
            @endforeach
        </div>

        <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem">
            <x-filament::button color="gray" icon="heroicon-o-sparkles" wire:click="aiIleDoldur" wire:loading.attr="disabled" wire:target="aiIleDoldur">
                <span wire:loading.remove wire:target="aiIleDoldur">AI ile doldur</span>
                <span wire:loading wire:target="aiIleDoldur">Analiz ediliyor…</span>
            </x-filament::button>
            <x-filament::button icon="heroicon-o-check" wire:click="kaydet">Bulguyu Kaydet</x-filament::button>
            @if ($kaynak === 'ai')<span style="font-size:.78rem;color:rgb(124 58 237);align-self:center">AI taslağı — kaydetmeden önce kontrol edin.</span>@endif
        </div>
    </x-filament::section>

    {{-- LİSTE --}}
    <x-filament::section icon="heroicon-o-queue-list" icon-color="gray">
        <x-slot name="heading">Saha Bulguları {{ $firmaId ? '' : '— tüm işyerleri' }} ({{ $this->bulgular->count() }})</x-slot>
        <x-slot name="afterHeader">
            <select wire:model.live="listeDurum" style="padding:.35rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem">
                <option value="acik">Açık</option>
                <option value="kapandi">Kapanan</option>
                <option value="">Tümü</option>
            </select>
        </x-slot>

        @if ($this->bulgular->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:860px">
                    <tr>@foreach (['Bulgu', 'İşyeri / Bölüm', 'Uygunsuzluk', 'Risk', 'Sorumlu / Termin', 'Foto', ''] as $b)<th style="{{ $th }}">{{ $b }}</th>@endforeach</tr>
                    @foreach ($this->bulgular as $b)
                        <tr>
                            <td style="{{ $td }};white-space:nowrap"><strong>{{ $b->bulgu_no }}</strong><div style="font-size:.72rem;color:rgb(107 114 128)">{{ $b->created_at?->format('d.m.Y') }}{{ $b->kaynak === 'ai' ? ' · AI' : '' }}</div></td>
                            <td style="{{ $td }}">{{ $b->firma?->unvan }}<div style="font-size:.72rem;color:rgb(107 114 128)">{{ collect([$b->bolum, $b->gozlem_konumu])->filter()->implode(' · ') }}@if ($b->konumLinki()) · <a href="{{ $b->konumLinki() }}" target="_blank" style="color:rgb(124 58 237)">harita</a>@endif</div></td>
                            <td style="{{ $td }};max-width:22rem">{{ \Illuminate\Support\Str::limit($b->uygunsuzluk, 110) }}@if ($b->kategori)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $b->kategori }}</div>@endif</td>
                            <td style="{{ $td }}"><span style="color:#fff;background:{{ $skorRenk($b->skor()) }};border-radius:.35rem;padding:.1rem .45rem;font-weight:700;font-size:.75rem">{{ $b->skor() }}</span><div style="font-size:.7rem;color:rgb(107 114 128)">{{ $b->seviyeEtiketi() }}</div></td>
                            <td style="{{ $td }};white-space:nowrap">{{ $b->sorumlu ?: '—' }}<div style="font-size:.72rem;color:{{ $b->terminGectiMi() ? '#dc2626' : 'rgb(107 114 128)' }}">{{ $b->termin?->format('d.m.Y') }}{{ $b->terminGectiMi() ? ' · geçti' : '' }}</div></td>
                            <td style="{{ $td }}">{{ count($b->fotograflar ?? []) }}</td>
                            <td style="{{ $td }};text-align:right;white-space:nowrap">
                                <x-filament::button size="xs" color="gray" wire:click="pdf({{ $b->id }})">Tutanak</x-filament::button>
                                @if ($b->durum === 'acik')
                                    <x-filament::button size="xs" color="warning" wire:click="dofeAktar({{ $b->id }})">DÖF'e Aktar</x-filament::button>
                                    <x-filament::button size="xs" color="success" wire:click="mountAction('kapat', { id: {{ $b->id }} })">Kapat</x-filament::button>
                                @else
                                    <x-filament::button size="xs" color="gray" wire:click="yenidenAc({{ $b->id }})">Yeniden aç</x-filament::button>
                                @endif
                                <x-filament::button size="xs" color="danger" wire:click="sil({{ $b->id }})" wire:confirm="Bulgu ve fotoğrafları silinsin mi?">Sil</x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <p style="font-size:.83rem;color:rgb(107 114 128)">Bu filtrede saha bulgusu yok.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
