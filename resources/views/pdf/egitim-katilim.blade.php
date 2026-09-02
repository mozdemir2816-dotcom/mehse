<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 20px 28px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik img { max-height: 50px; float: left; }
    .baslik h1 { font-size: 17px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    .blok { border: 1px solid #999; margin-bottom: 10px; page-break-inside: avoid; }
    .blok h3 { background: #ecf0f1; margin: 0; padding: 5px 8px; font-size: 11px; border-bottom: 1px solid #999; }
    .blok ol { margin: 6px 10px; padding-left: 16px; font-size: 9.5px; }
    .blok ol li { margin-bottom: 3px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid > tr > td { width: 50%; vertical-align: top; padding: 0; }
    table.ozel ol { font-size: 10px; margin: 8px 12px; }
    .dk { color: #666; font-weight: normal; }
    table.katilim { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9.5px; }
    table.katilim th, table.katilim td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.katilim th { background: #f0f0f0; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        @if ($firma?->logo)
            <img src="{{ storage_path('app/public/'.$firma->logo) }}">
        @endif
        <h1>EĞİTİM KATILIM FORMU</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Eğitim Konusu</td><td>{{ $kayit->basliklarEtiketi() }}</td>
            <td>Belge No</td><td>{{ $kayit->belge_no }}</td>
        </tr>
        <tr>
            <td>Eğitim Yeri</td><td>{{ $kayit->egitim_yeri ?: '—' }}</td>
            <td>Tarih</td><td>{{ $kayit->belge_tarihi?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td>Süre</td><td>{{ $kayit->sure_gun }} gün @if(($icerik['saat'] ?? null)) ({{ $icerik['saat'] }} saat) @endif</td>
            <td>Eğitimciler</td>
            <td>
                @if ($kayit->isg_uzmani_var) İş Güvenliği Uzmanı @endif
                @if ($kayit->isg_uzmani_var && $kayit->isyeri_hekimi_var) · @endif
                @if ($kayit->isyeri_hekimi_var) İşyeri Hekimi{{ $kayit->isyeri_hekimi_adi ? ' ('.$kayit->isyeri_hekimi_adi.')' : '' }} @endif
                @if (! $kayit->isg_uzmani_var && ! $kayit->isyeri_hekimi_var) — @endif
            </td>
        </tr>
    </table>

    @if (($icerik['tip'] ?? null) === 'genel')
        <table class="grid">
            <tr>
                <td style="padding-right:5px">
                    <div class="blok">
                        <h3>Genel Konular</h3>
                        <ol>
                            @foreach ($icerik['genel_konular'] as $m)
                                <li>{{ $m['madde'] }} <span class="dk">({{ $m['dakika'] }} dk)</span></li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="blok">
                        <h3>Sağlık Konuları</h3>
                        <ol>
                            @foreach ($icerik['saglik_konulari'] as $m)
                                <li>{{ $m['madde'] }} <span class="dk">({{ $m['dakika'] }} dk)</span></li>
                            @endforeach
                        </ol>
                    </div>
                </td>
                <td style="padding-left:5px">
                    <div class="blok">
                        <h3>Teknik Konular</h3>
                        <ol>
                            @foreach ($icerik['teknik_konular'] as $m)
                                <li>{{ $m['madde'] }} <span class="dk">({{ $m['dakika'] }} dk)</span></li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="blok">
                        <h3>İşyerine Özgü Riskler @if($icerik['isyerine_ozgu'] ?? null)— {{ $icerik['isyerine_ozgu']['sektor'] }} <span class="dk">({{ $icerik['isyerine_ozgu']['dakika'] }} dk)</span>@endif</h3>
                        @if ($icerik['isyerine_ozgu'] ?? null)
                            <ol>
                                @foreach ($icerik['isyerine_ozgu']['maddeler'] as $madde)
                                    <li>{{ $madde }}</li>
                                @endforeach
                            </ol>
                        @else
                            <p style="font-size:9.5px;color:#888;margin:8px 10px">Sektör seçilmedi.</p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
        <p style="font-size:9px;color:#666">Dinlenme/ara süresi: {{ $icerik['dinlenme_dk'] }} dakika.</p>
    @else
        <div class="blok ozel">
            <h3>{{ $icerik['ad'] ?? $kayit->basliklarEtiketi() }}</h3>
            <ol>
                @foreach (($icerik['maddeler'] ?? []) as $madde)
                    <li>{{ $madde }}</li>
                @endforeach
            </ol>
        </div>
    @endif

    <table class="katilim">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th style="width:15%">T.C. No</th><th style="width:20%">Görevi</th><th style="width:20%">İmza</th></tr>
        @forelse (($kayit->katilimcilar ?? []) as $i => $k)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $k['ad_soyad'] ?? '—' }}</td>
                <td>{{ $k['tc'] ?? '—' }}</td>
                <td>{{ $k['gorev'] ?? '—' }}</td>
                <td></td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:#888">Katılımcı eklenmedi.</td></tr>
        @endforelse
    </table>

    <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca düzenlenmiştir.</p>

</div>
</body>
</html>
