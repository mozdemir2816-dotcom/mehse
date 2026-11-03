@php
    /*
     * Yıldız Grup Eğitim Sertifikası — Excel şablonunun PDF karşılığı. Düzen Excel hücre geometrisinden
     * (App\Support\ExcelSayfaCizici) çıkarılır: çizgi/dolgular tek SVG, metin ve görseller mutlak konumlu kutular.
     */
    $yazi = fn (string $ad) => str_replace('\\', '/', resource_path('fonts/'.$ad));
    $kagitW = $kagit[0];
    $kagitH = $kagit[1] - 1;   // tam sayfa yüksekliği boş sayfa üretmesin
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        @font-face { font-family: 'Liberation Serif'; font-weight: normal; font-style: normal; src: url('{{ $yazi('LiberationSerif-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Liberation Serif'; font-weight: bold; font-style: normal; src: url('{{ $yazi('LiberationSerif-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Liberation Serif'; font-weight: normal; font-style: italic; src: url('{{ $yazi('LiberationSerif-Italic.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Liberation Serif'; font-weight: bold; font-style: italic; src: url('{{ $yazi('LiberationSerif-BoldItalic.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Carlito'; font-weight: normal; font-style: normal; src: url('{{ $yazi('Carlito-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Carlito'; font-weight: bold; font-style: normal; src: url('{{ $yazi('Carlito-Bold.ttf') }}') format('truetype'); }
        @page { margin: 0; }
        body { margin: 0; padding: 0; }
        .sf { position: relative; width: {{ $kagitW }}pt; height: {{ $kagitH }}pt; overflow: hidden; }
        .son { page-break-after: avoid; }
        .ara { page-break-after: always; }
        .k { position: absolute; }
        .k table { border-collapse: collapse; table-layout: fixed; }
        .k td { padding: 0; line-height: 1.1; }
    </style>
</head>
<body>
{{--
    $mod:  tam     → her katılımcı için tüm sayfalar (tek katılımcı)
           statik  → katılımcıya özgü alanlar (D8/D9) HARİÇ sabit sayfalar, bir kez (çok katılımcıda birleştirilir)
           dinamik → yalnız katılımcıya özgü alanlar (D8/D9), katılımcı başına 1 şeffaf sayfa (sabit sayfanın üstüne biner)
--}}
@php
    $mod = $mod ?? 'tam';
    $dinamikKutular = ['D8', 'D9'];
    $sayfalar = $mod === 'dinamik' ? array_slice($model['sayfalar'], 0, 1) : $model['sayfalar'];
    $sira = 0;
    $toplam = count($katilimcilar) * count($sayfalar);
@endphp
@foreach ($katilimcilar as $k)
    @foreach ($sayfalar as $sf)
        @php
            $sira++;
            $ox = $ortalaYatay ? ($kagitW - $sf['w']) / 2 : $kenar['sol'];
            $oy = $ortalaDikey ? ($kagitH - $sf['h']) / 2 : $kenar['ust'];
        @endphp
        <div class="sf {{ $sira < $toplam ? 'ara' : 'son' }}">
            <div style="position:absolute;left:{{ round($ox, 2) }}pt;top:{{ round($oy, 2) }}pt;width:{{ round($sf['w'], 2) }}pt;height:{{ round($sf['h'], 2) }}pt">
                @if ($mod !== 'dinamik')
                    <img src="{{ $sf['svg'] }}" style="position:absolute;left:0;top:0;width:{{ round($sf['w'], 2) }}pt;height:{{ round($sf['h'], 2) }}pt">
                @endif

                @foreach ($sf['kutular'] as $kt)
                    @continue(($mod === 'dinamik' && ! in_array($kt['anahtar'], $dinamikKutular, true)) || ($mod === 'statik' && in_array($kt['anahtar'], $dinamikKutular, true)))
                    @php
                        $html = str_replace(['[[AD]]', '[[GOREV]]'], [e($k['ad']), e($k['gorev'])], $kt['html']);
                        $sol = 1.2 + $kt['girinti'];
                    @endphp
                    <div class="k" style="left:{{ round($kt['x'], 2) }}pt;top:{{ round($kt['y'], 2) }}pt;width:{{ round($kt['w'], 2) }}pt;height:{{ round($kt['h'], 2) }}pt">
                        <table style="width:{{ round($kt['w'], 2) }}pt;height:{{ round($kt['h'], 2) }}pt"><tr>
                            <td style="font-size:{{ round($kt['boyut'], 2) }}pt;line-height:{{ round($kt['boyut'] * 1.12, 2) }}pt;vertical-align:{{ $kt['dikey'] }};text-align:{{ $kt['yatay'] }};padding:0 1.2pt 0 {{ $sol }}pt;white-space:{{ $kt['sar'] ? 'normal' : 'nowrap' }}">{!! $html !!}</td>
                        </tr></table>
                    </div>
                @endforeach

                @foreach ($mod === 'dinamik' ? [] : $sf['resimler'] as $rs)
                    <img src="{{ $rs['dosya'] }}" style="position:absolute;left:{{ round($rs['x'], 2) }}pt;top:{{ round($rs['y'], 2) }}pt;width:{{ round($rs['w'], 2) }}pt;height:{{ round($rs['h'], 2) }}pt">
                @endforeach
            </div>
        </div>
    @endforeach
@endforeach
</body>
</html>
