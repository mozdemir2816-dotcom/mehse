<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İSG Saha ve Uzmanlık Sınavı Hap Notları</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #111827; line-height: 1.4; }
        .baslik-kutu { border: 2px solid #b45309; background: #fffbeb; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; }
        .baslik { font-size: 13px; font-weight: bold; color: #92400e; }
        .alt-baslik { font-size: 8.5px; color: #4b5563; margin-top: 2px; }
        .kart { border: 1px solid #d1d5db; border-radius: 5px; margin-bottom: 8px; page-break-inside: avoid; }
        .kart-baslik { background: #f3f4f6; padding: 5px 10px; font-weight: bold; font-size: 9.5px; border-bottom: 1px solid #d1d5db; color: #1f2937; }
        .kart-icerik { padding: 6px 12px; }
        .kart-icerik ul { margin: 0; padding-left: 14px; }
        .kart-icerik li { margin-bottom: 2.5px; }
        .alt-not { margin-top: 10px; font-size: 8px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="baslik-kutu">
        <div class="baslik">İSG SAHA UYGULAMALARI VE UZMANLIK SINAVI SAYISAL HAP NOTLAR REHBERİ</div>
        <div class="alt-baslik">6331 Sayılı İSG Kanunu, 4857 Sayılı İş Kanunu, 5510 Sayılı Kanun ve İlgili Yönetmelikler Özeti &nbsp;|&nbsp; Basım Tarihi: {{ $tarih }}</div>
    </div>

    @foreach ($notlar as $n)
        <div class="kart">
            <div class="kart-baslik">{{ $n['baslik'] }} ({{ $n['kategori'] }})</div>
            <div class="kart-icerik">
                <ul>
                    @foreach ($n['maddeler'] as $m)
                        <li>{{ $m }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endforeach

    <div class="alt-not">
        Bu özet tablo İş Güvenliği Uzmanları, İşyeri Hekimleri ve Diğer Sağlık Personelinin saha denetimlerinde ve sınav hazırlığında hızlı başvuru kaynağı olarak hazırlanmıştır.
    </div>
</body>
</html>
