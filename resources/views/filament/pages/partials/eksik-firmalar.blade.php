{{--
    "Eksik Olan Firmalar" hızlı erişim — bu belgeyi henüz tamamlamamış aktif
    firmaları listeler, tıklanınca sayfanın $firmaId'sini o firmaya ayarlar.
    Kullanım: @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_plani'])
--}}
@php
    $eksikFirmalar = \App\Support\PortfoyKarne::eksikFirmalar(\Filament\Facades\Filament::auth()->id(), $kriterAnahtari);
    $mevcutId = $this->firmaId ?? null;
    $siradaki = $eksikFirmalar->first(fn ($f) => $f->id !== $mevcutId) ?? $eksikFirmalar->first();
@endphp
@if ($eksikFirmalar->isNotEmpty())
    <details open style="border:1px solid rgb(217 119 6 / .35);background:rgb(217 119 6 / .06);border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1rem">
        <summary style="cursor:pointer;font-weight:600;font-size:.85rem;color:rgb(180 83 9);display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
            <span>⚠ Eksik Olan Firmalar ({{ $eksikFirmalar->count() }}) — hızlı tamamlamak için firmaya tıklayın</span>
            @if ($siradaki)
                <button type="button" wire:click.stop="$set('firmaId', {{ $siradaki->id }})"
                    style="padding:.22rem .6rem;border-radius:9999px;border:1px solid rgb(180 83 9);background:rgb(217 119 6 / .14);color:rgb(180 83 9);cursor:pointer;font-size:.75rem;font-weight:700">
                    Sıradaki Eksik Firma: {{ \Illuminate\Support\Str::limit($siradaki->unvan, 32) }} →
                </button>
            @endif
        </summary>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem">
            @foreach ($eksikFirmalar as $f)
                @php $seciliMi = $mevcutId === $f->id; @endphp
                <button type="button" wire:click="$set('firmaId', {{ $f->id }})"
                    style="padding:.3rem .65rem;border-radius:.4rem;border:1px solid {{ $seciliMi ? 'rgb(180 83 9)' : 'rgb(107 114 128 / .3)' }};background:{{ $seciliMi ? 'rgb(217 119 6 / .18)' : 'transparent' }};font-weight:{{ $seciliMi ? '700' : '400' }};cursor:pointer;font-size:.78rem">
                    {{ $seciliMi ? '● ' : '' }}{{ $f->unvan }}
                </button>
            @endforeach
        </div>
    </details>
@endif
