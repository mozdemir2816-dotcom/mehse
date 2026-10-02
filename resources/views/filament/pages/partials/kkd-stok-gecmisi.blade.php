<div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:.8rem">
        <tr>
            @foreach (['Tarih', 'Hareket', 'Miktar', 'Açıklama'] as $b)
                <th style="text-align:left;padding:.35rem .5rem;border-bottom:1px solid rgb(107 114 128 / .3)">{{ $b }}</th>
            @endforeach
        </tr>
        @forelse ($hareketler as $h)
            <tr>
                <td style="padding:.35rem .5rem;white-space:nowrap">{{ $h->tarih?->format('d.m.Y') }}</td>
                <td style="padding:.35rem .5rem">{{ $h->tipEtiketi() }}</td>
                <td style="padding:.35rem .5rem;font-weight:600;color:{{ $h->miktar >= 0 ? '#16a34a' : '#dc2626' }}">{{ $h->miktar > 0 ? '+' : '' }}{{ $h->miktar }}</td>
                <td style="padding:.35rem .5rem">{{ $h->aciklama }}{{ $h->zimmet ? ' ('.$h->zimmet->zimmet_no.')' : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="padding:.6rem;text-align:center;color:rgb(107 114 128)">Hareket yok.</td></tr>
        @endforelse
    </table>
</div>
