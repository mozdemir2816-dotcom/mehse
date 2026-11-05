@php
    $renk = ['ok' => ['#15803d', 'rgb(21 128 61 / .12)'], 'uyari' => ['#b45309', 'rgb(245 158 11 / .15)'], 'yok' => ['#b91c1c', 'rgb(185 28 28 / .12)']];
@endphp
<x-filament-panels::page>
    @if (! $this->firma)
        <p style="color:#f59e0b;font-size:.9rem">Firma seçilmedi. Firmalar listesinden bir firma açıp "İSG Dosyası &amp; Evrak Takip" düğmesini kullanın.</p>
    @else
        <p style="font-size:.9rem;color:rgb(107 114 128);margin-top:-.5rem">
            <strong>{{ $this->firma->unvan }}</strong> · {{ $this->firma->tehlikeSinifiEtiketi() }} — tüm modüllerden güncel evrak özeti.
        </p>

        @foreach ($this->gruplar as $grup)
            <x-filament::section>
                <x-slot name="heading">{{ $grup['baslik'] }}</x-slot>
                <div style="display:flex;flex-direction:column;gap:.5rem">
                    @foreach ($grup['satirlar'] as $s)
                        <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;border:1px solid rgb(107 114 128 / .25);border-radius:.6rem;padding:.6rem .8rem">
                            <div style="flex:1;min-width:220px">
                                <div style="font-weight:600;font-size:.88rem">{{ $s['ad'] }}</div>
                                <div style="font-size:.78rem;color:rgb(107 114 128)">{{ $s['bilgi'] }}</div>
                            </div>
                            <span style="padding:.15rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700;color:{{ $renk[$s['durum']][0] }};background:{{ $renk[$s['durum']][1] }}">{{ $s['etiket'] }}</span>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                @if ($s['pdf'])
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="pdf('{{ $s['pdf'] }}')">PDF İndir</x-filament::button>
                                @endif
                                @if ($s['duzenle'])
                                    <x-filament::button size="xs" color="gray" icon="heroicon-o-pencil-square" tag="a" :href="$s['duzenle']">Düzenle</x-filament::button>
                                @endif
                                @if ($s['whatsapp'])
                                    <x-filament::button size="xs" color="success" tag="a" target="_blank"
                                        :href="'https://wa.me/?text='.rawurlencode($this->firma->unvan.' — '.$s['ad'].': '.$s['bilgi'])">WhatsApp</x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    @endif
</x-filament-panels::page>
