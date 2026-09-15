@if (filament()->auth()->check())
@php
    $sekmeler = [
        ['ad' => 'Kontrol', 'url' => \App\Filament\Pages\KontrolMerkezi::getUrl(), 'ikon' => 'heroicon-o-squares-2x2'],
        ['ad' => 'Firmalar', 'url' => \App\Filament\Resources\Firmas\FirmaResource::getUrl('index'), 'ikon' => 'heroicon-o-building-office-2'],
        ['ad' => 'Risk', 'url' => \App\Filament\Pages\RiskSihirbazi::getUrl(), 'ikon' => 'heroicon-o-sparkles'],
        ['ad' => 'Saha', 'url' => \App\Filament\Pages\SahaDenetimi::getUrl(), 'ikon' => 'heroicon-o-clipboard-document-list'],
        ['ad' => 'Tespit', 'url' => \App\Filament\Pages\TespitOneriDefteri::getUrl(), 'ikon' => 'heroicon-o-book-open'],
    ];

    $simdikiYol = rtrim(request()->path(), '/');
@endphp

<nav class="fi-mobil-alt-nav">
    @foreach ($sekmeler as $s)
        @php
            $sekmeYolu = rtrim(\Illuminate\Support\Str::after($s['url'], request()->getSchemeAndHttpHost()), '/');
            $aktif = $simdikiYol === $sekmeYolu;
        @endphp

        <a href="{{ $s['url'] }}" class="fi-mobil-alt-nav-item @if ($aktif) fi-active @endif">
            <span class="fi-mobil-alt-nav-icon-ctn">
                <x-filament::icon :icon="$s['ikon']" class="fi-mobil-alt-nav-icon" />
            </span>
            <span class="fi-mobil-alt-nav-label">{{ $s['ad'] }}</span>
        </a>
    @endforeach
</nav>
@endif
