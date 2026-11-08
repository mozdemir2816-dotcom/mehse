<div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
        <tr>
            @foreach (['Kontrol', 'Sonraki Termin', 'Kontrol Eden', 'Rapor No', 'Sonuç', 'Rapor', 'Not'] as $b)
                <th class="mehse-th">{{ $b }}</th>
            @endforeach
        </tr>
        @forelse ($kontroller as $k)
            <tr>
                <td style="padding:.35rem .5rem;white-space:nowrap;font-weight:{{ $loop->first ? '700' : '400' }}">{{ $k->kontrol_tarihi->format('d.m.Y') }}{{ $loop->first ? ' (son)' : '' }}</td>
                <td style="padding:.35rem .5rem;white-space:nowrap">{{ $k->sonraki_tarih?->format('d.m.Y') ?? '—' }}</td>
                <td class="mehse-td">{{ $k->kontrol_eden ?: '—' }}</td>
                <td class="mehse-td">{{ $k->rapor_no ?: '—' }}</td>
                <td class="mehse-td">{{ $k->sonucEtiketi() }}</td>
                <td class="mehse-td">
                    @if ($k->dosya_yolu)
                        <x-filament::button size="xs" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="kontrolRaporuIndir({{ $k->id }})">İndir</x-filament::button>
                    @else
                        —
                    @endif
                </td>
                <td class="mehse-td">{{ $k->notu }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:.6rem;text-align:center;color:rgb(107 114 128)">Henüz kontrol kaydı yok.</td></tr>
        @endforelse
    </table>
</div>
