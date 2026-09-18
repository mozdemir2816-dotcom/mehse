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
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 25%; }
    h2 { font-size: 10.5px; margin: 10px 0 4px; color: #0e7490; border-bottom: 1px solid #0e7490; padding-bottom: 2px; }
    p.metin { font-size: 9.5px; text-align: justify; margin: 0 0 5px; }
    .sure-kutu { width: 100%; border-collapse: collapse; font-size: 10.5px; margin: 8px 0; }
    .sure-kutu td { border: 2px solid #{{ $form->suresiGectiMi() ? 'b91c1c' : '0e7490' }}; padding: 8px; text-align: center; font-weight: bold; }
    .sure-kutu.gecti td { color: #b91c1c; }
    .imza { margin-top: 26px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 38px; border-top: 1px solid #111; font-size: 9px; }
    .imza img { max-height: 40px; display: block; margin: 0 auto -32px auto; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>MESLEK HASTALIĞI BİLDİRİM KAYIT FORMU</h1>
        <div class="ek">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu m.14 — SGK'ya Bildirim Yükümlülüğü</div>
        <div style="font-size:10px;margin-top:3px">{{ $firma?->unvan }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td>Belge No</td><td>{{ $form->belge_no }}</td>
            <td>Öğrenme Tarihi</td><td>{{ $form->ogrenme_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td>Ad Soyad</td><td>{{ $form->calisan_ad_soyad ?: '—' }}</td>
            <td>T.C. Kimlik No</td><td>{{ $form->calisan_tc ?: '—' }}</td>
        </tr>
        <tr>
            <td>Görevi</td><td>{{ $form->calisan_gorevi ?: '—' }}</td>
            <td>Öğrenme Kaynağı</td><td>{{ $form->ogrenmeKaynagiEtiketi() }}</td>
        </tr>
    </table>

    <h2>TANI BİLGİLERİ</h2>
    <table class="kunye">
        <tr>
            <td>Tanı Koyan Hastane</td><td>{{ $form->tani_hastane ?: '—' }}</td>
            <td>Tanı Tarihi</td><td>{{ $form->tani_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td>Sağlık Kurulu Rapor No</td><td>{{ $form->saglik_kurulu_rapor_no ?: '—' }}</td>
            <td>Meslekte K.G.K. Kaybı %</td><td>{{ $form->meslekte_kazanma_gucu_kaybi_yuzde ?: '—' }}</td>
        </tr>
    </table>
    <p class="metin"><strong>Meslek Hastalığı Tanısı:</strong> {{ $form->meslek_hastaligi_tanisi ?: '—' }}</p>

    <h2>SGK BİLDİRİM SÜRESİ</h2>
    <table class="sure-kutu {{ $form->suresiGectiMi() ? 'gecti' : '' }}">
        <tr><td>
            SGK'ya Bildirim Son Tarihi: {{ $form->bildirim_son_tarihi?->format('d.m.Y') ?: '—' }}
            (öğrenme tarihinden itibaren 3 iş günü)
            @if ($form->suresiGectiMi()) — SÜRE GEÇTİ, ACİLEN BİLDİRİM YAPIN @endif
        </td></tr>
    </table>

    <h2>SGK BİLDİRİM DURUMU</h2>
    <table class="kunye">
        <tr>
            <td>Bildirim Yapıldı mı</td><td>{{ $form->sgk_bildirimi_yapildi ? 'Evet' : 'Hayır' }}</td>
            <td>Bildirim Tarihi</td><td>{{ $form->sgk_bildirim_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td>Bildirim Yöntemi</td>
            <td>{{ $form->sgk_bildirim_yontemi ? config('isg.meslek_hastaligi.bildirim_yontemleri.'.$form->sgk_bildirim_yontemi, $form->sgk_bildirim_yontemi) : '—' }}</td>
            <td>SGK Sağlık Kurulu Durumu</td><td>{{ $form->sgkKurulOnayDurumuEtiketi() }}</td>
        </tr>
    </table>

    @if ($form->notlar)
        <h2>NOTLAR</h2>
        <p class="metin">{{ $form->notlar }}</p>
    @endif

    <table class="imza">
        <tr>
            <td>
                @if ($firma?->isyeriHekimi?->kase_gorseli)
                    <img src="{{ storage_path('app/public/'.$firma->isyeriHekimi->kase_gorseli) }}">
                @endif
                {{ $firma?->isyeriHekimi?->ad_soyad ?: 'İşyeri Hekimi' }}<br>(İşyeri Hekimi – Kaşe/İmza)
            </td>
            <td>İşveren / İşveren Vekili<br>(Kaşe/İmza)</td>
        </tr>
    </table>

</div>
</body>
</html>
