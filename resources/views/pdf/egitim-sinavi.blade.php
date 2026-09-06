<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 26px 32px; page-break-after: always; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    .soru { margin-bottom: 12px; page-break-inside: avoid; }
    .soru .metin { font-weight: bold; font-size: 10.5px; margin-bottom: 4px; }
    .soru .secenek { font-size: 10px; margin: 2px 0 2px 14px; }
    .dogru { color: #10b981; font-weight: bold; }
    table.anahtar { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 8px; }
    table.anahtar th, table.anahtar td { border: 1px solid #999; padding: 4px 8px; text-align: left; }
    table.anahtar th { background: #f0f0f0; }
</style>
</head>
<body>

@forelse (($sinav->katilimcilar ?: [null]) as $k)
    <div class="sayfa">
        <div class="baslik">
            <h1>EĞİTİM SORULARI SINAVI — {{ $sinav->zamanEtiketi() }}</h1>
            <div style="font-size:11px">{{ $firma?->unvan }}</div>
        </div>

        <table class="kunye">
            <tr>
                <td>Sektör</td><td>{{ $sinav->sektorEtiketi() }}</td>
                <td>Zorluk</td><td>{{ config('isg.egitim_sorulari.zorluklar.'.$sinav->zorluk, $sinav->zorluk) }}</td>
            </tr>
            <tr>
                <td>Sınav Zamanı</td><td colspan="3">{{ $sinav->zamanEtiketi() }}</td>
            </tr>
            @if ($k)
                <tr>
                    <td>Ad Soyad</td><td>{{ $k['ad_soyad'] ?? '—' }}</td>
                    <td>Sınav Tarihi</td><td>{{ $k['sinav_tarihi'] ?? '—' }}</td>
                </tr>
            @endif
        </table>

        @foreach (($sinav->sorular ?? []) as $i => $s)
            <div class="soru">
                <div class="metin">{{ $i + 1 }}. {{ $s['soru'] }}</div>
                @foreach (($s['secenekler'] ?? []) as $j => $secenek)
                    <div class="secenek {{ ($sinav->cevap_anahtari_dahil && $j === ($s['dogru_index'] ?? -1)) ? 'dogru' : '' }}">
                        {{ chr(65 + $j) }}) {{ $secenek }}
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@empty
@endforelse

@if ($sinav->cevap_anahtari_dahil)
    <div class="sayfa" style="page-break-after:auto">
        <div class="baslik"><h1>CEVAP ANAHTARI</h1></div>
        <table class="anahtar">
            <tr><th style="width:10%">Soru</th><th>Doğru Cevap</th></tr>
            @foreach (($sinav->sorular ?? []) as $i => $s)
                <tr><td>{{ $i + 1 }}</td><td>{{ chr(65 + ($s['dogru_index'] ?? 0)) }}) {{ $s['secenekler'][$s['dogru_index'] ?? 0] ?? '—' }}</td></tr>
            @endforeach
        </table>
    </div>
@endif

</body>
</html>
