{{--
    Yıllık plan/rapor imza bloğu — İşyeri Hekimi + İş Güvenliği Uzmanı (sistemde
    kayıtlı kaşeleriyle) + İşveren/İşveren Vekili. $firma değişkeni beklenir.
--}}
@php
    $hekim = $firma?->isyeriHekimi;
    $uzman = $firma?->igu;
    $hekimKase = $hekim?->kase_gorseli && is_file(storage_path('app/public/'.$hekim->kase_gorseli))
        ? storage_path('app/public/'.$hekim->kase_gorseli) : null;
    $uzmanKase = $uzman?->kase_gorseli && is_file(storage_path('app/public/'.$uzman->kase_gorseli))
        ? storage_path('app/public/'.$uzman->kase_gorseli) : null;
    $isveren = $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: null);
@endphp

<table class="imza">
    <tr>
        <td>
            <div class="kutu">@if ($hekimKase)<img src="{{ $hekimKase }}">@endif</div>
            <div class="ad">{{ $hekim?->ad_soyad ?: '……………………………' }}</div>
            <div class="rol">İşyeri Hekimi</div>
        </td>
        <td>
            <div class="kutu">@if ($uzmanKase)<img src="{{ $uzmanKase }}">@endif</div>
            <div class="ad">{{ $uzman?->ad_soyad ?: '……………………………' }}</div>
            <div class="rol">İş Güvenliği Uzmanı</div>
        </td>
        <td>
            <div class="kutu"></div>
            <div class="ad">{{ $isveren ?: '……………………………' }}</div>
            <div class="rol">İşveren / İşveren Vekili</div>
        </td>
    </tr>
</table>
