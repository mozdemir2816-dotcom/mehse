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
    .kunye td:nth-child(odd) { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #b45309; border-bottom: 1px solid #b45309; padding-bottom: 3px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    p.metin { font-size: 10px; text-align: justify; margin: 0 0 6px; }
    .tutar-kutu { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 8px; }
    .tutar-kutu td { border: 1px solid #999; padding: 6px 8px; }
    .tutar-kutu td:first-child { background: #f0f0f0; font-weight: bold; width: 55%; }
    .durum { font-weight: bold; }
    .imza { margin-top: 24px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .imza td { width: 50%; text-align: center; padding: 6px 10px; font-size: 9.5px; vertical-align: bottom; }
    .imza .alan { height: 42px; border-bottom: 1px solid #111; margin: 0 18px 4px; }
    .imza img { max-height: 40px; display: block; margin: 0 auto 2px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İDARİ PARA CEZASI (İPC) BİLGİLENDİRME VE TAKİP TUTANAĞI</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
        <div style="font-size:8.5px;color:#555;margin-top:2px">6331 SK m.26/2 gereği resmî İPC kararı Çalışma ve İş Kurumu İl Müdürlüğünce verilir; bu tutanak işveren içi bilgilendirme ve takip amaçlıdır.</div>
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
                <div class="alan">
                    @if (($imzali ?? true) && $tebligi->hazirlayan_kase && is_file(storage_path('app/public/'.$tebligi->hazirlayan_kase)))
                        <img src="{{ storage_path('app/public/'.$tebligi->hazirlayan_kase) }}">
                    @endif
                </div>
                İSG Uzmanı<br>(Hazırlayan – Kaşe)
            </td>
            <td>
                <div class="alan"></div>
                İşveren / İşveren Vekili<br>(Bilgilendirildi – İmza)
            </td>
        </tr>
    </table>

    <p class="yasal">
        6331 sayılı İSG Kanunu m.26/2 uyarınca idari para cezaları resmî tebliğinden itibaren <strong>30 gün içinde</strong> ödenir.
        Resmî tebliğ tarihinden itibaren <strong>15 gün içinde</strong> yetkili Sulh Ceza Hakimliği'ne itiraz edilebilir (5326 sayılı Kabahatler Kanunu m.27).
        Kanun yoluna başvurmadan önce (15 gün içinde) peşin ödenmesi halinde %25 indirim uygulanır; <strong>peşin ödeme, kişinin bu karara karşı kanun yoluna başvurma hakkını etkilemez</strong> (5326 SK m.17/6).
    </p>

</div>
</body>
</html>
