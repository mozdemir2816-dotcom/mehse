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
    .hitap { font-weight: bold; margin-bottom: 10px; }
    .govde { font-size: 11px; line-height: 1.6; text-align: justify; margin-bottom: 12px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 34%; }
    .bos { display: inline-block; min-width: 220px; border-bottom: 1px solid #111; }
    .imza { margin-top: 60px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
@php $bosMi = $bos ?? false; @endphp
<div class="sayfa">

    <div class="ust">
        <div class="sol">
            <div class="firma">@if ($bosMi)<span class="bos" style="min-width:260px">&nbsp;</span>@else{{ $firma?->unvan }}@endif</div>
            <h1>ÇALIŞAN TEMSİLCİSİ ADAY BAŞVURU DİLEKÇESİ</h1>
        </div>
        <div class="sag">
            Belge No: {{ $secim->dokuman_no }}<br>
            Düzenleme Tarihi: {{ $secim->ilan_tarihi?->format('d.m.Y') }}
        </div>
    </div>

    <div class="adres">İşyeri Adresi: @if ($bosMi)<span class="bos" style="min-width:320px">&nbsp;</span>@else{{ $firma?->adres }}@endif</div>

    <div class="hitap">Sayın İşveren / İşveren Vekili</div>

    <div class="govde">
        @if ($bosMi)<span class="bos" style="min-width:220px">&nbsp;</span>@else{{ $firma?->unvan }}@endif işyerinde çalışan temsilcisi seçimi yapılacağını öğrendim. 6331 sayılı İş Sağlığı ve
        Güvenliği Kanununun 20 nci maddesi ile ilgili Tebliğ hükümleri kapsamındaki adaylık şartlarını taşıdığımı
        beyan eder; çalışan temsilcisi adaylığımın kabul edilmesini arz ederim.
    </div>

    <div class="govde">
        Çalışan temsilciliği görevini yürütmem hâlinde; iş sağlığı ve güvenliği çalışmalarına katılacağımı,
        çalışanların görüşlerini temsil edeceğimi, işyerine ve çalışanlara ait öğrendiğim özel bilgileri gizli
        tutacağımı kabul ve taahhüt ederim.
    </div>

    <table class="kunye">
        <tr><td>Adayın Adı Soyadı</td><td>&nbsp;</td></tr>
        <tr><td>Unvanı / Görevi</td><td>&nbsp;</td></tr>
        <tr><td>İşyeri</td><td>@if ($bosMi)&nbsp;@else{{ $firma?->unvan }}@endif</td></tr>
        <tr><td>Başvuru Tarihi</td><td>&nbsp;</td></tr>
    </table>

    <table class="imza">
        <tr>
            <td>ADAY ÇALIŞAN<br><br>İmza</td>
            <td>TESLİM ALAN<br>İşveren / İşveren Vekili<br>İmza</td>
        </tr>
    </table>

    <p class="yasal">Dayanak: 6331 sayılı Kanun md.20 ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına İlişkin Tebliğ.</p>

</div>
</body>
</html>
