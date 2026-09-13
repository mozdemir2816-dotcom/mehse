@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sahada kullandığınız hazır İş Başı Konuşması (toolbox talk) metinlerinizi buraya
        Word, PDF veya Excel olarak yükleyip istediğiniz zaman indirin.
    </p>

    @forelse ($this->konusmalar as $k)
        <div style="{{ $kutu }};display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
            <div style="font-size:.86rem">
                <div style="font-weight:700">{{ $k->baslik }}</div>
                <div style="color:rgb(107 114 128);margin-top:.15rem">
                    {{ $k->dosya_adi }} ({{ $k->boyutEtiketi() }}) · {{ $k->created_at->format('d.m.Y') }}
                </div>
                @if ($k->aciklama)
                    <div style="margin-top:.3rem">{{ $k->aciklama }}</div>
                @endif
            </div>
            <div style="display:flex;gap:.4rem;flex-shrink:0">
                <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray"
                    wire:click="konusmaIndir({{ $k->id }})">İndir</x-filament::button>
                <x-filament::button size="xs" color="danger" icon="heroicon-o-trash"
                    wire:click="konusmaSil({{ $k->id }})"
                    wire:confirm="{{ $k->baslik }} silinsin mi? Dosya da kaldırılır.">Sil</x-filament::button>
            </div>
        </div>
    @empty
        <x-filament::section>
            <div style="text-align:center;color:rgb(107 114 128);padding:1.5rem 0">
                Henüz bir Toolbox Konuşması yüklenmemiş — sağ üstteki "Toolbox Konuşması Yükle" butonunu kullanın.
            </div>
        </x-filament::section>
    @endforelse
</x-filament-panels::page>
