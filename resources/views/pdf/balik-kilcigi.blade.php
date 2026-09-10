{{-- Balık Kılçığı (Ishikawa) Kök Neden Analizi — Olay Kaydı eki. A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 8.5px; line-height: 1.35; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 2px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 8px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 11%; }

    table.fb { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.fb td { vertical-align: top; padding: 0; }
    .kat { border: 1px solid #555; border-radius: 4px; margin: 3px; padding: 4px 6px; }
    .kat h3 { margin: 0 0 3px; font-size: 8px; background: #ecf0f1; padding: 2px 4px; border-radius: 2px; }
    .kat ul { margin: 2px 0 0; padding-left: 12px; font-size: 7.6px; line-height: 1.3; }
    .kat li { margin: 0 0 1px; }
    .kat .bos { color: #aaa; font-style: italic; padding-left: 4px; }

    .spine { background: #34495e; color: #fff; text-align: center; font-weight: bold;
        padding: 6px 4px; border-radius: 3px; margin: 3px; font-size: 8px; letter-spacing: .5px; }
    .problem { border: 2px solid #b91c1c; color: #b91c1c; border-radius: 4px;
        text-align: center; font-weight: bold; padding: 8px 4px; margin: 3px; font-size: 8.5px; }

    .ozet { margin-top: 8px; border: 1px solid #999; border-collapse: collapse; width: 100%; font-size: 8px; }
    .ozet td { border: 1px solid #999; padding: 3px 6px; }
    .ozet td.e { background: #eee; font-weight: bold; width: 14%; }
    .imza { margin-top: 16px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; }
</style>
</head>
<body>

@php
    $kats = collect($kayit->balikKilcigiKategorileri())->keyBy('anahtar');
    $ust = ['insan', 'makine', 'metot'];
    $alt = ['malzeme', 'olcum', 'cevre'];
    $blok = function ($anahtar) use ($kats) {
        $k = $kats[$anahtar] ?? ['etiket' => $anahtar, 'nedenler' => []];
        $html = '<div class="kat"><h3>'.e($k['etiket']).'</h3>';
        if (empty($k['nedenler'])) {
            $html .= '<div class="bos">— neden girilmedi —</div>';
        } else {
            $html .= '<ul>';
            foreach ($k['nedenler'] as $n) { $html .= '<li>'.e($n).'</li>'; }
            $html .= '</ul>';
        }
        return $html.'</div>';
    };
@endphp

<h1>BALIK KILÇIĞI (ISHIKAWA) KÖK NEDEN ANALİZİ</h1>
<div class="alt">6M yöntemi — İnsan · Makine · Yöntem · Malzeme · Çevre · Yönetim</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">Belge No</td><td>{{ $kayit->belge_no }}</td>
        <td class="e">Olay Tipi</td><td>{{ $kayit->tipEtiketi() }}</td>
        <td class="e">Olay Tarihi</td><td>{{ $kayit->olay_tarihi?->format('d.m.Y') ?? '—' }}</td>
    </tr>
</table>

<table class="fb">
    <tr>
        <td style="width:31%">{!! $blok('insan') !!}</td>
        <td style="width:31%">{!! $blok('makine') !!}</td>
        <td style="width:31%">{!! $blok('metot') !!}</td>
        <td style="width:7%" rowspan="3">
            <div class="problem">OLAY / PROBLEM<br><span style="font-weight:normal;font-size:7px">{{ \Illuminate\Support\Str::limit((string) $kayit->olay_ozeti, 90) }}</span></div>
        </td>
    </tr>
    <tr>
        <td colspan="3"><div class="spine">◄════════  KÖK NEDEN OMURGASI  ════════►</div></td>
    </tr>
    <tr>
        <td>{!! $blok('malzeme') !!}</td>
        <td>{!! $blok('olcum') !!}</td>
        <td>{!! $blok('cevre') !!}</td>
    </tr>
</table>

<table class="ozet">
    @if ($kayit->nedenZinciri())
        <tr><td class="e">5N Zinciri</td><td>{{ implode('  →  ', $kayit->nedenZinciri()) }}</td></tr>
    @endif
    @if ($kayit->kokNedenEtiketleri())
        <tr><td class="e">Kök Neden Kategorileri</td><td>{{ implode(' · ', $kayit->kokNedenEtiketleri()) }}</td></tr>
    @endif
    @if ($kayit->kok_neden)
        <tr><td class="e">Belirlenen Kök Neden</td><td>{{ $kayit->kok_neden }}</td></tr>
    @endif
    @if ($kayit->duzeltici_faaliyet)
        <tr><td class="e">Düzeltici Faaliyet</td><td>{{ $kayit->duzeltici_faaliyet }}</td></tr>
    @endif
</table>

<table class="imza">
    <tr>
        <td>Analizi Yapan (İş Güvenliği Uzmanı)</td>
        <td>İşyeri Hekimi</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
