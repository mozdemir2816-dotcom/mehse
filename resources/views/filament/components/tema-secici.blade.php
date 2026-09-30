{{--
    Görsel tema seçici (topbar, kullanıcı menüsünün solunda). Seçim anında
    <html data-mehse-tema="..."> ile uygulanır ve hesaba kaydedilir (POST
    mehse.ayar.tema → users.ayarlar.tema; Ayarlar sayfası da aynı alanı yazar).
    İlk boyamada uygulama: filament.components.head-ayarlar. Stiller:
    resources/css/filament/admin/temalar.css.
--}}
@php
    $temalar = [
        'klasik' => ['ad' => 'Klasik', 'aciklama' => 'Lacivert mehse', 'renkler' => ['#1E3A5F', '#2563EB', '#F0F4F8']],
        'material' => ['ad' => 'Google Material', 'aciklama' => 'Material 3 · Material You', 'renkler' => ['#F8FAFD', '#0B57D0', '#D3E3FD']],
        'windows11' => ['ad' => 'Windows 11', 'aciklama' => 'Fluent · Mica', 'renkler' => ['#F3F3F3', '#005FB8', '#FFFFFF']],
        'saha' => ['ad' => 'Saha', 'aciklama' => 'Baret sarısı · endüstriyel', 'renkler' => ['#1C1917', '#F5B400', '#FAFAF7']],
    ];
@endphp

<x-filament::dropdown placement="bottom-end" teleport class="fi-mehse-tema-secici">
    <x-slot name="trigger">
        <button
            type="button"
            class="fi-topbar-item-btn fi-mehse-tema-btn"
            aria-label="Görünüm teması"
            x-tooltip="{ content: 'Görünüm teması', theme: $store.theme }"
        >
            <x-filament::icon icon="heroicon-o-swatch" class="fi-icon fi-size-md" />
        </button>
    </x-slot>

    <x-filament::dropdown.header icon="heroicon-o-swatch">
        Görünüm teması
    </x-filament::dropdown.header>

    <x-filament::dropdown.list
        x-data="{ tema: document.documentElement.dataset.mehseTema || 'klasik' }"
    >
        @foreach ($temalar as $anahtar => $t)
            <button
                type="button"
                class="fi-mehse-tema-secenek"
                x-bind:class="{ 'fi-active': tema === '{{ $anahtar }}' }"
                x-on:click="
                    tema = '{{ $anahtar }}';
                    window.mehseTemaUygula(tema, true);
                "
            >
                <span class="fi-mehse-tema-ornek" aria-hidden="true">
                    @foreach ($t['renkler'] as $renk)
                        <span style="background: {{ $renk }}"></span>
                    @endforeach
                </span>
                <span class="fi-mehse-tema-metin">
                    <strong>{{ $t['ad'] }}</strong>
                    <small>{{ $t['aciklama'] }}</small>
                </span>
                <x-filament::icon
                    icon="heroicon-m-check"
                    class="fi-mehse-tema-tik"
                    x-show="tema === '{{ $anahtar }}'"
                />
            </button>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
