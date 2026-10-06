{{-- HazirRaporYukleme: dışarıda hazırlanıp yüklenen (Excel/Word/PDF) raporlar --}}
@if ($this->firma && $this->hazirRaporlar->isNotEmpty())
    <x-filament::section icon="heroicon-o-paper-clip" icon-color="gray" collapsible>
        <x-slot name="heading">Yüklenen Hazır Raporlar ({{ $this->hazirRaporlar->count() }})</x-slot>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                @foreach ($this->hazirRaporlar as $r)
                    <tr wire:key="hazir-{{ $r->id }}">
                        <td style="padding:.35rem .5rem;white-space:nowrap">{{ $r->baslangic_tarihi?->format('d.m.Y') }}</td>
                        <td style="padding:.35rem .5rem">
                            {{ $r->dosya_adi }}
                            <span style="font-size:.72rem;color:rgb(107 114 128)">· {{ $r->boyutEtiketi() }}</span>
                            @if ($r->aciklama)<div style="font-size:.72rem;color:rgb(107 114 128)">{{ $r->aciklama }}</div>@endif
                        </td>
                        <td style="padding:.35rem .5rem;text-align:right;white-space:nowrap">
                            <x-filament::button size="xs" color="gray" wire:click="hazirRaporIndir({{ $r->id }})">İndir</x-filament::button>
                            <x-filament::button size="xs" color="danger" wire:click="hazirRaporSil({{ $r->id }})" wire:confirm="Dosya silinsin mi? Arşivden de kaldırılır.">Sil</x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </x-filament::section>
@endif
