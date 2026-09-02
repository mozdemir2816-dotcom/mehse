<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 26px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #b45309; border-bottom: 1px solid #b45309; padding-bottom: 3px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    p.metin { font-size: 10px; text-align: justify; margin: 0 0 6px; }
    .tutar-kutu { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 8px; }
    .tutar-kutu td { border: 1px solid #999; padding: 6px 8px; }
    .tutar-kutu td:first-child { background: #f0f0f0; font-weight: bold; width: 55%; }
    .durum { font-weight: bold; }
    .imza { margin-top: 30px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 9.5px; }
    .imza img { max-height: 42px; display: block; margin: 0 auto -34px auto; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞVERENE İDARİ PARA CEZASI (İPC) TEBLİĞ TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Belge No</td><td>{{ $tebligi->belge_no }}</td>
            <td>Tebliğ Tarihi</td><td>{{ $tebligi->teblig_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td>Denetim Tarihi</td><td>{{ $tebligi->denetim_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Tespit Eden Kurum</td><td>{{ $tebligi->tespit_eden_kurum ?: '—' }}</td>
        </tr>
        <tr>
            <td>Müfettiş Adı</td><td colspan="3">{{ $tebligi->mufettis_adi ?: '—' }}</td>
        </tr>
    </table>

    <h2>İHLAL EDİLEN HÜKÜMLER</h2>
    <table class="liste">
        <tr><th style="width:32%">Başlık</th><th>Açıklama</th></tr>
        @forelse (($tebligi->ihlaller ?? []) as $ih)
            <tr><td>{{ $ih['baslik'] ?? '' }}</td><td>{{ $ih['aciklama'] ?? '' }}</td></tr>
        @empty
            <tr><td colspan="2" style="color:#888">İhlal belirtilmedi.</td></tr>
        @endforelse
    </table>
    @if ($tebligi->serbest_ihlal_metni)
        <p class="metin"><strong>Ek Açıklama:</strong> {{ $tebligi->serbest_ihlal_metni }}</p>
    @endif

    <h2>CEZA TUTARI VE ÖDEME</h2>
    <table class="tutar-kutu">
        <tr><td>İdari Para Cezası Tutarı</td><td>{{ $tebligi->ceza_tutari !== null ? number_format((float) $tebligi->ceza_tutari, 2, ',', '.').' TL' : '—' }}</td></tr>
        <tr><td>Peşin Ödeme Tutarı (%25 indirimli)</td><td>{{ $tebligi->pesin_odeme_tutari !== null ? number_format((float) $tebligi->pesin_odeme_tutari, 2, ',', '.').' TL' : '—' }}</td></tr>
        <tr><td>Ödeme Durumu</td><td class="durum">{{ $tebligi->odeme_yapildi ? 'Ödendi' : 'Ödenmedi' }}</td></tr>
        <tr><td>İtiraz Durumu</td><td class="durum">{{ $tebligi->itiraz_edildi ? 'İtiraz Edildi' : 'İtiraz Edilmedi' }}</td></tr>
    </table>
    @if ($tebligi->itiraz_notu)
        <p class="metin"><strong>İtiraz Notu:</strong> {{ $tebligi->itiraz_notu }}</p>
    @endif

    <table class="imza">
        <tr>
            <td>
                @if ($tebligi->hazirlayan_kase)
                    <img src="{{ storage_path('app/public/'.$tebligi->hazirlayan_kase) }}">
                @endif
                {{ $tebligi->hazirlayan ?: 'İSG Uzmanı' }}<br>(Hazırlayan – Kaşe)
            </td>
            <td>İşveren / İşveren Vekili<br>(Tebellüğ – İmza)</td>
        </tr>
    </table>

    <p class="yasal">
        Bu karara karşı tebliğ tarihinden itibaren 15 gün içinde yetkili Sulh Ceza Hakimliği'ne
        itiraz edilebilir (5326 sayılı Kabahatler Kanunu m.27). Cezanın tebliğ tarihinden itibaren
        15 gün içinde peşin ödenmesi halinde %25 indirim uygulanır (Kabahatler Kanunu m.17/6).
    </p>

</div>
</body>
</html>
