<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9px; }
    .sayfa { padding: 20px 26px; }
    table.ust { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 9px; }
    table.ust td { border: 1px solid #999; padding: 5px 8px; vertical-align: top; }
    table.ust .baslik-hucre { text-align: center; font-size: 15px; font-weight: bold; }
    table.ust .etiket { font-weight: bold; }
    .derece-legend { font-size: 7.5px; line-height: 1.5; }
    .derece-1 { color: #dc2626; font-weight: bold; }
    .derece-2 { color: #ea580c; font-weight: bold; }
    .derece-3 { color: #d97706; font-weight: bold; }
    .derece-4 { color: #16a34a; font-weight: bold; }
    .firma-banner { text-align: center; font-weight: bold; font-size: 11px; border: 1px solid #999; border-top: none; padding: 6px; margin-bottom: 8px; }
    table.bulgular { width: 100%; border-collapse: collapse; font-size: 8px; }
    table.bulgular th, table.bulgular td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.bulgular th { background: #f0f0f0; text-align: center; }
    table.bulgular td.orta { text-align: center; }
    table.bulgular img { width: 100%; max-height: 70px; object-fit: cover; }
    table.bulgular ol { margin: 0; padding-left: 12px; }
    .imza { margin-top: 20px; width: 40%; }
    .imza img { max-height: 55px; display: block; margin-bottom: 4px; }
    .imza .baslik { font-weight: bold; font-size: 9px; margin-bottom: 4px; }
</style>
</head>
<body>
<div class="sayfa">

    <table class="ust">
        <tr>
            <td style="width:22%">
                <span class="etiket">Gözetim Yapan :</span> {{ $rapor->gozetim_yapan ?: '—' }}<br>
                <span class="etiket">Sorumlu Kişi :</span> {{ $rapor->sorumlu_kisi ?: '—' }}
            </td>
            <td class="baslik-hucre" style="width:34%">İSG SAHA GÖZETİM RAPORU</td>
            <td style="width:22%">
                <span class="etiket">Gözetim Tarih Aralığı :</span> {{ $rapor->gozetim_tarih_araligi ?: '—' }}<br>
                <span class="etiket">Alan / Bölge :</span> {{ $rapor->alan_bolge ?: '—' }}
            </td>
            <td style="width:22%">
                <span class="etiket">İşveren/Vekili :</span> {{ $rapor->isveren_vekili_adi ?: '—' }}<br>
                <span class="etiket">Rapor Tarihi / No :</span> {{ $rapor->rapor_tarihi?->format('d.m.Y') }} / {{ $rapor->belge_no }}
            </td>
            <td style="width:20%">
                <div class="derece-legend">
                    <span class="etiket">Risk Derecesi :</span><br>
                    <span class="derece-1">1. Derece (Çok Yüksek)</span> &nbsp; <span class="derece-3">3. Derece (Orta)</span><br>
                    <span class="derece-2">2. Derece (Yüksek)</span> &nbsp; <span class="derece-4">4. Derece (Düşük)</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="firma-banner">{{ $firma?->unvan }}</div>

    <table class="bulgular">
        <tr>
            <th style="width:3%">Sıra No</th>
            <th style="width:8%">Bina/Bölge Adı</th>
            <th style="width:12%">Saha Gözetimi Uygunsuzluk Fotosu</th>
            <th style="width:22%">Uygunsuzluklar / Tehlikeler / Riskler</th>
            <th style="width:24%">Uygun Hale Getirme (Açıklama)</th>
            <th style="width:12%">Uygun Hale Getirme (Örnek Resim)</th>
            <th style="width:14%">İlgili Yasal Gerekçe</th>
            <th style="width:6%">Risk Derecesi</th>
        </tr>
        @forelse (($rapor->bulgular ?? []) as $i => $b)
            <tr>
                <td class="orta">{{ $i + 1 }}</td>
                <td>{{ $b['bina_bolge'] ?? '—' }}</td>
                <td>
                    @if (! empty($b['foto_yolu']))
                        <img src="{{ storage_path('app/public/'.$b['foto_yolu']) }}">
                    @endif
                </td>
                <td>{{ $b['tespit'] ?? '' }}</td>
                <td>
                    @if (! empty($b['oneriler']))
                        <ol>
                            @foreach ($b['oneriler'] as $oneri)
                                <li>{{ $oneri }}</li>
                            @endforeach
                        </ol>
                    @endif
                </td>
                <td></td>
                <td>{{ $b['yasal_gerekce'] ?? '—' }}</td>
                <td class="orta derece-{{ $b['risk_derecesi'] ?? 3 }}">{{ $b['risk_derecesi'] ?? 3 }}. Derece Risk</td>
            </tr>
        @empty
            <tr><td colspan="8" style="color:#888;text-align:center">Bulgu eklenmedi.</td></tr>
        @endforelse
    </table>

    <div class="imza">
        <div class="baslik">İŞ GÜVENLİĞİ UZMANI</div>
        @if ($rapor->gozetim_yapan_kase)
            <img src="{{ storage_path('app/public/'.$rapor->gozetim_yapan_kase) }}">
        @endif
        {{ $rapor->gozetim_yapan ?: '—' }}<br>İmza
    </div>

</div>
</body>
</html>
