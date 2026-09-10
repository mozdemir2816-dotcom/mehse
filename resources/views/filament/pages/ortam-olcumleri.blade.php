@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.4rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.82rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İşyerinde risk değerlendirmesine göre yapılması gereken ortam ölçümlerini (gürültü, toz,
        aydınlatma, termal konfor, kimyasal maruziyet vb.) listeleyin; her ölçüm için tarih,
        laboratuvar, ölçülen/sınır değer ve sonucu girin. Sonraki ölçüm tarihi periyoda göre
        otomatik hesaplanır.
    </p>

    <x-filament::section icon="heroicon-o-beaker" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }};max-width:420px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        {{-- KATALOG + SERBEST EKLEME --}}
        <x-filament::section icon="heroicon-o-plus-circle" icon-color="gray" collapsible collapsed>
            <x-slot name="heading">Ölçüm Ekle</x-slot>

            <div style="display:flex;flex-direction:column;gap:.5rem">
                @foreach ($this->katalog as $grup => $maddeler)
                    <div style="{{ $kutu }}">
                        <div style="font-weight:600;font-size:.85rem;margin-bottom:.5rem">{{ $grup }}</div>
                        <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                            @foreach ($maddeler as $m)
                                <x-filament::button size="xs" color="gray" icon="heroicon-o-plus"
                                    wire:click="katalogdanEkle(@js($grup), @js($m['ad']))">
                                    {{ $m['ad'] }} <span style="opacity:.6">· {{ $m['periyot_ay'] }} ay</span>
                                </x-filament::button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="{{ $kutu }};margin-top:.75rem;display:grid;grid-template-columns:2fr 1.5fr .8fr auto;gap:.5rem;align-items:end">
                <div>
                    <label style="font-size:.78rem;font-weight:600">Ölçüm parametresi (serbest)</label>
                    <input type="text" wire:model="yeniParametre" style="{{ $girdi }}" placeholder="örn. Kaynak Dumanı - Kaynakhane">
                </div>
                <div>
                    <label style="font-size:.78rem;font-weight:600">Grup</label>
                    <input type="text" wire:model="yeniGrup" style="{{ $girdi }}" placeholder="Diğer">
                </div>
                <div>
                    <label style="font-size:.78rem;font-weight:600">Periyot (ay)</label>
                    <input type="number" min="1" wire:model="yeniPeriyot" style="{{ $girdi }}">
                </div>
                <x-filament::button size="sm" wire:click="serbestOlcumEkle">Ekle</x-filament::button>
            </div>
        </x-filament::section>

        {{-- ÖLÇÜM LİSTESİ --}}
        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">Ölçüm Listesi ({{ count($satirlar) }})</x-slot>
            <x-slot name="description">Değişiklikleri kaydetmek için sağ üstteki “Kaydet” butonunu kullanın.</x-slot>

            @if (count($satirlar) === 0)
                <p style="color:rgb(107 114 128);font-size:.85rem">Yukarıdan ölçüm ekleyin.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:1000px">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.5rem">Parametre</th>
                                <th style="padding:.5rem;width:130px">Bölge / Ölçüm Noktası</th>
                                <th style="padding:.5rem;width:64px">Periyot (ay)</th>
                                <th style="padding:.5rem;width:130px">Ölçüm Tarihi</th>
                                <th style="padding:.5rem;width:150px">Laboratuvar / Rapor No</th>
                                <th style="padding:.5rem;width:150px">Ölçülen / Sınır / Birim</th>
                                <th style="padding:.5rem;width:140px">Sonuç</th>
                                <th style="padding:.5rem;width:130px">Sonraki Ölçüm</th>
                                <th style="padding:.5rem"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($satirlar as $i => $s)
                                <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                    <td style="padding:.4rem">
                                        <div style="font-weight:600">{{ $s['parametre'] }}</div>
                                        <div style="color:rgb(107 114 128);font-size:.72rem">{{ $s['grup'] }}</div>
                                    </td>
                                    <td style="padding:.4rem"><input type="text" wire:model.blur="satirlar.{{ $i }}.bolge" style="{{ $girdi }}"></td>
                                    <td style="padding:.4rem"><input type="number" min="1" wire:model.blur="satirlar.{{ $i }}.periyot_ay" style="{{ $girdi }}"></td>
                                    <td style="padding:.4rem"><input type="date" wire:model.blur="satirlar.{{ $i }}.olcum_tarihi" style="{{ $girdi }}"></td>
                                    <td style="padding:.4rem">
                                        <input type="text" wire:model.blur="satirlar.{{ $i }}.laboratuvar" placeholder="Yetkili laboratuvar" style="{{ $girdi }};margin-bottom:.2rem">
                                        <input type="text" wire:model.blur="satirlar.{{ $i }}.rapor_no" placeholder="Rapor no" style="{{ $girdi }}">
                                    </td>
                                    <td style="padding:.4rem">
                                        <div style="display:flex;gap:.2rem">
                                            <input type="text" wire:model.blur="satirlar.{{ $i }}.olculen_deger" placeholder="Ölçülen" style="{{ $girdi }}">
                                            <input type="text" wire:model.blur="satirlar.{{ $i }}.sinir_deger" placeholder="Sınır" style="{{ $girdi }}">
                                        </div>
                                        <input type="text" wire:model.blur="satirlar.{{ $i }}.birim" placeholder="Birim (dB(A), mg/m³ ...)" style="{{ $girdi }};margin-top:.2rem">
                                    </td>
                                    <td style="padding:.4rem">
                                        <select wire:model.blur="satirlar.{{ $i }}.sonuc" style="{{ $girdi }}">
                                            @foreach ($this->sonuclar as $anahtar => $etiket)
                                                <option value="{{ $anahtar }}">{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="padding:.4rem">
                                        <input type="date" wire:model.blur="satirlar.{{ $i }}.sonraki_olcum_tarihi" style="{{ $girdi }}">
                                        <div style="font-size:.68rem;color:rgb(107 114 128)">boş = otomatik</div>
                                    </td>
                                    <td style="padding:.4rem;text-align:center">
                                        <button type="button" wire:click="olcumSil({{ $i }})"
                                            style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top:1rem">
                <label style="font-size:.8rem;font-weight:600">Genel Not</label>
                <input type="text" wire:model.blur="genelNot" style="{{ $girdi }}" placeholder="örn. Ölçümleri yapan laboratuvar akreditasyon no; risk değerlendirmesi referansı">
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
