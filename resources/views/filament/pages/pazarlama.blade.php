@php
    $mavi = '#2563EB';
    $yesil = '#16A34A';
    $sari = '#D97706';
    $kirmizi = '#DC2626';
    $kutu = 'background:#FFFFFF;border:1px solid #E2E8F0;border-radius:12px;padding:1rem';
@endphp
<x-filament-panels::page>
<div style="display:flex;flex-direction:column;gap:1rem">
        @php $po = $this->pazarlamaOzeti; @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem">
            @foreach ([
                ['Toplam Aday', $po['toplam'], $mavi],
                ['Teklif Aşamasında', $po['teklif'], $sari],
                ['Kazanılanlar', $po['kazanilan'], $yesil],
                ['Açık Hatırlatmalar', $po['acik_hatirlatma'], $kirmizi],
            ] as [$etiket, $deger, $renk])
                <div style="{{ $kutu }};padding:.85rem 1rem">
                    <div style="font-size:.74rem;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.03em">{{ $etiket }}</div>
                    <div style="font-size:1.5rem;font-weight:800;color:{{ $renk }};margin-top:.15rem">{{ $deger }}</div>
                </div>
            @endforeach
        </div>

        <div style="{{ $kutu }};overflow-x:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;flex-wrap:wrap;gap:.5rem">
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <input type="text" wire:model.live.debounce.400ms="pazarlamaArama" placeholder="Firma, yetkili veya telefon ara..."
                        style="padding:.45rem .7rem;border:1px solid #CBD5E1;border-radius:8px;font-size:.82rem;min-width:14rem;background:#FFFFFF">
                    <select wire:model.live="pazarlamaAsamaFiltre" style="padding:.45rem .7rem;border:1px solid #CBD5E1;border-radius:8px;font-size:.82rem;background:#FFFFFF">
                        <option value="">Tüm aşamalar</option>
                        @foreach (config('isg.pazarlama.asamalar') as $anahtar => $ad)
                            <option value="{{ $anahtar }}">{{ $ad }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filament::button size="sm" icon="heroicon-o-plus" wire:click="mountAction('adayFirmaKaydet')">Aday Firma Ekle</x-filament::button>
            </div>

            @if ($this->adayFirmalar->isEmpty())
                <div style="text-align:center;padding:2rem;color:#64748B">Bu filtrelerde aday firma bulunamadı.</div>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:.83rem">
                    <thead><tr style="text-align:left;color:#64748B;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em">
                        <th style="padding:.5rem .45rem">Unvan</th><th style="padding:.5rem .45rem">Yetkili</th>
                        <th style="padding:.5rem .45rem">Telefon</th><th style="padding:.5rem .45rem">Şehir</th>
                        <th style="padding:.5rem .45rem">Aşama</th><th style="padding:.5rem .45rem">Hatırlatma</th>
                        <th style="padding:.5rem .45rem">İşlemler</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($this->adayFirmalar as $aday)
                        <tr style="border-top:1px solid #E2E8F0">
                            <td style="padding:.5rem .45rem;font-weight:600">{{ $aday->unvan }}</td>
                            <td style="padding:.5rem .45rem">{{ $aday->yetkili_ad ?: '—' }}</td>
                            <td style="padding:.5rem .45rem">{{ $aday->telefon ?: '—' }}</td>
                            <td style="padding:.5rem .45rem">{{ $aday->sehir ?: '—' }}</td>
                            <td style="padding:.5rem .45rem">
                                <x-filament::badge :color="$aday->asama === 'kazanildi' ? 'success' : ($aday->asama === 'kaybedildi' ? 'danger' : 'warning')">
                                    {{ $aday->asamaEtiketi() }}
                                </x-filament::badge>
                            </td>
                            <td style="padding:.5rem .45rem;color:{{ $aday->acikHatirlatmasiMi() ? $kirmizi : 'inherit' }}">
                                {{ $aday->hatirlatma_tarihi?->format('d.m.Y') ?: '—' }}
                            </td>
                            <td style="padding:.5rem .45rem;white-space:nowrap">
                                <div style="display:inline-flex;align-items:center;gap:.35rem">
                                    @if ($aday->asama === 'kazanildi')
                                        <x-filament::button size="xs" color="success" icon="heroicon-o-arrow-right-circle"
                                            wire:click="adayFirmaDonustur({{ $aday->id }})">Firmaya Dönüştür</x-filament::button>
                                    @endif
                                    <x-filament::icon-button icon="heroicon-o-pencil-square" label="Düzenle"
                                        wire:click="mountAction('adayFirmaKaydet', { id: {{ $aday->id }} })" />
                                    <x-filament::icon-button icon="heroicon-o-trash" label="Sil" color="danger"
                                        wire:click="adayFirmaSil({{ $aday->id }})" wire:confirm="Bu aday firma silinsin mi?" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
</div>
</x-filament-panels::page>
