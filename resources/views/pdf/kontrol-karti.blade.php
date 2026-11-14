@php
    use App\Models\KontrolKarti;
    use App\Models\SahaAnalizi;

    $maddeler = $kart->maddeler ?? [];
    $sayim = KontrolKarti::sayim($maddeler);
    $uygunsuzlar = collect($maddeler)->map(fn ($m, $i) => $m + ['no' => $i + 1])->where('durum', KontrolKarti::UYGUNSUZ)->values();

    $igu = $firma?->igu;
    $iguKase = $igu?->kase_gorseli ?: $firma?->user?->kase_gorseli;
    $iguImza = $igu?->imza_gorseli ?: $firma?->user?->imza_gorseli;
    $gorsel = fn (?string $yol) => $yol && is_file(storage_path('app/public/'.$yol)) ? storage_path('app/public/'.$yol) : null;

    // Pano renkleri — durum yalnız renkle değil, işaret + metinle de okunur.
    $renk = ['uygun' => '#00704A', 'uygunsuz' => '#B3122E', 'kapsam_disi' => '#6B7280'];
    $sembol = ['uygun' => '✓', 'uygunsuz' => '✗', 'kapsam_disi' => '–'];
    $bantKutusu = function (?float $skor) {
        $b = SahaAnalizi::fineKinneyBandi($skor);

        return $b ? ['ad' => $b['ad'], 'renk' => $b['renk'], 'koyu' => in_array($b['renk'], ['#84cc16', '#22c55e', '#f59e0b'], true)] : null;
    };
    $sayi = fn ($v) => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
@endphp
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 30px 30px 118px 30px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #22272B; font-size: 8.4px; }
    .ust { background: #22272B; color: #fff; padding: 9px 12px; }
    .ust .ad { font-size: 14px; font-weight: bold; }
    .ust .alt { font-size: 8px; color: #cfd4da; margin-top: 2px; }
    table { border-collapse: collapse; }
    table.kunye { width: 100%; margin-top: 6px; }
    table.kunye td { border: 1px solid #cfd4dc; padding: 4px 6px; }
    table.kunye td.e { background: #f1f3f5; font-weight: bold; width: 15%; }
    h2 { font-size: 9.5px; margin: 12px 0 5px; padding: 0 0 3px; border-bottom: 2px solid #22272B; }
    table.liste { width: 100%; }
    table.liste td { border-bottom: 1px solid #e3e6ea; padding: 3.5px 4px; vertical-align: top; }
    .no { width: 14px; height: 12px; padding-top: 2px; color: #fff; font-size: 7px; font-weight: bold; text-align: center; }
    .no.daire { border-radius: 7px; }
    .no.kare { border-radius: 2px; }
    .durum { font-weight: bold; white-space: nowrap; }
    .ozet { margin-top: 6px; font-size: 8px; }
    .ozet span { margin-right: 10px; }
    .madde { margin-top: 9px; page-break-inside: avoid; border: 1px solid #cfd4dc; }
    .madde .bas { background: #f1f3f5; padding: 5px 7px; border-left: 4px solid #B3122E; }
    .madde .bas .t { font-weight: bold; font-size: 9px; }
    .madde .bas .r { font-size: 7.3px; color: #444; margin-top: 2px; }
    table.risk { width: 100%; }
    table.risk th { background: #22272B; color: #fff; font-size: 7px; padding: 3px; border: 1px solid #22272B; }
    table.risk td { border: 1px solid #cfd4dc; padding: 4px; font-size: 7.6px; vertical-align: middle; }
    table.risk td.s { text-align: center; width: 5%; }
    .bant { display: block; text-align: center; font-weight: bold; padding: 3px 2px; font-size: 7.3px; }
    .alt-serit { position: fixed; bottom: -92px; left: 0; right: 0; height: 86px; }
    .alt-serit table { width: 100%; }
    .alt-serit td { width: 50%; text-align: center; vertical-align: bottom; font-size: 7.5px; padding: 0 30px; }
    .alt-serit .baslik { font-weight: bold; font-size: 8px; margin-bottom: 2px; }
    .alt-serit .gorseller { height: 44px; }
    .alt-serit .gorseller img { max-height: 44px; max-width: 110px; margin: 0 3px; }
    .alt-serit .rol { border-top: 1px solid #22272B; padding-top: 2px; color: #444; }
</style>
</head>
<body>

<div class="alt-serit">
    <table>
        <tr>
            <td>
                <div class="baslik">KONTROL EDEN</div>
                <div class="gorseller">
                    @if ($imzali)
                        @if ($p = $gorsel($iguKase))<img src="{{ $p }}">@endif
                        @if ($p = $gorsel($iguImza))<img src="{{ $p }}">@endif
                    @endif
                </div>
                <div class="rol">İş Güvenliği Uzmanı — Kaşe / İmza</div>
            </td>
            <td>
                <div class="baslik">ONAYLAYAN</div>
                <div class="gorseller">
                    @if ($imzali)
                        @if ($p = $gorsel($firma?->isveren_kase_gorseli))<img src="{{ $p }}">@endif
                        @if ($p = $gorsel($firma?->isveren_imza_gorseli))<img src="{{ $p }}">@endif
                    @endif
                </div>
                <div class="rol">İşveren / İşveren Vekili — Kaşe / İmza</div>
            </td>
        </tr>
    </table>
</div>

<div class="ust">
    <div class="ad">{{ mb_strtoupper($kart->alan_adi ?: $kart->sablonAdi(), 'UTF-8') }} — KONTROL KARTI</div>
    <div class="alt">{{ $firma?->unvan }}</div>
</div>

<table class="kunye">
    <tr>
        <td class="e">Belge No</td><td>{{ $kart->belge_no }}</td>
        <td class="e">Kontrol Tarihi</td><td>{{ $kart->kontrol_tarihi?->format('d.m.Y') ?? $kart->belge_tarihi?->format('d.m.Y') }}</td>
    </tr>
    <tr>
        <td class="e">Kontrol Listesi</td><td>{{ $kart->sablonAdi() }}</td>
        <td class="e">Konum / Bölüm</td><td>{{ $kart->konum ?: '—' }}</td>
    </tr>
    @if (filled($kart->kontrol_eden))
        <tr><td class="e">Kontrol Eden</td><td colspan="3">{{ $kart->kontrol_eden }}</td></tr>
    @endif
</table>

<h2>Fotoğraf ve kontrol listesi</h2>

<table style="width:100%">
    <tr>
        @if ($foto)
            <td style="width:{{ $foto['genislik'] + 10 }}px;vertical-align:top;padding-right:10px">
                {{-- İşaretler KontrolKartiUretici::isaretliFoto ile fotoğrafa çizili gelir. --}}
                <img src="{{ $foto['yol'] }}" style="width:{{ $foto['genislik'] }}px;height:{{ $foto['yukseklik'] }}px">
            </td>
        @endif
        <td style="vertical-align:top">
            <table class="liste">
                @foreach ($maddeler as $i => $m)
                    @php $d = $m['durum'] ?? null; @endphp
                    <tr>
                        <td style="width:16px"><div class="no {{ $d === 'uygunsuz' || ! $d ? 'daire' : 'kare' }}" style="background:{{ $renk[$d] ?? '#0057A8' }}">{{ $i + 1 }}</div></td>
                        <td>
                            <strong>{{ $m['baslik'] }}</strong><br>
                            <span style="color:#555;font-size:7.3px">{{ $m['kontrol'] }}</span>
                        </td>
                        <td class="durum" style="width:62px;color:{{ $renk[$d] ?? '#6B7280' }}">
                            {{ $d ? $sembol[$d].' '.KontrolKarti::DURUMLAR[$d] : 'Bakılmadı' }}
                        </td>
                    </tr>
                @endforeach
            </table>
            <div class="ozet">
                <span style="color:#00704A"><strong>✓ {{ $sayim['uygun'] }}</strong> uygun</span>
                <span style="color:#B3122E"><strong>✗ {{ $sayim['uygunsuz'] }}</strong> uygunsuz</span>
                @if ($sayim['kapsam_disi'])<span style="color:#6B7280"><strong>– {{ $sayim['kapsam_disi'] }}</strong> kapsam dışı</span>@endif
                @if ($sayim['bos'])<span style="color:#6B7280"><strong>{{ $sayim['bos'] }}</strong> bakılmadı</span>@endif
            </div>
        </td>
    </tr>
</table>

@if ($uygunsuzlar->isNotEmpty())
    <h2>Uygunsuzluklar ve risk değerlendirmesi (Fine-Kinney)</h2>

    @foreach ($uygunsuzlar as $m)
        @php
            $skor = KontrolKarti::skor($m);
            $yeni = KontrolKarti::yeniSkor($m);
            $bant = $bantKutusu($skor);
            $yeniBant = $bantKutusu($yeni);
        @endphp
        <div class="madde">
            <div class="bas">
                <div class="t">{{ $m['no'] }}. {{ $m['baslik'] }} — {{ $m['tespit'] }}</div>
                @if (filled($m['yonetmelik'] ?? null) || filled($m['standart'] ?? null))
                    <div class="r">
                        @if (filled($m['yonetmelik'] ?? null))<strong>Yönetmelik:</strong> {{ $m['yonetmelik'] }}@endif
                        @if (filled($m['yonetmelik'] ?? null) && filled($m['standart'] ?? null)) &nbsp;|&nbsp; @endif
                        @if (filled($m['standart'] ?? null))<strong>Standart:</strong> {{ $m['standart'] }}@endif
                    </div>
                @endif
            </div>
            <table class="risk">
                <tr>
                    <th style="width:22%">Risk (olası sonuç)</th>
                    <th style="width:15%">Etkilenenler</th>
                    <th style="width:18%">Mevcut önlem</th>
                    <th>O</th><th>F</th><th>Ş</th><th>Skor</th>
                    <th style="width:14%">Seviye</th>
                </tr>
                <tr>
                    <td>{{ $m['olasi_sonuc'] ?: '—' }}</td>
                    <td>{{ $m['etkilenenler'] ?: '—' }}</td>
                    <td>{{ $m['mevcut_onlem'] ?: '—' }}</td>
                    <td class="s">{{ $sayi($m['olasilik'] ?? null) }}</td>
                    <td class="s">{{ $sayi($m['frekans'] ?? null) }}</td>
                    <td class="s">{{ $sayi($m['siddet'] ?? null) }}</td>
                    <td class="s"><strong>{{ $sayi($skor) }}</strong></td>
                    <td style="padding:0">@if ($bant)<span class="bant" style="background:{{ $bant['renk'] }};color:{{ $bant['koyu'] ? '#22272B' : '#fff' }}">{{ $bant['ad'] }}</span>@endif</td>
                </tr>
                <tr>
                    <th colspan="2">Ek önlem / aksiyon</th>
                    <th>Sorumlu · hedef süre</th>
                    <th>Yeni O</th><th>Yeni F</th><th>Yeni Ş</th><th>Yeni skor</th>
                    <th>Yeni seviye</th>
                </tr>
                <tr>
                    <td colspan="2">{{ $m['oneri'] ?: '—' }}</td>
                    <td>{{ $m['sorumlu'] ?: '—' }}@if (filled($m['hedef_gun'] ?? null))<br><strong>{{ $m['hedef_gun'] }} gün</strong>@endif</td>
                    <td class="s">{{ $sayi($m['yeni_olasilik'] ?? null) }}</td>
                    <td class="s">{{ $sayi($m['yeni_frekans'] ?? null) }}</td>
                    <td class="s">{{ $sayi($m['yeni_siddet'] ?? null) }}</td>
                    <td class="s"><strong>{{ $sayi($yeni) }}</strong></td>
                    <td style="padding:0">@if ($yeniBant)<span class="bant" style="background:{{ $yeniBant['renk'] }};color:{{ $yeniBant['koyu'] ? '#22272B' : '#fff' }}">{{ $yeniBant['ad'] }}</span>@endif</td>
                </tr>
            </table>
        </div>
    @endforeach
@else
    <p style="margin-top:10px;color:#00704A;font-weight:bold">Uygunsuz madde işaretlenmedi.</p>
@endif

<p style="margin-top:10px;font-size:7px;color:#555">
    Fine-Kinney: Risk = Olasılık (O) × Frekans (F) × Şiddet (Ş). Yeni değerler, önerilen ek önlem uygulandıktan sonra beklenen durumu gösterir.
</p>

</body>
</html>
