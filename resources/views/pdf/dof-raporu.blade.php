<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    /* Çoklu DÖF Raporu (A4 yatay) — 02.11.2026 yerleşim düzeltmesi: başlık en üstte, sabit sütun
       genişlikleri (table-layout: fixed), tüm hücreler üste hizalı, tablo başlığı her sayfada tekrar,
       tek temiz imza bloğu (kaşe görseli basılmaz — görseldeki küçük yazı okunmuyor ve metinle mükerrerdi). */
    @page { margin: 22px 30px 26px 30px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }

    .baslik { width: 100%; text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin: 0 0 10px; }
    .baslik h1 { font-size: 15px; margin: 0 0 3px; letter-spacing: .2px; }
    .baslik .firma { font-size: 11px; }

    table.kunye { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.5px; margin: 0 0 10px; }
    table.kunye td { border: 1px solid #999; padding: 4px 7px; vertical-align: top; }
    table.kunye td.e { background: #f0f0f0; font-weight: bold; width: 15%; }

    table.maddeler { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8.8px; margin: 0 0 10px; }
    table.maddeler thead { display: table-header-group; }
    table.maddeler tr { page-break-inside: avoid; }
    table.maddeler th, table.maddeler td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; word-wrap: break-word; }
    table.maddeler th { background: #f0f0f0; font-size: 8.6px; }
    table.maddeler td.sira { text-align: center; font-weight: bold; }
    table.maddeler td.orta { text-align: center; }
    table.maddeler td.varsayilan { color: #555; font-style: italic; }
    table.maddeler td.foto { text-align: center; color: #888; font-size: 7.5px; }
    table.maddeler img.satir-foto { width: 100%; max-height: 60px; }
    .oncelik-kritik { color: #b91c1c; font-weight: bold; }
    .oncelik-yuksek { color: #dc2626; }
    .oncelik-orta { color: #d97706; }
    .oncelik-dusuk { color: #16a34a; }

    table.imza { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 26px; page-break-inside: avoid; }
    table.imza td { width: 50%; padding: 0 30px; text-align: center; vertical-align: top; font-size: 9.5px; line-height: 1.5; }
    table.imza .cizgi { height: 40px; border-bottom: 1px solid #111; margin-bottom: 5px; }
    table.imza .ad { font-weight: bold; font-size: 10.5px; }
    table.imza .kucuk { color: #555; font-size: 8.5px; }

    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 10.5px; font-weight: bold; margin: 0 0 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 640px; }
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

<table class="maddeler">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:28%">Tespit</th>
            <th style="width:10%">Öncelik</th>
            <th style="width:24%">Öneri / Düzeltici Faaliyet</th>
            <th style="width:14%">Sorumlu</th>
            <th style="width:10%">Termin</th>
            <th style="width:5%">Durum</th>
            <th style="width:5%">Foto</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($maddeler as $i => $m)
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
        @empty
            <tr><td colspan="8" style="color:#888">Madde eklenmedi.</td></tr>
        @endforelse
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

{{-- Fotoğraf kanıtı sayfaları: yalnız diskte gerçekten bulunan fotoğraflar --}}
@php $fotoNo = 0; @endphp
@foreach ($maddeler as $i => $m)
    @if ($fotoVar($m))
        @php $fotoNo++; @endphp
        <div class="foto-sayfa">
            <div class="baslik">
                <h1>ÇOKLU DÜZELTİCİ ÖNLEYİCİ FAALİYET (DÖF) RAPORU</h1>
                <div class="firma">{{ $firma?->unvan }}</div>
            </div>
            <div class="baslik2">FOTOĞRAF KANITI {{ $fotoNo }} · Madde {{ $i + 1 }} — {{ $m['tespit'] ?? '' }}</div>
            <img src="{{ $fotoYolu($m) }}">
        </div>
    @endif
@endforeach

</body>
</html>
