{{-- Topbar bildirim zili: açık + okunmamış bildirim sayısı → Bildirim Merkezi. --}}
@if (\App\Filament\Pages\BildirimMerkezi::canAccess())
    @php $sayi = \App\Models\Bildirim::okunmamisSayisi((int) auth()->id()); @endphp
    <a href="{{ \App\Filament\Pages\BildirimMerkezi::getUrl() }}" title="Bildirimler{{ $sayi ? ' — '.$sayi.' okunmamış' : '' }}"
       style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:9999px;color:inherit">
        <x-filament::icon icon="heroicon-o-bell" style="width:1.35rem;height:1.35rem" />
        @if ($sayi > 0)
            <span style="position:absolute;top:.05rem;right:.05rem;min-width:1.05rem;height:1.05rem;padding:0 .25rem;border-radius:9999px;background:rgb(220 38 38);color:#fff;font-size:.62rem;font-weight:700;line-height:1.05rem;text-align:center">{{ $sayi > 99 ? '99+' : $sayi }}</span>
        @endif
    </a>
@endif
