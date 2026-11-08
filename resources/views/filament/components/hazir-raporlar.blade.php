{{-- HazirRaporYukleme: dışarıda hazırlanıp yüklenen (Excel/Word/PDF) raporlar --}}
@if ($this->firma && $this->hazirRaporlar->isNotEmpty())
    <x-filament::section icon="heroicon-o-paper-clip" icon-color="gray" collapsible>
        <x-slot name="heading">Yüklenen Hazır Raporlar ({{ $this->hazirRaporlar->count() }})</x-slot>
        <div style="overflow-x:auto">
            <table class="mehse-tablo">
                @foreach ($this->hazirRaporlar as $r)
                    <tr wire:key="hazir-{{ $r->id }}">
                        <td style="padding:.35rem .5rem;white-space:nowrap">{{ $r->baslangic_tarihi?->format('d.m.Y') }}</td>
                        <td class="mehse-td">
                            {{ $r->dosya_adi }}
                            <span class="mehse-soluk">· {{ $r->boyutEtiketi() }}</span>
                            @if ($r->aciklama)<div class="mehse-soluk">{{ $r->aciklama }}</div>@endif
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
