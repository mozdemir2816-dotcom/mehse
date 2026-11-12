<div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
        <tr>
            @foreach (['Tarih', 'Hareket', 'Miktar', 'Açıklama'] as $b)
                <th class="mehse-th">{{ $b }}</th>
            @endforeach
        </tr>
        @forelse ($hareketler as $h)
            <tr>
                <td style="padding:.35rem .5rem;white-space:nowrap">{{ $h->tarih?->format('d.m.Y') }}</td>
                <td class="mehse-td">{{ $h->tipEtiketi() }}</td>
                <td style="padding:.35rem .5rem;font-weight:600;color:{{ $h->miktar >= 0 ? '#15803d' : '#dc2626' }}">{{ $h->miktar > 0 ? '+' : '' }}{{ $h->miktar }}</td>
                <td class="mehse-td">{{ $h->aciklama }}{{ $h->zimmet ? ' ('.$h->zimmet->zimmet_no.')' : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="padding:.6rem;text-align:center;color:rgb(107 114 128)">Hareket yok.</td></tr>
        @endforelse
    </table>
</div>
