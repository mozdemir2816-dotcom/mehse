@php
    $renkler = ['bos' => '#374151', 'planlandi' => '#f59e0b', 'tamamlandi' => '#10b981'];
    $etiketler = ['bos' => 'Boş', 'planlandi' => 'Planlandı', 'tamamlandi' => 'Tamamlandı'];
    $p = $this->plan;
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        İSG mevzuatına uygun yıllık çalışma planı oluşturun; her ay hücresine tıklayarak
        durumu Boş → Planlandı → Tamamlandı arasında değiştirin.
    </p>

    {{-- 1. FİRMA & YIL --}}
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="primary">
        <x-slot name="heading">Firma & Yıl</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma Seçin <span style="color:#ef4444">*</span></label>
                <select wire:model.live="firmaId"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)
                        <option value="{{ $id }}">{{ $ad }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Yıl</label>
                <select wire:model.live="yil"
                    style="margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent">
                    @foreach (range(now()->year - 1, now()->year + 2) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @if ($p)
        {{-- 2. YILLIK ÇALIŞMA PLANI --}}
        <x-filament::section icon="heroicon-o-table-cells" icon-color="primary">
            <x-slot name="heading">
                Yıllık Çalışma Planı — {{ $yil }}
                <span style="font-weight:400;font-size:.78rem;color:rgb(107 114 128)">
                    (<span style="color:{{ $renkler['bos'] }}">●</span> Boş
                    <span style="color:{{ $renkler['planlandi'] }}">●</span> Planlandı
                    <span style="color:{{ $renkler['tamamlandi'] }}">●</span> Tamamlandı — tıklayarak değiştirin)
                </span>
            </x-slot>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:.75rem;min-width:900px">
                    <tr>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Faaliyet</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Sorumlu</th>
                        <th style="text-align:left;padding:.3rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">Açıklama</th>
                        @foreach (\App\Filament\Pages\YillikPlanlar::AYLAR as $ay)
                            <th style="padding:.3rem .3rem;border-bottom:1px solid rgb(107 114 128 / .3);width:2.2rem">{{ $ay }}</th>
                        @endforeach
                        <th style="border-bottom:1px solid rgb(107 114 128 / .3)"></th>
                    </tr>
                    @foreach (($p->faaliyetler ?? []) as $fi => $f)
                        <tr>
                            <td style="padding:.3rem .5rem;font-weight:600">{{ $f['faaliyet'] }}</td>
                            <td style="padding:.3rem .5rem;color:rgb(107 114 128)">{{ $f['sorumlu'] ?? '—' }}</td>
                            <td style="padding:.3rem .5rem;color:rgb(107 114 128);font-size:.7rem">{{ $f['aciklama'] ?? '' }}</td>
                            @foreach (($f['aylar'] ?? array_fill(0, 12, 'bos')) as $ai => $durum)
                                <td style="padding:.15rem;text-align:center">
                                    <button type="button" wire:click="ayDurumDegistir({{ $fi }}, {{ $ai }})" title="{{ $etiketler[$durum] ?? $durum }}"
                                        style="width:1.4rem;height:1.4rem;border-radius:.25rem;border:none;cursor:pointer;background:{{ $renkler[$durum] ?? $renkler['bos'] }}"></button>
                                </td>
                            @endforeach
                            <td style="padding:.3rem .3rem">
                                <button type="button" wire:click="faaliyetSil({{ $fi }})" style="color:#ef4444;cursor:pointer;background:none;border:none">✕</button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>

            <div style="margin-top:1rem;display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.5rem">
                <input type="text" wire:model="yeniFaaliyet" placeholder="Faaliyet"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniSorumlu" placeholder="Sorumlu"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <input type="text" wire:model="yeniAciklama" placeholder="Açıklama"
                    style="padding:.45rem .6rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;font-size:.82rem">
                <x-filament::button size="sm" wire:click="faaliyetEkle">+ Aktivite Ekle</x-filament::button>
            </div>

            <div style="margin-top:.75rem">
                <x-filament::button size="xs" color="gray" wire:click="varsayilanaSifirla">Varsayılana Sıfırla</x-filament::button>
            </div>
        </x-filament::section>
    @else
        <p style="margin-top:1rem;font-size:.85rem;color:#f59e0b">Devam etmek için bir firma seçin.</p>
    @endif
</x-filament-panels::page>
