<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10.5px; }
    .sayfa { padding: 24px 30px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 14px; margin: 0 0 3px; }
    .baslik .ek { font-size: 9px; color: #666; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 10px; }
    .kunye td { border: 1px solid #999; padding: 4px 7px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 20%; }
    h2 { font-size: 10.5px; margin: 10px 0 4px; color: #0e7490; border-bottom: 1px solid #0e7490; padding-bottom: 2px; }
    p.metin { font-size: 9.5px; text-align: justify; margin: 0 0 5px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 6px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 3px 5px; text-align: left; }
    table.liste th { background: #f0f0f0; }
    .anormal { color: #b91c1c; font-weight: bold; }
    .sonuc-kutu { width: 100%; border-collapse: collapse; font-size: 10.5px; margin: 8px 0; }
    .sonuc-kutu td { border: 2px solid #0e7490; padding: 8px; text-align: center; font-weight: bold; }
    .imza { margin-top: 26px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 38px; border-top: 1px solid #111; font-size: 9px; }
    .imza img { max-height: 40px; display: block; margin: 0 auto -32px auto; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞE GİRİŞ / PERİYODİK MUAYENE FORMU</h1>
        <div class="ek">EK-2 — İşyeri Hekimi ve Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik</div>
        <div style="font-size:10px;margin-top:3px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Belge No</td><td>{{ $form->belge_no }}</td>
            <td>Muayene Tarihi</td><td>{{ $form->muayene_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td>Ad Soyad</td><td>{{ $form->calisan_ad_soyad ?: '—' }}</td>
            <td>T.C. Kimlik No</td><td>{{ $form->calisan_tc ?: '—' }}</td>
        </tr>
        <tr>
            <td>Doğum Tarihi</td><td>{{ $form->calisan_dogum_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Görevi</td><td>{{ $form->calisan_gorevi ?: '—' }}</td>
        </tr>
        <tr>
            <td>İşe Giriş Tarihi</td><td>{{ $form->ise_giris_tarihi?->format('d.m.Y') ?: '—' }}</td>
            <td>Muayene Türü</td><td>{{ $form->muayeneTuruEtiketi() }}</td>
        </tr>
    </table>

    <h2>MESLEK ÖYKÜSÜ VE MARUZİYET</h2>
    <p class="metin"><strong>Meslek Öyküsü:</strong> {{ $form->meslek_oykusu ?: '—' }}</p>
    <p class="metin"><strong>Maruz Kalınan Riskler:</strong> {{ $form->maruz_kalinan_riskler ?: '—' }}</p>

    <h2>ÖZGEÇMİŞ VE SOYGEÇMİŞ</h2>
    <p class="metin"><strong>Özgeçmiş:</strong> {{ $form->ozgecmis ?: '—' }}</p>
    <p class="metin"><strong>Soygeçmiş:</strong> {{ $form->soygecmis ?: '—' }}</p>

    <h2>SİSTEMİK MUAYENE BULGULARI</h2>
    <table class="liste">
        <tr><th style="width:28%">Sistem</th><th style="width:14%">Sonuç</th><th>Not</th></tr>
        @foreach (($form->sistemik_muayene ?? []) as $s)
            <tr>
                <td>{{ $s['baslik'] ?? '' }}</td>
                <td class="{{ ($s['sonuc'] ?? '') === 'anormal' ? 'anormal' : '' }}">{{ ($s['sonuc'] ?? '') === 'anormal' ? 'Anormal' : 'Normal' }}</td>
                <td>{{ $s['not'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>LABORATUVAR / TETKİK SONUÇLARI</h2>
    <table class="liste">
        <tr><th style="width:28%">Tetkik</th><th style="width:12%">Yapıldı</th><th style="width:14%">Sonuç</th><th>Not</th></tr>
        @foreach (($form->tetkikler ?? []) as $t)
            <tr>
                <td>{{ config('isg.muayene.tetkikler.'.($t['anahtar'] ?? ''), $t['anahtar'] ?? '') }}</td>
                <td>{{ ($t['yapildi'] ?? false) ? 'Evet' : 'Hayır' }}</td>
                <td class="{{ ($t['sonuc'] ?? '') === 'anormal' ? 'anormal' : '' }}">{{ $t['sonuc'] === 'anormal' ? 'Anormal' : ($t['sonuc'] === 'normal' ? 'Normal' : '—') }}</td>
                <td>{{ $t['not'] ?? '' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>SONUÇ VE KANAAT</h2>
    <table class="sonuc-kutu">
        <tr><td>{{ $form->sonucKanaatiEtiketi() }}</td></tr>
    </table>
    @if ($form->sart_aciklamasi)
        <p class="metin"><strong>Şart Açıklaması:</strong> {{ $form->sart_aciklamasi }}</p>
    @endif
    <p class="metin"><strong>Önerilen Bir Sonraki Kontrol Tarihi:</strong> {{ $form->onerilen_kontrol_tarihi?->format('d.m.Y') ?: '—' }}</p>

    <table class="imza">
        <tr>
            <td>
                @if ($form->hekim_kase)
                    <img src="{{ storage_path('app/public/'.$form->hekim_kase) }}">
                @endif
                {{ $form->hekim_adi ?: 'İşyeri Hekimi' }}<br>(Muayeneyi Yapan Hekim – Kaşe/İmza)
            </td>
            <td>{{ $form->calisan_ad_soyad ?: '' }}<br>(Muayene Olan – İmza)</td>
        </tr>
    </table>

</div>
</body>
</html>
