{{-- Ortam Ölçümleri Takip Listesi — İş Hijyeni Ölçüm, Test ve Analizi Yapan
     Laboratuvarlar Hakkında Yönetmelik. A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 8.5px; line-height: 1.35; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 10px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 12%; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th, table.liste td { border: 1px solid #777; padding: 3px 4px; vertical-align: top; }
    table.liste th { background: #eee; text-align: left; font-size: 7.5px; }
    .s-uygun { color: #15803d; font-weight: bold; }
    .s-asim { color: #b91c1c; font-weight: bold; }
    .s-sinir { color: #b45309; font-weight: bold; }
    .s-bekliyor { color: #6b7280; }
    .yasal { margin-top: 10px; font-size: 7.5px; color: #555; }
    .imza { margin-top: 26px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; }
</style>
</head>
<body>

<h1>ORTAM ÖLÇÜMLERİ TAKİP LİSTESİ</h1>
<div class="alt">İş hijyeni ölçüm, test ve analizleri yetkili laboratuvarlarca yapılır; sonuçlar risk değerlendirmesinde kullanılır.</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Liste Tarihi</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    @if ($olcum->genel_not)
        <tr><td class="e">Not</td><td colspan="5">{{ $olcum->genel_not }}</td></tr>
    @endif
</table>

<table class="liste">
    <thead>
        <tr>
            <th style="width:16px">#</th>
            <th>Ölçüm Parametresi</th>
            <th style="width:78px">Grup</th>
            <th style="width:80px">Bölge / Nokta</th>
            <th style="width:30px">Periyot (ay)</th>
            <th style="width:52px">Ölçüm Tarihi</th>
            <th style="width:100px">Laboratuvar / Rapor No</th>
            <th style="width:80px">Ölçülen / Sınır Değer</th>
            <th style="width:64px">Sonuç</th>
            <th style="width:52px">Sonraki Ölçüm</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($olcum->olcumler ?? [] as $i => $m)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $m['parametre'] ?? '' }}</td>
                <td>{{ $m['grup'] ?? '' }}</td>
                <td>{{ $m['bolge'] ?? '—' }}</td>
                <td style="text-align:center">{{ $m['periyot_ay'] ?? '' }}</td>
                <td>{{ !empty($m['olcum_tarihi']) ? \Illuminate\Support\Carbon::parse($m['olcum_tarihi'])->format('d.m.Y') : '—' }}</td>
                <td>
                    {{ $m['laboratuvar'] ?? '—' }}
                    @if (!empty($m['rapor_no'])) <br><span style="color:#555">{{ $m['rapor_no'] }}</span> @endif
                </td>
                <td>
                    {{ $m['olculen_deger'] ?? '—' }}{{ !empty($m['birim']) ? ' '.$m['birim'] : '' }}
                    @if (!empty($m['sinir_deger'])) <br><span style="color:#555">Sınır: {{ $m['sinir_deger'] }}</span> @endif
                </td>
                <td class="s-{{ $m['sonuc'] ?? 'bekliyor' }}">{{ $sonuclar[$m['sonuc'] ?? 'bekliyor'] ?? ($m['sonuc'] ?? '') }}</td>
                <td>{{ !empty($m['sonraki_olcum_tarihi']) ? \Illuminate\Support\Carbon::parse($m['sonraki_olcum_tarihi'])->format('d.m.Y') : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center;color:#888">Ölçüm girilmemiş.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    Ortam ölçümleri, İş Hijyeni Ölçüm, Test ve Analizi Yapan Laboratuvarlar Hakkında Yönetmelik
    kapsamında yetkilendirilmiş laboratuvarlarca; ölçüm periyodu ise risk değerlendirmesi
    sonuçlarına göre belirlenir. "Sınır Değer Aşımı" bulunan parametrelerde kaynağında önlem
    alınır ve ölçüm tekrarlanır.
</p>

<table class="imza">
    <tr>
        <td>Ölçümü Yapan<br>(Yetkili Laboratuvar)</td>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
