{{--
    Kimyasal güvenlik afişi — A4 dikey, kimyasalın kullanıldığı / depolandığı yere asılır
    (App\Support\KimyasalOzetleri::afisPdf). Ekipman afişiyle aynı dil: üstte büyük ad, ortada
    işaret (burada GHS piktogramları + sinyal kelime), altta ne yapılacağı. Renkler: GHS
    kırmızısı tehlike, ISO 7010 mavisi zorunlu KKD, ISO 7010 yeşili ilk yardım.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>{{ $baslik }}</title>
<style>
    /* Alt bilgi alt kenar boşluğunda durur: içerik 261 mm'yi geçerse yeni sayfaya taşar ve KimyasalOzetleri::afisPdf küçültüp yeniden basar. */
    @page { margin: 10mm 11mm 36mm; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; }
    table { border-collapse: collapse; width: 100%; }

    .bant { border-bottom: 2.2mm solid #E4002B; }
    .bant td { padding: 0 0 3mm; vertical-align: bottom; }
    .bant .ad { font-size: {{ $adPunto }}pt; font-weight: bold; line-height: 1.02; letter-spacing: -0.4pt; }
    .bant .alt { font-size: 10.5pt; margin-top: 1.4mm; color: #333; }
    .bant .logo { width: 34mm; text-align: right; }
    .bant .logo img { max-height: 17mm; max-width: 32mm; }

    .isaret { margin-top: {{ 4 * $k }}mm; }
    .isaret td { vertical-align: middle; }
    .sinyal { width: 42mm; padding-right: 4mm; }
    .sinyal div { font-size: 21pt; font-weight: bold; text-align: center; padding: 5mm 0; letter-spacing: 0.6pt; }
    .sinyal .tehlike { background: #E4002B; color: #fff; }
    .sinyal .uyari { background: #FFC72C; color: #111; border: 0.6mm solid #111; }
    .piktogram { display: inline-block; width: {{ $piktogramMm + 2 }}mm; text-align: center; margin-right: 2mm; vertical-align: top; }
    .piktogram img { width: {{ $piktogramMm }}mm; height: {{ $piktogramMm }}mm; }
    .piktogram span { display: block; font-size: 7.2pt; line-height: 1.15; color: #333; }

    .baslik { color: #fff; font-size: {{ 12.5 * $k }}pt; font-weight: bold; padding: {{ 1.8 * $k }}mm 3.5mm; }
    .liste td.sutun { width: 50%; vertical-align: top; }
    .liste td.ara { width: 3mm; }
    .liste ul { list-style: none; margin: 0; padding: 0; }
    .liste li { font-size: {{ 9.8 * $k }}pt; line-height: 1.25; padding: {{ 1.4 * $k }}mm 2.5mm {{ 1.4 * $k }}mm 3mm; border-bottom: 0.3mm solid #d4d8dd; }
    .tehlike .baslik { background: #E4002B; }
    .tehlike ul { border-left: 1.2mm solid #E4002B; }
    .onlem .baslik { background: #1f2937; }
    .onlem ul { border-left: 1.2mm solid #1f2937; }

    .kkd { margin-top: {{ 3.5 * $k }}mm; }
    .kkd .etiket { font-size: 10pt; font-weight: bold; color: #005387; padding-bottom: 1.4mm; }
    .kkd .rozet { display: inline-block; background: #005387; color: #fff; font-size: {{ 9.2 * $k }}pt; font-weight: bold; border-radius: 4mm; padding: 1.3mm 3.4mm; margin: 0 1.3mm 1.4mm 0; }

    .ilkyardim { margin-top: {{ 3 * $k }}mm; }
    .ilkyardim .baslik { background: #00843D; }
    .ilkyardim td.k { width: 50%; vertical-align: top; border: 0.3mm solid #b7d4c3; padding: {{ 1.8 * $k }}mm 2.6mm; font-size: {{ 9.3 * $k }}pt; line-height: 1.25; }
    .ilkyardim td.k b { display: block; color: #00602c; font-size: 9.6pt; margin-bottom: 0.6mm; }

    .ek { margin-top: {{ 3 * $k }}mm; }
    .ek td.k { width: 50%; vertical-align: top; border: 0.3mm solid #c9ced6; padding: {{ 1.8 * $k }}mm 2.6mm; font-size: {{ 9.1 * $k }}pt; line-height: 1.25; }
    .ek td.k b { display: block; font-size: 9.6pt; margin-bottom: 0.6mm; }
    .ek td.ara { width: 3mm; border: 0; }

    .dip { position: fixed; left: 0; right: 0; top: 262mm; border-top: 0.6mm solid #111; }
    .dip td { vertical-align: middle; padding-top: 2.2mm; font-size: 8.4pt; }
    .dip .acil { font-size: 14pt; font-weight: bold; }
    .dip .acil span { color: #E4002B; }
    .dip td.sag { text-align: right; color: #444; }
    .taslak { color: #9a3412; font-weight: bold; }
</style>
</head>
<body>
    <table class="bant">
        <tr>
            <td>
                <div class="ad">{{ $baslik }}</div>
                <div class="alt">Kimyasal güvenlik bilgileri{{ $u->cas_no ? ' — CAS '.$u->cas_no : '' }}{{ $u->kullanim_alani ? ' — '.$u->kullanim_alani : '' }}</div>
            </td>
            @if ($logo)<td class="logo"><img src="{{ $logo }}"></td>@endif
        </tr>
    </table>

    @if ($ghs || $ozet['sinyal_kelime'])
        <table class="isaret">
            <tr>
                @if ($ozet['sinyal_kelime'])
                    <td class="sinyal"><div class="{{ $ozet['sinyal_kelime'] === 'Tehlike' ? 'tehlike' : 'uyari' }}">{{ $ozet['sinyal_kelime'] === 'Tehlike' ? 'TEHLİKE' : 'UYARI' }}</div></td>
                @endif
                <td>
                    {{-- Piktogramlar arasında boşluk karakteri kalmasın: inline-block satırı genişletip alt satıra iterdi. --}}
                    @foreach ($ghs as $kod => $yol)<div class="piktogram"><img src="{{ $yol }}"><span>{{ \Illuminate\Support\Str::after($ghsAdlari[$kod] ?? $kod, '— ') }}</span></div>@endforeach
                </td>
            </tr>
        </table>
    @endif

    <table class="liste" style="margin-top:{{ 4 * $k }}mm">
        <tr>
            <td class="sutun tehlike">
                <div class="baslik">Tehlikeler</div>
                <ul>@forelse ($ozet['tehlikeler'] as $m)<li>{{ $m }}</li>@empty<li>Güvenlik bilgi formuna bakın.</li>@endforelse</ul>
            </td>
            <td class="ara"></td>
            <td class="sutun onlem">
                <div class="baslik">Önlemler</div>
                <ul>@forelse ($ozet['onlemler'] as $m)<li>{{ $m }}</li>@empty<li>Güvenlik bilgi formuna bakın.</li>@endforelse</ul>
            </td>
        </tr>
    </table>

    @if (filled($ozet['kkd']))
        <div class="kkd">
            <div class="etiket">Bu kimyasalla çalışırken kullanılması zorunlu koruyucu donanım</div>
            @foreach ($ozet['kkd'] as $k)<span class="rozet">{{ $k }}</span>@endforeach
        </div>
    @endif

    @if (filled(array_filter($ozet['ilk_yardim'])))
        <div class="ilkyardim">
            <div class="baslik">İlk yardım</div>
            <table>
                @foreach (array_chunk($ilkYardimBasliklari, 2, true) as $cift)
                    <tr>
                        @foreach ($cift as $anahtar => $etiket)
                            <td class="k"><b>{{ $etiket }}</b>{{ $ozet['ilk_yardim'][$anahtar] ?? '' ?: '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if (filled($ozet['yangin'] ?? null) || filled($ozet['dokulme'] ?? null))
        <table class="ek">
            <tr>
                <td class="k"><b>Yangında</b>{{ ($ozet['yangin'] ?? '') ?: '—' }}</td>
                <td class="ara"></td>
                <td class="k"><b>Dökülme / sızıntıda</b>{{ ($ozet['dokulme'] ?? '') ?: '—' }}</td>
            </tr>
        </table>
    @endif

    <table class="dip">
        <tr>
            <td>
                <div class="acil">Acil: <span>112</span> &nbsp; Zehir Danışma: <span>114</span></div>
                <div>Ayrıntılı bilgi için ürünün güvenlik bilgi formuna (SDS) bakın.</div>
            </td>
            <td class="sag">
                @if ($firma){{ $firma->unvan }}<br>@endif
                {{ $dokumanKodu }} &nbsp;
                @if ($taslak)<span class="taslak">Taslak — uzman onayı bekliyor</span>@else Tarih: {{ $tarih }}@endif
            </td>
        </tr>
    </table>
</body>
</html>
