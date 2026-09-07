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
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
    .kunye td { border: 1px solid #999; padding: 4px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 22%; }
    h2 { font-size: 11.5px; margin: 12px 0 5px; color: #ef4444; border-bottom: 1px solid #ef4444; padding-bottom: 3px; }
    p.metin { font-size: 10px; text-align: justify; margin: 0 0 8px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    .imza { margin-top: 30px; width: 100%; }
    .imza td { width: 33.33%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 9.5px; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa .baslik2 { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>TATBİKAT TUTANAĞI — {{ mb_strtoupper($tutanak->senaryoEtiketi(), 'UTF-8') }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Tatbikat Tarihi</td><td>{{ $tutanak->tatbikat_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Tatbikat Yeri</td><td>{{ $tutanak->tatbikat_yeri ?: '—' }}</td>
        </tr>
        <tr>
            <td>Başlama / Bitiş</td><td>{{ $tutanak->baslama_saati ?: '—' }} / {{ $tutanak->bitis_saati ?: '—' }}</td>
            <td>Tahliye Süresi</td><td>{{ $tutanak->tahliye_dk ?: '—' }} dk</td>
        </tr>
        <tr>
            <td>İşveren / Vekili</td><td>{{ $tutanak->isveren_vekili ?: '—' }}</td>
            <td>İş Güvenliği Uzmanı</td><td>{{ $tutanak->is_guvenligi_uzmani ?: '—' }}</td>
        </tr>
        <tr>
            <td>Tatbikat Koordinatörü</td><td>{{ $tutanak->tatbikat_koordinatoru ?: '—' }}</td>
            <td>Niteliği</td>
            <td>{{ $tutanak->haberli_tatbikat ? 'Haberli' : 'Habersiz' }}, {{ $tutanak->yillik_plan_dahilinde ? 'Yıllık plan dahilinde' : 'Plan dışı' }}</td>
        </tr>
    </table>

    <h2>SENARYO</h2>
    <p class="metin">{{ $tutanak->senaryo_metni ?: '—' }}</p>

    <h2>GÖREV ALAN EKİPLER</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Ekip</th></tr>
        @forelse (($tutanak->ekipler ?? []) as $i => $e)
            <tr><td>{{ $i + 1 }}</td><td>{{ $e['ad_soyad'] ?? '' }}</td><td>{{ $e['ekip'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">Ekip üyesi eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>DEĞERLENDİRME KONTROL LİSTESİ</h2>
    <table class="liste">
        <tr><th>Soru</th><th style="width:15%">Cevap</th></tr>
        @forelse (($tutanak->degerlendirmeler ?? []) as $d)
            <tr><td>{{ $d['soru'] ?? '' }}</td><td>{{ config('isg.tatbikat.degerlendirme_secenekleri.'.($d['cevap'] ?? ''), '—') }}</td></tr>
        @empty
            <tr><td colspan="2" style="color:#888">Değerlendirme yapılmadı.</td></tr>
        @endforelse
    </table>

    <h2>GÖZLEM VE GENEL DEĞERLENDİRME</h2>
    <p class="metin">{{ $tutanak->gozlem ?: '—' }}</p>

    <h2>TESPİT EDİLEN EKSİKLİKLER</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Eksiklik</th></tr>
        @forelse (($tutanak->eksiklikler ?? []) as $i => $e)
            <tr><td>{{ $i + 1 }}</td><td>{{ $e }}</td></tr>
        @empty
            <tr><td colspan="2" style="color:#888">Eksiklik tespit edilmedi.</td></tr>
        @endforelse
    </table>

    <h2>YAPILACAK DÜZENLEMELER (DÖF)</h2>
    <table class="liste">
        <tr><th>Faaliyet</th><th>Sorumlu</th><th style="width:15%">Tarih</th></tr>
        @forelse (($tutanak->dof_onerileri ?? []) as $d)
            <tr><td>{{ $d['faaliyet'] ?? '' }}</td><td>{{ $d['sorumlu'] ?? '—' }}</td><td>{{ $d['tarih'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="3" style="color:#888">Düzenleme önerisi eklenmedi.</td></tr>
        @endforelse
    </table>

    <h2>KATILIMCILAR</h2>
    <table class="liste">
        <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>T.C. No</th><th>Görevi</th></tr>
        @forelse (($tutanak->katilimcilar ?? []) as $i => $k)
            <tr><td>{{ $i + 1 }}</td><td>{{ $k['ad_soyad'] ?? '' }}</td><td>{{ $k['tc'] ?? '—' }}</td><td>{{ $k['gorev'] ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="4" style="color:#888">Katılımcı eklenmedi.</td></tr>
        @endforelse
    </table>

    <table class="imza">
        <tr>
            <td>{{ $tutanak->isveren_vekili ?: 'İşveren / Vekili' }}<br>(İmza – Kaşe)</td>
            <td>{{ $tutanak->is_guvenligi_uzmani ?: 'İş Güvenliği Uzmanı' }}<br>(İmza)</td>
            <td>{{ $tutanak->tatbikat_koordinatoru ?: 'Tatbikat Koordinatörü' }}<br>(İmza)</td>
        </tr>
    </table>

</div>

@foreach (($tutanak->fotograflar ?? []) as $i => $foto)
    <div class="sayfa foto-sayfa">
        <div class="baslik">
            <h1>TATBİKAT TUTANAĞI — {{ mb_strtoupper($tutanak->senaryoEtiketi(), 'UTF-8') }}</h1>
            <div style="font-size:11px">{{ $firma?->unvan }}</div>
        </div>
        <div class="baslik2">FOTOĞRAF {{ $i + 1 }}</div>
        <img src="{{ storage_path('app/public/'.$foto) }}">
    </div>
@endforeach

</body>
</html>
