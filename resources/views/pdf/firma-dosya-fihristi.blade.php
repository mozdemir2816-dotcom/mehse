{{-- Firma İSG Dosyası Fihristi — TEDBİR ON "Firma Çalışma Merkezi" referansı.
     Sol menüdeki 12 kategoriye göre gruplanmış, numaralı içindekiler sayfası. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 28px 32px; }
    body { margin: 0; color: #111; font-size: 9.5px; line-height: 1.4; }
    .baslik { text-align: center; border-bottom: 3px double #4338ca; padding-bottom: 8px; margin-bottom: 14px; }
    .baslik h1 { font-size: 14px; margin: 0 0 3px; color: #4338ca; }
    .baslik .firma { font-size: 10.5px; font-weight: bold; }
    .baslik .alt { font-size: 8.5px; color: #666; margin-top: 2px; }
    h2 { font-size: 10px; margin: 12px 0 4px; color: #fff; background: #4338ca; padding: 3px 7px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 2px; }
    table.liste td, table.liste th { border: 1px solid #ccc; padding: 3px 6px; }
    table.liste th { background: #eef2ff; font-size: 8.5px; text-align: left; }
    td.no { width: 22px; text-align: center; color: #666; }
    td.durum { width: 90px; text-align: center; font-weight: bold; }
    td.vade { width: 80px; text-align: center; font-size: 8.5px; }
    .d-tamam { color: #16a34a; }
    .d-yakin { color: #d97706; }
    .d-eksik { color: #b91c1c; }
    .ozet { margin-top: 14px; font-size: 9px; border-top: 1px solid #ccc; padding-top: 8px; }
    .altbilgi { margin-top: 18px; font-size: 8px; color: #666; }
</style>
</head>
<body>

<div class="baslik">
    <h1>İSG DOSYASI FİHRİSTİ (İÇİNDEKİLER)</h1>
    <div class="firma">{{ $firma->unvan }}</div>
    <div class="alt">{{ $firma->tehlikeSinifiEtiketi() }} · {{ $firma->calisan_sayisi ?? 0 }} çalışan · Basım tarihi: {{ now()->format('d.m.Y') }}</div>
</div>

@php $sira = 1; @endphp
@foreach ($gruplu as $kategori => $satirlar)
    <h2>{{ $kategori }}</h2>
    <table class="liste">
        <tr>
            <th style="width:22px">#</th>
            <th>Belge / Kayıt</th>
            <th style="width:90px">Durum</th>
            <th style="width:80px">Vade Tarihi</th>
        </tr>
        @foreach ($satirlar as $s)
            <tr>
                <td class="no">{{ $sira++ }}</td>
                <td>{{ $s['ad'] }}@if (! $s['hazir']) <span style="color:#999;font-size:8px"> (modül henüz yok)</span>@endif</td>
                <td class="durum d-{{ $s['durum'] === 'tamamlandi' ? 'tamam' : $s['durum'] }}">
                    {{ match ($s['durum']) { 'tamamlandi' => 'TAMAM', 'yakin' => 'YAKLAŞIYOR', default => 'EKSİK' } }}
                </td>
                <td class="vade">{{ $s['vade_tarihi']?->format('d.m.Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@endforeach

@php
    $tumSatirlar = $gruplu->flatten(1);
    $toplam = $tumSatirlar->count();
    $tamam = $tumSatirlar->where('durum', 'tamamlandi')->count();
@endphp
<div class="ozet">
    <strong>Genel Durum:</strong> {{ $tamam }} / {{ $toplam }} kalem tamam
    ({{ $toplam > 0 ? round($tamam / $toplam * 100) : 0 }}%).
</div>

<div class="altbilgi">
    Bu fihrist, işyerinin İSG dosyasında bulunması gereken belge ve kayıtların özet listesidir;
    mevzuat gereği fiilen dosyada saklanması gereken ıslak imzalı/kaşeli nüshaların yerini tutmaz.
</div>

</body>
</html>
