@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'width:100%;padding:.4rem .55rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $bugun = \Illuminate\Support\Carbon::today();
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Çalışanların işe giriş / periyodik muayene ve işe özgü tetkiklerini (odyometri, SFT,
        portör, psikoteknik vb.) takip edin. Tetkik tarihi girince sonraki tetkik tarihi
        periyoda / tehlike sınıfına göre otomatik hesaplanır. Muayene formunu (EK-2) “Muayene
        Formu” ekranından üretebilirsiniz.
    </p>

    <x-filament::section icon="heroicon-o-heart" icon-color="primary">
        <x-slot name="heading">Firma</x-slot>
        <select wire:model.live="firmaId" style="{{ $girdi }};max-width:420px">
            <option value="">— Firma seçin —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}">{{ $ad }}</option>
            @endforeach
        </select>
    </x-filament::section>

    @if ($this->firma)
        <x-filament::section icon="heroicon-o-plus-circle" icon-color="gray">
            <x-slot name="heading">Tetkik Satırı Ekle</x-slot>

            @if ($this->calisanlar->isEmpty())
                <p style="font-size:.82rem;color:#f59e0b">Bu firmaya kayıtlı aktif çalışan yok — önce Çalışanlar ekranından ekleyin.</p>
            @else
                <div style="{{ $kutu }};display:grid;grid-template-columns:1.4fr 1.6fr auto auto;gap:.5rem;align-items:end">
                    <div>
                        <label style="font-size:.78rem;font-weight:600">Çalışan</label>
                        <select wire:model="yeniCalisanId" style="{{ $girdi }}">
                            <option value="">— seçin —</option>
                            @foreach ($this->calisanlar as $c)
                                <option value="{{ $c->id }}">{{ $c->ad_soyad }}@if ($c->gorev) — {{ $c->gorev }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.78rem;font-weight:600">Tetkik Türü</label>
                        <select wire:model="yeniTur" style="{{ $girdi }}">
                            @foreach ($this->turler as $anahtar => $tanim)
                                <option value="{{ $anahtar }}">{{ $tanim['ad'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-filament::button size="sm" wire:click="satirEkle">Ekle</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="tumCalisanlaraEkle" tooltip="Seçili tetkik türünü tüm aktif çalışanlara ekle">Tüm çalışanlara</x-filament::button>
                </div>
                <p style="font-size:.72rem;color:rgb(107 114 128);margin-top:.4rem">
                    {{ $this->turler[$yeniTur]['aciklama'] ?? '' }}
                </p>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-clipboard-document-check" icon-color="primary">
            <x-slot name="heading">Sağlık Gözetimi Listesi ({{ count($satirlar) }})</x-slot>
            <x-slot name="description">Kaydetmek için sağ üstteki “Kaydet”. Kırmızı sonraki tetkik tarihi = süresi geçmiş.</x-slot>

            @if (count($satirlar) === 0)
                <p style="color:rgb(107 114 128);font-size:.85rem">Yukarıdan tetkik satırı ekleyin.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:.78rem;min-width:900px">
                        <thead>
                            <tr style="text-align:left;background:rgb(107 114 128 / .08)">
                                <th style="padding:.4rem">Çalışan</th>
                                <th style="padding:.4rem">Tetkik</th>
                                <th style="padding:.4rem;width:130px">Tetkik Tarihi</th>
                                <th style="padding:.4rem;width:110px">Sonraki</th>
                                <th style="padding:.4rem;width:140px">Sonuç</th>
                                <th style="padding:.4rem;width:120px">Rapor No</th>
                                <th style="padding:.4rem"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($satirlar as $i => $s)
                                @php
                                    $sonraki = !empty($s['sonraki_tarih']) ? \Illuminate\Support\Carbon::parse($s['sonraki_tarih']) : null;
                                    $gecmis = $sonraki && $sonraki->lt($bugun);
                                @endphp
                                <tr style="border-top:1px solid rgb(107 114 128 / .18)">
                                    <td style="padding:.35rem">
                                        <div style="font-weight:600">{{ $s['calisan_adi'] ?? '' }}</div>
                                        <div style="font-size:.68rem;color:rgb(107 114 128)">{{ $s['gorev'] ?? '' }}</div>
                                    </td>
                                    <td style="padding:.35rem">{{ $this->turler[$s['tetkik_turu']]['ad'] ?? $s['tetkik_turu'] }}</td>
                                    <td style="padding:.35rem"><input type="date" wire:model.blur="satirlar.{{ $i }}.tarih" style="{{ $girdi }}"></td>
                                    <td style="padding:.35rem;{{ $gecmis ? 'color:#ef4444;font-weight:600' : 'color:rgb(107 114 128)' }}">
                                        {{ $sonraki ? $sonraki->format('d.m.Y') : '—' }}
                                    </td>
                                    <td style="padding:.35rem">
                                        <select wire:model.blur="satirlar.{{ $i }}.sonuc" style="{{ $girdi }}">
                                            @foreach ($this->sonuclar as $anahtar => $etiket)
                                                <option value="{{ $anahtar }}">{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="padding:.35rem"><input type="text" wire:model.blur="satirlar.{{ $i }}.rapor_no" style="{{ $girdi }}"></td>
                                    <td style="padding:.35rem;text-align:center">
                                        <button type="button" wire:click="satirSil({{ $i }})" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1rem">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div style="margin-top:1rem">
                <label style="font-size:.8rem;font-weight:600">Genel Not</label>
                <input type="text" wire:model.blur="genelNot" style="{{ $girdi }}" placeholder="örn. Muayeneleri yapan işyeri hekimi; OSGB sözleşme no">
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
