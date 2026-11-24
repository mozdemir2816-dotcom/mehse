<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; margin: 0; }
        h1 { font-size: 13px; text-align: center; margin: 0 0 4px; }
        .alt { font-size: 9px; text-align: center; color: #475569; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 11px; }
        th, td { border: 1px solid #334155; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; width: 32%; }
        .gizli { font-size: 8px; color: #334155; background: #f8fafc; border: 1px solid #cbd5e1; padding: 5px 7px; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>EK-1 RADYASYON ÇALIŞANI SAĞLIK RAPORU / MUAYENE FORMU</h1>
    <div class="alt">6331 Sayılı İSG Kanunu m.15 ve İlgili Radyasyon Güvenliği Mevzuatı Uyarınca Özel Sağlık Gözetimi</div>

    <table>
        <tr><th>İşyeri Unvanı</th><td>{{ $firma?->unvan ?: '—' }}</td></tr>
        <tr><th>SGK Sicil No</th><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td></tr>
        <tr><th>Çalışan Adı Soyadı</th><td><strong>{{ $calisan_ad ?: '—' }}</strong></td></tr>
        <tr><th>T.C. Kimlik No</th><td>{{ $calisan_tc ?: '—' }}</td></tr>
        <tr><th>Görevi / Birimi</th><td>{{ $calisan_gorev ?: 'Radyasyon Görevlisi' }}</td></tr>
        <tr><th>Muayene Tarihi / Sonraki Muayene</th><td>{{ $tarih }} &nbsp;|&nbsp; Sonraki: {{ $sonraki_tarih ?? '....../....../..........' }}</td></tr>
        <tr><th>Dozimetre / Takip No</th><td>{{ $dozimetre_no ?: '—' }}</td></tr>
    </table>

    <table>
        <tr><th colspan="2" style="text-align:center">ÖZEL RADYASYON TETKİK VE DEĞERLENDİRMELERİ</th></tr>
        <tr><th>Hemogram (Tam Kan Sayımı)</th><td>{{ filled($hemogram ?? null) ? $hemogram : '................................................................' }}</td></tr>
        <tr><th>Periferik Yayma</th><td>{{ filled($periferik ?? null) ? $periferik : '................................................................' }}</td></tr>
        <tr><th>Göz / Katarakt Muayenesi</th><td>{{ filled($goz ?? null) ? $goz : '................................................................' }}</td></tr>
        <tr><th>Dermatolojik Muayene (Cilt/Tırnak)</th><td>{{ filled($cilt ?? null) ? $cilt : '................................................................' }}</td></tr>
        <tr><th>Tiroid &amp; Sistemik Değerlendirme</th><td>{{ filled($tiroid ?? null) ? $tiroid : '................................................................' }}</td></tr>
    </table>

    <table>
        <tr><th>HEKİM KANAATİ</th><td><strong>{{ filled($kanaat ?? null) ? $kanaat : '☐ Elverişlidir   ☐ Şartlı Elverişlidir   ☐ Elverişli Değildir (Hekimce işaretlenir)' }}</strong></td></tr>
        <tr>
            <th>İşyeri Hekimi</th>
            <td>
                {{ $hekim_adi ?: ($firma?->isyeriHekimi?->ad_soyad ?: '........................................') }}
                @if ($firma?->isyeriHekimi?->sertifika_no) · Belge No: {{ $firma->isyeriHekimi->sertifika_no }}@endif
                <br><br>Diploma No: ...................... &nbsp;&nbsp;&nbsp; Kaşe / İmza:
            </td>
        </tr>
    </table>

    <div class="gizli">
        <strong>TIBBİ GİZLİLİK (6331 SK m.15/5 &amp; 6698 KVKK m.6):</strong> Bu muayene formundaki sağlık bulguları gizlidir; yalnızca işyeri hekimi tarafından sağlık dosyasında muhafaza edilir.
    </div>
</body>
</html>
