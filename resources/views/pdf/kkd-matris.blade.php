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
    .imza { margin-top: 22px; width: 100%; border-collapse: collapse; font-size: 8px; page-break-inside: avoid; }
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

@php
    $sutunKisa = [
        'baret' => 'Baret (EN 397)',
        'gozluk' => 'Gözlük / Siperlik (EN 166)',
        'kulaklik' => 'Kulak Kor. (EN 352)',
        'maske' => 'Solunum (EN 149)',
        'eldiven' => 'Eldiven (EN 388)',
        'ayakkabi' => 'İş Ayakkabısı (EN 20345)',
        'yuksek_ayakkabi' => 'Çizme / Yüksek (EN 20345)',
        'yelek' => 'Reflektif (EN 20471)',
        'kemer' => 'Düşme Durd. (EN 361)',
        'diger' => 'Diğer KKD',
    ];
    $pastelRenk = [
        'zorunlu' => '#fee2e2',
        'kosullu' => '#fef3c7',
        'onerilen' => '#dcfce7',
        'gerekmez' => '#f8fafc',
    ];
@endphp

<table class="m">
    <thead>
        <tr>
            <th style="width:130px">İş Kalemi / Görev</th>
            @foreach ($sutunlar as $anahtar => $tam)
                <th>{{ $sutunKisa[$anahtar] ?? $tam }}</th>
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
                    @php
                        $durum = $s[$anahtar] ?? 'gerekmez';
                        $meta = $durumlar[$durum] ?? $durumlar['gerekmez'];
                        $not = $s[$anahtar.'_not'] ?? null;
                        $bg = $pastelRenk[$durum] ?? '#f8fafc';
                    @endphp
                    <td style="background:{{ $bg }}">
                        {{ $meta['sembol'] }}
                        @if ($not)
                            <br><span style="font-size:6px">{{ $not }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($sutunlar) + 1 }}" style="text-align:center;color:#888">İş kalemi girilmemiş.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    @foreach ($durumlar as $meta)
        "{{ $meta['sembol'] }}" = {{ $meta['etiket'] }};
    @endforeach
    KKD'ler ilgili uyumlaştırılmış standarda (EN) uygun, CE işaretli olmalı; çalışanlara zimmetle
    teslim edilmeli ve kullanımı denetlenmelidir. Bu matris risk değerlendirmesinin ekidir.
</p>

<table class="imza">
    <tr>
        <td>Hazırlayan (İş Güvenliği Uzmanı)<br>{{ $firma?->igu?->ad_soyad ?: '' }}<br>İmza / Tarih</td>
        <td>İşyeri Hekimi<br>{{ $firma?->isyeriHekimi?->ad_soyad ?: '' }}<br>İmza / Tarih</td>
        <td>İşveren / İşveren Vekili<br>{{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '') }}<br>Kaşe / İmza / Tarih</td>
    </tr>
</table>

</body>
</html>
