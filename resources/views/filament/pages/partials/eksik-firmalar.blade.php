{{--
    "Eksik Olan Firmalar" hızlı erişim — bu belgeyi henüz tamamlamamış aktif
    firmaları listeler, tıklanınca sayfanın $firmaId'sini o firmaya ayarlar.
    Kullanım: @include('filament.pages.partials.eksik-firmalar', ['kriterAnahtari' => 'acil_durum_plani'])
--}}
@php
    $eksikFirmalar = \App\Support\PortfoyKarne::eksikFirmalar(\Filament\Facades\Filament::auth()->id(), $kriterAnahtari);
@endphp
@if ($eksikFirmalar->isNotEmpty())
    <details style="border:1px solid rgb(217 119 6 / .35);background:rgb(217 119 6 / .06);border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1rem">
        <summary style="cursor:pointer;font-weight:600;font-size:.85rem;color:rgb(217 119 6)">
            ⚠ Eksik Olan Firmalar ({{ $eksikFirmalar->count() }})
        </summary>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem">
            @foreach ($eksikFirmalar as $f)
                <button type="button" wire:click="$set('firmaId', {{ $f->id }})"
                    style="padding:.3rem .65rem;border-radius:.4rem;border:1px solid rgb(107 114 128 / .3);background:transparent;cursor:pointer;font-size:.78rem">
                    {{ $f->unvan }}
                </button>
            @endforeach
        </div>
    </details>
@endif
