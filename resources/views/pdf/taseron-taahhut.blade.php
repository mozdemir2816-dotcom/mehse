<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; line-height: 1.55; }
    .sayfa { padding: 30px 36px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .alan { border-bottom: 1px solid #111; display: inline-block; min-width: 220px; }
    .imza { margin-top: 30px; }
    .imza td { border: none; width: 50%; text-align: center; padding-top: 40px; vertical-align: top; }
    .imza span { display: block; border-top: 1px solid #111; margin: 0 30px; padding-top: 4px; }
    p { margin: 6px 0; text-align: justify; }
</style>
</head>
<body>
<div class="sayfa">
    <div class="baslik">
        <h1>İSG TAAHHÜTNAMESİ</h1>
        <div>{{ $t->turEtiketi() }}</div>
    </div>

    <p>Tarih: {{ now()->format('d.m.Y') }}</p>
    <p>İşyeri (asıl işveren): <span class="alan">{{ $firma?->unvan }}</span></p>
    <p>Firma: <span class="alan">{{ $t->unvan }}</span></p>

    <p>
        {{ $t->unvan }} firması olarak, 6331 sayılı İş Sağlığı ve Güvenliği Kanunu ve ilgili mevzuat ile
        işyeri İSG kurallarına uyacağımızı; çalışanlarımıza gerekli eğitimi, sağlık gözetimini ve
        kişisel koruyucu donanımı sağlayacağımızı; risk değerlendirmesi ve acil durum planı gereklerini
        yerine getireceğimizi; iş kazası ve ramak kala olaylarını derhal bildireceğimizi; izinsiz iş ve
        yetkisiz alana girişten kaçınacağımızı taahhüt ederiz.
    </p>

    <p>Çalışma konusu: <span class="alan">{{ $t->faaliyet }}</span></p>
    <p>Süre:
        <span class="alan">{{ $t->sozlesme_baslangic?->format('d.m.Y') ?? '......' }}</span>
        -
        <span class="alan">{{ $t->sozlesme_bitis?->format('d.m.Y') ?? '......' }}</span>
    </p>

    <table class="imza" style="width:100%">
        <tr>
            <td><strong>{{ $t->tur === 'ziyaretci_firma' ? 'Ziyaretçi firma yetkilisi' : 'Taşeron / alt işveren yetkilisi' }}</strong><br>Ad soyad / kaşe / imza<span></span></td>
            <td><strong>İşveren / İSG uzmanı</strong><br>Ad soyad / imza<span></span></td>
        </tr>
    </table>
</div>
</body>
</html>
