<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 14px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .izgara { width: 100%; border-collapse: collapse; }
    .izgara td { width: 50%; vertical-align: top; padding: 0; }
    .pusula { border: 1px dashed #999; padding: 8px 10px; margin: 4px; }
    .baslik { display: table; width: 100%; margin-bottom: 6px; }
    .baslik .sol { display: table-cell; font-weight: bold; }
    .baslik .sag { display: table-cell; text-align: right; font-size: 8.5px; }
    .aday { display: table; width: 100%; margin: 3px 0; }
    .aday .ad { display: table-cell; }
    .aday .daire { display: table-cell; width: 16px; text-align: right; }
    .daire span { display: inline-block; width: 12px; height: 12px; border: 1.3px solid #1e3a8a; border-radius: 50%; }
    .bosluk { height: 8px; }
</style>
</head>
<body>
@php
    $adaylar = $secim->adaylarListesi();
    $pusulaSayisi = 16;
@endphp

<table class="izgara">
    @for ($satir = 0; $satir < $pusulaSayisi / 2; $satir++)
        <tr>
            @for ($sutun = 0; $sutun < 2; $sutun++)
                <td>
                    <div class="pusula">
                        <div class="baslik">
                            <div class="sol">OY PUSULASI</div>
                            <div class="sag">EN FAZLA {{ $secim->zorunlu_temsilci_sayisi }} TERCİH</div>
                        </div>
                        @forelse ($adaylar as $i => $aday)
                            <div class="aday">
                                <div class="ad">{{ $i + 1 }}. {{ $aday['ad_soyad'] }}</div>
                                <div class="daire"><span></span></div>
                            </div>
                        @empty
                            <div class="aday"><div class="ad">Aday eklenmedi.</div></div>
                        @endforelse
                        <div class="bosluk"></div>
                    </div>
                </td>
            @endfor
        </tr>
    @endfor
</table>

</body>
</html>
