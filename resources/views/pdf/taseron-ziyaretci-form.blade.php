<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; line-height: 1.5; }
    .sayfa { padding: 26px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 14px; margin: 0 0 4px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
    th, td { border: 1px solid #555; padding: 6px 7px; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; }
    td.k { background: #f0f0f0; font-weight: bold; width: 22%; }
    .kurallar li { margin-bottom: 3px; }
    .imza td { border: none; width: 50%; text-align: center; padding-top: 40px; }
    .imza span { display: block; border-top: 1px solid #111; margin: 0 30px; padding-top: 4px; }
</style>
</head>
<body>
<div class="sayfa">
    <div class="baslik">
        <h1>ZİYARETÇİ KAYIT VE İSG BİLGİLENDİRME FORMU</h1>
        <div style="font-size:10px;color:#555">{{ $firma?->unvan }} · {{ now()->format('d.m.Y') }}</div>
    </div>

    <table>
        <tr><td class="k">Ziyaretçi firma</td><td colspan="3">{{ $t->unvan }}</td></tr>
        <tr><td class="k">Ziyaret amacı</td><td colspan="3">{{ $t->faaliyet }}&nbsp;</td></tr>
        <tr><td class="k">Ziyaret süresi</td><td>{{ $t->sozlesme_baslangic?->format('d.m.Y') ?? '......' }} – {{ $t->sozlesme_bitis?->format('d.m.Y') ?? '......' }}</td>
            <td class="k">Eşlikçi / sorumlu</td><td>{{ $t->yetkili }}&nbsp;</td></tr>
    </table>

    <table>
        <tr><th>Sıra</th><th>Ad soyad</th><th>Kimlik no (son 2 hane)</th><th>Plaka</th><th>Giriş saati</th><th>Çıkış saati</th><th>İmza</th></tr>
        @for ($i = 1; $i <= 6; $i++)
            <tr><td>{{ $i }}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        @endfor
    </table>

    <p><strong>Bilgilendirme:</strong> Yukarıda adı yazılı ziyaretçi olarak; refakatçi olmadan dolaşmayacağımızı,
        yaya yollarını kullanacağımızı, gerekli kişisel koruyucu donanımı takacağımızı, acil durumda toplanma
        alanına gideceğimizi, yasak alanlara girmeyeceğimizi ve aşağıdaki kurallara uyacağımızı kabul ederiz.</p>

    <ol class="kurallar">
        @foreach (config('isg.ziyaretci.kurallar', []) as $kural)
            <li>{{ $kural }}</li>
        @endforeach
    </ol>

    <p>KVKK aydınlatma metnini okuduğumu ve kayıt işlemi için kimlik bilgilerimin işlenmesine onay verdiğimi beyan ederim.</p>

    <table class="imza">
        <tr>
            <td><strong>Ziyaretçi firma yetkilisi</strong><br>Ad soyad / kaşe / imza<span></span></td>
            <td><strong>Karşılayan / eşlikçi personel</strong><br>Ad soyad / imza<span></span></td>
        </tr>
    </table>
</div>
</body>
</html>
