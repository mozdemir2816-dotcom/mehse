{{--
    Yasal metin modalları (AdminPanelProvider SIMPLE_PAGE_END). Onay kutusundaki
    "…görüntüle" bağlantısı $dispatch('open-modal', {id: 'yasal-<anahtar>'}) ile açar.
    Yalnız metin dosyası (config isg.kayit.yasal_metinler.*.gorunum) varsa basılır.
--}}
@foreach (\App\Support\YasalMetinler::aktifler() as $anahtar => $metin)
    <x-filament::modal id="yasal-{{ $anahtar }}" width="3xl" :close-by-clicking-away="true">
        <x-slot name="heading">{{ $metin['baslik'] }}</x-slot>
        @if (filled($metin['revizyon'] ?? null))
            <x-slot name="description">Revizyon: {{ $metin['revizyon'] }}</x-slot>
        @endif

        <div class="mehse-yasal-metin">
            @include($metin['gorunum'])
        </div>

        <x-slot name="footerActions">
            @if ($anahtar === 'sozlesme' && auth()->check())
                <x-filament::button tag="a" color="success" icon="heroicon-o-arrow-down-tray" :href="route('filament.admin.sozlesme.pdf')">Onaylı Sözleşmeyi PDF İndir</x-filament::button>
            @endif
            <x-filament::button x-on:click="close()">Okudum, kapat</x-filament::button>
        </x-slot>
    </x-filament::modal>
@endforeach
