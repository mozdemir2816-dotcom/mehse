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
            @php $trend = $this->denetimSkorTrendi; @endphp
            @if (count($trend))
                <x-filament::section heading="Ziyaret & Saha Denetimi Skor Trendi (0–100)" description="Her geçmiş saha denetiminin uygunluk skoru — firmanın aydan aya gelişimi.">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.6rem;align-items:end">
                        @foreach ($trend as $t)
                            <div style="border:1px solid rgb(107 114 128 / .2);border-radius:.6rem;padding:.55rem .65rem;text-align:center">
                                <div style="font-size:.72rem;color:rgb(107 114 128)">{{ $t['tarih'] }}</div>
                                <div style="font-size:1.35rem;font-weight:800;color:{{ $t['renk'] }};margin:.15rem 0">{{ $t['skor'] }}</div>
                                <div style="height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-bottom:.3rem">
                                    <div style="height:100%;width:{{ $t['skor'] }}%;background:{{ $t['renk'] }}"></div>
                                </div>
                                <div style="font-size:.68rem;color:rgb(107 114 128)">✓ {{ $t['uygun'] }} · ✕ {{ $t['uygun_degil'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
            @include('filament.pages.partials.komuta-tesis')
        @elseif ($bolum === 'firma360')
            @include('filament.pages.partials.komuta-isyeri')
        @else
            @include('filament.pages.partials.komuta-uzman')
        @endif
    @endif
</x-filament-panels::page>
