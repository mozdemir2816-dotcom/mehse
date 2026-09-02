<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 30px 36px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    .aciklama { font-size: 11px; font-style: italic; color: #444; margin-bottom: 14px; }
    .kkd span { display: inline-block; background: #3b82f6; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 9.5px; margin: 0 4px 4px 0; }
    ol.maddeler { font-size: 11px; line-height: 1.7; margin: 14px 0; padding-left: 1.3rem; }
    .imza { margin-top: 50px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>{{ $talimat->baslik }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr><td>Kategori</td><td>{{ $talimat->kategoriEtiketi() }}</td></tr>
    </table>

    @if ($talimat->aciklama)
        <p class="aciklama">{{ $talimat->aciklama }}</p>
    @endif

    <div class="kkd">
        @foreach (($talimat->kkdler ?? []) as $kkd)
            <span>{{ $kkd }}</span>
        @endforeach
    </div>

    <ol class="maddeler">
        @forelse (($talimat->maddeler ?? []) as $madde)
            <li>{{ $madde }}</li>
        @empty
            <li style="color:#888">Madde eklenmedi.</li>
        @endforelse
    </ol>

    <table class="imza">
        <tr>
            <td>İş Güvenliği Uzmanı<br>(İmza – Kaşe)</td>
            <td>Çalışan<br>(Okudum, Anladım – İmza)</td>
        </tr>
    </table>

    <p class="yasal">
        6331 sayılı İş Sağlığı ve Güvenliği Kanunu uyarınca işveren, çalışana yapacağı iş ve
        kullanacağı ekipmanla ilgili güvenli çalışma talimatı vermekle yükümlüdür.
    </p>

</div>
</body>
</html>
