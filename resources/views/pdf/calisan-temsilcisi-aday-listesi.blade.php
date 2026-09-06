<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 30px 36px; }
    .ust { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
    .ust .sol { display: table-cell; }
    .ust .sag { display: table-cell; text-align: right; font-size: 9px; color: #555; vertical-align: top; }
    .ust h1 { font-size: 14px; margin: 4px 0 0; }
    .ust .firma { font-size: 11px; font-weight: bold; }
    .adres { margin-bottom: 12px; }
    .govde { font-size: 11px; line-height: 1.6; text-align: justify; margin-bottom: 14px; }
    .liste { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 16px; }
    .liste th { background: #1e3a8a; color: #fff; padding: 8px; text-align: left; }
    .liste td { border: 1px solid #ccc; padding: 8px; }
    .liste .tercih { text-align: center; width: 60px; }
    .daire { display: inline-block; width: 14px; height: 14px; border: 1.5px solid #1e3a8a; border-radius: 50%; }
    .imza { margin-top: 40px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .cizgi { display: inline-block; border-bottom: 1px solid #111; }
</style>
</head>
<body>
@php $bosMi = $bos ?? false; @endphp
<div class="sayfa">

    <div class="ust">
        <div class="sol">
            <div class="firma">@if ($bosMi)<span class="cizgi" style="min-width:260px">&nbsp;</span>@else{{ $firma?->unvan }}@endif</div>
            <h1>ÇALIŞAN TEMSİLCİSİ KESİN ADAY LİSTESİ</h1>
        </div>
        <div class="sag">
            Belge No: {{ $secim->dokuman_no }}<br>
            Düzenleme Tarihi: {{ $secim->ilan_tarihi?->format('d.m.Y') }}
        </div>
    </div>

    <div class="adres">İşyeri Adresi: @if ($bosMi)<span class="cizgi" style="min-width:320px">&nbsp;</span>@else{{ $firma?->adres }}@endif</div>

    <div class="govde">
        Çalışan temsilcisi seçimi için süresi içinde başvuruda bulunan adayların listesi aşağıdadır. Bu liste
        seçimden önce işyerinde çalışanların görebileceği şekilde ilan edilir.
    </div>

    <table class="liste">
        <tr><th>Sıra</th><th>Adayın Adı Soyadı</th><th>Unvanı / Görevi</th><th class="tercih">Tercih</th></tr>
        @if ($bosMi)
            @for ($i = 0; $i < 10; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="tercih"><span class="daire"></span></td>
                </tr>
            @endfor
        @else
            @forelse ($secim->adaylarListesi() as $i => $aday)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $aday['ad_soyad'] }}</td>
                    <td>{{ $aday['unvan'] ?: '—' }}</td>
                    <td class="tercih"><span class="daire"></span></td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#888">Aday eklenmedi.</td></tr>
            @endforelse
        @endif
    </table>

    <div class="govde">
        Listede yer alan adaylar, başvuru ve değerlendirme süreci sonunda seçime katılmaya uygun bulunmuştur. Seçim
        gizli oy esasına göre gerçekleştirilecektir.
    </div>

    <table class="imza">
        <tr>
            <td>DÜZENLEYEN<br>İşveren / İşveren Vekili</td>
            <td>İLAN TARİHİ<br>{{ $secim->ilan_tarihi?->format('d.m.Y') }}</td>
        </tr>
    </table>

    <p class="yasal">Dayanak: 6331 sayılı Kanun md.20 ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına İlişkin Tebliğ.</p>

</div>
</body>
</html>
