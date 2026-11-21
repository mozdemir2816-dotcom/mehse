{{--
    Ekipman güvenlik afişi — A4 dikey, makinenin yanına asılır (App\Support\EkipmanAfisKutuphanesi::pdfIcerik).
    Renkler TS EN ISO 7010 güvenlik renklerinden: sarı uyarı bandı, yeşil güvenli davranış,
    kırmızı yasak, mavi zorunlu KKD. Makine adı uzaktan okunur; uzun ad ve çok maddede
    yazı/resim küçülür ($olcu) ki alt bilgiyle çakışmasın ve tek sayfada kalsın.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>{{ $baslik }}</title>
<style>
    @page { margin: 10mm 11mm 9mm; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; }
    table { border-collapse: collapse; width: 100%; }

    .bant { background: #FFC72C; border-bottom: 2.2mm solid #111; }
    .bant td { padding: 3.5mm 5mm 3mm; vertical-align: middle; }
    .bant .ad { font-size: {{ $olcu['ad'] }}pt; font-weight: bold; line-height: 1.02; letter-spacing: -0.4pt; }
    .bant .alt { font-size: 12pt; margin-top: 1.2mm; }
    .bant .logo { width: 34mm; text-align: right; }
    .bant .logo img { max-height: 17mm; max-width: 32mm; }

    .resim { text-align: center; padding: 4mm 0 3mm; height: {{ $olcu['resim'] }}mm; }
    .resim img { height: {{ $olcu['resim'] }}mm; max-width: 186mm; }

    .kurallar td.sutun { width: 50%; vertical-align: top; }
    .kurallar td.ara { width: 3mm; }
    .kurallar .baslik { color: #fff; font-size: 15pt; font-weight: bold; padding: 2.2mm 4mm; }
    .kurallar .yap .baslik { background: #00843D; }
    .kurallar .yapma .baslik { background: #C8102E; }
    .kurallar ul { list-style: none; margin: 0; padding: 0; }
    .kurallar li { font-size: {{ $olcu['madde'] }}pt; line-height: 1.28; padding: 1.7mm 3mm 1.7mm 9mm; border-bottom: 0.3mm solid #d4d8dd; position: relative; page-break-inside: avoid; }
    .kurallar li span.i { position: absolute; left: 2.4mm; top: 1.5mm; font-weight: bold; font-size: 12pt; }
    .kurallar .yap li span.i { color: #00843D; }
    .kurallar .yapma li span.i { color: #C8102E; }
    .kurallar .yap ul { border-left: 1.2mm solid #00843D; }
    .kurallar .yapma ul { border-left: 1.2mm solid #C8102E; }

    .kkd { margin-top: 4mm; }
    .kkd .etiket { font-size: 10.5pt; font-weight: bold; color: #005387; padding-bottom: 1.5mm; }
    .kkd .rozet { display: inline-block; background: #005387; color: #fff; font-size: 9.6pt; font-weight: bold; border-radius: 4mm; padding: 1.4mm 3.6mm; margin: 0 1.4mm 1.6mm 0; }

    .dip { position: fixed; left: 0; right: 0; top: 258mm; border-top: 0.6mm solid #111; }
    .dip td { vertical-align: middle; padding-top: 2.5mm; font-size: 8.6pt; }
    .dip td.qr { width: 27mm; }
    .dip td.qr img { width: 25mm; height: 25mm; }
    .dip .okut { font-size: 11pt; font-weight: bold; }
    .dip .kunye { color: #444; margin-top: 1mm; }
    .dip td.sag { text-align: right; color: #444; }
    .taslak { color: #9a3412; font-weight: bold; }
</style>
</head>
<body>
    <table class="bant">
        <tr>
            <td>
                <div class="ad">{{ $baslik }}</div>
                <div class="alt">Güvenli kullanım kuralları</div>
            </td>
            @if ($logo)<td class="logo"><img src="{{ $logo }}"></td>@endif
        </tr>
    </table>

    <div class="resim">
        @if ($gorsel)<img src="{{ $gorsel }}">@endif
    </div>

    <table class="kurallar">
        <tr>
            <td class="sutun yap">
                <div class="baslik">✔ Yapın</div>
                <ul>@foreach ($afis->yapin ?? [] as $m)<li><span class="i">✔</span>{{ $m }}</li>@endforeach</ul>
            </td>
            <td class="ara"></td>
            <td class="sutun yapma">
                <div class="baslik">✖ Yapmayın</div>
                <ul>@foreach ($afis->yapmayin ?? [] as $m)<li><span class="i">✖</span>{{ $m }}</li>@endforeach</ul>
            </td>
        </tr>
    </table>

    @if (filled($afis->kkd))
        <div class="kkd">
            <div class="etiket">Bu makinede kullanılması zorunlu koruyucu donanım</div>
            @foreach ($afis->kkd as $k)<span class="rozet">{{ $k }}</span>@endforeach
        </div>
    @endif

    <table class="dip">
        <tr>
            @if ($qr)
                <td class="qr"><img src="{{ $qr }}"></td>
                <td>
                    <div class="okut">Kullanmadan önce okutun</div>
                    <div>Günlük kontrol formu ve çalışma talimatı telefonunuzda açılır.</div>
                    @if ($e)<div class="kunye">{{ collect([$e->seri_no ? 'Seri no: '.$e->seri_no : null, $e->konum ? 'Konum: '.$e->konum : null])->filter()->implode('   ') }}</div>@endif
                </td>
            @else
                <td><div class="okut">Kullanmadan önce günlük kontrolü yapın</div></td>
            @endif
            <td class="sag">
                @if ($firma){{ $firma->unvan }}<br>@endif
                @if ($afis->onayBekliyorMu())<span class="taslak">Taslak — uzman onayı bekliyor</span>@else Afiş tarihi: {{ ($afis->onaylandi_at ?? $afis->updated_at)?->format('d.m.Y') }}@endif
            </td>
        </tr>
    </table>
</body>
</html>
