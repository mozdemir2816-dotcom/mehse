@php
    $mor = 'rgb(139 92 246)';
    $kirmizi = 'rgb(239 68 68)';
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $cakismalar = $this->cakismalar();
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Excel'den Risk Kütüphanesi'ne yüklerken metni mevcut bir maddeye çok benzeyen
        (ama birebir aynı olmayan) satırlar burada birikir. Her biri için karar verin.
    </p>

    @if ($cakismalar->isEmpty())
        <div style="{{ $kutu }};text-align:center;padding:2rem;color:rgb(107 114 128)">
            🎉 Bekleyen çakışma yok.
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:1rem">
            @foreach ($cakismalar as $c)
                <div style="{{ $kutu }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem">
                        <x-filament::badge color="gray">{{ $c->kategori?->ad }}</x-filament::badge>
                        <x-filament::badge color="warning">%{{ $c->benzerlik_yuzdesi }} benzer</x-filament::badge>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
                        <div style="border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.65rem .8rem">
                            <div style="font-size:.72rem;font-weight:700;color:rgb(107 114 128);text-transform:uppercase;letter-spacing:.03em">Mevcut madde</div>
                            <div style="font-weight:600;font-size:.88rem;margin-top:.25rem">{{ $c->mevcutTehlike?->tehlike }}</div>
                            <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.2rem">{{ $c->mevcutTehlike?->risk }}</div>
                        </div>
                        <div style="border:1px solid {{ $mor }};border-radius:.5rem;padding:.65rem .8rem;background:rgb(139 92 246 / .06)">
                            <div style="font-size:.72rem;font-weight:700;color:{{ $mor }};text-transform:uppercase;letter-spacing:.03em">Yeni (Excel'den)</div>
                            <div style="font-weight:600;font-size:.88rem;margin-top:.25rem">{{ $c->yeni_veri['tehlike'] ?? '—' }}</div>
                            <div style="font-size:.78rem;color:rgb(107 114 128);margin-top:.2rem">{{ $c->yeni_veri['risk'] ?? '—' }}</div>
                        </div>
                    </div>

                    <div style="display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap">
                        <x-filament::button size="sm" color="gray" wire:click="mevcuduKoru({{ $c->id }})">
                            Mevcudu Koru
                        </x-filament::button>
                        <x-filament::button size="sm" color="primary" wire:click="yenisiniKullan({{ $c->id }})">
                            Yenisini Kullan
                        </x-filament::button>
                        <x-filament::button size="sm" color="gray" wire:click="ikisiniDeTut({{ $c->id }})">
                            İkisini de Tut
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
