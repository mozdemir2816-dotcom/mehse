@php
    $girdi = 'padding:.45rem .7rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.85rem';
    $kart = 'border:1px solid rgb(107 114 128 / .2);border-radius:.75rem;padding:.75rem 1rem';
    $th = 'text-align:left;padding:.5rem .6rem;font-size:.7rem;letter-spacing:.05em;text-transform:uppercase;color:rgb(107 114 128);border-bottom:1px solid rgb(107 114 128 / .2)';
    $td = 'padding:.5rem .6rem;border-bottom:1px solid rgb(107 114 128 / .12);vertical-align:top';
    $tipler = $this->tipSecenekleri();
@endphp

<x-filament-panels::page>
    <div style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.75rem">
        Ekipman risk analizi Excel'lerinizi (No · Ekipman · Faaliyet · Tehlike · Olasılık P · Frekans F · Şiddet S · Açıklayıcı DÖF · Hedef P/F/S · Sorumlu · Önerilen termin · dayanaklar)
        seçin. Her ekipman kütüphanede ayrı kategori olur; şiddet 40 → 30 (ölüm), 100 → 40 (çoklu ölüm) çevrilir.
        <strong>Ekipman tipi</strong> seçerseniz, o ekipman bir firmaya eklendiğinde bu maddeler firmanın risk değerlendirmesine otomatik önerilir.
    </div>

    <div style="{{ $kart }}">
        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.35rem">Excel dosyaları (birden çok seçebilirsiniz)</label>
        <input type="file" wire:model="dosyalar" multiple accept=".xlsx,.xls" style="{{ $girdi }};width:100%;max-width:520px">
        <div wire:loading wire:target="dosyalar" style="font-size:.8rem;color:rgb(107 114 128);margin-top:.35rem">Dosyalar okunuyor…</div>
        @error('dosyalar.*') <div style="font-size:.8rem;color:rgb(220 38 38);margin-top:.35rem">{{ $message }}</div> @enderror
    </div>

    @if ($hatalar)
        <div style="{{ $kart }};border-color:rgb(220 38 38 / .4);background:rgb(220 38 38 / .05)">
            @foreach ($hatalar as $h)
                <div style="font-size:.82rem;color:rgb(185 28 28)">⚠ {{ $h }}</div>
            @endforeach
        </div>
    @endif

    @if ($onizleme)
        <div style="{{ $kart }};padding:0;overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem">
                <thead>
                    <tr>
                        <th style="{{ $th }}">Dosya / ekipman</th>
                        <th style="{{ $th }}">Madde</th>
                        <th style="{{ $th }}">Kütüphane kategorisi</th>
                        <th style="{{ $th }}">Bağlanacak ekipman tipi</th>
                        <th style="{{ $th }}"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($onizleme as $i => $s)
                        @php $mevcut = $this->mevcutMadde($s['kategori_adi']); @endphp
                        <tr wire:key="satir-{{ $s['anahtar'] }}">
                            <td style="{{ $td }}">
                                <div style="font-weight:600">{{ $s['ekipman'] }}</div>
                                <div style="font-size:.75rem;color:rgb(107 114 128)">{{ $s['dosya'] }}</div>
                            </td>
                            <td style="{{ $td }};white-space:nowrap">{{ $s['madde'] }}</td>
                            <td style="{{ $td }};min-width:240px">
                                <input type="text" wire:model.blur="onizleme.{{ $i }}.kategori_adi" style="{{ $girdi }};width:100%">
                                <div style="font-size:.72rem;margin-top:.2rem;color:{{ $mevcut !== null ? 'rgb(217 119 6)' : 'rgb(21 128 61)' }}">
                                    {{ $mevcut !== null ? "Kütüphanede var ({$mevcut} madde) — güncellenecek" : 'Yeni kategori' }}
                                </div>
                                @error("onizleme.$i.kategori_adi") <div style="font-size:.75rem;color:rgb(220 38 38)">{{ $message }}</div> @enderror
                            </td>
                            <td style="{{ $td }};min-width:240px">
                                <select wire:model="onizleme.{{ $i }}.ekipman_tipi" style="{{ $girdi }};width:100%">
                                    <option value="">— Bağlama (yalnız kütüphanede kalsın) —</option>
                                    @foreach ($tipler as $grup => $secenekler)
                                        <optgroup label="{{ $grup }}">
                                            @foreach ($secenekler as $tip)
                                                <option value="{{ $tip }}">{{ $tip }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </td>
                            <td style="{{ $td }};text-align:right">
                                <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Listeden çıkar" wire:click="satirKaldir({{ $i }})" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <x-filament::button icon="heroicon-o-check" color="success" wire:click="kaydet" wire:loading.attr="disabled">Kütüphaneye Kaydet</x-filament::button>
            <x-filament::button color="gray" wire:click="temizle">Vazgeç</x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
