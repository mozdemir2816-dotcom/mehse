{{--
    Sidebar alt kartı (AdminPanelProvider SIDEBAR_FOOTER): oturumdaki uzman,
    ünvanı ve Ayarlar kısayolu. Stiller: theme.css "Sidebar alt kartı".
--}}
@php
    /** @var \App\Models\User|null $kullanici */
    $kullanici = filament()->auth()->user();
@endphp

@if ($kullanici instanceof \App\Models\User)
    @php
        $parcalar = preg_split('/\s+/u', trim((string) $kullanici->name)) ?: [];
        $basHarfler = mb_strtoupper(
            str_replace('i', 'İ', mb_substr($parcalar[0] ?? '?', 0, 1).mb_substr(count($parcalar) > 1 ? end($parcalar) : '', 0, 1))
        );
        $ayarlarAktif = request()->routeIs(\App\Filament\Pages\Ayarlar::getRouteName());
    @endphp

    <div class="mehse-sidebar-alt">
        <div class="mehse-sidebar-alt-kart">
            <span class="mehse-sidebar-alt-avatar" aria-hidden="true">{{ $basHarfler }}</span>
            <span class="mehse-sidebar-alt-kisi">
                <strong title="{{ $kullanici->name }}">{{ $kullanici->name }}</strong>
                <small>{{ $kullanici->unvanEtiketi() }}</small>
            </span>
            <a
                href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}"
                class="mehse-sidebar-alt-ayar @if ($ayarlarAktif) fi-active @endif"
                title="Ayarlar"
                aria-label="Ayarlar"
            >
                <x-filament::icon icon="heroicon-o-cog-6-tooth" class="mehse-sidebar-alt-ayar-ikon" />
            </a>
        </div>
    </div>
@endif
