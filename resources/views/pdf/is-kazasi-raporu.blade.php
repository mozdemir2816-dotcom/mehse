<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 26px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 16px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 12px; }
    .kunye td { border: 1px solid #999; padding: 5px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 20%; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #dc2626; border-bottom: 1px solid #dc2626; padding-bottom: 3px; }
    p.metin { font-size: 10px; text-align: justify; margin: 0 0 6px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    .imza { margin-top: 30px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 9.5px; }
    .imza img { max-height: 42px; display: block; margin: 0 auto -34px auto; }
    .sgk-durum { font-weight: bold; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞ KAZASI İNCELEME RAPORU</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Belge No</td><td>{{ $rapor->belge_no }}</td>
            <td>Kaza Tarihi / Saati</td><td>{{ $rapor->kaza_tarihi?->format('d.m.Y') ?: '—' }} {{ $rapor->kaza_saati ?: '' }}</td>
        </tr>
        <tr>
            <td>Kazazede</td><td>{{ $rapor->kazazede_ad_soyad ?: '—' }}</td>
            <td>T.C. Kimlik No</td><td>{{ $rapor->kazazede_tc ?: '—' }}</td>
        </tr>
        <tr>
            <td>Görevi</td><td>{{ $rapor->kazazede_gorev ?: '—' }}</td>
            <td>Kaza Yeri</td><td>{{ $rapor->kaza_yeri ?: '—' }}</td>
        </tr>
        <tr>
            <td>Kaza Türü</td><td>{{ $rapor->kazaTuruEtiketi() }}</td>
            <td>Ağırlık Derecesi</td><td>{{ $rapor->agirlikDerecesiEtiketi() }}</td>
        </tr>
        <tr>
            <td>Kayıp Gün Sayısı</td><td>{{ $rapor->kayip_gun_sayisi ?? '—' }}</td>
            <td>SGK Bildirimi</td>
            <td class="sgk-durum">
                {{ $rapor->sgk_bildirimi_yapildi ? 'Yapıldı' : 'Yapılmadı' }}
                @if ($rapor->sgk_bildirimi_yapildi && $rapor->sgk_bildirim_tarihi)
                    ({{ $rapor->sgk_bildirim_tarihi->format('d.m.Y') }})
                @endif
            </td>
        </tr>
    </table>

    <h2>KAZA TANIMI</h2>
    <p class="metin">{{ $rapor->kaza_tanimi ?: '—' }}</p>

    <h2>KAZA NASIL OLDU</h2>
    <p class="metin">{{ $rapor->kaza_nasil_oldu ?: '—' }}</p>

    <h2>KÖK NEDEN ANALİZİ</h2>
    @if ($rapor->kokNedenEtiketleri())
        <p class="metin"><strong>Kategoriler:</strong> {{ implode(', ', $rapor->kokNedenEtiketleri()) }}</p>
    @endif
    <p class="metin">{{ $rapor->kaza_nedeni ?: '—' }}</p>

    <h2>ALINAN / ALINACAK ÖNLEMLER</h2>
    <p class="metin">{{ $rapor->alinan_onlemler ?: '—' }}</p>

    <h2>TANIKLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görevi</th></tr>
        @forelse (($rapor->taniklar ?? []) as $i => $t)
            <tr><td>{{ $i + 1 }}</td><td>{{ $t['ad_soyad'] ?? '' }}</td><td>{{ $t['gorev'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">Tanık belirtilmedi.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>
                @if ($rapor->rapor_hazirlayan_kase)
                    <img src="{{ storage_path('app/public/'.$rapor->rapor_hazirlayan_kase) }}">
                @endif
                {{ $rapor->rapor_hazirlayan ?: 'İSG Uzmanı' }}<br>(İmza – Kaşe)
            </td>
            <td>İşveren / İşveren Vekili<br>(İmza – Kaşe)</td>
        </tr>
    </table>

    <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu m.14 uyarınca düzenlenmiştir.</p>

</div>
</body>
</html>
