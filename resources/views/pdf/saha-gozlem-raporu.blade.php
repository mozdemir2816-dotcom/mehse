@php
    use App\Models\SahaAnalizi;

    $igu = $firma?->igu;
    $iguKase = $rapor->gozetim_yapan_kase ?: $igu?->kase_gorseli ?: $firma?->user?->kase_gorseli;
    $iguImza = $igu?->imza_gorseli ?: $firma?->user?->imza_gorseli;
    $isverenKase = $firma?->isveren_kase_gorseli;
    $isverenImza = $firma?->isveren_imza_gorseli;
    $gorsel = fn (?string $yol) => $yol && is_file(storage_path('app/public/'.$yol)) ? storage_path('app/public/'.$yol) : null;
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 34px 30px 118px 30px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 8.5px; }
    h1 { text-align: center; font-size: 14px; letter-spacing: .5px; margin: 6px 0 8px; padding-bottom: 6px; border-bottom: 2px solid #111; }
    h2 { font-size: 9.5px; margin: 12px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #999; }
    table.bilgi { width: 100%; border-collapse: collapse; }
    table.bilgi td { border: 1px solid #cfd4dc; padding: 5px 7px; }
    table.bilgi td.e { background: #f3f4f6; font-weight: bold; width: 16%; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th { background: #f3f4f6; border: 1px solid #9ca3af; padding: 5px 4px; font-size: 8px; text-align: center; }
    table.liste td { border: 1px solid #9ca3af; padding: 5px; vertical-align: middle; }
    table.liste tr { page-break-inside: avoid; }
    table.liste ul { margin: 0; padding-left: 11px; }
    table.liste li { margin-bottom: 3px; }
    .gorsel img { width: 100%; max-height: 120px; }
    .gorsel .alt { font-size: 6.5px; color: #555; text-align: center; font-style: italic; margin-top: 2px; }
    .risk { text-align: center; }
    .risk .ad { font-weight: bold; font-size: 7.5px; }
    .risk .skor { display: inline-block; color: #fff; font-weight: bold; padding: 2px 8px; margin: 3px 0; font-size: 9px; }
    .risk .alt { font-size: 6.3px; color: #444; line-height: 1.35; }
    table.ref { width: 100%; border-collapse: collapse; }
    table.ref td { border: 1px solid #cfd4dc; padding: 5px; vertical-align: middle; }
    table.ref img { width: 62px; height: 62px; }
    .filigran { position: fixed; top: 300px; left: 40px; width: 100%; text-align: center; font-size: 120px; font-weight: bold;
        color: #f8d4d4; transform: rotate(-35deg); z-index: -1; }
    .alt-serit { position: fixed; bottom: -96px; left: 0; right: 0; height: 90px; }
    .alt-serit table { width: 100%; border-collapse: collapse; }
    .alt-serit td { width: 50%; text-align: center; vertical-align: bottom; font-size: 7.5px; padding: 0 30px; }
    .alt-serit .baslik { font-weight: bold; font-size: 8px; margin-bottom: 2px; }
    .alt-serit .gorseller { height: 46px; }
    .alt-serit .gorseller img { max-height: 46px; max-width: 110px; margin: 0 3px; }
    .alt-serit .rol { border-top: 1px solid #111; padding-top: 2px; color: #444; }
</style>
</head>
<body>

@if ($taslak)
    <div class="filigran">TASLAK</div>
@endif

{{-- Her sayfada: Hazırlayan / Onaylayan (görevli adı basılmaz — yalnız rol + kaşe/imza) --}}
<div class="alt-serit">
    <table>
        <tr>
            <td>
                <div class="baslik">HAZIRLAYAN</div>
                <div class="gorseller">
                    @if ($imzali)
                        @if ($p = $gorsel($iguKase))<img src="{{ $p }}">@endif
                        @if ($p = $gorsel($iguImza))<img src="{{ $p }}">@endif
                    @endif
                </div>
                <div class="rol">İş Güvenliği Uzmanı — Kaşe / İmza</div>
            </td>
            <td>
                <div class="baslik">ONAYLAYAN</div>
                <div class="gorseller">
                    @if ($imzali)
                        @if ($p = $gorsel($isverenKase))<img src="{{ $p }}">@endif
                        @if ($p = $gorsel($isverenImza))<img src="{{ $p }}">@endif
                    @endif
                </div>
                <div class="rol">İşveren / İşveren Vekili — Kaşe / İmza</div>
            </td>
        </tr>
    </table>
</div>

<h1>SAHA GÖZLEM RAPORU</h1>

<table class="bilgi">
    <tr>
        <td class="e">Rapor No:</td><td>{{ $rapor->belge_no }}</td>
        <td class="e">Rapor Tarihi:</td><td>{{ $rapor->rapor_tarihi?->format('d.m.Y') }}</td>
    </tr>
    <tr><td class="e">Firma:</td><td colspan="3">{{ $firma?->unvan }}</td></tr>
    <tr><td class="e">Firma Adresi:</td><td colspan="3">{{ trim(($firma?->adres ?? '').' '.($firma?->ilce ?? '').' '.($firma?->il ?? '')) ?: '—' }}</td></tr>
    @if ($rapor->alan_bolge || $rapor->gozetim_tarih_araligi)
        <tr>
            <td class="e">Alan / Bölge:</td><td>{{ $rapor->alan_bolge ?: '—' }}</td>
            <td class="e">Gözlem Tarihleri:</td><td>{{ $rapor->gozetim_tarih_araligi ?: '—' }}</td>
        </tr>
    @endif
</table>

<h2>TESPİT EDİLEN UYGUNSUZLUKLAR</h2>

<table class="liste">
    <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:20%">Görsel</th>
            <th style="width:22%">Uygunsuzluk</th>
            <th style="width:26%">Alınması Gereken Önlemler</th>
            <th style="width:14%">Mevzuat Referansı</th>
            <th style="width:14%">Risk</th>
        </tr>
    </thead>
    <tbody>
        @forelse (($rapor->bulgular ?? []) as $i => $b)
            @php
                $skor = $b['skor'] ?? SahaAnalizi::fineKinneySkoru($b);
                $bant = SahaAnalizi::fineKinneyBandi($skor);
            @endphp
            <tr>
                <td style="text-align:center">{{ $i + 1 }}</td>
                <td class="gorsel">
                    @if ($p = $gorsel($b['foto_yolu'] ?? null))
                        <img src="{{ $p }}">
                    @endif
                    @if (! empty($b['bina_bolge']))
                        <div class="alt">{{ $b['bina_bolge'] }}</div>
                    @endif
                </td>
                <td>{{ $b['tespit'] ?? '' }}</td>
                <td>
                    @if (! empty($b['oneriler']))
                        <ul>
                            @foreach ($b['oneriler'] as $oneri)
                                <li>{{ $oneri }}</li>
                            @endforeach
                        </ul>
                    @endif
                </td>
                <td style="text-align:center">{{ $b['yasal_gerekce'] ?? '—' }}</td>
                <td class="risk">
                    @if ($bant)
                        <div class="ad">{{ $bant['ad'] }}</div>
                        <div class="skor" style="background:{{ $bant['renk'] }}">{{ number_format($skor, 2, '.', '') }}</div>
                        <div class="alt">
                            Olasılık: {{ $b['olasilik'] }} — {{ SahaAnalizi::olcekEtiketi('olasilik', $b['olasilik']) }}<br>
                            Şiddet: {{ $b['siddet'] }} — {{ SahaAnalizi::olcekEtiketi('siddet', $b['siddet']) }}<br>
                            Frekans: {{ $b['frekans'] }} — {{ SahaAnalizi::olcekEtiketi('frekans', $b['frekans']) }}
                        </div>
                    @else
                        <div class="ad">{{ ($b['risk_derecesi'] ?? 3) }}. Derece</div>
                        <div class="alt">{{ SahaAnalizi::riskDerecesiEtiketi($b['risk_derecesi'] ?? 3) }}</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#888">Uygunsuzluk eklenmedi.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($referanslar)
    <h2>MEVZUAT REFERANSLARI</h2>
    <table class="ref">
        @foreach (array_chunk($referanslar, 2) as $satir)
            <tr>
                @foreach ($satir as $r)
                    <td style="width:12%;text-align:center">@if ($r['qr'])<img src="{{ $r['qr'] }}">@endif</td>
                    <td style="width:38%">
                        <strong>{{ $r['ad'] }}</strong>
                        @if ($r['url'])<br><span style="font-size:6.5px;color:#555">{{ $r['url'] }}</span>@endif
                    </td>
                @endforeach
                @if (count($satir) === 1)<td colspan="2" style="border:none"></td>@endif
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>
