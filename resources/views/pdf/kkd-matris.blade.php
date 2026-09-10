{{-- KKD Seçim Matrisi — Kişisel Koruyucu Donanımların İşyerlerinde Kullanılması
     Hakkında Yönetmelik. A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 20px 22px; }
    body { margin: 0; color: #111; font-size: 7.5px; line-height: 1.3; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 8px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 12%; }
    table.m { width: 100%; border-collapse: collapse; }
    table.m th, table.m td { border: 1px solid #888; padding: 3px 3px; vertical-align: top; }
    table.m th { background: #eee; font-size: 6.8px; text-align: center; }
    table.m td.is { text-align: left; font-weight: bold; width: 130px; }
    table.m td { text-align: center; }
    .grp { background: #f4f4f4; font-weight: bold; text-align: left; font-size: 8px; }
    .yasal { margin-top: 8px; font-size: 7px; color: #555; }
    .imza { margin-top: 22px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; }
</style>
</head>
<body>

<h1>KİŞİSEL KORUYUCU DONANIM (KKD) SEÇİM MATRİSİ</h1>
<div class="alt">Hangi işte hangi KKD'nin kullanılacağı — KKD Yönetmeliği ve risk değerlendirmesi sonuçlarına göre</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Tarih</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    @if ($matris->genel_not)
        <tr><td class="e">Not</td><td colspan="5">{{ $matris->genel_not }}</td></tr>
    @endif
</table>

@php $sutunKisa = ['baret' => 'Baret', 'gozluk' => 'Gözlük / Siperlik', 'kulaklik' => 'Kulak Kor.', 'maske' => 'Solunum Kor.', 'eldiven' => 'Eldiven', 'ayakkabi' => 'İş Ayakkabısı', 'yelek' => 'Reflektif Yelek', 'kemer' => 'Düşme Durd.', 'diger' => 'Diğer KKD']; @endphp

<table class="m">
    <thead>
        <tr>
            <th style="width:130px">İş Kalemi / Görev</th>
            @foreach ($sutunlar as $anahtar => $tam)
                <th>{{ $sutunKisa[$anahtar] ?? $anahtar }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php $sonGrup = null; @endphp
        @forelse ($matris->satirlar ?? [] as $s)
            @if (($s['grup'] ?? null) && $s['grup'] !== $sonGrup)
                <tr><td class="grp" colspan="{{ count($sutunlar) + 1 }}">{{ $s['grup'] }}</td></tr>
                @php $sonGrup = $s['grup']; @endphp
            @endif
            <tr>
                <td class="is">{{ $s['is_kalemi'] ?? '' }}</td>
                @foreach (array_keys($sutunlar) as $anahtar)
                    <td>{{ $s[$anahtar] ?? '' ?: '–' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($sutunlar) + 1 }}" style="text-align:center;color:#888">İş kalemi girilmemiş.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    "✔" = zorunlu; "Gerekirse" = risk değerlendirmesi / işin niteliğine göre. KKD'ler ilgili
    uyumlaştırılmış standarda (EN) uygun, CE işaretli olmalı; çalışanlara zimmetle teslim edilmeli
    ve kullanımı denetlenmelidir. Bu matris risk değerlendirmesinin ekidir.
</p>

<table class="imza">
    <tr>
        <td>Hazırlayan (İş Güvenliği Uzmanı)</td>
        <td>İşyeri Hekimi</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
