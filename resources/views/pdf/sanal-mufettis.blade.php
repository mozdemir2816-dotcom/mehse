<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; margin: 22px; }
        .ust { background: #0f172a; color: #fff; padding: 14px 16px; border-radius: 6px; margin-bottom: 14px; }
        .ust h1 { font-size: 15px; margin: 0 0 4px; }
        .ozet { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .ozet td { border: 1px solid #cbd5e1; padding: 8px 10px; width: 33.3%; }
        table.liste { width: 100%; border-collapse: collapse; }
        table.liste th, table.liste td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.liste th { background: #f1f5f9; font-weight: bold; }
        .eksik { color: #dc2626; font-weight: bold; }
        .ok { color: #15803d; font-weight: bold; }
    </style>
</head>
<body>
@php $tl = fn (int $n) => number_format($n, 0, ',', '.').' TL'; @endphp
<div class="ust">
    <h1>SANAL MÜFETTİŞ — 6331 SAYILI KANUN İPC RİSK RAPORU</h1>
    <div>
        {{ $firma->secimEtiketi() }} · {{ $firma->tehlikeSinifiEtiketi() }} · {{ $firma->calisan_sayisi ?: 1 }} Çalışan · {{ $tarih }}
        @if ($firma->sgk_sicil_no) · SGK: {{ $firma->sgk_sicil_no }} @endif
        @if ($firma->nace_kodu) · NACE: {{ $firma->nace_kodu }} @endif
    </div>
</div>

<table class="ozet">
    <tr>
        <td><strong>Denetim Skoru</strong><br><span style="font-size:15px;font-weight:bold;color:{{ $rapor['renk'] }}">{{ $rapor['puan'] }} / 100</span><br>{{ $rapor['seviye'] }}</td>
        <td><strong>Tahmini 6331 İPC Riski</strong><br><span style="font-size:13px;font-weight:bold;color:#dc2626">{{ $rapor['max_ceza_tl'] > 0 ? $tl($rapor['min_ceza_tl']).' – '.$tl($rapor['max_ceza_tl']) : '0 TL' }}</span><br>Çarpan: x{{ $rapor['carpan'] }}</td>
        <td><strong>Eksik Yasal Kriter</strong><br><span style="font-size:15px;font-weight:bold">{{ $rapor['eksik_sayisi'] }} / {{ $rapor['toplam_kriter'] }}</span><br>Acil aksiyon planı aşağıdadır</td>
    </tr>
</table>

<table class="liste">
    <thead>
        <tr>
            <th style="width:11%">Kod</th>
            <th style="width:27%">Yasal Kriter</th>
            <th style="width:20%">Dayanak</th>
            <th style="width:10%">Durum</th>
            <th style="width:16%">Tahmini İPC</th>
            <th style="width:16%">Önerilen Aksiyon</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rapor['bulgular'] as $b)
            <tr>
                <td><strong>{{ $b['kod'] }}</strong></td>
                <td>{{ $b['baslik'] }}</td>
                <td>{{ $b['dayanak'] }}</td>
                <td class="{{ $b['durum'] === 'uyumlu' ? 'ok' : 'eksik' }}">{{ $b['durum'] === 'uyumlu' ? 'Uyumlu' : 'Eksik' }}</td>
                <td class="{{ $b['max_tl'] > 0 ? 'eksik' : 'ok' }}">{{ $b['max_tl'] > 0 ? $tl($b['min_tl']).' - '.$tl($b['max_tl']) : '0 TL' }}</td>
                <td>{{ $b['aksiyon'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="width:100%;border-collapse:collapse;margin-top:14px;page-break-inside:avoid;font-size:9px">
    <tr>
        <td style="width:50%;border:1px solid #cbd5e1;padding:7px 9px;vertical-align:top">
            <strong>Raporu Hazırlayan (İSG Uzmanı)</strong><br>
            {{ $firma->igu?->ad_soyad ?: '—' }}@if ($firma->igu?->sertifika_no) ({{ $firma->igu->sertifika_no }})@endif
            <div style="height:28px"></div>
            <span style="color:#64748b">İmza / Kaşe · {{ $tarih }}</span>
        </td>
        <td style="width:50%;border:1px solid #cbd5e1;padding:7px 9px;vertical-align:top">
            <strong>Bilgilendirilen (İşveren / İşveren Vekili)</strong><br>
            {{ $firma->isveren_vekili ?: ($firma->isveren_ad ?: '—') }}
            <div style="height:28px"></div>
            <span style="color:#64748b">İmza / Kaşe · Tarih: .... / .... / ........</span>
        </td>
    </tr>
</table>

<p style="margin-top:10px;font-size:8.5px;color:#475569">
    Not: Bu rapor 6331 sayılı İş Sağlığı ve Güvenliği Kanunu m.26 esas alınarak işvereni olası teftiş riskleri konusunda bilgilendirmek üzere Mehse İSG tarafından üretilmiştir.
</p>
</body>
</html>
