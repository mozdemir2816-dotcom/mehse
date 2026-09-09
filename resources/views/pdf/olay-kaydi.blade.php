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
    .kunye td.k { background: #f0f0f0; font-weight: bold; width: 20%; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #7c3aed; border-bottom: 1px solid #7c3aed; padding-bottom: 3px; }
    p.metin { font-size: 10px; text-align: justify; margin: 0 0 6px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.liste th { background: #f0f0f0; }
    ol.nedenler { margin: 2px 0 8px; padding-left: 18px; font-size: 10px; }
    ol.nedenler li { margin-bottom: 3px; }
    .rozet { display: inline-block; padding: 1px 6px; border: 1px solid #7c3aed; border-radius: 8px; font-size: 9px; }
    .imza { margin-top: 28px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 38px; border-top: 1px solid #111; font-size: 9.5px; }
    .imza img { max-height: 42px; display: block; margin: 0 auto -32px auto; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>OLAY KAYIT VE İNCELEME FORMU</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td class="k">Belge No</td><td>{{ $kayit->belge_no }}</td>
            <td class="k">Olay Tipi</td><td>{{ $kayit->tipEtiketi() }}</td>
        </tr>
        <tr>
            <td class="k">Olay Tarihi / Saati</td><td>{{ $kayit->olay_tarihi?->format('d.m.Y') ?: '—' }} {{ $kayit->olay_saati }}</td>
            <td class="k">Olay Yeri</td><td>{{ $kayit->olay_yeri ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Bildiren</td><td>{{ $kayit->bildiren_ad_soyad ?: '—' }}</td>
            <td class="k">Bildirim Tarihi</td><td>{{ $kayit->bildirim_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Etkilenen Kişi</td><td>{{ $kayit->etkilenen_ad_soyad ?: '—' }} {{ $kayit->etkilenen_gorev ? '('.$kayit->etkilenen_gorev.')' : '' }}</td>
            <td class="k">Etkilenen Unsurlar</td><td>{{ implode(', ', $kayit->etkilenenEtiketleri()) ?: '—' }}</td>
        </tr>
    </table>

    <h2>1. OLAY ÖZETİ</h2>
    <p class="metin">{{ $kayit->olay_ozeti ?: '—' }}</p>

    <h2>2. SINIFLANDIRMA VE POTANSİYEL RİSK</h2>
    <table class="liste">
        <tr>
            <th style="width:25%">Sonuç Türü</th>
            <th style="width:25%">Olasılık</th>
            <th style="width:25%">Şiddet</th>
            <th style="width:25%">Potansiyel Risk</th>
        </tr>
        <tr>
            <td>{{ $kayit->sonucEtiketi() }}</td>
            <td>{{ $kayit->olasilikEtiketi() }}</td>
            <td>{{ $kayit->siddetEtiketi() }}</td>
            <td><span class="rozet">{{ $kayit->potansiyel_skor ?? '—' }} — {{ $kayit->potansiyelSeviye() }}</span></td>
        </tr>
    </table>

    <h2>3. KÖK NEDEN ANALİZİ — 5 NEDEN (5N)</h2>
    @if ($kayit->nedenZinciri())
        <ol class="nedenler">
            @foreach ($kayit->nedenZinciri() as $neden)
                <li>{{ $neden }}</li>
            @endforeach
        </ol>
    @else
        <p class="metin">5N zinciri girilmedi.</p>
    @endif

    @if ($kayit->kokNedenEtiketleri())
        <p class="metin"><strong>Kök Neden Kategorileri:</strong> {{ implode(', ', $kayit->kokNedenEtiketleri()) }}</p>
    @endif
    <p class="metin"><strong>Belirlenen Kök Neden:</strong> {{ $kayit->kok_neden ?: '—' }}</p>

    <h2>4. DÜZELTİCİ / ÖNLEYİCİ FAALİYET</h2>
    <p class="metin">{{ $kayit->duzeltici_faaliyet ?: '—' }}</p>
    @if ($kayit->dofRaporu)
        <p class="metin"><strong>İlgili DÖF Raporu:</strong> {{ $kayit->dofRaporu->belge_no }}</p>
    @endif

    @if ($kayit->isKazasiMi())
        <h2>5. İŞ KAZASI / MESLEK HASTALIĞI BİLDİRİMİ</h2>
        <table class="liste">
            <tr><th style="width:34%">SGK Bildirimi</th><th style="width:33%">Kolluk Bildirimi</th><th style="width:33%">Kayıp Gün Sayısı</th></tr>
            <tr>
                <td>{{ $kayit->sgk_bildirimi_yapildi ? 'Yapıldı '.($kayit->sgk_bildirim_tarihi ? '('.$kayit->sgk_bildirim_tarihi->format('d.m.Y').')' : '') : 'Yapılmadı' }}</td>
                <td>{{ $kayit->kolluk_bildirimi_yapildi ? 'Yapıldı' : 'Yapılmadı / Gerekmiyor' }}</td>
                <td>{{ $kayit->kayip_gun_sayisi ?? '—' }}</td>
            </tr>
        </table>
    @endif

    <h2>TANIKLAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görevi</th></tr>
        @forelse (($kayit->taniklar ?? []) as $i => $t)
            <tr><td>{{ $i + 1 }}</td><td>{{ $t['ad_soyad'] ?? '' }}</td><td>{{ $t['gorev'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">Tanık belirtilmedi.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>
                @if ($kayit->rapor_hazirlayan_kase)
                    <img src="{{ storage_path('app/public/'.$kayit->rapor_hazirlayan_kase) }}">
                @endif
                {{ $kayit->rapor_hazirlayan ?: 'İSG Uzmanı' }}<br>(İmza – Kaşe)
            </td>
            <td>İşveren / İşveren Vekili<br>(İmza – Kaşe)</td>
        </tr>
    </table>

    <p class="yasal">
        6331 Sayılı İş Sağlığı ve Güvenliği Kanunu m.14 uyarınca; işveren, iş kazalarının ve
        meslek hastalıklarının yanı sıra "ramak kala" olaylarını da kayıt altına alır ve
        gerekli incelemeleri yaparak raporlarını düzenler.
    </p>
</div>

@foreach (($kayit->fotograflar ?? []) as $i => $foto)
    <div class="sayfa foto-sayfa">
        <div class="baslik">
            <h1>OLAY KAYIT VE İNCELEME FORMU</h1>
            <div style="font-size:11px">{{ $firma?->unvan }} — Fotoğraf {{ $i + 1 }}</div>
        </div>
        <img src="{{ storage_path('app/public/'.$foto) }}">
    </div>
@endforeach

</body>
</html>
