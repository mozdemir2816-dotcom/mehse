{{-- Risk Sihirbazı · Hızlı Kurulum ekipman kutusu ($ek, $e, $hizliEkipmanSecim, $mor, $yesil) --}}
@php
    $isaretli = in_array($ek, $hizliEkipmanSecim, true);
    $yok = $e['adet'] === 0;
    $tamam = ! $yok && $e['eklenen'] >= $e['adet'];
@endphp
<button type="button" wire:key="hizli-{{ $ek }}" @if (! $yok && ! $tamam) wire:click="hizliEkipmanToggle('{{ $ek }}')" @endif
    @disabled($yok || $tamam)
    @style([
        'text-align:left;display:flex;gap:.6rem;align-items:flex-start;padding:.7rem;border-radius:.6rem;background:transparent',
        'border:2px solid '.($isaretli ? $mor : 'rgb(107 114 128 / .3)'),
        'background:rgb(139 92 246 / .08)' => $isaretli,
        'cursor:pointer' => ! $yok && ! $tamam,
        'opacity:.5;cursor:not-allowed' => $yok,
        'border-color:rgb(34 197 94 / .6);background:rgb(34 197 94 / .06)' => $tamam,
    ])>
    <span style="flex:none;margin-top:.1rem;width:1.1rem;height:1.1rem;border-radius:.25rem;border:2px solid {{ $isaretli || $tamam ? ($tamam ? $yesil : $mor) : 'rgb(107 114 128 / .5)' }};background:{{ $isaretli ? $mor : ($tamam ? $yesil : 'transparent') }};display:grid;place-items:center;color:#fff;font-size:.7rem;line-height:1">{{ $isaretli || $tamam ? '✓' : '' }}</span>
    <x-filament::icon :icon="$e['ikon']" style="flex:none;width:1.4rem;height:1.4rem;color:{{ $mor }}" />
    <span style="min-width:0">
        <span style="display:block;font-weight:600;font-size:.85rem">{{ $e['ad'] }}</span>
        <span style="display:block;font-size:.72rem;color:rgb(107 114 128)">
            @if ($yok) kütüphanede yok — manuel ekleyin
            @elseif ($tamam) {{ $e['adet'] }} madde eklendi
            @else {{ $e['adet'] }} madde @if ($e['eklenen']) · {{ $e['eklenen'] }} ekli @endif
            @endif
        </span>
    </span>
</button>
