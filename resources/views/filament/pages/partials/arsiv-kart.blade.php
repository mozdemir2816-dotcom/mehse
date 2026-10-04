{{-- Arşiv kategori kartı: $anahtar, $k (config), $o (DokumanYonetimi::kartlar), $kart (stil), $firmaSayisi --}}
@php
    $serit = $o['gecikmis'] ? '#dc2626' : ($o['yaklasan'] ? '#d97706' : null);
@endphp
<button type="button" wire:key="kart-{{ $anahtar }}" wire:click="kategoriAc('{{ $anahtar }}')" class="arsiv-kart"
    style="{{ $kart }};text-align:left;padding:.9rem 1rem;cursor:pointer;display:flex;flex-direction:column;gap:.45rem;min-height:7.5rem;color:inherit;{{ $serit ? 'border-left:4px solid '.$serit : '' }}">
    <x-filament::icon :icon="$k['ikon']" style="width:1.3rem;height:1.3rem;color:rgb(37 99 235)" />
    <span style="font-weight:600;font-size:.95rem;line-height:1.25">{{ $k['ad'] }}</span>
    <span style="margin-top:auto;font-size:.82rem;color:{{ $serit ?? 'rgb(107 114 128)' }}">
        @if ($o['gecikmis'])
            {{ $o['gecikmis'] }} gecikmiş
        @elseif ($o['yaklasan'])
            {{ $o['yaklasan'] }} yaklaşıyor
        @elseif ($o['kayit'])
            {{ $o['kayit'] }} kayıt
        @else
            Kayıt yok
        @endif
        @if ($o['imza'])<span style="display:block;color:#b45309">{{ $o['imza'] }} imza bekliyor</span>@endif
        @if ($o['eksik'] && ! $o['gecikmis'])<span style="display:block">{{ $firmaSayisi > 1 ? $o['eksik'].' firmada eksik' : 'Eksik' }}</span>@endif
    </span>
</button>
