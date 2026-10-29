{{-- Komuta Merkezi · Firma Denetim & Operasyon: ortak firma çubuğu + üç bölüm (yalnız açık bölüm hesaplanır) --}}
<x-filament-panels::page>
    @include('filament.pages.partials.komuta-firma-cubugu', ['tumPortfoy' => false])

    @if (! $this->firma)
        <x-filament::section>
            <div style="font-size:.85rem;color:rgb(107 114 128)">Denetim ve operasyon görünümü için yukarıdan bir firma seçin.</div>
        </x-filament::section>
    @else
        <div class="idm-yazdirma" style="display:flex;flex-wrap:wrap;gap:.4rem" role="tablist" aria-label="Firma denetim bölümleri">
            @foreach (\App\Filament\Pages\FirmaDenetimOperasyon::BOLUMLER as $kod => $ad)
                <button type="button" role="tab" aria-selected="{{ $bolum === $kod ? 'true' : 'false' }}" wire:click="bolumSec('{{ $kod }}')"
                    style="font-size:.82rem;font-weight:600;padding:.4rem .95rem;border-radius:9999px;cursor:pointer;
                        border:1px solid {{ $bolum === $kod ? '#0284C7' : 'rgb(107 114 128 / .35)' }};
                        background:{{ $bolum === $kod ? 'rgb(2 132 199 / .1)' : 'transparent' }};
                        color:{{ $bolum === $kod ? '#0369A1' : 'inherit' }}">
                    {{ $ad }}
                </button>
            @endforeach
        </div>

        @if ($bolum === 'skor')
            @include('filament.pages.partials.komuta-tesis')
        @elseif ($bolum === 'firma360')
            @include('filament.pages.partials.komuta-isyeri')
        @else
            @include('filament.pages.partials.komuta-uzman')
        @endif
    @endif
</x-filament-panels::page>
