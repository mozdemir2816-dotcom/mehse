@php
    /** Firma seçiliyse "Hazırlayan" imza satırına firmanın İSG Uzmanı + kaşesi basılır. */
    $uzman = $uzman ?? $firma?->igu;
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9px; }
    .sayfa { padding: 20px 24px; }
    .ust { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .ust td { vertical-align: middle; }
    .ust .logo { width: 90px; }
    .ust .logo img { max-width: 88px; max-height: 48px; }
    h1 { font-size: 13px; margin: 0; text-align: center; }
    .kunye { text-align: center; font-size: 8.5px; color: #333; margin-top: 3px; }
    .kapsam { font-size: 8px; color: #444; margin: 4px 0 8px; text-align: justify; }
    table.jsa { width: 100%; border-collapse: collapse; }
    table.jsa th, table.jsa td { border: 1px solid #888; padding: 3px 4px; vertical-align: top; text-align: left; }
    table.jsa th { background: #eee; font-size: 8.5px; }
    table.jsa td { font-size: 8px; }
    .no { width: 22px; text-align: center; }
    .risk { text-align: center; white-space: nowrap; }
    .rozet { display: inline-block; color: #fff; padding: 1px 5px; border-radius: 3px; font-size: 7.5px; }
    .r-kritik { background: #7f1d1d; }
    .r-yuksek { background: #dc2626; }
    .r-orta { background: #f59e0b; }
    .r-dusuk { background: #16a34a; }
    .r-none { background: #9ca3af; }
    .cok-satir { white-space: pre-line; }
    h2 { font-size: 10px; margin: 14px 0 5px; border-bottom: 1px solid #111; padding-bottom: 2px; }
    ol.notlar { margin: 0; padding-left: 16px; }
    ol.notlar li, ul.notlar li { font-size: 8px; margin-bottom: 2px; }
    ul.notlar { margin: 0; padding-left: 14px; list-style: none; }
    table.imza { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.imza th, table.imza td { border: 1px solid #888; padding: 5px 6px; font-size: 8px; }
    table.imza th { background: #eee; }
    table.imza td { height: 26px; }
    table.imza td.kase { height: 42px; text-align: center; vertical-align: middle; }
</style>
</head>
<body>
<div class="sayfa">

    <table class="ust">
        <tr>
            @if ($firma?->logo)
                <td class="logo"><img src="{{ storage_path('app/public/'.$firma->logo) }}"></td>
            @endif
            <td>
                <h1>{{ $sablon->baslik }}</h1>
                <div class="kunye">
                    @if ($firma){{ $firma->unvan }} &nbsp;•&nbsp; @endif
                    @if ($sablon->dokuman_ref)Doküman Ref: {{ $sablon->dokuman_ref }} &nbsp;•&nbsp; @endif
                    @if ($sablon->revizyon)Rev: {{ $sablon->revizyon }} &nbsp;•&nbsp; @endif
                    Tarih: {{ $sablon->belge_tarihi ?: now()->format('d.m.Y') }}
                </div>
            </td>
            @if ($firma?->logo)<td class="logo"></td>@endif
        </tr>
    </table>

    @if ($sablon->kapsam)
        <div class="kapsam"><strong>Kapsam:</strong> {{ $sablon->kapsam }}</div>
    @endif

    <table class="jsa">
        <thead>
            <tr>
                <th class="no">#</th>
                <th style="width:15%">İş Adımı / Faaliyet</th>
                <th style="width:17%">Olası Tehlikeler</th>
                <th style="width:15%">Olası Sonuçlar / Riskler</th>
                <th style="width:7%">Başlangıç Risk</th>
                <th style="width:22%">Kontrol Tedbirleri (KKD Dahil)</th>
                <th style="width:7%">Kalıntı Risk</th>
                <th>Sorumlu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sablon->adimlar ?? [] as $a)
                @php
                    $bas = \App\Models\JsaSablonu::riskRengi($a['baslangic_risk'] ?? null);
                    $kal = \App\Models\JsaSablonu::riskRengi($a['kalinti_risk'] ?? null);
                @endphp
                <tr>
                    <td class="no">{{ $a['sira'] ?? '' }}</td>
                    <td>{{ $a['is_adimi'] ?? '' }}</td>
                    <td class="cok-satir">{{ $a['tehlikeler'] ?? '' }}</td>
                    <td class="cok-satir">{{ $a['sonuclar'] ?? '' }}</td>
                    <td class="risk">
                        @if (($a['baslangic_risk'] ?? '') !== '')<span class="rozet r-{{ $bas }}">{{ $a['baslangic_risk'] }}</span>@endif
                    </td>
                    <td class="cok-satir">{{ $a['kontrol_tedbirleri'] ?? '' }}</td>
                    <td class="risk">
                        @if (($a['kalinti_risk'] ?? '') !== '')<span class="rozet r-{{ $kal }}">{{ $a['kalinti_risk'] }}</span>@endif
                    </td>
                    <td class="cok-satir">{{ $a['sorumlu'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="color:#888">İş adımı yok.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($sablon->notlar)
        <h2>Notlar ve Ek Gereksinimler</h2>
        <ul class="notlar">
            @foreach ($sablon->notlar as $not)
                <li>{{ $not }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Onay ve İmza</h2>
    <table class="imza">
        <tr>
            <th style="width:34%">Görevi / Rolü</th>
            <th style="width:26%">Adı Soyadı</th>
            <th style="width:20%">İmza</th>
            <th style="width:20%">Tarih</th>
        </tr>
        @foreach (($sablon->imza_rolleri ?: \App\Models\JsaSablonu::VARSAYILAN_IMZA_ROLLERI) as $rol)
            @php $hazirlayan = $uzman && \App\Models\JsaSablonu::hazirlayanRoluMu($rol['rol'] ?? null); @endphp
            <tr>
                <td>{{ $rol['rol'] ?? '' }}</td>
                <td>
                    {{ $hazirlayan ? $uzman->ad_soyad : ($rol['ad'] ?? '') }}
                    @if ($hazirlayan && $uzman->unvan)<br><span style="color:#555;font-size:7px">{{ $uzman->unvan }}</span>@endif
                </td>
                <td class="kase">
                    @if ($hazirlayan && $uzman->kase_gorseli)
                        <img src="{{ storage_path('app/public/'.$uzman->kase_gorseli) }}" style="max-height:38px;max-width:95%">
                    @endif
                </td>
                <td>{{ $rol['tarih'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>

</div>
</body>
</html>
