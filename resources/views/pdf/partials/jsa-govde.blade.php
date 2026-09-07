@php
    /** Firma seçiliyse "Hazırlayan" imza satırına firmanın İSG Uzmanı + kaşesi basılır. */
    $uzman = $uzman ?? $firma?->igu;
    /** Toplu çıktıda ilk analiz hariç her JSA yeni sayfada başlar. */
    $yeniSayfa = $yeniSayfa ?? false;
@endphp
<div class="sayfa"@if ($yeniSayfa) style="page-break-before: always"@endif>

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
