@php
    $mor = 'rgb(139 92 246)';
    $yesil = 'rgb(34 197 94)';
    $sari = 'rgb(245 158 11)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(128 116 148 / .3);border-radius:.75rem;padding:1rem';
@endphp
<x-filament-panels::page>
<div style="display:flex;flex-direction:column;gap:1rem">
        @php $po = $this->pazarlamaOzeti; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem">
            @foreach ([
                ['Toplam Aday', $po['toplam'], $mor],
                ['Teklif Aşamasında', $po['teklif'], $sari],
                ['Kazanılanlar', $po['kazanilan'], $yesil],
                ['Açık Hatırlatmalar', $po['acik_hatirlatma'], $kirmizi],
            ] as [$etiket, $deger, $renk])
                <div style="{{ $kutu }};padding:.75rem">
                    <div style="font-size:.75rem;color:rgb(128 116 148);text-transform:uppercase">{{ $etiket }}</div>
                    <div style="font-size:1.4rem;font-weight:800;color:{{ $renk }}">{{ $deger }}</div>
                </div>
            @endforeach
        </div>

        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;flex-wrap:wrap;gap:.5rem">
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <input type="text" wire:model.live.debounce.400ms="pazarlamaArama" placeholder="Firma, yetkili veya telefon ara..."
                        style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem;min-width:14rem">
                    <select wire:model.live="pazarlamaAsamaFiltre" style="padding:.4rem .6rem;border:1px solid rgb(128 116 148 / .3);border-radius:.4rem;font-size:.82rem">
                        <option value="">Tüm aşamalar</option>
                        @foreach (config('isg.pazarlama.asamalar') as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filament::button size="sm" wire:click="mountAction('adayFirmaKaydet')">+ Aday Firma Ekle</x-filament::button>
            </div>

            @if ($this->adayFirmalar->isEmpty())
                <div style="text-align:center;padding:2rem;color:rgb(128 116 148)">Bu filtrelerde aday firma bulunamadı.</div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:rgb(128 116 148)">
                        <th style="padding:.4rem">Unvan</th><th style="padding:.4rem">Yetkili</th>
                        <th style="padding:.4rem">Telefon</th><th style="padding:.4rem">Şehir</th>
                        <th style="padding:.4rem">Aşama</th><th style="padding:.4rem">Hatırlatma</th>
                        <th style="padding:.4rem">İşlemler</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->adayFirmalar as $aday)
                        <tr style="border-top:1px solid rgb(128 116 148 / .15)">
                            <td style="padding:.4rem;font-weight:600">{{ $aday->unvan }}</td>
                            <td style="padding:.4rem">{{ $aday->yetkili_ad ?: '—' }}</td>
                            <td style="padding:.4rem">{{ $aday->telefon ?: '—' }}</td>
                            <td style="padding:.4rem">{{ $aday->sehir ?: '—' }}</td>
                            <td style="padding:.4rem">
                                <x-filament::badge :color="$aday->asama === 'kazanildi' ? 'success' : ($aday->asama === 'kaybedildi' ? 'danger' : 'warning')">
                                    {{ $aday->asamaEtiketi() }}
                                </x-filament::badge>
                            </td>
                            <td style="padding:.4rem;color:{{ $aday->acikHatirlatmasiMi() ? $kirmizi : 'inherit' }}">
                                {{ $aday->hatirlatma_tarihi?->format('d.m.Y') ?: '—' }}
                            </td>
                            <td style="padding:.4rem;white-space:nowrap">
                                <x-filament::icon-button icon="heroicon-o-pencil-square" label="Düzenle"
                                    wire:click="mountAction('adayFirmaKaydet', { id: {{ $aday->id }} })" />
                                <x-filament::icon-button icon="heroicon-o-trash" label="Sil" color="danger"
                                    wire:click="adayFirmaSil({{ $aday->id }})" wire:confirm="Bu aday firma silinsin mi?" />
                                @if ($aday->asama === 'kazanildi')
                                    <x-filament::icon-button icon="heroicon-o-arrow-right-circle" label="Firmaya Dönüştür" color="success"
                                        wire:click="adayFirmaDonustur({{ $aday->id }})" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        {{ $this->adayFirmaKaydetAction }}
</div>
</x-filament-panels::page>
