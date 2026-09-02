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
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 24%; }
    h2 { font-size: 11.5px; margin: 12px 0 4px; color: #0ea5e9; }
    ul.konular { margin: 0 0 8px; padding-left: 1.2rem; font-size: 10px; }
    ul.konular li { margin-bottom: 2px; }
    .imza { margin-top: 40px; width: 100%; }
    .imza td { width: 33.33%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 9.5px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞBAŞI / ORYANTASYON EĞİTİM TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Çalışan</td><td>{{ $tutanak->calisan_ad_soyad }}</td>
            <td>T.C. Kimlik No</td><td>{{ $tutanak->tcGorunur() }}</td>
        </tr>
        <tr>
            <td>Eğitim Tarihi</td><td>{{ $tutanak->egitim_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Süre</td><td>{{ $tutanak->sure_saat ?? '—' }} saat</td>
        </tr>
        <tr>
            <td>Eğitim Yeri</td><td>{{ $tutanak->egitim_yeri ?: '—' }}</td>
            <td>Eğitim Yöntemi</td><td>{{ $tutanak->egitim_yontemi }}</td>
        </tr>
        <tr>
            <td>Eğitimi Veren</td><td>{{ $tutanak->egitimi_veren ?: '—' }}</td>
            <td>Belge Tarihi</td><td>{{ $tutanak->belge_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
    </table>

    @foreach (config('isg.isbasi_egitim.konu_kategorileri') as $kategori => $maddeler)
        <h2>{{ mb_strtoupper($kategori, 'UTF-8') }}</h2>
        <ul class="konular">
            @foreach ($maddeler as $madde)
                @php $isaretli = in_array($madde, $tutanak->konular ?? [], true); @endphp
                <li>{{ $isaretli ? '☑' : '☐' }} {{ $madde }}</li>
            @endforeach
        </ul>
    @endforeach

    <table class="imza">
        <tr>
            <td>{{ $tutanak->calisan_ad_soyad }}<br>(Çalışan İmzası)</td>
            <td>{{ $tutanak->egitimi_veren ?: 'Eğitici' }}<br>(İmza)</td>
            <td>
                @if ($tutanak->igu_imzasi) İş Güvenliği Uzmanı @endif
                @if ($tutanak->igu_imzasi && $tutanak->isyeri_hekimi_imzasi) / @endif
                @if ($tutanak->isyeri_hekimi_imzasi) İşyeri Hekimi @endif
                @if (! $tutanak->igu_imzasi && ! $tutanak->isyeri_hekimi_imzasi) — @endif
                <br>(İmza – Kaşe)
            </td>
        </tr>
    </table>

</div>
</body>
</html>
