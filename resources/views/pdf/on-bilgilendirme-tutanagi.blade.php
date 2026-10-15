<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 6px 8px; vertical-align: top; }
    .kunye td.e { background: #f0f0f0; font-weight: bold; width: 26%; }
    .metin { font-size: 10.5px; line-height: 1.55; margin: 14px 0; text-align: justify; }
    .imza { margin-top: 40px; width: 100%; border-collapse: collapse; }
    .imza td { width: 50%; text-align: center; vertical-align: top; font-size: 10px; padding: 0 12px; }
    .imza .kutu { border-top: 1px solid #111; padding-top: 6px; margin-top: 60px; }
</style>
</head>
<body>
<div class="sayfa">
    <div class="baslik">
        <h1>TEBLİĞ - TEBELLÜĞ TUTANAĞI</h1>
        <div style="font-size:11px">İş Sağlığı ve Güvenliği Ön Bilgilendirmesi</div>
    </div>

    <table class="kunye">
        <tr><td class="e">İşyeri</td><td>{{ $firma?->unvan }}</td></tr>
        <tr><td class="e">Konu</td><td>{{ $b->konu }}</td></tr>
        <tr><td class="e">Ekli belge</td><td>{{ $b->dosya_adi }}</td></tr>
        <tr><td class="e">Bildirim tarihi</td><td>{{ $b->bildirim_tarihi?->format('d.m.Y') }}</td></tr>
        @if ($b->aciklama)
            <tr><td class="e">Açıklama</td><td>{{ $b->aciklama }}</td></tr>
        @endif
        <tr>
            <td class="e">Tebliğ şekli</td>
            <td>
                @if ($b->isyeri_okundu_at)
                    İşyeri Girişi paneli üzerinden {{ $b->isyeri_okundu_at->format('d.m.Y H:i') }} tarihinde okundu olarak onaylandı ({{ $b->isyeri_onaylayan }}).<br>
                @endif
                @if ($b->elden_teslim_tarihi)
                    {{ $b->elden_teslim_tarihi->format('d.m.Y') }} tarihinde elden teslim edildi ({{ $b->elden_teslim_alan }}).
                @endif
                @if (! $b->isyeri_okundu_at && ! $b->elden_teslim_tarihi)
                    Elden — Tarih: ..... / ..... / ..........
                @endif
            </td>
        </tr>
    </table>

    <p class="metin">
        Yukarıda konusu belirtilen ve işe başlanmadan önce alınması gereken iş sağlığı ve güvenliği
        tedbirlerini içeren bilgilendirme yazısı işverene / işveren vekiline tebliğ edilmiş, bir
        nüshası teslim alınmıştır.
    </p>

    <table class="imza">
        <tr>
            <td><div class="kutu"><strong>TEBLİĞ EDEN</strong><br>İş Güvenliği Uzmanı<br>(İmza – Kaşe)</div></td>
            <td><div class="kutu"><strong>TEBELLÜĞ EDEN</strong><br>İşveren / İşveren Vekili<br>Adı Soyadı – İmza</div></td>
        </tr>
    </table>
</div>
</body>
</html>
