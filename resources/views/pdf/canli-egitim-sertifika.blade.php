<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 24px; color: #0f172a; }
        .cerceve { border: 6px double #1e3a8a; padding: 28px 34px; text-align: center; }
        h1 { font-size: 24px; color: #1e3a8a; margin: 0 0 6px; letter-spacing: 1px; }
        .alt { font-size: 12px; color: #475569; margin-bottom: 18px; }
        .isim { font-size: 22px; font-weight: bold; margin: 12px 0; color: #0f172a; }
        .detay { font-size: 12px; line-height: 1.6; max-width: 620px; margin: 0 auto 20px; }
        table { width: 100%; margin-top: 22px; border-collapse: collapse; }
        td { width: 33.3%; text-align: center; vertical-align: middle; font-size: 11px; }
    </style>
</head>
<body>
<div class="cerceve">
    <h1>KATILIM VE BAŞARI SERTİFİKASI</h1>
    <div class="alt">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu · Canlı Eğitim Programı</div>

    <div class="isim">{{ mb_strtoupper($sonuc['ad_soyad'] ?? '', 'UTF-8') }}</div>

    <div class="detay">
        <strong>{{ $oturum->firma?->unvan }}</strong> işyerinde düzenlenen
        <strong>{{ $oturum->baslik }}</strong> ({{ $oturum->kategoriEtiketi() }} — {{ $oturum->sure_saat }} Saat)
        canlı eğitim oturumuna katılmış ve değerlendirme sınavından <strong>{{ $sonuc['puan'] }} / 100</strong> puan alarak
        bu sertifikayı almaya hak kazanmıştır.
    </div>

    <table>
        <tr>
            <td>
                <strong>Eğitici / İSG Uzmanı</strong><br>
                {{ $oturum->user?->name ?? '—' }}<br>
                {{ $oturum->user?->unvanEtiketi() ?? '' }}
            </td>
            <td>
                <img src="{{ $qrUri }}" style="width:92px;height:92px" alt="QR"><br>
                <span style="font-size:9px;color:#475569">Kod: {{ $sonuc['kod'] }}</span>
            </td>
            <td>
                <strong>Belge Tarihi</strong><br>
                {{ $sonuc['tarih'] }}<br>
                Sınav Başarı Puanı: %{{ $sonuc['puan'] }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
