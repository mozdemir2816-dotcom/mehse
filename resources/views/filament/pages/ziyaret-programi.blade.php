@php
    $mor = 'rgb(124 58 237)';
    $girdi = 'width:100%;padding:.4rem .5rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.78rem';
    $durumRenk = ['bos' => 'rgb(156 163 175)', 'planlandi' => 'rgb(180 83 9)', 'tamamlandi' => 'rgb(21 128 61)'];
    $durumEtiket = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Firma başına yıllık, 12 aylık saha ziyaret programı. Durum hücresine tıklayarak
        Boş→Planlandı→Tamamlandı arasında geçiş yapın; isterseniz AI'dan o ay için kısa
        bir amaç/kapsam önerisi alın.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
        <div>
            <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
            <select wire:model.live="firmaId" style="{{ $girdi }};margin-top:.3rem;padding:.55rem .75rem">
                <option value="">— Firma seçin —</option>
                @foreach ($this->firmalar as $id => $ad)
                    <option value="{{ $id }}">{{ $ad }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-weight:600;font-size:.82rem">Yıl</label>
            <input type="number" wire:model.live="yil" style="{{ $girdi }};margin-top:.3rem;padding:.55rem .75rem">
        </div>
    </div>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-calendar" icon-color="primary">
            <x-slot name="heading">{{ $yil }} Yılı Ziyaret Programı</x-slot>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;min-width:900px">
                    <tr>
                        <th style="text-align:left;padding:.4rem;width:70px">Ay</th>
                        <th style="text-align:left;padding:.4rem;width:130px">Tarih</th>
                        <th style="text-align:left;padding:.4rem">Amaç / Kapsam</th>
                        <th style="text-align:left;padding:.4rem;width:90px">Süre (sa.)</th>
                        <th style="text-align:left;padding:.4rem;width:110px">Durum</th>
                        <th style="text-align:left;padding:.4rem;width:160px">Notlar</th>
                    </tr>
                    @foreach (\App\Models\ZiyaretProgrami::AYLAR as $i => $ayAdi)
                        @php $z = $this->program->ziyaretler[$i] ?? ['tarih' => null, 'amac' => null, 'durum' => 'bos', 'sure_saat' => null, 'notlar' => null]; @endphp
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.4rem;font-weight:600">{{ $ayAdi }}</td>
                            <td style="padding:.4rem">
                                <input type="date" value="{{ $z['tarih'] }}"
                                    wire:change="ayGuncelle({{ $i }}, 'tarih', $event.target.value)" style="{{ $girdi }}">
                            </td>
                            <td style="padding:.4rem">
                                <div style="display:flex;gap:.3rem">
                                    <input type="text" list="amac-katalogu" value="{{ $z['amac'] }}"
                                        wire:change="ayGuncelle({{ $i }}, 'amac', $event.target.value)" style="{{ $girdi }}">
                                    <x-filament::icon-button icon="heroicon-o-sparkles" color="primary" size="sm"
                                        wire:click="aiAmacOner({{ $i }})" tooltip="AI'dan öneri al"/>
                                </div>
                            </td>
                            <td style="padding:.4rem">
                                <input type="number" step="0.5" min="0" value="{{ $z['sure_saat'] }}"
                                    wire:change="ayGuncelle({{ $i }}, 'sure_saat', $event.target.value)" style="{{ $girdi }}">
                            </td>
                            <td style="padding:.4rem">
                                <button type="button" wire:click="durumDegistir({{ $i }})"
                                    style="width:100%;padding:.4rem;border-radius:.4rem;cursor:pointer;font-size:.75rem;font-weight:700;
                                        border:1px solid {{ $durumRenk[$z['durum']] }};color:{{ $durumRenk[$z['durum']] }};background:transparent">
                                    {{ $durumEtiket[$z['durum']] }}
                                </button>
                            </td>
                            <td style="padding:.4rem">
                                <input type="text" value="{{ $z['notlar'] }}"
                                    wire:change="ayGuncelle({{ $i }}, 'notlar', $event.target.value)" style="{{ $girdi }}">
                            </td>
                        </tr>
                    @endforeach
                </table>
                <datalist id="amac-katalogu">
                    @foreach ($this->amacKategorileri as $k)
                        <option value="{{ $k }}"></option>
                    @endforeach
                </datalist>
            </div>
        </x-filament::section>
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
