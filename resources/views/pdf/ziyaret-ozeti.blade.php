<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    /* Ziyaret Modu — "Ziyaret Tamamlandı (PDF İndir)" (02.11.2026): ziyaret özeti + künye + imzalar. */
    @page { margin: 30px 36px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin: 0 0 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 3px; }
    table.kunye { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.5px; margin: 0 0 14px; }
    table.kunye td { border: 1px solid #777; padding: 5px 8px; vertical-align: top; }
    table.kunye td.e { background: #eef0f3; font-weight: bold; width: 22%; }
    .govde { white-space: pre-line; font-size: 10.5px; line-height: 1.6; border: 1px solid #ccc; border-radius: 3px; padding: 10px 12px; }
    table.imza { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 36px; page-break-inside: avoid; }
    table.imza td.kutu { width: 45%; text-align: center; vertical-align: top; font-size: 10px; line-height: 1.5; }
    table.imza td.ara { width: 10%; }
    table.imza .cizgi { height: 40px; border-bottom: 1px solid #111; margin-bottom: 5px; }
    table.imza .ad { font-weight: bold; font-size: 11px; }
    table.imza .kucuk { color: #555; font-size: 8.5px; }
</style>
</head>
<body>
@php
    $uzman = \App\Support\FirmaImza::uzman($firma);
    $isveren = \App\Support\FirmaImza::isveren($firma);
@endphp

<div class="baslik">
    <h1>İSG SAHA ZİYARET RAPORU</h1>
    <div>{{ $firma?->unvan }}</div>
</div>

<table class="kunye">
    <tr><td class="e">İşyeri Unvanı</td><td colspan="3">{{ $firma?->unvan ?: '—' }}</td></tr>
    <tr>
        <td class="e">Ziyaret Tarihi</td><td>{{ $tarih }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">NACE / Tehlike Sınıfı</td>
        <td colspan="3">{{ $firma?->nace_kodu ?: '—' }} / {{ $firma?->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : '—' }}</td>
    </tr>
</table>

<div class="govde">{{ str_replace('*', '', $ozet) }}</div>

<table class="imza">
    <tr>
        <td class="kutu">
            <div class="cizgi"></div>
            <div class="ad">{{ $uzman['ad'] ?: '.............................' }}</div>
            <div>{{ $uzman['unvan'] }}</div>
            @if ($uzman['belge_no'])<div>Belge No: {{ $uzman['belge_no'] }}</div>@endif
            <div class="kucuk">(İmza – Kaşe)</div>
        </td>
        <td class="ara"></td>
        <td class="kutu">
            <div class="cizgi"></div>
            <div class="ad">{{ $isveren ?: '.............................' }}</div>
            <div>İşveren / İşveren Vekili</div>
            <div class="kucuk">(İmza)</div>
        </td>
    </tr>
</table>
</body>
</html>
