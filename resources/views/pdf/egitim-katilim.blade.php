<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 8px; }
    /* alt-şerit position:fixed olduğundan her sayfada tekrarlar; içerik onun
       üstünde kalsın diye alta 80px boşluk bırakılır. */
    .sayfa { padding: 14px 20px 82px; }

    .baslik { text-align: center; border-bottom: 2px double #111; padding-bottom: 4px; margin-bottom: 6px; }
    .baslik img { max-height: 34px; float: left; }
    .baslik h1 { font-size: 13px; margin: 0; }
    .baslik div { font-size: 8px; }

    .kunye { width: 100%; border-collapse: collapse; font-size: 7.5px; margin-bottom: 4px; }
    .kunye td { border: 1px solid #999; padding: 2px 5px; }
    .kunye td.e { background: #f0f0f0; font-weight: bold; width: 12%; }

    table.secim { width: 100%; border-collapse: collapse; font-size: 7.5px; margin-bottom: 6px; }
    table.secim td { border: 1px solid #999; padding: 3px 5px; }
    table.secim td.e { background: #f0f0f0; font-weight: bold; width: 12%; }
    .kutu { display: inline-block; width: 8px; height: 8px; border: 1px solid #333; text-align: center; line-height: 7px; font-size: 8px; margin: 0 2px 0 6px; }
    .kutu:first-child, td .kutu:first-of-type { margin-left: 0; }

    table.grid { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
    table.grid > tr > td { width: 50%; vertical-align: top; padding: 0; }
    .blok { border: 1px solid #999; margin-bottom: 4px; page-break-inside: avoid; }
    .blok.l { margin-right: 3px; }
    .blok.r { margin-left: 3px; }
    .blok h3 { background: #ecf0f1; margin: 0; padding: 2px 5px; font-size: 7.5px; border-bottom: 1px solid #999; }
    .blok ol { margin: 2px 6px 3px; padding-left: 12px; font-size: 6.8px; line-height: 1.28; }
    .blok ol li { margin: 0; }
    .blok.ozel ol { font-size: 8px; margin: 5px 12px; line-height: 1.4; }
    .dk { color: #666; font-weight: normal; }

    .lt { font-weight: bold; font-size: 8px; margin: 6px 0 2px; }
    table.katilim { width: 100%; border-collapse: collapse; font-size: 8px; }
    table.katilim th, table.katilim td { border: 1px solid #999; padding: 2px 5px; text-align: left; }
    table.katilim th { background: #f0f0f0; font-size: 7.5px; }
    table.katilim td { height: 16px; }

    .alt-serit { position: fixed; left: 20px; right: 20px; bottom: 8px; background: #fff; }
    table.imza { width: 100%; border-collapse: collapse; }
    table.imza td { width: 50%; vertical-align: top; border: 1px solid #999; padding: 4px 7px; font-size: 8px; }
    table.imza .rol { font-weight: bold; margin-bottom: 1px; }
    table.imza .ad { margin-bottom: 2px; }
    table.imza .kase { height: 28px; }
    table.imza .kase img { max-height: 28px; max-width: 85%; }
    table.imza .imza-satir { color: #666; }

    .yasal { margin: 3px 0 0; font-size: 6.5px; color: #666; text-align: center; }
</style>
</head>
<body>

{{-- Eğitmen kaşe/imzası — position:fixed olduğu için formun HER sayfasında görünür.
     Katılımcı listesi ikinci sayfaya taşarsa da eğitmen imzası orada da olur. --}}
@if ($kayit->isg_uzmani_var || $kayit->isyeri_hekimi_var)
    <div class="alt-serit">
        <table class="imza">
            <tr>
                @if ($kayit->isg_uzmani_var)
                    <td @if (! $kayit->isyeri_hekimi_var) style="width:100%" @endif>
                        <div class="rol">Eğitimi Veren — İş Güvenliği Uzmanı</div>
                        <div class="ad">{{ $kayit->isg_uzmani_adi ?: '.....................................' }}</div>
                        <div class="kase">
                            @if ($kayit->isg_uzmani_kase)
                                <img src="{{ storage_path('app/public/'.$kayit->isg_uzmani_kase) }}">
                            @endif
                        </div>
                        <div class="imza-satir">Kaşe / İmza</div>
                    </td>
                @endif
                @if ($kayit->isyeri_hekimi_var)
                    <td @if (! $kayit->isg_uzmani_var) style="width:100%" @endif>
                        <div class="rol">Eğitimi Veren — İşyeri Hekimi</div>
                        <div class="ad">{{ $kayit->isyeri_hekimi_adi ?: '.....................................' }}</div>
                        <div class="kase">
                            @if ($kayit->isyeri_hekimi_kase)
                                <img src="{{ storage_path('app/public/'.$kayit->isyeri_hekimi_kase) }}">
                            @endif
                        </div>
                        <div class="imza-satir">Kaşe / İmza</div>
                    </td>
                @endif
            </tr>
        </table>
        <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca düzenlenmiştir.</p>
    </div>
@endif

<div class="sayfa">

    <div class="baslik">
        @if ($firma?->logo)
            <img src="{{ storage_path('app/public/'.$firma->logo) }}">
        @endif
        <h1>EĞİTİM KATILIM FORMU</h1>
        <div>{{ $firma?->unvan }}</div>
    </div>

    @php
        $ikiGun = ($kayit->sure_gun ?? 1) >= 2;
        $sureMetni = (($icerik['saat'] ?? null) ? $icerik['saat'].' Ders Saati · ' : '')
            .($kayit->sure_gun ?? 1).' gün'.($ikiGun ? ' (1. ve 2. gün)' : '');
        $tekrarMi = ($kayit->egitim_turu ?? 'ilk') === 'tekrar';
        $sekil = $kayit->egitim_sekli ?? 'yuz_yuze';
        $tik = fn (bool $v) => '<span class="kutu">'.($v ? 'X' : '').'</span>';
    @endphp

    <table class="kunye">
        <tr>
            <td class="e">Eğitim Konusu</td><td style="width:38%">{{ $kayit->basliklarEtiketi() }}</td>
            <td class="e">Belge No</td><td>{{ $kayit->belge_no }}</td>
        </tr>
        <tr>
            <td class="e">Eğitim Yeri</td><td>{{ $kayit->egitim_yeri ?: '—' }}</td>
            <td class="e">Tarih</td><td>{{ $kayit->belge_tarihi?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="e">Süre</td>
            <td>{{ $sureMetni }}</td>
            <td class="e">Eğitimciler</td>
            <td>
                @if ($kayit->isg_uzmani_var) İş Güvenliği Uzmanı{{ $kayit->isg_uzmani_adi ? ' ('.$kayit->isg_uzmani_adi.')' : '' }} @endif
                @if ($kayit->isg_uzmani_var && $kayit->isyeri_hekimi_var) · @endif
                @if ($kayit->isyeri_hekimi_var) İşyeri Hekimi{{ $kayit->isyeri_hekimi_adi ? ' ('.$kayit->isyeri_hekimi_adi.')' : '' }} @endif
                @if (! $kayit->isg_uzmani_var && ! $kayit->isyeri_hekimi_var) — @endif
            </td>
        </tr>
    </table>

    <table class="secim">
        <tr>
            <td class="e">Eğitim Türü</td>
            <td>{!! $tik(! $tekrarMi) !!} İlk Defa &nbsp;&nbsp;&nbsp; {!! $tik($tekrarMi) !!} Tekrar (Yenileme)</td>
            <td class="e">Eğitim Şekli</td>
            <td>{!! $tik($sekil === 'yuz_yuze') !!} Yüz Yüze &nbsp;&nbsp; {!! $tik($sekil === 'uzaktan') !!} Uzaktan &nbsp;&nbsp; {!! $tik($sekil === 'karma') !!} Karma</td>
        </tr>
    </table>

    @php
        $goster = fn (array $maddeler) => collect($maddeler)->where('dahil', true)->values();
        $sure = fn (array $maddeler) => \App\Support\EgitimIcerikOlusturucu::bolumSuresi($maddeler);
        $blok = function (string $baslik, array $maddeler, string $kenar) use ($goster, $sure) {
            $s = $sure($maddeler);
            $html = '<div class="blok '.$kenar.'"><h3>'.e($baslik).' <span class="dk">('.$s['fiili'].'+'.$s['dinlenme'].' dk)</span></h3><ol>';
            foreach ($goster($maddeler) as $m) {
                $html .= '<li>'.e($m['madde']).' <span class="dk">('.$m['dakika'].')</span></li>';
            }
            return $html.'</ol></div>';
        };
    @endphp

    @if (($icerik['tip'] ?? null) === 'genel')
        <table class="grid">
            <tr>
                <td>
                    {!! $blok('Genel Konular', $icerik['genel_konular'], 'l') !!}
                    {!! $blok('Sağlık Konuları', $icerik['saglik_konulari'], 'l') !!}
                </td>
                <td>
                    {!! $blok('Teknik Konular', $icerik['teknik_konular'], 'r') !!}
                    @if ($icerik['isyerine_ozgu'] ?? null)
                        {!! $blok('İşyerine Özgü Riskler — '.$icerik['isyerine_ozgu']['sektor'], $icerik['isyerine_ozgu']['maddeler'], 'r') !!}
                    @else
                        <div class="blok r"><h3>İşyerine Özgü Riskler</h3><p style="font-size:7px;color:#888;margin:4px 8px">Sektör seçilmedi.</p></div>
                    @endif
                </td>
            </tr>
        </table>
    @else
        @php $ozelSure = $sure($icerik['maddeler'] ?? []); @endphp
        <div class="blok ozel">
            <h3>{{ $icerik['ad'] ?? $kayit->basliklarEtiketi() }} <span class="dk">(Fiili: {{ $ozelSure['fiili'] }}dk / Din: {{ $ozelSure['dinlenme'] }}dk)</span></h3>
            <ol>
                @foreach ($goster($icerik['maddeler'] ?? []) as $m)
                    <li>{{ $m['madde'] }} <span class="dk">({{ $m['dakika'] }} dk)</span></li>
                @endforeach
            </ol>
        </div>
    @endif

    @php
        $katilimcilar = $kayit->katilimcilar ?? [];
        // Sabit 24 imza satırı — tutarlı imza yeri. Katılımcı sayısı 24'ü aşarsa
        // hepsi listelenir (fazlası 2. sayfaya taşar, eğitmen imzası orada da var).
        $satirSayisi = max(count($katilimcilar), 24);
    @endphp
    <div class="lt">Katılımcı Listesi ve İmzaları</div>
    <table class="katilim">
        <thead>
            <tr>
                <th style="width:4%">#</th><th>Ad Soyad</th><th style="width:15%">T.C. No</th><th style="width:16%">Görevi</th>
                @if ($ikiGun)
                    <th style="width:17%">İmza (1. Gün)</th><th style="width:17%">İmza (2. Gün)</th>
                @else
                    <th style="width:22%">İmza</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $satirSayisi; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $katilimcilar[$i]['ad_soyad'] ?? '' }}</td>
                    <td>{{ $katilimcilar[$i]['tc'] ?? '' }}</td>
                    <td>{{ $katilimcilar[$i]['gorev'] ?? '' }}</td>
                    <td></td>
                    @if ($ikiGun) <td></td> @endif
                </tr>
            @endfor
        </tbody>
    </table>

    @if (! $kayit->isg_uzmani_var && ! $kayit->isyeri_hekimi_var)
        <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 17 uyarınca düzenlenmiştir.</p>
    @endif

</div>
</body>
</html>
