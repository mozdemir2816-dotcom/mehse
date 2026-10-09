<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{--
    İSG Kurulu Toplantıya Çağrı Formu — Yönetmelik Md.9: gündem, yer, gün ve
    saat toplantıdan en az 48 saat önce üyelere bildirilir. Tutanakla aynı
    görsel düzen (pdf/kurul-toplantisi). Üretici: App\Support\KurulCagriUretici::pdf()
--}}
<style>
    @page { margin: 26px 30px 40px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #1e293b; font-size: 10px; }
    .ust { width: 100%; border-collapse: collapse; border: 1px solid #94a3b8; margin-bottom: 10px; }
    .ust td { vertical-align: middle; padding: 8px 10px; }
    .ust .firma { width: 26%; text-align: center; border-right: 1px solid #cbd5e1; }
    .ust .firma img { max-width: 170px; max-height: 62px; }
    .ust .baslik { text-align: center; background: #eff6ff; font-size: 15px; font-weight: bold; color: #1e3a5f; line-height: 1.3; border-right: 1px solid #cbd5e1; }
    .ust .bilgi { width: 27%; font-size: 9px; padding: 4px 8px; }
    .ust .bilgi table { width: 100%; border-collapse: collapse; }
    .ust .bilgi td { padding: 2px 0; border: none; }
    .ust .bilgi td.e { font-weight: bold; color: #475569; width: 48%; }
    table.kunye { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9.5px; }
    table.kunye td { border: 1px solid #cbd5e1; padding: 5px 7px; }
    table.kunye td.e { background: #f1f5f9; font-weight: bold; color: #475569; width: 16%; }
    h2 { font-size: 12px; color: #1e3a5f; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #2563eb; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.liste th { background: #dbeafe; color: #1e3a5f; text-align: left; padding: 5px 6px; border: 1px solid #cbd5e1; }
    table.liste td { padding: 5px 6px; border: 1px solid #cbd5e1; vertical-align: top; }
    .metin { font-size: 10px; line-height: 1.5; text-align: justify; margin: 0 0 6px; }
    ul.notlar { margin: 0; padding-left: 14px; font-size: 8.5px; color: #334155; }
    ul.notlar li { margin-bottom: 3px; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 14px; }
    table.imza td { width: 50%; text-align: center; vertical-align: top; padding: 4px; }
    .ortala { text-align: center; }
    .soluk { color: #94a3b8; }
    .altbilgi { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 4px; }
</style>
</head>
<body>
<div class="altbilgi">
    İSG Kurulu Toplantıya Çağrı Formu · {{ $firma?->unvan }} · Toplantı No: {{ $toplanti->toplanti_no ?: '—' }} · Çağrı: {{ $cagri_tarihi }}
</div>

<table class="ust">
    <tr>
        <td class="firma">@if ($logo)<img src="{{ $logo }}">@endif</td>
        <td class="baslik">İŞ SAĞLIĞI VE GÜVENLİĞİ KURULU<br>TOPLANTIYA ÇAĞRI FORMU</td>
        <td class="bilgi">
            <table>
                @foreach ($bilgi as $e => $d)
                    <tr><td class="e">{{ $e }}</td><td>{{ $d }}</td></tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>

<table class="kunye">
    @foreach ($kunye as $s)
        <tr>
            <td class="e">{{ $s[0] }}</td>
            @if ($s[2] === null)
                <td colspan="3">{{ $s[1] }}</td>
            @else
                <td>{{ $s[1] }}</td>
                <td class="e">{{ $s[2] }}</td><td>{{ $s[3] }}</td>
            @endif
        </tr>
    @endforeach
</table>

<p class="metin"><strong>Sayın Kurul Üyesi,</strong></p>
<p class="metin">{{ $metin }}</p>
@if ($olaganustu && $olaganustu_nedeni !== '')
    <p class="metin"><strong>Olağanüstü toplantı nedeni:</strong> {{ $olaganustu_nedeni }}</p>
@endif

<h2>Gündem</h2>
<table class="liste">
    @foreach ($gundem as $i => $madde)
        <tr><td style="width:4%" class="ortala">{{ $i + 1 }}</td><td>{{ $madde }}</td></tr>
    @endforeach
    <tr><td style="width:4%" class="ortala">{{ count($gundem) + 1 }}</td><td style="color:#475569">Dilek ve temenniler</td></tr>
</table>

@if ($onceki_kararlar)
    <h2>Önceki Toplantıdan Takipteki Kararlar</h2>
    <table class="liste">
        <tr>
            <th style="width:4%">#</th>
            <th>Karar</th>
            <th style="width:17%">Sorumlu</th>
            <th style="width:11%">Termin</th>
            <th style="width:13%">Durum</th>
        </tr>
        @foreach ($onceki_kararlar as $i => $k)
            <tr>
                <td class="ortala">{{ $i + 1 }}</td>
                <td>{{ $k['karar_metni'] }}</td>
                <td>{{ $k['sorumlu'] }}</td>
                <td style="white-space:nowrap">{{ $k['termin'] }}</td>
                <td>{{ $k['durum'] }}</td>
            </tr>
        @endforeach
    </table>
@endif

<h2>Bilgilendirme (Yönetmelik Md.9)</h2>
<ul class="notlar">
    @foreach ($notlar as $not)
        <li>{{ $not }}</li>
    @endforeach
</ul>

<table class="imza" style="page-break-inside:avoid">
    <tr>
        <td>
            <strong style="color:#1e3a5f">Kurul Başkanı</strong><br>
            <span style="font-size:8px;color:#64748b">İşveren / İşveren Vekili</span><br><br>
            {{ $baskan ?: '……………………………' }}<br><br><br>
            <span style="font-size:8px;color:#94a3b8">İmza</span>
        </td>
        <td>
            <strong style="color:#1e3a5f">Kurul Sekreteri</strong><br>
            <span style="font-size:8px;color:#64748b">İş Güvenliği Uzmanı</span><br><br>
            {{ $sekreter ?: '……………………………' }}<br><br><br>
            <span style="font-size:8px;color:#94a3b8">İmza</span>
        </td>
    </tr>
</table>

{{-- Tebliğ-tebellüğ föyü bölünmez: sığmazsa başlığıyla birlikte sonraki sayfaya geçer. --}}
<div style="page-break-inside:avoid">
<h2>Tebliğ – Tebellüğ</h2>
<div style="font-size:8.5px;color:#475569;margin-bottom:4px">Toplantı çağrısını ve gündemini tebellüğ ettim.</div>
<table class="liste">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:27%">Ad Soyad</th>
        <th style="width:33%">Kuruldaki Görevi</th>
        <th style="width:14%">Tebliğ Tarihi</th>
        <th>İmza</th>
    </tr>
    @forelse ($davetliler as $i => $k)
        <tr style="page-break-inside:avoid">
            <td class="ortala" style="height:30px;vertical-align:middle">{{ $i + 1 }}</td>
            <td style="vertical-align:middle">{{ $k['ad_soyad'] }}</td>
            <td style="vertical-align:middle">{{ $k['kurul_gorevi'] }}</td>
            <td style="vertical-align:middle" class="soluk">…../…../……..</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="5" class="soluk">Kurul üyesi eklenmedi.</td></tr>
    @endforelse
</table>
</div>

</body>
</html>
