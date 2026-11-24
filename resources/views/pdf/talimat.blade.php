<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
{{--
    Çalışma talimatı — kullanıcının inşaat talimatlarının düzeni (10.10.2026):
    her sayfada logo | talimat adı | künye tablosu, altta sayfa no. Üretici:
    App\Support\TalimatUretici::pdf(). Word karşılığı: TalimatWordUretici.
--}}
<style>
    @page { margin: 132px 52px 54px 52px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; line-height: 1.5; }
    .ust { position: fixed; top: -104px; left: 0; right: 0; }
    .ust table { width: 100%; border-collapse: collapse; }
    .ust td { border: 1px solid #222; height: 82px; vertical-align: middle; padding: 4px 8px; }
    .ust .logo { width: 33%; text-align: center; }
    .ust .logo img { max-width: 175px; max-height: 70px; }
    .ust .logo .unvan { font-size: 10px; font-weight: bold; }
    .ust .ad { width: 33%; text-align: center; font-size: 13px; line-height: 1.35; }
    .ust .kunye { font-size: 9.5px; line-height: 1.55; }
    .sayfano { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 9.5px; }
    .sayfano:after { content: counter(page); }
    h1 { font-size: 12px; text-align: center; margin: 4px 0 10px; }
    h2 { font-size: 11px; margin: 9px 0 4px; page-break-after: avoid; }
    .kapanis { page-break-inside: avoid; }
    p { margin: 0 0 6px; text-align: justify; }
    ul { margin: 0 0 4px; padding-left: 34px; }
    ul li { margin-bottom: 3px; }
    .madde { margin: 0 0 7px; text-align: justify; }
    .kkd { margin: 0 0 9px; }
    .taahhut { margin-top: 10px; }
    .taahhut.girintili { padding-left: 34px; }
    table.imza { width: 100%; margin-top: 26px; page-break-inside: avoid; }
    table.imza td { width: 50%; vertical-align: top; padding: 0 22px; font-size: 10.5px; }
    table.imza .rol { font-weight: bold; margin-bottom: 6px; }
    table.imza .satir { margin-bottom: 4px; }
    table.imza img { max-height: 52px; max-width: 120px; margin-right: 6px; }
</style>
</head>
<body>

<div class="ust">
    <table>
        <tr>
            <td class="logo">
                @if ($logo)<img src="{{ $logo }}">@else<span class="unvan">{{ $firma?->unvan }}</span>@endif
            </td>
            <td class="ad">{{ $talimat->baslik }}</td>
            <td class="kunye">
                Doküman No: {{ $talimat->dokumanNoGoster() }}<br>
                Yayınlanma Tarihi: {{ $talimat->yayinTarihiGoster() }}<br>
                Revizyon No: {{ $talimat->revizyon_no }}<br>
                Revizyon Tarihi: {{ $talimat->revizyon_tarihi?->format('d.m.Y') }}
            </td>
        </tr>
    </table>
</div>
<div class="sayfano"></div>

@if ($talimat->bolumluMu())
    @php $bolumler = $talimat->doluBolumler(); @endphp
    <h1>{{ $talimat->baslik }}</h1>

    @foreach ($bolumler as $i => $b)
        <h2>{{ $i + 1 }}. {{ $b['baslik'] }}</h2>
        @if (filled($b['aciklama'] ?? null))
            <p>{!! nl2br(e($b['aciklama'])) !!}</p>
        @endif
        @if (! empty($b['maddeler']))
            <ul>
                @foreach ($b['maddeler'] as $m)
                    <li>{!! nl2br(e($m)) !!}</li>
                @endforeach
            </ul>
        @endif
    @endforeach

    <div class="kapanis">
    <h2>{{ count($bolumler) + 1 }}. TAAHHÜT</h2>
    <div class="taahhut girintili">
        @foreach (preg_split('/\R/u', $talimat->taahhutMetni()) as $paragraf)
            <p>{{ $paragraf }}</p>
        @endforeach
    </div>
@else
    @if ($talimat->kkdler)
        <p class="kkd"><strong>Kullanılacak kişisel koruyucu donanımlar:</strong> {{ implode(', ', $talimat->kkdler) }}</p>
    @endif

    @forelse ($talimat->maddeler ?? [] as $i => $madde)
        <p class="madde">{{ $i + 1 }}. {!! nl2br(e($madde)) !!}</p>
    @empty
        <p style="color:#888">Madde eklenmedi.</p>
    @endforelse

    <div class="kapanis">
    <div class="taahhut">
        @foreach (preg_split('/\R/u', $talimat->taahhutMetni()) as $paragraf)
            <p>{{ $paragraf }}</p>
        @endforeach
    </div>
@endif

<table class="imza">
    <tr>
        <td style="width:33.33%">
            <div class="rol">TEBLİĞ EDEN (HAZIRLAYAN)</div>
            <div class="satir">Ad Soyad : {{ $imzali ? $tebligEden->ad : '' }}</div>
            <div class="satir">Unvan : {{ $tebligEden->unvan }}@if ($firma?->igu?->sertifika_no) ({{ $firma->igu->sertifika_no }})@endif</div>
            <div class="satir">Tarih :</div>
            <div class="satir">İmza :</div>
            @php
                $kaseYol = $tebligEden->kase_gorseli ? storage_path('app/public/'.$tebligEden->kase_gorseli) : null;
                $imzaYol = $tebligEden->imza_gorseli ? storage_path('app/public/'.$tebligEden->imza_gorseli) : null;
            @endphp
            @if ($imzali && (($kaseYol && is_file($kaseYol)) || ($imzaYol && is_file($imzaYol))))
                <div>
                    @if ($kaseYol && is_file($kaseYol))<img src="{{ $kaseYol }}">@endif
                    @if ($imzaYol && is_file($imzaYol))<img src="{{ $imzaYol }}">@endif
                </div>
            @endif
        </td>
        <td style="width:33.33%">
            <div class="rol">ONAYLAYAN (İŞVEREN / VEKİLİ)</div>
            <div class="satir">Ad Soyad : {{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '') }}</div>
            <div class="satir">Unvan : İşveren / İşveren Vekili</div>
            <div class="satir">Tarih :</div>
            <div class="satir">Kaşe / İmza :</div>
        </td>
        <td style="width:33.33%">
            <div class="rol">TEBELLÜĞ EDEN (ÇALIŞAN)</div>
            <div class="satir">Ad Soyad : {{ $tebellugAlan['ad_soyad'] ?? '' }}</div>
            <div class="satir">T.C. Kimlik No : {{ $tebellugAlan['tc'] ?? '' }}</div>
            <div class="satir">Görevi : {{ $tebellugAlan['gorev'] ?? '' }}</div>
            <div class="satir">Tarih : {{ $tebellugAlan['tarih'] ?? '' }}</div>
            <div class="satir">İmza :</div>
            @if (! empty($tebellugAlan['imza'] ?? null) && (str_starts_with((string) $tebellugAlan['imza'], 'data:image/') || is_file((string) $tebellugAlan['imza'])))
                <div><img src="{{ $tebellugAlan['imza'] }}"></div>
            @endif
        </td>
    </tr>
</table>
    </div>{{-- .kapanis: taahhüt + imza aynı sayfada --}}

@if ($tebellugSatiri ?? 0)
    {{-- Çok kişilik tebellüğ: firmada kalemle doldurulur (kullanıcı 15.10.2026) --}}
    <div style="page-break-before: always">
        <h2 style="text-align:center;font-size:12px">TALİMAT TEBELLÜĞ İMZA LİSTESİ</h2>
        <p style="font-size:9px;margin:4px 0 8px">
            <strong>{{ $firma?->unvan }}</strong> — "{{ $talimat->baslik }}" talimatı aşağıda imzası bulunan çalışanlara
            okunarak / anlatılarak tebliğ edilmiş, bir nüshası teslim edilmiştir.
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:9px">
            <thead style="display:table-header-group">
                <tr>
                    @foreach (['Sıra' => '5%', 'Adı Soyadı' => '24%', 'T.C. Kimlik No' => '17%', 'Görevi' => '20%', 'Tarih' => '13%', 'İmza' => '21%'] as $baslik => $gen)
                        <th style="border:1px solid #555;background:#eee;padding:4px;width:{{ $gen }}">{{ $baslik }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @for ($i = 1; $i <= $tebellugSatiri; $i++)
                <tr style="page-break-inside:avoid">
                    <td style="border:1px solid #555;height:24px;text-align:center">{{ $i }}</td>
                    <td style="border:1px solid #555"></td><td style="border:1px solid #555"></td>
                    <td style="border:1px solid #555"></td><td style="border:1px solid #555"></td>
                    <td style="border:1px solid #555"></td>
                </tr>
            @endfor
            </tbody>
        </table>
    </div>
@endif

</body>
</html>
