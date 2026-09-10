{{-- Kimyasal Risk Değerlendirmesi (Kontrol Bantlama) — Kimyasal Maddelerle
     Çalışmalarda Sağlık ve Güvenlik Önlemleri Hakkında Yönetmelik. A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 20px 24px; }
    body { margin: 0; color: #111; font-size: 8px; line-height: 1.3; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 8px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 12%; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th, table.liste td { border: 1px solid #888; padding: 3px 4px; vertical-align: top; }
    table.liste th { background: #eee; font-size: 7px; text-align: left; }
    .y1 { background: #dcfce7; } .y2 { background: #fef9c3; }
    .y3 { background: #fed7aa; } .y4 { background: #fecaca; }
    .yb { text-align: center; font-weight: bold; }
    .yasal { margin-top: 8px; font-size: 7px; color: #555; }
    .imza { margin-top: 18px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; }
</style>
</head>
<body>

<h1>KİMYASAL RİSK DEĞERLENDİRMESİ (KONTROL BANTLAMA)</h1>
<div class="alt">COSHH Essentials yaklaşımı — tehlike grubu × kullanım miktarı × uçuculuk/tozlaşma → kontrol yaklaşımı</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Değerlendirme Tarihi</td><td>{{ $kayit->degerlendirme_tarihi?->format('d.m.Y') ?? now()->format('d.m.Y') }}</td>
    </tr>
    @if ($kayit->genel_not)
        <tr><td class="e">Not</td><td colspan="5">{{ $kayit->genel_not }}</td></tr>
    @endif
</table>

<table class="liste">
    <thead>
        <tr>
            <th style="width:14px">#</th>
            <th>Kimyasal / Ticari Ad</th>
            <th style="width:90px">Kullanım Alanı</th>
            <th style="width:44px">Tehlike Grubu</th>
            <th style="width:70px">Miktar</th>
            <th style="width:80px">Uçuculuk / Tozlaşma</th>
            <th style="width:60px">Maruziyet Yolu</th>
            <th style="width:26px">CMR</th>
            <th style="width:36px">Kontrol Yaklaşımı</th>
            <th>Alınan / Önerilen Önlemler</th>
            <th style="width:44px">Artık Risk</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($kayit->satirlar ?? [] as $i => $s)
            @php $y = (int) ($s['kontrol_yaklasimi'] ?? 1); @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $s['kimyasal_adi'] ?? '' }}</strong></td>
                <td>{{ $s['kullanim_alani'] ?? '—' }}</td>
                <td>{{ $s['tehlike_grubu'] ?? '' }}{{ !empty($s['deri_goz_yolu']) ? ' + S' : '' }}</td>
                <td>{{ $miktarlar[$s['miktar'] ?? 'az'] ?? '' }}</td>
                <td>{{ $ucuculuk[$s['ucuculuk'] ?? 'dusuk'] ?? '' }}</td>
                <td>
                    @php $yollar = ['soluma' => 'Soluma', 'deri' => 'Deri', 'yutma' => 'Yutma', 'goz' => 'Göz']; @endphp
                    {{ collect($s['maruziyet_yollari'] ?? [])->map(fn ($k) => $yollar[$k] ?? $k)->implode(', ') ?: '—' }}
                </td>
                <td style="text-align:center">{{ !empty($s['cmr']) ? '✔' : '' }}</td>
                <td class="yb y{{ $y }}">{{ $y }}</td>
                <td>{{ $s['alinan_onlemler'] ?? '' }}</td>
                <td style="text-align:center">{{ ucfirst($s['artik_risk'] ?? '') }}</td>
            </tr>
        @empty
            <tr><td colspan="11" style="text-align:center;color:#888">Kimyasal girilmemiş.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    <strong>Kontrol yaklaşımları:</strong>
    @foreach ($yaklasimlar as $no => $ad) {{ $ad }}@if (!$loop->last) &nbsp;·&nbsp; @endif @endforeach
    <br>
    CMR (kanserojen/mutajen/üreme sistemine toksik) maddeler için en az yaklaşım 3 uygulanır ve
    ikame öncelikle değerlendirilir. Bu değerlendirme SDS (GBF) bilgileriyle birlikte kullanılır;
    ölçüm gerektiren durumlarda ortam ölçümü yapılır.
</p>

<table class="imza">
    <tr>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşyeri Hekimi</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
