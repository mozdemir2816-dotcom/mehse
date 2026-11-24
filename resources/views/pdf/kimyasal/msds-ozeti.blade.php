{{--
    MSDS (GBF) özeti — A4 dikey, tek sayfa (App\Support\KimyasalOzetleri::ozetPdf). Ekipman belgeleriyle
    aynı antet ve stil (pdf.ekipman._stil). Bölümler SDS'nin çalışanı ilgilendiren başlıklarını SDS
    sırasıyla izler; asıl form yerine geçmez, dipnot bunu söyler.
--}}
<!doctype html>
<html lang="tr"><head><meta charset="utf-8">@include('pdf.ekipman._stil')
<style>
    table.ghs td { border: 1px solid #94a3b8; padding: 3px 5px; vertical-align: middle; font-size: 8.2px; }
    table.ghs td.p { width: 1%; white-space: nowrap; }
    table.ghs td.p img { width: 46px; height: 46px; margin-right: 2px; }
    .sinyal { font-size: 13px; font-weight: bold; padding: 3px 8px; display: inline-block; }
    .sinyal.tehlike { background: #E4002B; color: #fff; }
    .sinyal.uyari { background: #FFC72C; color: #111; border: 1px solid #111; }
    table.bolum td { border: 1px solid #94a3b8; padding: {{ 3.5 * $k }}px 6px; vertical-align: top; font-size: {{ 8.6 * $k }}px; line-height: 1.4; }
    body { font-size: {{ 9 * $k }}px; }
    h2 { margin: {{ 8 * $k }}px 0 {{ 4 * $k }}px; }
    li { margin: {{ 1.5 * $k }}px 0; }
    table.bolum td.e { background: #f1f5f9; font-weight: bold; width: 22%; }
    .taslak { color: #9a3412; font-weight: bold; }
    table.imza td { height: 62px; }
</style>
</head>
<body>
<table class="antet">
    <tr>
        @if ($logo && is_file((string) $logo))<td class="logo"><img src="{{ $logo }}"></td>@endif
        <td class="baslik">
            <h1>KİMYASAL GÜVENLİK BİLGİ FORMU (GBF / MSDS) ÖZETİ</h1>
            <div>{{ $firma?->unvan }}</div>
            <div>{{ collect([$firma?->sgk_sicil_no ? 'SGK Sicil: '.$firma->sgk_sicil_no : null, trim(($firma?->adres ?? '').' '.($firma?->ilce ?? '').($firma?->il ? ' / '.$firma->il : ''))])->filter()->implode(' · ') }}</div>
        </td>
    </tr>
</table>
<table class="kunye">
    <tr><td class="e">Ürün adı</td><td>{{ $u->urun_adi }}</td><td class="e">Doküman kodu</td><td>{{ $dokumanKodu }}</td></tr>
    <tr><td class="e">CAS no</td><td>{{ $u->cas_no ?: '—' }}</td><td class="e">Tarih</td><td>{{ $tarih }}</td></tr>
    <tr><td class="e">Üretici / tedarikçi</td><td>{{ $u->tedarikci ?: '—' }}</td><td class="e">Kullanım alanı</td><td>{{ $u->kullanim_alani ?: '—' }}</td></tr>
    <tr><td class="e">Fiziksel özellik</td><td colspan="3">{{ ($ozet['fiziksel'] ?? '') ?: (($u->fiziksel_hal ? config('isg.kimyasal.fiziksel_hal.'.$u->fiziksel_hal) : null) ?: '—') }}</td></tr>
</table>

<h2>1. Tehlike tanımı</h2>
<table class="ghs">
    <tr>
        @if ($ghs)
            <td class="p">@foreach ($ghs as $yol)<img src="{{ $yol }}">@endforeach</td>
        @endif
        <td>
            @if ($ozet['sinyal_kelime'])<span class="sinyal {{ $ozet['sinyal_kelime'] === 'Tehlike' ? 'tehlike' : 'uyari' }}">{{ $ozet['sinyal_kelime'] === 'Tehlike' ? 'TEHLİKE' : 'UYARI' }}</span><br>@endif
            @if ($ozet['tehlikeler'])<ul style="margin-top:3px">@foreach ($ozet['tehlikeler'] as $m)<li>{{ $m }}</li>@endforeach</ul>@else Tehlike ifadesi girilmemiş.@endif
        </td>
    </tr>
</table>

@if ($ozet['onlemler'])
    <h2>2. Önlem ifadeleri</h2>
    <ul>@foreach ($ozet['onlemler'] as $m)<li>{{ $m }}</li>@endforeach</ul>
@endif

<h2>3. İlk yardım</h2>
<table class="bolum">
    @foreach ($ilkYardimBasliklari as $anahtar => $etiket)
        <tr><td class="e">{{ $etiket }}</td><td>{{ ($ozet['ilk_yardim'][$anahtar] ?? '') ?: '—' }}</td></tr>
    @endforeach
</table>

<h2>4. Yangın, dökülme ve depolama</h2>
<table class="bolum">
    <tr><td class="e">Yangınla mücadele</td><td>{{ ($ozet['yangin'] ?? '') ?: '—' }}</td></tr>
    <tr><td class="e">Dökülme / sızıntı</td><td>{{ ($ozet['dokulme'] ?? '') ?: '—' }}</td></tr>
    <tr><td class="e">Elleçleme ve depolama</td><td>{{ ($ozet['depolama'] ?? '') ?: ($u->depolama ?: '—') }}</td></tr>
</table>

<h2>5. Maruziyet kontrolü ve kişisel korunma</h2>
<table class="bolum">
    <tr><td class="e">Maruziyet / havalandırma</td><td>{{ ($ozet['maruziyet'] ?? '') ?: '—' }}</td></tr>
    <tr><td class="e">Zorunlu KKD</td><td>{{ $ozet['kkd'] ? implode(', ', $ozet['kkd']) : '—' }}</td></tr>
    <tr><td class="e">Atık bertarafı</td><td>{{ ($ozet['bertaraf'] ?? '') ?: '—' }}</td></tr>
</table>

<div class="uyari">Acil durumda 112'yi, zehirlenme şüphesinde Ulusal Zehir Danışma Merkezi 114'ü arayın. Doktora giderken bu özeti ve ürün ambalajını götürün.</div>

<table class="imza">
    <tr><th>HAZIRLAYAN<br>İş Güvenliği Uzmanı</th><th>ONAYLAYAN<br>İşveren / İşveren Vekili</th></tr>
    <tr>
        <td>{{ $firma ? \App\Support\TalimatUretici::tebligEden($firma)->ad : '' }}<br>Tarih: {{ $tarih }}<br>İmza:</td>
        <td>{{ $firma?->isveren_vekili ?: $firma?->isveren_ad }}<br>Tarih: ....../....../..........<br>Kaşe / İmza:</td>
    </tr>
</table>

<div class="dipnot">
    Bu özet, ürünün tedarikçisinden alınan Güvenlik Bilgi Formu'nun (KKDİK Ek-II) yerine geçmez; çalışanların hızlı başvurusu içindir.
    Asıl form çalışanların erişebileceği yerde tutulmalıdır.
    @if ($taslak)<br><span class="taslak">Taslak — yapay zekâ ile hazırlandı, iş güvenliği uzmanı onayı bekliyor.</span>@endif
</div>
</body></html>
