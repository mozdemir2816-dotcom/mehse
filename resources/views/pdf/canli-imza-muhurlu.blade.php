<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; margin: 24px; }
        .baslik { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 16px; }
        .baslik h1 { font-size: 16px; margin: 0 0 4px; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td, th { border: 1px solid #cbd5e1; padding: 7px 9px; text-align: left; }
        th { background: #f1f5f9; width: 32%; font-weight: bold; }
        .imza-kutu { border: 1px solid #94a3b8; border-radius: 6px; padding: 12px; text-align: center; margin-top: 12px; }
        .alt { margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 10px; font-size: 9.5px; color: #475569; }
    </style>
</head>
<body>
    <div class="baslik">
        <h1>QR DOĞRULAMALI BASİT ELEKTRONİK ONAY VE İMZA KAYIT BELGESİ</h1>
        <div>{{ $imza->firma?->unvan }}</div>
    </div>

    <table>
        <tr><th>Belge Türü</th><td>{{ $imza->belgeTuruEtiketi() }}</td></tr>
        <tr><th>Belge Başlığı</th><td>{{ $imza->belge_baslik }}</td></tr>
        <tr><th>İmzalayan Çalışan</th><td>{{ $imza->calisan_adi ?: '—' }}</td></tr>
        <tr><th>T.C. Kimlik No</th><td>{{ $imza->maskeliTc() }}</td></tr>
        <tr><th>İmza Tarih & Saati</th><td>{{ $imza->imzalandi_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i:s') ?: 'İmza Bekliyor' }}</td></tr>
        <tr><th>Doğrulama Kodu</th><td><strong>{{ $imza->dogrulama_kodu ?: '—' }}</strong></td></tr>
        <tr><th>IP / Cihaz Özeti</th><td>{{ $imza->ip_adresi ?: '—' }}</td></tr>
    </table>

    <table style="border:none">
        <tr>
            <td style="width:62%;vertical-align:top;border:none;padding-left:0">
                <div class="imza-kutu">
                    <div style="font-weight:bold;margin-bottom:6px">Çalışan Islak / Canlı İmza Örneği</div>
                    @if ($imzaBase64)
                        <img src="{{ $imzaBase64 }}" style="max-height:110px;max-width:260px" alt="İmza">
                    @else
                        <div style="padding:30px;color:#94a3b8">Henüz imzalanmadı</div>
                    @endif
                </div>
            </td>
            <td style="width:38%;vertical-align:top;text-align:center;border:none;padding-right:0">
                @if ($qrUri)
                    <img src="{{ $qrUri }}" style="width:120px;height:120px" alt="QR">
                    <div style="font-size:9px;color:#475569;margin-top:4px">QR okutarak doğrulayın</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="alt">
        Bu belge Mehse İSG sisteminde dokunmatik ekran / fare üzerinden alınan basit elektronik onay ve imza görseli kaydıdır;
        5070 sayılı Elektronik İmza Kanunu anlamında nitelikli (güvenli) elektronik imza niteliği taşımaz.
        Doğrulama adresi: {{ $dogrulamaUrl ?: '—' }}
    </div>
</body>
</html>
