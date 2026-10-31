@php
    // Aynı işi yapan sayfaların ortak sekme şeridi — App\Support\SayfaAileleri.
    // Gruplu aile (Saha: 7 sayfa → 3 ana sekme): ana sekmeler + aktif grubun alt hap şeridi + otomatik firma bilgi çubuğu.
    $gruplu = \App\Support\SayfaAileleri::sekmeGruplari($scopes ?? []);
    $sekmeler = $gruplu ? [] : \App\Support\SayfaAileleri::sekmeler($scopes ?? []);
@endphp

@if ($gruplu && count($gruplu['ust']) > 0)
    <x-filament::tabs class="mehse-sayfa-sekmeleri mehse-saha-ana-sekmeler" style="margin-bottom:.25rem">
        @foreach ($gruplu['ust'] as $s)
            <x-filament::tabs.item tag="a" :href="$s['url']" :active="$s['aktif']" :icon="$s['ikon']">
                {{ $s['etiket'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    @if ($gruplu['alt'])
        <nav class="mehse-saha-alt-sekmeler" aria-label="Alt bölümler">
            @foreach ($gruplu['alt'] as $s)
                <a href="{{ $s['url'] }}" @class(['mehse-saha-hap', 'mehse-saha-hap-aktif' => $s['aktif']]) @if ($s['aktif']) aria-current="page" @endif>{{ $s['etiket'] }}</a>
            @endforeach
        </nav>
    @endif

    @if ($gruplu['aile'] === 'saha')
        @include('filament.components.saha-firma-baglami')
    @endif
@elseif (count($sekmeler) > 1)
    <x-filament::tabs class="mehse-sayfa-sekmeleri" style="margin-bottom:.25rem">
        @foreach ($sekmeler as $s)
            <x-filament::tabs.item tag="a" :href="$s['url']" :active="$s['aktif']" :badge="$s['rozet'] ?? null" badge-color="danger">
                {{ $s['etiket'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
@endif
