{{--
    Yıllık plan/rapor imza bloğu — İşyeri Hekimi + İş Güvenliği Uzmanı (sistemde
    kayıtlı kaşeleriyle) + İşveren/İşveren Vekili. $firma değişkeni beklenir.
    Görevli ad soyadı basılmaz (kullanıcı kararı 03.10.2026): kaşe adı zaten taşır.
--}}
@php
    $hekim = $firma?->isyeriHekimi;
    $uzman = $firma?->igu;
    $hekimKase = ($imzali ?? true) && $hekim?->kase_gorseli && is_file(storage_path('app/public/'.$hekim->kase_gorseli))
        ? storage_path('app/public/'.$hekim->kase_gorseli) : null;
    $uzmanKase = ($imzali ?? true) && $uzman?->kase_gorseli && is_file(storage_path('app/public/'.$uzman->kase_gorseli))
        ? storage_path('app/public/'.$uzman->kase_gorseli) : null;
    $isveren = $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: null);
@endphp

<table class="imza">
    <tr>
        <td>
            <div class="kutu">@if ($hekimKase)<img src="{{ $hekimKase }}">@endif</div>
            <div class="rol">İşyeri Hekimi</div>
        </td>
        <td>
            <div class="kutu">@if ($uzmanKase)<img src="{{ $uzmanKase }}">@endif</div>
            <div class="rol">İş Güvenliği Uzmanı</div>
        </td>
        <td>
            <div class="kutu"></div>
            <div class="rol">İşveren / İşveren Vekili</div>
        </td>
    </tr>
</table>
