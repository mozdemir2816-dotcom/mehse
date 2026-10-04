@if (filament()->auth()->check())
@php
    // Sahaya göre (kullanıcı isteği 04.10.2026): Bugün (Ziyaret Modu) · Saha Gözlem ·
    // ortada büyük kamera (Hızlı Bulgu) · Arşiv · Firmalar. Ziyaret Modu'nda seçilen
    // firma oturumda tutulur; kısayollar o firmayla açılır.
    $firma = session('ziyaret_firma') ? ['firma' => (int) session('ziyaret_firma')] : [];
    $sekmeler = [
        ['ad' => 'Bugün', 'url' => \App\Filament\Pages\ZiyaretModu::getUrl($firma), 'ikon' => 'heroicon-o-map-pin'],
        ['ad' => 'Saha Gözlem', 'url' => \App\Filament\Pages\AiSahaAnalizi::getUrl($firma), 'ikon' => 'heroicon-o-document-magnifying-glass'],
        ['ad' => 'Bulgu', 'url' => \App\Filament\Pages\HizliSahaBulgusu::getUrl($firma), 'ikon' => 'heroicon-o-camera', 'merkez' => true],
        ['ad' => 'Arşiv', 'url' => \App\Filament\Pages\DokumanYonetimi::getUrl($firma), 'ikon' => 'heroicon-o-folder-open'],
        ['ad' => 'Firmalar', 'url' => \App\Filament\Resources\Firmas\FirmaResource::getUrl('index'), 'ikon' => 'heroicon-o-building-office-2'],
    ];

    $simdikiYol = '/'.trim(request()->path(), '/');
@endphp

<nav class="fi-mobil-alt-nav">
    @foreach ($sekmeler as $s)
        @php
            $sekmeYolu = '/'.trim((string) parse_url($s['url'], PHP_URL_PATH), '/');
            $aktif = $simdikiYol === $sekmeYolu;
        @endphp

        <a href="{{ $s['url'] }}" class="fi-mobil-alt-nav-item @if ($aktif) fi-active @endif @if ($s['merkez'] ?? false) fi-mobil-alt-nav-merkez @endif">
            <span class="fi-mobil-alt-nav-icon-ctn">
                <x-filament::icon :icon="$s['ikon']" class="fi-mobil-alt-nav-icon" />
            </span>
            <span class="fi-mobil-alt-nav-label">{{ $s['ad'] }}</span>
        </a>
    @endforeach
</nav>
@endif
