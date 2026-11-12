<!doctype html>
<html lang="tr"><head><meta charset="utf-8">@include('pdf.ekipman._stil')</head>
<body>
@include('pdf.ekipman._antet', ['baslik' => $basligi])
@php
    $kkdBolumu = collect($talimat->bolumler ?? [])->first(fn ($b) => str_contains(mb_strtoupper($b['baslik']), 'KKD') || str_contains(mb_strtoupper($b['baslik']), 'KORUYUCU'));
    $kkdler = $talimat->kkdler ?: ($kkdBolumu['maddeler'] ?? []);
    $no = 0;
@endphp
<table class="kunye">
    <tr><td class="e">Ekipman tipi</td><td>{{ $e->tip ?: '—' }}</td><td class="e">Yasal dayanak</td><td>{{ $e->yasal_standart ?: 'İş Ekipmanları Yönetmeliği' }}</td></tr>
    <tr><td class="e">Zorunlu KKD</td><td colspan="3">{{ $kkdler ? implode(' · ', array_map(fn ($k) => \Illuminate\Support\Str::before($k, ':'), $kkdler)) : 'Talimatta belirtilen' }}</td></tr>
</table>

@foreach ($talimat->bolumler ?? [] as $b)
    @continue($kkdBolumu && $b === $kkdBolumu && $talimat->kkdler)
    <h2>{{ $b['baslik'] }}</h2>
    @if (filled($b['aciklama'] ?? null))<p class="aciklama">{{ $b['aciklama'] }}</p>@endif
    @if (! empty($b['maddeler']))
        <ol start="{{ $no + 1 }}">@foreach ($b['maddeler'] as $m)<li>{{ $m }}</li>@php $no++ @endphp @endforeach</ol>
    @endif
@endforeach
@if (! empty($talimat->maddeler))
    <h2>GÜVENLİ ÇALIŞMA KURALLARI</h2>
    <ol>@foreach ($talimat->maddeler as $m)<li>{{ $m }}</li>@endforeach</ol>
@endif
@if (filled($talimat->taahhut))
    <p class="aciklama" style="margin-top:6px"><strong>Taahhüt:</strong> {{ $talimat->taahhut }}</p>
@endif

<table class="imza">
    <tr><th>HAZIRLAYAN<br>İş Güvenliği Uzmanı</th><th>ONAYLAYAN<br>İşveren / İşveren Vekili</th><th>TEBELLÜĞ EDEN<br>Operatör</th></tr>
    <tr>
        <td>{{ $tebligEden->ad }}<br>@if ($tebligEden->imza_gorseli && is_file($p = storage_path('app/public/'.$tebligEden->imza_gorseli)))<img src="{{ $p }}">@endif<br>İmza:</td>
        <td>{{ $firma->isveren_vekili ?: $firma->isveren_ad }}<br><br>Kaşe / İmza:</td>
        <td>Adı soyadı:<br><br>Tarih: ....../....../..........<br>İmza:</td>
    </tr>
</table>
@if ($talimat->aciklama && str_contains($talimat->aciklama, 'Yapay zekâ'))
    <div class="dipnot">Bu talimat yapay zekâ taslağından hazırlanmıştır; İSG uzmanı tarafından gözden geçirilmelidir.</div>
@endif
</body></html>
