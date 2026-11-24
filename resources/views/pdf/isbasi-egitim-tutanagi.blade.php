<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 28px 34px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:nth-child(odd) { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 11.5px; margin: 12px 0 4px; color: #0ea5e9; }
    ul.konular { margin: 0 0 8px; padding-left: 1.2rem; font-size: 10px; }
    ul.konular li { margin-bottom: 2px; }
    .imza { margin-top: 22px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .imza td { width: 25%; text-align: center; border: 1px solid #999; padding: 6px 5px 34px; font-size: 8.8px; vertical-align: top; }
    .imza .rol { font-weight: bold; background: #f0f0f0; display: block; margin: -6px -5px 6px; padding: 3px; border-bottom: 1px solid #999; }
    .yasal { margin-top: 10px; font-size: 8.5px; color: #475569; text-align: center; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞBAŞI / ORYANTASYON EĞİTİM TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Çalışan</td><td>{{ $tutanak->calisan_ad_soyad }}</td>
            <td>T.C. Kimlik No</td><td>{{ $tutanak->tcGorunur() }}</td>
        </tr>
        <tr>
            <td>Eğitim Tarihi / Saat</td><td>{{ $tutanak->egitim_tarihi?->format('d.m.Y') ?: '—' }} · Saat: ....:.... – ....:....</td>
            <td>Süre</td><td>{{ $tutanak->sure_saat ?? '—' }} saat (Yön. m.7 asgari 2 saat)</td>
        </tr>
        <tr>
            <td>Eğitim Yeri</td><td>{{ $tutanak->egitim_yeri ?: '—' }}</td>
            <td>Eğitim Yöntemi</td><td>{{ $tutanak->egitim_yontemi ?: 'Uygulamalı ve Yüz Yüze' }}</td>
        </tr>
        <tr>
            <td>Eğitimi Veren</td><td>{{ $tutanak->egitimi_veren ?: '—' }}</td>
            <td>Belge Tarihi</td><td>{{ $tutanak->belge_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
    </table>

    @foreach (config('isg.isbasi_egitim.konu_kategorileri') as $kategori => $maddeler)
        <h2>{{ \App\Support\TurkceMetin::buyuk($kategori) }}</h2>
        <ul class="konular">
            @foreach ($maddeler as $madde)
                @php $isaretli = in_array($madde, $tutanak->konular ?? [], true); @endphp
                <li>{{ $isaretli ? '☑' : '☐' }} {{ $madde }}</li>
            @endforeach
        </ul>
    @endforeach

    <table class="imza">
        <tr>
            <td>
                <span class="rol">ÇALIŞAN (TEBELLÜĞ)</span>
                {{ $tutanak->calisan_ad_soyad }}
                @if (! empty($canliImza) && (str_starts_with((string) $canliImza, 'data:image/') || is_file((string) $canliImza)))
                    <img src="{{ $canliImza }}" style="max-height:36px;display:block;margin:4px auto 0">
                @endif
            </td>
            <td>
                <span class="rol">EĞİTİMİ VEREN</span>
                {{ $tutanak->egitimi_veren ?: 'Eğitici' }}
            </td>
            <td>
                <span class="rol">İGU / İŞYERİ HEKİMİ</span>
                @if ($tutanak->igu_imzasi) İGU: {{ $firma?->igu?->ad_soyad ?: 'İş Güvenliği Uzmanı' }}{{ $firma?->igu?->sertifika_no ? ' ('.$firma->igu->sertifika_no.')' : '' }} @endif
                @if ($tutanak->igu_imzasi && $tutanak->isyeri_hekimi_imzasi)<br>@endif
                @if ($tutanak->isyeri_hekimi_imzasi) İH: {{ $firma?->isyeriHekimi?->ad_soyad ?: 'İşyeri Hekimi' }} @endif
                @if (! $tutanak->igu_imzasi && ! $tutanak->isyeri_hekimi_imzasi) — @endif
            </td>
            <td>
                <span class="rol">İŞVEREN / VEKİLİ (ONAY)</span>
                {{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '') }}
            </td>
        </tr>
    </table>

    <p class="yasal">
        Çalışanların İSG Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik (RG 02.04.2026 / 33212, m.7) gereğince işe başlama eğitimi fiilen çalışmaya başlamadan önce en az 2 saat uygulamalı ve yüz yüze verilmiş olup temel eğitim süresinden sayılmaz.
    </p>

</div>
</body>
</html>
