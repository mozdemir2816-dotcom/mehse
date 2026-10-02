<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .sayfa { padding: 28px; }
    .kart { width: 320px; border: 2px solid #111; border-radius: 10px; overflow: hidden; }
    .ust { background: #7c3aed; color: #fff; text-align: center; padding: 10px 8px; }
    .ust .baslik { font-size: 16px; font-weight: bold; letter-spacing: 2px; }
    .ust .firma { font-size: 9.5px; margin-top: 2px; }
    .govde { padding: 10px 12px; }
    .ad { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
    .kurum { font-size: 10px; color: #444; margin-bottom: 8px; }
    table.bilgi { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.bilgi td { padding: 2px 0; vertical-align: top; }
    table.bilgi td.k { color: #555; width: 38%; }
    .qr { text-align: center; padding: 6px 0 2px; }
    .qr img { width: 120px; height: 120px; }
    .kartno { text-align: center; font-size: 11px; font-weight: bold; letter-spacing: 1px; }
    .not { text-align: center; font-size: 7.5px; color: #555; padding-bottom: 8px; }
    .kurallar { margin-top: 18px; width: 320px; border: 1px solid #999; border-radius: 8px; padding: 10px 12px; }
    .kurallar h3 { font-size: 10.5px; margin: 0 0 6px; color: #7c3aed; }
    .kurallar ol { margin: 0; padding-left: 16px; font-size: 8.5px; }
    .kurallar li { margin-bottom: 3px; }
    .kes { font-size: 8px; color: #888; margin: 6px 0 0; }
</style>
</head>
<body>
<div class="sayfa">
    <div class="kart">
        <div class="ust">
            <div class="baslik">ZİYARETÇİ</div>
            <div class="firma">{{ $firma?->unvan }}</div>
        </div>
        <div class="govde">
            <div class="ad">{{ $z->ad_soyad }}</div>
            <div class="kurum">{{ $z->kurum ?: ' ' }}</div>
            <table class="bilgi">
                <tr><td class="k">Ziyaret amacı</td><td>{{ $z->ziyaret_amaci ?: '—' }}</td></tr>
                <tr><td class="k">Ziyaret edilen</td><td>{{ $z->ziyaret_edilen ?: '—' }}</td></tr>
                <tr><td class="k">Geçerlilik</td><td>{{ $z->gecerlilik_baslangic->format('d.m.Y H:i') }}<br>{{ $z->gecerlilik_bitis->format('d.m.Y H:i') }}</td></tr>
                <tr><td class="k">Verilen KKD</td><td>{{ $z->verilen_kkd ?: '—' }}</td></tr>
                <tr><td class="k">İSG bilgilendirme</td><td>{{ $z->isg_bilgilendirme ? 'Yapıldı' : 'Yapılmadı' }}</td></tr>
            </table>
            <div class="qr"><img src="{{ $z->qrDataUri() }}"></div>
            <div class="kartno">{{ $z->kart_no }}</div>
            <div class="not">Geçerliliği doğrulamak için QR kodu okutun.</div>
        </div>
    </div>
    <p class="kes">✂ Kartı çizgiden kesip kart kılıfına takabilirsiniz.</p>

    <div class="kurallar">
        <h3>ZİYARETÇİ GÜVENLİK KURALLARI</h3>
        <ol>
            @foreach (config('isg.ziyaretci.kurallar', []) as $kural)
                <li>{{ $kural }}</li>
            @endforeach
        </ol>
    </div>
</div>
</body>
</html>
