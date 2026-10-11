@php
    // Aynı işi yapan sayfaların ortak sekme şeridi — App\Support\SayfaAileleri.
    $sekmeler = \App\Support\SayfaAileleri::sekmeler($scopes ?? []);
@endphp

@if (count($sekmeler) > 1)
    <x-filament::tabs class="mehse-sayfa-sekmeleri" style="margin-bottom:.25rem">
        @foreach ($sekmeler as $s)
            <x-filament::tabs.item tag="a" :href="$s['url']" :active="$s['aktif']">
                {{ $s['etiket'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
@endif
