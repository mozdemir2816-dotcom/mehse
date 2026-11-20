<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    /* Çoklu DÖF Raporu (A4 yatay) — 02.11.2026 yerleşim düzeltmesi: başlık en üstte, sabit sütun
       genişlikleri (table-layout: fixed), tüm hücreler üste hizalı, tablo başlığı her sayfada tekrar,
       tek temiz imza bloğu (kaşe görseli basılmaz — görseldeki küçük yazı okunmuyor ve metinle mükerrerdi). */
    @page { margin: 16px 26px 18px 26px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }

    .baslik { width: 100%; text-align: center; border-bottom: 3px double #111; padding-bottom: 6px; margin: 0 0 7px; }
    .baslik h1 { font-size: 15px; margin: 0 0 2px; letter-spacing: .2px; }
    .baslik .firma { font-size: 11px; }

    table.kunye { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.2px; margin: 0 0 7px; }
    table.kunye td { border: 1px solid #999; padding: 3px 6px; vertical-align: top; }
    table.kunye td.e { background: #f0f0f0; font-weight: bold; width: 15%; }

    table.maddeler { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8.6px; margin: 0; }
    table.maddeler thead { display: table-header-group; }
    table.maddeler tr { page-break-inside: avoid; }
    table.maddeler th, table.maddeler td { border: 1px solid #999; padding: 3px 5px; text-align: left; vertical-align: top; word-wrap: break-word; }
    table.maddeler.son-satir td { border-top: none; }
    /* Başlıklar tek satır, ortalı (02.11.2026: Durum / Foto başlığı kırılıyordu) */
    table.maddeler thead th { background: #f0f0f0; font-size: 9.2px; white-space: nowrap; vertical-align: middle; text-align: center; padding: 4px; }
    table.maddeler td.sira { text-align: center; font-weight: bold; }
    table.maddeler td.orta { text-align: center; }
    table.maddeler td.varsayilan { color: #555; font-style: italic; }
    table.maddeler td.foto { text-align: center; color: #888; font-size: 7.5px; }
    table.maddeler img.satir-foto { width: 100%; max-height: 48px; }
    .oncelik-kritik { color: #b91c1c; font-weight: bold; }
    .oncelik-yuksek { color: #dc2626; }
    .oncelik-orta { color: #d97706; }
    .oncelik-dusuk { color: #16a34a; }

    /* Son madde + imza bloğu birlikte tutulur — imza hiçbir zaman tek başına yeni sayfaya düşmez */
    .son-blok { page-break-inside: avoid; }
    table.imza { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px; page-break-inside: avoid; }
    table.imza td { width: 50%; padding: 0 30px; text-align: center; vertical-align: top; font-size: 9.2px; line-height: 1.35; }
    table.imza .cizgi { height: 28px; border-bottom: 1px solid #111; margin-bottom: 4px; }
    table.imza .ad { font-weight: bold; font-size: 10px; }
    table.imza .kucuk { color: #555; font-size: 8.2px; }

    .foto-sayfa { page-break-before: always; }
    .foto-sayfa-alt { font-size: 9px; font-weight: bold; color: #333; margin: 0 0 6px; text-align: center; }
    table.foto-izgara { width: 100%; border-collapse: separate; border-spacing: 6px; table-layout: fixed; }
    table.foto-izgara td.foto-kart { width: 25%; border: 1px solid #bbb; border-radius: 4px; padding: 5px; vertical-align: top; background: #fafafa; }
    table.foto-izgara td.foto-bos { width: 25%; border: none; background: transparent; }
    .foto-kutu { text-align: center; background: #fff; border: 1px solid #e5e7eb; padding: 3px; margin-bottom: 4px; }
    .foto-kutu img { max-width: 100%; width: auto; height: auto; display: block; margin: 0 auto; }
    .foto-kutu.tek-satir { height: 270px; }
    .foto-kutu.tek-satir img { max-height: 264px; }
    .foto-kutu.cift-satir { height: 210px; }
    .foto-kutu.cift-satir img { max-height: 204px; }
    .foto-baslik { font-size: 8px; font-weight: bold; color: #111; margin-bottom: 2px; }
    .foto-aciklama { font-size: 7.4px; color: #333; line-height: 1.25; }
</style>
</head>
<body>
@php
    $gy = $rapor->gozetimYapanBilgisi();
    $isveren = $rapor->isverenAdi();
    $maddeler = array_values($rapor->maddeler ?? []);
    $fotoYolu = fn (array $m) => \Illuminate\Support\Facades\Storage::disk('public')->path((string) ($m['foto_yolu'] ?? ''));
    $fotoVar = fn (array $m) => filled($m['foto_yolu'] ?? null) && is_file($fotoYolu($m));
@endphp

<div class="baslik">
    <h1>ÇOKLU DÜZELTİCİ ÖNLEYİCİ FAALİYET (DÖF) RAPORU</h1>
    <div class="firma">{{ $firma?->unvan }}</div>
</div>

<table class="kunye">
    <tr>
        <td class="e">Belge No</td><td>{{ $rapor->belge_no }}</td>
        <td class="e">Rapor Tarihi</td><td>{{ $rapor->rapor_tarihi?->format('d.m.Y') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">Alan / Bölge</td><td>{{ $rapor->alanBolgeMetni() }}</td>
        <td class="e">Gözetim Tarih Aralığı</td><td>{{ $rapor->gozetimAraligiMetni() }}</td>
    </tr>
    <tr>
        <td class="e">Gözetim Yapan</td><td>{{ $gy['ad'] ? $gy['ad'].' ('.$gy['kisa_unvan'].')' : $gy['unvan'] }}</td>
        <td class="e">Sertifika No</td><td>{{ $gy['belge_no'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">Sorumlu Kişi</td><td colspan="3">{{ $rapor->sorumluKisiAdi() ?: '—' }}</td>
    </tr>
</table>

@php
    $maddeSayisi = count($maddeler);
    $ustMaddeler = $maddeSayisi > 1 ? array_slice($maddeler, 0, $maddeSayisi - 1, true) : [];
    $sonMaddeIndex = $maddeSayisi > 0 ? $maddeSayisi - 1 : null;
    $sonMadde = $sonMaddeIndex !== null ? $maddeler[$sonMaddeIndex] : null;
@endphp

@if ($maddeSayisi > 1)
<table class="maddeler">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:26%">Tespit</th>
            <th style="width:10%">Öncelik</th>
            <th style="width:22%">Öneri / Düzeltici Faaliyet</th>
            <th style="width:12%">Sorumlu</th>
            <th style="width:10%">Termin</th>
            <th style="width:8%">Durum</th>
            <th style="width:8%">Foto</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ustMaddeler as $i => $m)
            <tr>
                <td class="sira">{{ $i + 1 }}</td>
                <td>{{ $m['tespit'] ?? '' }}</td>
                <td class="oncelik-{{ $m['oncelik'] ?? 'orta' }}">{{ \App\Models\DofRaporu::oncelikEtiketi($m['oncelik'] ?? null) }}</td>
                <td>{!! nl2br(e($m['oneri'] ?? '—')) !!}</td>
                <td class="{{ blank($m['sorumlu'] ?? null) ? 'varsayilan' : '' }}">{{ \App\Models\DofRaporu::maddeSorumlusu($m) }}</td>
                <td class="orta {{ blank($m['termin'] ?? null) ? 'varsayilan' : '' }}">{{ $rapor->maddeTermini($m) }}</td>
                <td>{{ \App\Models\DofRaporu::durumEtiketi($m['durum'] ?? null) }}</td>
                <td class="foto">
                    @if ($fotoVar($m))
                        <img class="satir-foto" src="{{ $fotoYolu($m) }}">
                    @else
                        Görsel yok
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="son-blok">
    <table class="maddeler {{ $maddeSayisi > 1 ? 'son-satir' : '' }}">
        @if ($maddeSayisi <= 1)
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:26%">Tespit</th>
                    <th style="width:10%">Öncelik</th>
                    <th style="width:22%">Öneri / Düzeltici Faaliyet</th>
                    <th style="width:12%">Sorumlu</th>
                    <th style="width:10%">Termin</th>
                    <th style="width:8%">Durum</th>
                    <th style="width:8%">Foto</th>
                </tr>
            </thead>
        @endif
        <tbody>
            @if ($sonMadde !== null)
                <tr>
                    <td class="sira" style="width:4%">{{ $sonMaddeIndex + 1 }}</td>
                    <td style="width:26%">{{ $sonMadde['tespit'] ?? '' }}</td>
                    <td class="oncelik-{{ $sonMadde['oncelik'] ?? 'orta' }}" style="width:10%">{{ \App\Models\DofRaporu::oncelikEtiketi($sonMadde['oncelik'] ?? null) }}</td>
                    <td style="width:22%">{!! nl2br(e($sonMadde['oneri'] ?? '—')) !!}</td>
                    <td class="{{ blank($sonMadde['sorumlu'] ?? null) ? 'varsayilan' : '' }}" style="width:12%">{{ \App\Models\DofRaporu::maddeSorumlusu($sonMadde) }}</td>
                    <td class="orta {{ blank($sonMadde['termin'] ?? null) ? 'varsayilan' : '' }}" style="width:10%">{{ $rapor->maddeTermini($sonMadde) }}</td>
                    <td style="width:8%">{{ \App\Models\DofRaporu::durumEtiketi($sonMadde['durum'] ?? null) }}</td>
                    <td class="foto" style="width:8%">
                        @if ($fotoVar($sonMadde))
                            <img class="satir-foto" src="{{ $fotoYolu($sonMadde) }}">
                        @else
                            Görsel yok
                        @endif
                    </td>
                </tr>
            @else
                <tr><td colspan="8" style="color:#888">Madde eklenmedi.</td></tr>
            @endif
        </tbody>
    </table>

    <table class="imza">
        <tr>
            <td>
                <div class="cizgi"></div>
                <div class="ad">{{ $gy['ad'] ?: '.............................' }}</div>
                <div>{{ $gy['unvan'] }}</div>
                @if ($gy['belge_no'])<div>Belge No: {{ $gy['belge_no'] }}</div>@endif
                <div class="kucuk">(İmza – Kaşe)</div>
            </td>
            <td>
                <div class="cizgi"></div>
                <div class="ad">{{ $isveren ?: '.............................' }}</div>
                <div>İşveren / İşveren Vekili</div>
                <div class="kucuk">(İmza – Kaşe)</div>
            </td>
        </tr>
    </table>
</div>

{{-- Fotoğraf kanıtı sayfaları: sayfa başına en fazla 8 fotoğraf (4×2 ızgara) — gereksiz sayfa israfını önler --}}
@php
    $fotoListesi = [];
    foreach ($maddeler as $i => $m) {
        if ($fotoVar($m)) {
            $fotoListesi[] = [
                'no' => count($fotoListesi) + 1,
                'madde_no' => $i + 1,
                'tespit' => (string) ($m['tespit'] ?? ''),
                'oncelik' => $m['oncelik'] ?? 'orta',
                'yol' => $fotoYolu($m),
            ];
        }
    }
    $fotoSayfalari = array_chunk($fotoListesi, 8);
    $toplamFotoSayfa = count($fotoSayfalari);
    $toplamFoto = count($fotoListesi);
@endphp
@foreach ($fotoSayfalari as $sayfaIndex => $sayfaFotolari)
    @php
        $tekSatir = count($sayfaFotolari) <= 4;
        $satirlar = array_chunk($sayfaFotolari, 4);
    @endphp
    <div class="foto-sayfa">
        <div class="baslik">
            <h1>ÇOKLU DÜZELTİCİ ÖNLEYİCİ FAALİYET (DÖF) RAPORU</h1>
            <div class="firma">{{ $firma?->unvan }}</div>
        </div>
        <div class="foto-sayfa-alt">
            FOTOĞRAF KANITLARI ({{ $sayfaIndex + 1 }} / {{ $toplamFotoSayfa }}) · Toplam {{ $toplamFoto }} Fotoğraf
        </div>
        <table class="foto-izgara">
            @foreach ($satirlar as $satir)
                <tr>
                    @foreach ($satir as $f)
                        <td class="foto-kart">
                            <div class="foto-kutu {{ $tekSatir ? 'tek-satir' : 'cift-satir' }}">
                                <img src="{{ $f['yol'] }}">
                            </div>
                            <div class="foto-baslik">
                                FOTOĞRAF KANITI {{ $f['no'] }} · Madde {{ $f['madde_no'] }}
                                <span class="oncelik-{{ $f['oncelik'] }}">({{ \App\Models\DofRaporu::oncelikEtiketi($f['oncelik']) }})</span>
                            </div>
                            <div class="foto-aciklama">
                                {{ \Illuminate\Support\Str::limit($f['tespit'], 115) }}
                            </div>
                        </td>
                    @endforeach
                    @for ($bos = count($satir); $bos < 4; $bos++)
                        <td class="foto-bos"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </div>
@endforeach

</body>
</html>
