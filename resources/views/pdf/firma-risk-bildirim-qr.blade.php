<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sahadan Risk ve Tehlike Bildirim QR Posteri</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; line-height: 1.45; }
        .cerceve { border: 3px solid #b91c1c; border-radius: 12px; padding: 20px 24px; text-align: center; }
        .ust-etiket { background: #b91c1c; color: #ffffff; font-weight: bold; font-size: 13px; padding: 6px 16px; border-radius: 999px; display: inline-block; letter-spacing: 0.5px; }
        .ana-baslik { font-size: 22px; font-weight: bold; color: #991b1b; margin: 12px 0 4px; }
        .alt-aciklama { font-size: 11px; color: #4b5563; margin-bottom: 14px; }
        .firma-kutu { background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
        .firma-unvan { font-size: 15px; font-weight: bold; color: #111827; }
        .firma-detay { font-size: 10px; color: #4b5563; margin-top: 3px; }
        .qr-kutu { border: 2px dashed #dc2626; border-radius: 10px; padding: 14px; display: inline-block; background: #ffffff; margin: 6px 0 14px; }
        .adimlar-tablo { width: 100%; border-collapse: collapse; margin-top: 10px; text-align: left; }
        .adimlar-tablo td { border: 1px solid #e5e7eb; padding: 8px 10px; font-size: 10px; vertical-align: top; width: 33.3%; background: #f9fafb; }
        .adim-baslik { font-weight: bold; color: #b91c1c; font-size: 10.5px; margin-bottom: 3px; }
        .iletisim-tablo { width: 100%; border-collapse: collapse; margin-top: 14px; text-align: left; }
        .iletisim-tablo th, .iletisim-tablo td { border: 1px solid #d1d5db; padding: 6px 10px; font-size: 9.5px; }
        .iletisim-tablo th { background: #f3f4f6; color: #374151; width: 28%; }
        .alt-uyari { margin-top: 14px; font-size: 8.5px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="cerceve">
        <div class="ust-etiket">6331 SAYILI İŞ SAĞLIĞI VE GÜVENLİĞİ KANUNU — ÇALIŞAN KATILIMI</div>
        <div class="ana-baslik">SAHADAN RİSK, TEHLİKE VE RAMAK KALA BİLDİRİM NOKTASI</div>
        <div class="alt-aciklama">
            Çalışma alanınızda gördüğünüz tehlikeleri, güvensiz durumları veya ramak kala olayları telefon kameranızla aşağıdaki QR kodu okutarak fotoğraflı şekilde anında bildirebilirsiniz.
        </div>

        <div class="firma-kutu">
            <div class="firma-unvan">{{ $firma->unvan }}</div>
            <div class="firma-detay">
                Tehlike Sınıfı: <strong>{{ $firma->tehlikeSinifiEtiketi() }}</strong>
                @if ($firma->sgk_sicil_no) &nbsp;|&nbsp; SGK Sicil No: <strong>{{ $firma->sgk_sicil_no }}</strong> @endif
                @if ($firma->il) &nbsp;|&nbsp; Konum: <strong>{{ $firma->il }}{{ $firma->ilce ? ' / '.$firma->ilce : '' }}</strong> @endif
            </div>
        </div>

        <div class="qr-kutu">
            <img src="{{ $qrDataUri }}" alt="Risk Bildirim QR Kodu" style="width: 210px; height: 210px;">
            <div style="font-size: 9.5px; font-weight: bold; color: #991b1b; margin-top: 4px;">
                📱 TELEFON KAMERASIYLA OKUTUN
            </div>
        </div>

        <table class="adimlar-tablo">
            <tr>
                <td>
                    <div class="adim-baslik">1. QR Kodu Okutun</div>
                    Telefonunuzun kamerasını yukarıdaki karekoda tutun ve açılan bildirim bağlantısına dokunun.
                </td>
                <td>
                    <div class="adim-baslik">2. Fotoğraf Çekin &amp; Yazın</div>
                    Gördüğünüz tehlikenin fotoğrafını çekin, bulunduğu bölümü ve kısa açıklamayı ekleyin.
                </td>
                <td>
                    <div class="adim-baslik">3. Önlem Alınsın</div>
                    Bildiriminiz doğrudan İSG Uzmanı ve İşveren Vekiline iletilir; DÖF veya Risk Analizine aktarılır.
                </td>
            </tr>
        </table>

        <table class="iletisim-tablo">
            <tr>
                <th>İş Güvenliği Uzmanı (İGU)</th>
                <td>{{ $firma->igu?->ad_soyad ?: '—' }}</td>
                <th>İşyeri Hekimi</th>
                <td>{{ $firma->isyeriHekimi?->ad_soyad ?: '—' }}</td>
            </tr>
            <tr>
                <th>İşveren / İşveren Vekili</th>
                <td>{{ $firma->isveren_vekili ?: ($firma->isveren_ad ?: '—') }}</td>
                <th>Acil Çağrı / İSG İletişim</th>
                <td><strong>112 Acil Çağrı</strong> {{ $firma->telefon ? ' · '.$firma->telefon : '' }}</td>
            </tr>
        </table>

        <div class="alt-uyari">
            <strong>6331 Sayılı Kanun Madde 19:</strong> Çalışanlar, işyerinde sağlık ve güvenliği etkileyebilecek tehlikeleri işverene veya çalışan temsilcisine bildirmekle yükümlüdür. &nbsp;|&nbsp; Poster Basım Tarihi: {{ $tarih }}
        </div>
    </div>
</body>
</html>
