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

<div class="sayfa">
    <div class="baslik">
        <h1>YILLIK ÇALIŞMA PLANI — {{ $plan->yil }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="plan">
        <tr>
            <th>Yasal Gereklilik / Faaliyet</th><th>Sorumlu</th><th>Frekans</th>
            @foreach ($aylar as $ay)
                <th>{{ $ay }}</th>
            @endforeach
        </tr>
        @forelse (($plan->faaliyetler ?? []) as $f)
            <tr>
                <td class="aciklama">
                    <strong>{{ $f['faaliyet'] }}</strong>
                    @if (!empty($f['yasal_gereklilik']))<br><span style="color:#666;font-size:7px">{{ $f['yasal_gereklilik'] }}</span>@endif
                </td>
                <td class="sorumlu">{{ $f['sorumlu'] ?? '—' }}</td>
                <td class="sorumlu">{{ $f['frekans'] ?? '—' }}</td>
                @foreach (($f['aylar'] ?? array_fill(0, 12, 'bos')) as $durum)
                    @php $renk = match ($durum) { 'tamamlandi' => '#10b981', 'planlandi' => '#f59e0b', default => '#374151' }; @endphp
                    <td><span class="durum" style="background:{{ $renk }}"></span></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="15" style="color:#888">Faaliyet eklenmedi.</td></tr>
        @endforelse
    </table>

    <p style="font-size:8px;color:#666;margin-top:10px">
        <span class="durum" style="background:#374151"></span> Boş &nbsp;
        <span class="durum" style="background:#f59e0b"></span> Planlandı &nbsp;
        <span class="durum" style="background:#10b981"></span> Tamamlandı
    </p>

    @include('pdf.partials.yillik-plan-imza')
</div>

<div class="sayfa">
    <div class="baslik">
        <h1>YILLIK EĞİTİM PLANI — {{ $plan->yil }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="plan">
        <tr>
            <th>Kategori</th><th>Eğitim Konusu</th><th>Süre</th><th>Eğitici</th>
            @foreach ($aylar as $ay)
                <th>{{ $ay }}</th>
            @endforeach
        </tr>
        @php $egitimKat = config('isg.yillik_plan.egitim_kategorileri'); @endphp
        @forelse (($plan->egitimler ?? []) as $e)
            <tr>
                <td class="sorumlu">{{ $egitimKat[$e['kategori'] ?? ''] ?? '—' }}</td>
                <td class="faaliyet">{{ $e['konu'] }}</td>
                <td class="sorumlu">{{ $e['sure_saat'] ?? '—' }} saat</td>
                <td class="sorumlu">{{ $e['egitici'] ?? '—' }}</td>
                @foreach (($e['aylar'] ?? array_fill(0, 12, 'bos')) as $durum)
                    @php $renk = match ($durum) { 'tamamlandi' => '#10b981', 'planlandi' => '#f59e0b', default => '#374151' }; @endphp
                    <td><span class="durum" style="background:{{ $renk }}"></span></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="16" style="color:#888">Eğitim eklenmedi.</td></tr>
        @endforelse
    </table>

    @include('pdf.partials.yillik-plan-imza')
</div>

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
