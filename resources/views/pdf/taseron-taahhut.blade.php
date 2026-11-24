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
    .imza { margin-top: 24px; page-break-inside: avoid; width: 100%; border-collapse: collapse; }
    .imza td { border: none; width: 50%; text-align: center; padding: 6px 10px; vertical-align: bottom; font-size: 9.5px; }
    .imza .cizgi { height: 42px; border-bottom: 1px solid #111; margin: 0 24px 5px; }
    p { margin: 6px 0; text-align: justify; }
</style>
</head>
<body>
<div class="sayfa">
    <div class="baslik">
        <h1>İSG TAAHHÜTNAMESİ</h1>
        <div>{{ $t->turEtiketi() }}</div>
    </div>

    <p>Tarih: {{ ($t->sozlesme_baslangic ?? $t->created_at ?? now())->format('d.m.Y') }}</p>
    <p>İşyeri (Asıl İşveren): <span class="alan">{{ $firma?->unvan }}</span>@if ($firma?->sgk_sicil_no) &nbsp;·&nbsp; SGK Sicil: {{ $firma->sgk_sicil_no }}@endif</p>
    <p>Alt İşveren / Firma: <span class="alan">{{ $t->unvan }}</span>@if ($t->yetkili) &nbsp;·&nbsp; Yetkili: {{ $t->yetkili }}@endif</p>
    <p>Vergi / SGK Sicil No: <span class="alan">{{ collect([$t->vergi_no ?? null, $t->sgk_sicil_no ?? null])->filter()->implode(' / ') ?: '' }}</span></p>

    <p>
        {{ $t->unvan }} firması olarak, 6331 sayılı İş Sağlığı ve Güvenliği Kanunu (m.22/2 kurul koordinasyonu ve m.23 aynı çalışma alanında işverenler arası İSG koordinasyonu ve karşılıklı risk bilgilendirmesi dahil) ile 4857 sayılı İş Kanunu m.2/6–7 ve işyeri İSG kurallarına tam olarak uyacağımızı; çalışma öncesinde tüm personelimize ait SGK işe giriş bildirgeleri, geçerli İSG eğitim belgeleri, işe giriş/periyodik sağlık muayene raporları (EK-2), mesleki yeterlilik belgeleri (MYK), KKD zimmet tutanakları ve sahada kullanılacak iş ekipmanlarının geçerli periyodik kontrol raporlarını asıl işverene ibraz edeceğimizi; risk değerlendirmesi ve acil durum planı gereklerini yerine getireceğimizi; iş kazası ve ramak kala olaylarını derhal bildireceğimizi; izinsiz iş ve yetkisiz alana girişten kaçınacağımızı taahhüt ederiz.
    </p>

    <p>Çalışma konusu: <span class="alan">{{ $t->faaliyet }}</span></p>
    <p>Süre:
        <span class="alan">{{ $t->sozlesme_baslangic?->format('d.m.Y') ?? '......' }}</span>
        -
        <span class="alan">{{ $t->sozlesme_bitis?->format('d.m.Y') ?? '......' }}</span>
    </p>

    <table class="imza">
        <tr>
            <td>
                <div class="cizgi"></div>
                <strong>{{ $t->tur === 'ziyaretci_firma' ? 'Ziyaretçi Firma Yetkilisi' : 'Taşeron / Alt İşveren Yetkilisi' }}</strong><br>
                {{ $t->yetkili ?: 'Ad Soyad' }} · Kaşe / İmza
            </td>
            <td>
                <div class="cizgi"></div>
                <strong>Asıl İşveren / İSG Uzmanı</strong><br>
                {{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: 'Ad Soyad') }} · İmza
            </td>
        </tr>
    </table>
</div>
</body>
</html>
