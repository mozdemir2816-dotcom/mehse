<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 24px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 4px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 16%; }
    table.maddeler { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 10px; }
    table.maddeler th, table.maddeler td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: middle; }
    table.maddeler td.orta { text-align: center; }
    table.maddeler td.varsayilan { color: #555; font-style: italic; }
    table.maddeler th { background: #f0f0f0; }
    table.maddeler td.foto { text-align: center; vertical-align: middle; color: #888; font-size: 8px; }
    table.maddeler img.satir-foto { max-width: 100%; max-height: 75px; }
    .oncelik-kritik { color: #b91c1c; font-weight: bold; }
    .oncelik-yuksek { color: #dc2626; }
    .oncelik-orta { color: #d97706; }
    .oncelik-dusuk { color: #16a34a; }
    .imza { margin-top: 22px; width: 100%; border-collapse: separate; border-spacing: 24px 0; page-break-inside: avoid; }
    .imza td { width: 50%; text-align: center; vertical-align: bottom; font-size: 9.5px; line-height: 1.45; }
    .imza .alan { height: 46px; border-bottom: 1px solid #111; margin-bottom: 4px; }
    .imza .alan img { max-height: 44px; }
    .imza .ad { font-weight: bold; font-size: 10.5px; }
    .imza .kucuk { color: #555; font-size: 8.5px; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
@php
    $gy = $rapor->gozetimYapanBilgisi();
    $isveren = $rapor->isverenAdi();
@endphp
<div class="sayfa">

    <div class="baslik">
        <h1>ÇOKLU DÜZELTİCİ ÖNLEYİCİ FAALİYET (DÖF) RAPORU</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Belge No</td><td>{{ $rapor->belge_no }}</td>
            <td>Rapor Tarihi</td><td>{{ $rapor->rapor_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td>Alan / Bölge</td><td>{{ $rapor->alanBolgeMetni() }}</td>
            <td>Gözetim Tarih Aralığı</td><td>{{ $rapor->gozetimAraligiMetni() }}</td>
        </tr>
        <tr>
            <td>Gözetim Yapan</td><td>{{ $gy['ad'] ? $gy['ad'].' ('.$gy['kisa_unvan'].')' : $gy['unvan'] }}</td>
            <td>Sertifika No</td><td>{{ $gy['belge_no'] ?: '—' }}</td>
        </tr>
        <tr>
            <td>Sorumlu Kişi</td><td colspan="3">{{ $rapor->sorumluKisiAdi() ?: '—' }}</td>
        </tr>
    </table>

    <table class="maddeler">
        <tr>
            <th style="width:4%">#</th>
            <th style="width:22%">Tespit</th>
            <th style="width:7%">Öncelik</th>
            <th style="width:22%">Öneri / Düzeltici Faaliyet</th>
            <th style="width:12%">Sorumlu</th>
            <th style="width:9%">Termin</th>
            <th style="width:10%">Durum</th>
            <th style="width:14%">Foto</th>
        </tr>
        @forelse (($rapor->maddeler ?? []) as $i => $m)
            <tr>
                <td class="orta">{{ $i + 1 }}</td>
                <td>{{ $m['tespit'] ?? '' }}</td>
                <td class="oncelik-{{ $m['oncelik'] ?? 'orta' }}">{{ \App\Models\DofRaporu::oncelikEtiketi($m['oncelik'] ?? null) }}</td>
                <td>{!! nl2br(e($m['oneri'] ?? '—')) !!}</td>
                <td class="{{ blank($m['sorumlu'] ?? null) ? 'varsayilan' : '' }}">{{ \App\Models\DofRaporu::maddeSorumlusu($m) }}</td>
                <td class="orta {{ blank($m['termin'] ?? null) ? 'varsayilan' : '' }}">{{ $rapor->maddeTermini($m) }}</td>
                <td>{{ \App\Models\DofRaporu::durumEtiketi($m['durum'] ?? null) }}</td>
                <td class="foto">
                    @if (! empty($m['foto_yolu']) && is_file(storage_path('app/public/'.$m['foto_yolu'])))
                        <img class="satir-foto" src="{{ storage_path('app/public/'.$m['foto_yolu']) }}">
                    @else
                        Görsel yok
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="color:#888">Madde eklenmedi.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>
                <div class="alan">
                    @if ($rapor->gozetim_yapan_kase && ($imzali ?? true) && is_file(storage_path('app/public/'.$rapor->gozetim_yapan_kase)))
                        <img src="{{ storage_path('app/public/'.$rapor->gozetim_yapan_kase) }}">
                    @endif
                </div>
                <div class="ad">{{ $gy['ad'] ?: '.............................' }}</div>
                <div>{{ $gy['unvan'] }}</div>
                @if ($gy['belge_no'])<div>Belge No: {{ $gy['belge_no'] }}</div>@endif
                <div class="kucuk">(İmza – Kaşe)</div>
            </td>
            <td>
                <div class="alan"></div>
                <div class="ad">{{ $isveren ?: '.............................' }}</div>
                <div>İşveren / İşveren Vekili</div>
                <div class="kucuk">(İmza – Kaşe)</div>
            </td>
        </tr>
    </table>

</div>

@php $fotoNo = 0; @endphp
@foreach (($rapor->maddeler ?? []) as $i => $m)
    @if (! empty($m['foto_yolu']))
        @php $fotoNo++; @endphp
        <div class="sayfa foto-sayfa">
            <div class="baslik">
                <h1>ÇOKLU DÜZELTİCİ ÖNLEYİCİ FAALİYET (DÖF) RAPORU</h1>
                <div style="font-size:11px">{{ $firma?->unvan }}</div>
            </div>
            <div class="baslik2">FOTOĞRAF KANITI {{ $fotoNo }} · Madde {{ $i + 1 }} — {{ $m['tespit'] ?? '' }}</div>
            <img src="{{ storage_path('app/public/'.$m['foto_yolu']) }}">
        </div>
    @endif
@endforeach

</body>
</html>
