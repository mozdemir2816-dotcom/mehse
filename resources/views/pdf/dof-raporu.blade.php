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
    table.maddeler th, table.maddeler td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.maddeler th { background: #f0f0f0; }
    .oncelik-kritik { color: #b91c1c; font-weight: bold; }
    .oncelik-yuksek { color: #dc2626; }
    .oncelik-orta { color: #d97706; }
    .oncelik-dusuk { color: #16a34a; }
    .imza { margin-top: 26px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 9.5px; }
    .imza img { max-height: 40px; display: block; margin: 0 auto 4px; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
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
            <td>Alan / Bölge</td><td>{{ $rapor->alan_bolge ?: '—' }}</td>
            <td>Gözetim Tarih Aralığı</td><td>{{ $rapor->gozetim_tarih_araligi ?: '—' }}</td>
        </tr>
        <tr>
            <td>Gözetim Yapan</td><td>{{ $rapor->gozetim_yapan ?: '—' }}</td>
            <td>Sertifika No</td><td>{{ $rapor->gozetim_yapan_sertifika_no ?: '—' }}</td>
        </tr>
        <tr>
            <td>Sorumlu Kişi</td><td colspan="3">{{ $rapor->sorumlu_kisi ?: '—' }}</td>
        </tr>
    </table>

    <table class="maddeler">
        <tr>
            <th style="width:4%">#</th>
            <th style="width:26%">Tespit</th>
            <th style="width:8%">Öncelik</th>
            <th style="width:26%">Öneri / Düzeltici Faaliyet</th>
            <th style="width:14%">Sorumlu</th>
            <th style="width:10%">Termin</th>
            <th style="width:12%">Durum</th>
        </tr>
        @forelse (($rapor->maddeler ?? []) as $i => $m)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $m['tespit'] ?? '' }}</td>
                <td class="oncelik-{{ $m['oncelik'] ?? 'orta' }}">{{ \App\Models\DofRaporu::oncelikEtiketi($m['oncelik'] ?? null) }}</td>
                <td>{{ $m['oneri'] ?? '—' }}</td>
                <td>{{ $m['sorumlu'] ?? '—' }}</td>
                <td>{{ ! empty($m['termin']) ? \Illuminate\Support\Carbon::parse($m['termin'])->format('d.m.Y') : '—' }}</td>
                <td>{{ \App\Models\DofRaporu::durumEtiketi($m['durum'] ?? null) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="color:#888">Madde eklenmedi.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>
                @if ($rapor->gozetim_yapan_kase)
                    <img src="{{ storage_path('app/public/'.$rapor->gozetim_yapan_kase) }}">
                @endif
                {{ $rapor->gozetim_yapan ?: 'Gözetim Yapan (İSG Uzmanı)' }}
            </td>
            <td>{{ $rapor->isveren_vekili_adi ?: 'İşveren / İşveren Vekili' }}<br>(İmza – Kaşe)</td>
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
