@php
    /** Yıldız Grup Eğitim Sertifikası — Excel şablonunun PDF karşılığı (sayfa 1: belge, sayfa 2: eğitim konuları). */
    $gorsel = fn (?string $yol) => $yol && is_file(storage_path('app/public/'.$yol)) ? storage_path('app/public/'.$yol) : null;
    $osgbLogo = resource_path('belge/logolar/yildiz-grup-osgb.png');
    $firmaLogo = $gorsel($firma?->logo);
    $secili = fn (bool $b) => $b ? 'font-weight:bold' : '';
    $isaret = fn (bool $b) => $b ? 'X' : '';
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 9mm; }
        body { font-family: "DejaVu Serif", serif; font-size: 10.5pt; color: #000; margin: 0; }
        .sayfa { border: 2.5px solid #ff6600; padding: 4mm 8mm; height: 257mm; overflow: hidden; page-break-after: always; }
        .sayfa:last-child { page-break-after: auto; }
        table { border-collapse: collapse; width: 100%; }
        .logolar td { vertical-align: top; }
        .baslik { text-align: center; font-weight: bold; font-size: 12.5pt; margin: 3mm 0 2.5mm; }
        .b { font-weight: bold; }
        .alan { font-weight: bold; font-size: 11pt; margin: 2mm 0; }
        .aciklama { font-weight: bold; font-size: 11pt; line-height: 1.45; margin: 3mm 0 4mm; text-indent: 9mm; }
        .bilgi td { border: 0.8px solid #000; padding: 1.5mm 2.5mm; vertical-align: middle; font-size: 10.5pt; }
        .bilgi td.e { font-weight: bold; width: 34%; }
        .x { text-align: center; width: 9mm; font-weight: bold; font-size: 13pt; }
        .imza td { vertical-align: bottom; text-align: center; padding: 1mm 2mm; }
        .imza .kase { height: 26mm; }
        .imza img { max-height: 24mm; max-width: 52mm; }
        .alt td { padding: 1.6mm 0; font-weight: bold; font-size: 10.5pt; vertical-align: bottom; }
        .cizgi { border-bottom: 0.8px dotted #000; }
        .konu td { border: 0.8px solid #000; padding: 0.85mm 2mm; font-size: 9.2pt; vertical-align: top; }
        .konu th { border: 0.8px solid #000; padding: 1.2mm 2mm; font-size: 10.5pt; text-align: left; }
        .konu td.s { width: 22mm; text-align: center; white-space: nowrap; }
        .konu td.kat { font-weight: bold; font-family: "DejaVu Sans", sans-serif; font-size: 8.6pt; background: #f3f4f6; }
        .konu.sik td { padding: 0.35mm 2mm; font-size: 8.4pt; }
        .konu.sik td.kat { font-size: 8pt; }
        .harf { font-weight: bold; }
    </style>
</head>
<body>
@foreach ($katilimcilar as $k)
    {{-- SAYFA 1: BELGE --}}
    <div class="sayfa">
        <table class="logolar"><tr>
            <td style="text-align:left">@if (is_file($osgbLogo))<img src="{{ $osgbLogo }}" style="height:13mm">@endif</td>
            <td style="text-align:right">@if ($firmaLogo)<img src="{{ $firmaLogo }}" style="height:13mm;max-width:60mm">@endif</td>
        </tr></table>

        <div class="baslik">TEMEL EĞİTİM BELGESİ</div>
        <div class="alan" style="text-indent:5mm">İşbu belge,</div>
        <div class="alan">KATILIMCININ ADI : {{ $k['ad'] }}</div>
        <div class="alan">GÖREVİ : {{ $k['gorev'] }}</div>

        <div class="aciklama">Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik kapsamında işyerinde görevli iş güvenliği uzmanı ve işyeri hekimi tarafından {{ $tarihIfadesi }} gerçekleştirilen temel eğitim sonunda düzenlenmiştir.</div>

        <table class="bilgi">
            <tr><td class="e">Belge Düzenlenme Tarihi:</td><td colspan="2">{{ $belgeTarihi }}</td></tr>
            <tr><td class="e">Eğitimin Süresi:</td><td colspan="2">{{ $sure }}</td></tr>
            <tr><td class="e" rowspan="2">Eğitimin Türü:</td><td style="{{ $secili($tur === 'ilk_defa') }}">İlk Defa Verilen Temel Eğitim</td><td class="x">{{ $isaret($tur === 'ilk_defa') }}</td></tr>
            <tr><td style="{{ $secili($tur === 'tekrar') }}">Tekrar Verilen Temel Eğitim</td><td class="x">{{ $isaret($tur === 'tekrar') }}</td></tr>
            <tr><td class="e" rowspan="2">Eğitimin Şekli:</td><td style="{{ $secili($sekil === 'uzaktan') }}">Uzaktan</td><td class="x">{{ $isaret($sekil === 'uzaktan') }}</td></tr>
            <tr><td style="{{ $secili($sekil === 'yuz_yuze') }}">Yüz Yüze</td><td class="x">{{ $isaret($sekil === 'yuz_yuze') }}</td></tr>
        </table>

        <table class="bilgi" style="margin-top:3mm">
            <tr><td class="e" rowspan="2">Eğiticilerin Adı Soyadı ve Ünvanı:</td><td style="text-align:center;font-weight:bold">İş Güvenliği Uzmanı</td><td style="text-align:center;font-weight:bold">İşyeri Hekimi</td></tr>
            <tr>
                <td class="kase" style="height:20mm;text-align:center">@if ($imzali && $iguGorsel)<img src="{{ $iguGorsel }}" style="max-height:19mm;max-width:50mm">@endif</td>
                <td class="kase" style="height:20mm;text-align:center">@if ($imzali && $hekimGorsel)<img src="{{ $hekimGorsel }}" style="max-height:19mm;max-width:50mm">@endif</td>
            </tr>
            <tr><td class="e">Eğiticilerin İmzası:</td><td style="height:7mm"></td><td></td></tr>
        </table>

        <table class="alt" style="margin-top:3mm">
            <tr><td style="width:62mm">Çalışanın İşyerinin Ünvanı:</td><td class="cizgi">&nbsp;</td></tr>
            <tr><td>İşverenin / İşveren Vekili Adı Soyadı:</td><td class="cizgi">&nbsp;</td></tr>
            <tr><td>İşverenin / İşveren Vekilinin İmzası:</td><td class="cizgi" style="height:9mm">&nbsp;</td></tr>
        </table>
    </div>

    {{-- SAYFA 2: EĞİTİM KONULARI --}}
    <div class="sayfa">
        @php $toplamMadde = collect($kategoriler)->sum(fn ($kat) => count($kat['maddeler'])); @endphp
        <table class="konu {{ $toplamMadde > 27 ? 'sik' : '' }}">
            <tr><th>EĞİTİM KONULARI</th><th style="text-align:center;width:22mm">SÜRE</th></tr>
            @foreach ($kategoriler as $kat)
                <tr><td class="kat" colspan="2">{{ $kat['baslik'] }}</td></tr>
                @foreach ($kat['maddeler'] as $i => $m)
                    <tr>
                        <td><span class="harf">{{ $harfler[$i] ?? ($i + 1) }})</span>{{ $m['madde'] }}</td>
                        <td class="s">{{ $m['dakika'] }} Dk.</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
