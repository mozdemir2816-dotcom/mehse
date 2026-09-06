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
    .cizgi { display: inline-block; border-bottom: 1px solid #111; }
</style>
</head>
<body>
@php
    $bosMi = $bos ?? false;
    $tire = fn ($deger) => filled($deger) ? $deger : ($bosMi ? '' : '—');
@endphp
<div class="sayfa">

    <div class="ust">
        <div class="sol">
            <div class="firma">@if ($bosMi)<span class="cizgi" style="min-width:260px">&nbsp;</span>@else{{ $firma?->unvan }}@endif</div>
            <h1>ÇALIŞAN TEMSİLCİSİ SEÇİM DUYURU İLANI</h1>
        </div>
        <div class="sag">
            Belge No: {{ $secim->dokuman_no }}<br>
            Düzenleme Tarihi: {{ $secim->ilan_tarihi?->format('d.m.Y') }}
        </div>
    </div>

    <div class="adres">İşyeri Adresi: @if ($bosMi)<span class="cizgi" style="min-width:320px">&nbsp;</span>@else{{ $firma?->adres }}@endif</div>

    <div class="govde">
        İşyerimizde iş sağlığı ve güvenliği ile ilgili çalışmalara katılmak, çalışmaları izlemek, gerekli tedbirlerin
        alınmasını istemek ve çalışanları temsil etmek üzere çalışan temsilcisi seçimi yapılacaktır.
    </div>

    <table class="kunye">
        <tr><td>İşyeri</td><td>@if ($bosMi)&nbsp;@else{{ $firma?->unvan }}@endif</td></tr>
        <tr><td>Çalışan Sayısı</td><td>{{ $tire($secim->isyeri_calisan_sayisi) }}</td></tr>
        <tr><td>Seçilecek Temsilci Sayısı</td><td>{{ $tire($secim->zorunlu_temsilci_sayisi) }}</td></tr>
        <tr><td>Aday Başvuru Son Tarihi</td><td>{{ $tire($secim->aday_basvuru_son_tarihi?->format('d.m.Y')) }}</td></tr>
        <tr><td>Seçim Tarihi ve Saati</td><td>{{ $tire($secim->secim_tarihi?->format('d.m.Y')) }} {{ $secim->secim_saati }}</td></tr>
        <tr><td>Seçim Yeri</td><td>{{ $tire($secim->secim_yeri) }}</td></tr>
    </table>

    <div class="govde">
        Aday olmak isteyen çalışanların, başvuru süresi içinde imzalı aday başvuru dilekçesiyle işverenliğe
        başvurmaları gerekmektedir. Aday başvuru süresi, Tebliğ uyarınca yedi günden az olamaz.
    </div>

    <div class="govde">
        Adaylarda; işyerinin tam süreli daimî çalışanı olma, en az üç yıllık iş deneyimine sahip olma ve en az
        ortaokul düzeyinde öğrenim görmüş olma şartları aranır. Tebliğde belirtilen istisnalar saklıdır.
    </div>

    <div class="govde">
        Aday sayısının zorunlu çalışan temsilcisi sayısının üç katını aşması hâlinde, adaylar öğrenim durumu,
        işyerindeki deneyim süresi ve yaş kriterleri esas alınarak belirlenir ve ilan edilir.
    </div>

    <div class="govde">
        Seçim gizli oy esasına göre yapılacak; vardiyalı çalışılan işyerlerinde tüm vardiyalardaki çalışanların oy
        kullanabilmesi sağlanacaktır. Oy kullanma ve sayım işlemleri kayıt altına alınacaktır.
    </div>

    <table class="imza">
        <tr>
            <td>İLAN EDEN<br>İşveren / İşveren Vekili</td>
            <td>İLAN TARİHİ<br>{{ $secim->ilan_tarihi?->format('d.m.Y') }}</td>
        </tr>
    </table>

    <p class="yasal">Dayanak: 6331 sayılı Kanun md.20 ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına İlişkin Tebliğ.</p>

</div>
</body>
</html>
