<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #0f172a; margin: 18px; }
        .cerceve { border: 2px solid #0f172a; border-radius: 10px; overflow: hidden; }
        .ust { background: #1e3a8a; color: #fff; padding: 12px 14px; }
        .ust h1 { font-size: 14px; margin: 0 0 3px; }
        .govde { padding: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; width: 42%; font-weight: bold; }
        .qr-kutu { text-align: center; border: 1px dashed #64748b; border-radius: 8px; padding: 10px; background: #f8fafc; }
    </style>
</head>
<body>
<div class="cerceve">
    <div class="ust">
        <h1>İŞ EKİPMANI QR EMNİYET PASAPORTU</h1>
        <div style="font-size:10px;opacity:.9">{{ $firma?->unvan }}</div>
    </div>
    <div class="govde">
        <table>
            <tr><th>Ekipman Adı / Tanımı</th><td><strong>{{ $ekipman->ekipman_adi }}</strong></td></tr>
            <tr><th>Kategori / Seri No</th><td>{{ $ekipman->kategori ?: '—' }} / {{ $ekipman->seri_no ?: '—' }}</td></tr>
            <tr><th>Konum / Bölüm</th><td>{{ $ekipman->konum ?: '—' }}</td></tr>
            <tr><th>Son Periyodik Kontrol</th><td>{{ $ekipman->son_muayene_tarihi?->format('d.m.Y') ?: '—' }}</td></tr>
            <tr><th>Sonraki Kontrol Tarihi</th><td><strong>{{ $ekipman->sonraki_vize_tarihi?->format('d.m.Y') ?: '—' }}</strong></td></tr>
            <tr><th>Kontrol Eden / EKİPNET No</th><td>{{ $ekipnetNo ?: ($ekipman->kontrol_kurulusu ?: '—') }}</td></tr>
            <tr><th>Pasaport Düzenleme Tarihi</th><td>{{ $tarih }}</td></tr>
        </table>

        <div class="qr-kutu">
            <img src="{{ $qrUri }}" style="width:130px;height:130px" alt="QR Emniyet Pasaportu">
            <div style="font-weight:bold;margin-top:6px">OPERATÖR TALİMATI &amp; GÜNLÜK KONTROL İÇİN OKUTUN</div>
            <div style="font-size:8.5px;color:#475569;margin-top:2px">{{ $operatorUrl }}</div>
        </div>
    </div>
</div>
</body>
</html>
