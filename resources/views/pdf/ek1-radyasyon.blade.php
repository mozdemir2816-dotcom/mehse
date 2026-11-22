<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #0f172a; margin: 20px; }
        h1 { font-size: 13.5px; text-align: center; margin: 0 0 4px; }
        .alt { font-size: 9.5px; text-align: center; color: #475569; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #334155; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; width: 32%; }
    </style>
</head>
<body>
    <h1>EK-1 RADYASYON ÇALIŞANI SAĞLIK RAPORU / MUAYENE FORMU</h1>
    <div class="alt">Sağlık Hizmetlerinde İyonlaştırıcı Radyasyon Kaynakları ile Çalışan Personelin Radyasyon Doz Limitleri ve Çalışma Esasları Hakkında Yönetmelik</div>

    <table>
        <tr><th>İşyeri Ünvanı</th><td>{{ $firma?->unvan }}</td></tr>
        <tr><th>Çalışan Adı Soyadı</th><td><strong>{{ $calisan_ad ?: '—' }}</strong></td></tr>
        <tr><th>T.C. Kimlik No</th><td>{{ $calisan_tc ?: '—' }}</td></tr>
        <tr><th>Görevi / Birimi</th><td>{{ $calisan_gorev ?: 'Radyasyon Görevlisi' }}</td></tr>
        <tr><th>Muayene Tarihi</th><td>{{ $tarih }}</td></tr>
        <tr><th>Dozimetre / Takip No</th><td>{{ $dozimetre_no ?: '—' }}</td></tr>
    </table>

    <table>
        <tr><th colspan="2" style="text-align:center">ÖZEL RADYASYON TETKİK VE DEĞERLENDİRMELERİ</th></tr>
        <tr><th>Hemogram (Tam Kan Sayımı)</th><td>{{ $hemogram ?: 'Normal sınırlar içinde' }}</td></tr>
        <tr><th>Periferik Yayma</th><td>{{ $periferik ?: 'Normal' }}</td></tr>
        <tr><th>Göz / Katarakt Muayenesi</th><td>{{ $goz ?: 'Katarakt / lens opasitesi saptanmadı' }}</td></tr>
        <tr><th>Dermatolojik Muayene (Cilt/Tırnak)</th><td>{{ $cilt ?: 'Radyasyon dermatiti bulgusu yok' }}</td></tr>
        <tr><th>Tiroid &amp; Sistemik Değerlendirme</th><td>{{ $tiroid ?: 'Normal' }}</td></tr>
    </table>

    <table>
        <tr><th>HEKİM KANAATİ</th><td><strong>{{ $kanaat ?: 'İyonlaştırıcı radyasyon kaynakları ile çalışmaya elverişlidir.' }}</strong></td></tr>
        <tr><th>İşyeri Hekimi</th><td>{{ $hekim_adi ?: '........................................' }} &nbsp;&nbsp;&nbsp; Kaşe / İmza:</td></tr>
    </table>
</body>
</html>
