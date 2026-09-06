<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 30px 36px; }
    .ust { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
    .ust .sol { display: table-cell; }
    .ust .sag { display: table-cell; text-align: right; font-size: 9px; color: #555; vertical-align: top; }
    .ust h1 { font-size: 14px; margin: 4px 0 0; }
    .ust .firma { font-size: 11px; font-weight: bold; }
    .adres { margin-bottom: 12px; }
    .govde { font-size: 11px; line-height: 1.6; text-align: justify; margin-bottom: 12px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 6px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 34%; }
    .imza { margin-top: 60px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">
    @php $aday = $secim->secilenAday(); @endphp

    <div class="ust">
        <div class="sol">
            <div class="firma">{{ $firma?->unvan }}</div>
            <h1>ÇALIŞAN TEMSİLCİSİ ATAMA TUTANAĞI</h1>
        </div>
        <div class="sag">
            Belge No: {{ $secim->dokuman_no }}<br>
            Düzenleme Tarihi: {{ $secim->ilan_tarihi?->format('d.m.Y') }}
        </div>
    </div>

    <div class="adres">İşyeri Adresi: {{ $firma?->adres }}</div>

    <div class="govde">
        {{ $firma?->unvan }} işyerinde gerçekleştirilen çalışan temsilcisi seçimi sonucunda aşağıda bilgileri yer
        alan çalışan, 6331 sayılı İş Sağlığı ve Güvenliği Kanununun 20 nci maddesi kapsamında çalışan temsilcisi
        olarak görevlendirilmiştir.
    </div>

    <table class="kunye">
        <tr><td>Atanan Çalışanın Adı Soyadı</td><td>{{ $aday['ad_soyad'] ?? '—' }}</td></tr>
        <tr><td>Unvanı / Görevi</td><td>{{ $aday['unvan'] ?: '—' }}</td></tr>
        <tr><td>Görevlendirme Tarihi</td><td>{{ ($secim->gorevlendirme_tarihi ?: $secim->secim_tarihi)?->format('d.m.Y') ?: '—' }}</td></tr>
        <tr><td>İşyeri Çalışan Sayısı</td><td>{{ $secim->isyeri_calisan_sayisi }}</td></tr>
        <tr><td>Zorunlu Temsilci Sayısı</td><td>{{ $secim->zorunlu_temsilci_sayisi }}</td></tr>
    </table>

    <div class="govde">
        Çalışan temsilcisi; iş sağlığı ve güvenliği ile ilgili çalışmalara katılmaya, çalışmaları izlemeye, tehlike
        kaynağının yok edilmesi veya riskin azaltılması için işverenden tedbir alınmasını istemeye ve çalışanları
        temsil etmeye yetkilidir.
    </div>

    <div class="govde">
        Görevini yürütmesi nedeniyle çalışan temsilcisinin hakları kısıtlanamaz. Çalışan temsilcisi, görevi gereği
        öğrendiği işyeri sırlarını ve çalışanlara ait özel bilgileri gizli tutmakla yükümlüdür.
    </div>

    <table class="imza">
        <tr>
            <td>ÇALIŞAN TEMSİLCİSİ<br>{{ $aday['ad_soyad'] ?? '' }}<br>İmza</td>
            <td>İŞVEREN / İŞVEREN VEKİLİ<br><br>İmza</td>
        </tr>
    </table>

    <p class="yasal">Dayanak: 6331 sayılı Kanun md.20 ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına İlişkin Tebliğ.</p>

</div>
</body>
</html>
