{{--
    Matbu Evrak Paketi kapağı (App\Support\MatbuEvrakPaketi): firma künyesi, içindekiler (paket sayfa
    numarasıyla) ve tüm belgeler için tek ortak teslim / tebliğ tutanağı. Ekipman belgeleriyle aynı stil.
--}}
<!doctype html>
<html lang="tr"><head><meta charset="utf-8">@include('pdf.ekipman._stil')
<style>
    .ozet { margin: 8px 0 2px; font-size: 9.4px; }
    table.icindekiler { margin-top: 4px; }
    table.icindekiler th { background: #e2e8f0; border: 1px solid #94a3b8; padding: 3.5px 5px; font-size: 8.2px; text-align: left; }
    table.icindekiler td { border: 1px solid #cbd5e1; padding: 3px 5px; font-size: 8.4px; vertical-align: top; }
    table.icindekiler td.konu { font-weight: bold; width: 34%; }
    table.icindekiler td.grup { width: 11%; color: #475569; }
    table.icindekiler td.no { width: 9%; text-align: right; }
    table.icindekiler tr { page-break-inside: avoid; }
    .metin { font-size: 9.2px; line-height: 1.6; text-align: justify; margin: 8px 0; }
    .tutanak { page-break-inside: avoid; margin-top: 10px; }
    table.imza td { height: 70px; }
</style>
</head>
<body>
<table class="antet">
    <tr>
        @if ($logo)<td class="logo"><img src="{{ $logo }}"></td>@endif
        <td class="baslik">
            <h1>İSG EKİPMAN VE KİMYASAL EVRAK PAKETİ</h1>
            <div>{{ $firma->unvan }}</div>
            <div>{{ collect([$firma->sgk_sicil_no ? 'SGK Sicil: '.$firma->sgk_sicil_no : null, trim(($firma->adres ?? '').' '.($firma->ilce ?? '').($firma->il ? ' / '.$firma->il : ''))])->filter()->implode(' · ') }}</div>
        </td>
    </tr>
</table>
<table class="kunye">
    <tr><td class="e">Hazırlanma tarihi</td><td>{{ $tarih }}</td><td class="e">Hazırlayan</td><td>{{ $tebligEden->ad }}, İş Güvenliği Uzmanı</td></tr>
    <tr><td class="e">Kapsam</td><td>{{ $ekipmanSayisi }} iş ekipmanı, {{ $kimyasalSayisi }} kimyasal</td><td class="e">Toplam sayfa</td><td>{{ $toplamSayfa }}</td></tr>
</table>

<p class="ozet">Bu paket, işyerinde kullanılan iş ekipmanları ve kimyasallar için çalışanlara duyurulması,
    makine / kullanım yerine asılması ve takip edilmesi gereken belgeleri içerir. Aylık günlük kontrol
    çizelgeleri {{ $ayAdi }} ayı içindir; sonraki aylar için aynı çizelge çoğaltılarak kullanılır.</p>

<h2>İçindekiler</h2>
<table class="icindekiler">
    <thead><tr><th>Tür</th><th>Ekipman / kimyasal</th><th>Belge</th><th style="text-align:right">Sayfa</th></tr></thead>
    <tbody>
        @foreach ($icindekiler as $satirlar)
            @foreach ($satirlar as $i => $s)
                <tr>
                    @if ($i === 0)
                        <td class="grup" rowspan="{{ count($satirlar) }}">{{ $s['grup'] }}</td>
                        <td class="konu" rowspan="{{ count($satirlar) }}">{{ $s['konu'] }}</td>
                    @endif
                    <td>{{ $s['belge'] }}</td>
                    <td class="no">{{ $s['sayfa'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

<div class="tutanak">
    <h2>Teslim ve tebliğ tutanağı</h2>
    <p class="metin">Yukarıda listelenen iş ekipmanlarına ait çalışma ve güvenlik talimatları, günlük kontrol
        çizelgeleri ve güvenlik afişleri ile kimyasallara ait MSDS özeti afişleri hazırlanarak işverene / işveren vekiline teslim edilmiştir. 6331 sayılı İş Sağlığı ve Güvenliği
        Kanunu, İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği ve Kimyasal Maddelerle
        Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik kapsamında; talimatların ilgili çalışanlara
        imza karşılığı duyurulması, afişlerin makinelerin yanına ve kimyasalların kullanıldığı alanlara asılması, kimyasalların asıl güvenlik bilgi formlarının (MSDS) çalışanların erişebileceği yerde tutulması, günlük
        kontrollerin her kullanım öncesi yapılıp kayıt altına alınması ve uygunsuzluk görülen ekipmanın giderilene
        kadar kullanılmaması işverenin sorumluluğundadır.</p>

    <table class="imza">
        <tr><th>TESLİM EDEN<br>İş Güvenliği Uzmanı</th><th>TESLİM ALAN<br>İşveren / İşveren Vekili</th></tr>
        <tr>
            <td>{{ $tebligEden->ad }}<br>Tarih: {{ $tarih }}<br>
                @if ($tebligEden->imza_gorseli && is_file($p = storage_path('app/public/'.$tebligEden->imza_gorseli)))<img src="{{ $p }}">@endif<br>İmza:</td>
            <td>{{ $firma->isveren_vekili ?: $firma->isveren_ad }}<br>Tarih: ....../....../..........<br><br>Kaşe / İmza:</td>
        </tr>
    </table>
</div>
</body></html>
