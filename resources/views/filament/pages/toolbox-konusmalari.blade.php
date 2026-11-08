@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Sahada kullandığınız hazır İş Başı Konuşması (toolbox talk) metinlerinizi buraya
        Word, PDF veya Excel olarak yükleyip istediğiniz zaman indirin.
    </p>

    <x-filament::section icon="heroicon-o-device-phone-mobile" icon-color="primary">
        <x-slot name="heading">Sahada okunabilir hazır kartlar</x-slot>
        <x-slot name="description">Telefondan açıp ekibe okuyun; kartın altından boş katılım imza föyünü (PDF) indirip sahada imzalatın.</x-slot>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.75rem">
            @foreach (\App\Support\ToolboxKartlari::liste() as $anahtar => $kart)
                <details style="background:#fff;border:1px solid #e2e8f0;border-radius:1rem;padding:.8rem 1rem">
                    <summary style="cursor:pointer;font-weight:700;font-size:.88rem;color:#0f172a">{{ $kart['baslik'] }} <span style="font-weight:500;font-size:.75rem;color:#64748b">· 5 madde</span></summary>
                    <ol style="margin:.6rem 0 .6rem 1.1rem;font-size:.82rem;color:#334155;line-height:1.45">
                        @foreach ($kart['maddeler'] as $m)<li style="margin-bottom:.3rem">{{ $m }}</li>@endforeach
                    </ol>
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="imzaFoyu('{{ $anahtar }}')">İmza Föyü (PDF)</x-filament::button>
                </details>
            @endforeach
        </div>
    </x-filament::section>

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
