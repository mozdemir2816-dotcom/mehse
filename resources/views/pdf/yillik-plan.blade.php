<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .sayfa { padding: 20px 26px; page-break-after: always; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    table.plan { width: 100%; border-collapse: collapse; font-size: 8px; }
    table.plan th, table.plan td { border: 1px solid #999; padding: 3px 4px; text-align: center; }
    table.plan th { background: #f0f0f0; }
    table.plan td.faaliyet { text-align: left; font-weight: bold; width: 14%; }
    table.plan td.sorumlu { text-align: left; width: 8%; }
    table.plan td.aciklama { text-align: left; width: 20%; font-size: 7.5px; }
    .durum { width: 16px; height: 16px; display: inline-block; border-radius: 2px; }
    table.rapor { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.rapor th, table.rapor td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.rapor th { background: #f0f0f0; }

    table.imza { width: 100%; border-collapse: collapse; margin-top: 24px; page-break-inside: avoid; }
    table.imza td { width: 33.33%; text-align: center; font-size: 9px; vertical-align: top; padding: 0 10px; }
    table.imza .kutu { height: 46px; border-bottom: 1px solid #111; text-align: center; }
    table.imza .kutu img { max-height: 44px; max-width: 100%; }
    table.imza .ad { font-weight: bold; margin-top: 5px; }
    table.imza .rol { color: #555; font-size: 8px; }
</style>
</head>
<body>

{{-- Çalışma ve eğitim planı artık kullanıcının Excel şablonlarıyla üretilir
     (App\Support\YillikPlanExcelUretici); bu PDF yalnız Değerlendirme Raporu. --}}
<div class="sayfa" style="page-break-after:auto">
    <div class="baslik">
        <h1>YILLIK DEĞERLENDİRME RAPORU — {{ $plan->yil }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="rapor">
        <tr>
            <th style="width:4%">No</th><th>Yapılan Çalışmalar</th><th style="width:9%">Tarih</th>
            <th>Yapan Kişi ve Unvanı</th><th style="width:8%">Tekrar Sayısı</th><th>Kullanılan Yöntem</th><th>Sonuç ve Yorum</th>
        </tr>
        @forelse (($plan->degerlendirmeler ?? []) as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $d['calisma'] }}</td>
                <td>{{ $d['tarih'] ?? '—' }}</td>
                <td>{{ $d['yapan_kisi'] ?? '—' }}</td>
                <td>{{ $d['tekrar_sayisi'] ?? '—' }}</td>
                <td>{{ $d['yontem'] ?? '—' }}</td>
                <td>{{ $d['sonuc'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="color:#888">Çalışma eklenmedi.</td></tr>
        @endforelse
    </table>

    @include('pdf.partials.yillik-plan-imza')
</div>

</body>
</html>
