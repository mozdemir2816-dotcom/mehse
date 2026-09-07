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
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 12px; margin: 14px 0 6px; color: #ef4444; border-bottom: 1px solid #ef4444; padding-bottom: 3px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    .yaptirim { display: inline-block; background: #ef4444; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 10.5px; font-weight: bold; }
    .imza { margin-top: 30px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İSG CEZA VE TEBLİĞ TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }} — Tutanak No: {{ $tutanak->tutanak_no }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Çalışan</td><td>{{ $tutanak->calisan_ad_soyad ?: '—' }}</td>
            <td>T.C. Kimlik No</td><td>{{ $tutanak->calisan_tc ?: '—' }}</td>
        </tr>
        <tr>
            <td>Görevi / Bölümü</td><td>{{ $tutanak->calisan_gorev ?: '—' }} / {{ $tutanak->calisan_bolum ?: '—' }}</td>
            <td>İstihdam Şekli</td><td>{{ $tutanak->istihdamSekliEtiketi() }}</td>
        </tr>
        <tr>
            <td>İşe Giriş Tarihi</td><td>{{ $tutanak->ise_giris_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Tutanak Tarihi</td><td>{{ $tutanak->tutanak_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
    </table>

    <h2>OLAY BİLGİLERİ</h2>
    <table class="kunye">
        <tr>
            <td>Olay Tarihi / Saati</td><td>{{ $tutanak->olay_tarihi?->format('d.m.Y') ?: '—' }} {{ $tutanak->olay_saati }}</td>
            <td>Olay Yeri</td><td>{{ $tutanak->olay_yeri ?: '—' }}</td>
        </tr>
        <tr><td>Açıklama</td><td colspan="3">{{ $tutanak->olay_aciklamasi ?: '—' }}</td></tr>
    </table>

    <h2>İHLAL EDİLEN KURALLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Madde</th><th style="width:35%">Yasal Dayanak</th></tr>
        @forelse (($tutanak->ihlaller ?? []) as $i => $ih)
            <tr><td>{{ $i + 1 }}</td><td>{{ $ih['madde'] ?? '' }}</td><td>{{ $ih['dayanak'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">İhlal maddesi eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>TANIKLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görevi</th></tr>
        @forelse (($tutanak->taniklar ?? []) as $i => $tk)
            <tr><td>{{ $i + 1 }}</td><td>{{ $tk['ad_soyad'] ?? '' }}</td><td>{{ $tk['gorev'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">Tanık eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>UYGULANAN YAPTIRIM</h2>
    <span class="yaptirim">{{ $tutanak->yaptirimEtiketi() }}</span>

    <h2>TEBLİĞ / TEBELLÜĞ</h2>
    <table class="kunye">
        <tr>
            <td>Tebliğ Tarihi</td><td>{{ $tutanak->teblig_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Çalışanın İmza Durumu</td>
            <td>{{ $tutanak->imza_durumu === 'imtina_etti' ? 'İmzadan İmtina Etti' : 'İmzaladı / Teslim Aldı' }}</td>
        </tr>
    </table>

    <table class="imza">
        <tr>
            <td>Düzenleyen (İSG Uzmanı)<br>(İmza – Kaşe)</td>
            <td>Çalışan<br>(İmza)</td>
        </tr>
    </table>

    <p class="yasal">
        6331 sayılı İş Sağlığı ve Güvenliği Kanunu md.19, 4857 sayılı İş Kanunu md.25-II ve
        md.38 uyarınca düzenlenmiştir.
    </p>

</div>

@foreach (($tutanak->fotograflar ?? []) as $i => $foto)
    <div class="sayfa foto-sayfa">
        <div class="baslik">
            <h1>İSG CEZA VE TEBLİĞ TUTANAĞI</h1>
            <div style="font-size:11px">{{ $firma?->unvan }} — Tutanak No: {{ $tutanak->tutanak_no }}</div>
        </div>
        <div class="baslik2">FOTOĞRAF {{ $i + 1 }}</div>
        <img src="{{ storage_path('app/public/'.$foto) }}">
    </div>
@endforeach

</body>
</html>
