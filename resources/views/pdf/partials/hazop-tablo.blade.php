{{-- HAZOP risk tablosu: kaynak Excel ile aynı 14 sütun, puan yok. Sütunlar App\Support\HazopCiktisi'nden gelir. --}}
@php
    $hazopSutunlar = \App\Support\HazopCiktisi::sutunlar();
    $sira = 0;
@endphp
@foreach ($rd->maddeler->chunk($parcaBoyutu ?? 100) as $parca)
<table class="risk hazop" @if ($loop->first) style="font-size:6.8px" @else style="font-size:6.8px;margin-top:0;page-break-before:always" @endif>
    <thead>
    <tr>
        @foreach ($hazopSutunlar as [$baslik, , , $yuzde])
            <th style="width:{{ $yuzde }}%">{{ $baslik }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach ($parca as $m)
        @php $no = ++$sira; @endphp
        <tr>
            @foreach ($hazopSutunlar as [, $deger])
                <td>{{ $deger($m, $no) }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
@endforeach
