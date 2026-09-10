{{-- İşe Dönüş Belgesi — 6331 s.K. Madde 15, İşyeri Hekimi Yönetmeliği. A4 dikey. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 30px 34px; }
    body { margin: 0; color: #111; font-size: 10px; line-height: 1.45; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 14px; }
    .baslik h1 { font-size: 15px; margin: 0 0 3px; }
    .baslik .firma { font-size: 10px; }
    table.kunye { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 12px; }
    table.kunye td { border: 1px solid #999; padding: 4px 7px; }
    table.kunye td.e { background: #f0f0f0; font-weight: bold; width: 26%; }
    h2 { font-size: 11px; margin: 14px 0 5px; color: #1d4ed8; }
    .kutu { border: 1px solid #999; border-radius: 4px; padding: 8px 10px; font-size: 9.5px; margin-bottom: 8px; }
    ul.kisit { margin: 4px 0 0; padding-left: 16px; font-size: 9.5px; }
    .uygun-tam { color: #15803d; font-weight: bold; }
    .uygun-kisitli { color: #b45309; font-weight: bold; }
    .uygun-uygun_degil { color: #b91c1c; font-weight: bold; }
    .uygun-gecici_gorev { color: #b45309; font-weight: bold; }
    .taahhut { font-size: 8.5px; color: #444; margin-top: 4px; }
    .imza { margin-top: 36px; width: 100%; border-collapse: collapse; }
    .imza td { width: 50%; text-align: center; font-size: 9px; padding: 0 10px; }
    .imza .rol { font-weight: bold; }
    .imza .kutu2 { border: 1px solid #999; height: 70px; margin-top: 4px; position: relative; }
    .imza .kutu2 img { max-height: 62px; max-width: 90%; position: absolute; top: 4px; left: 50%; margin-left: -45%; }
    .yasal { margin-top: 16px; font-size: 8px; color: #666; }
</style>
</head>
<body>

<div class="baslik">
    <h1>İŞE DÖNÜŞ BELGESİ</h1>
    <div class="firma">{{ $firma?->unvan }}</div>
</div>

<table class="kunye">
    <tr>
        <td class="e">Belge No</td><td>{{ $belge->belge_no }}</td>
    </tr>
    <tr>
        <td class="e">Çalışan Ad Soyad</td><td>{{ $belge->calisan_ad_soyad }}</td>
    </tr>
    <tr>
        <td class="e">T.C. Kimlik No</td><td>{{ $belge->calisan_tc ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">Görevi</td><td>{{ $belge->gorev ?: '—' }}</td>
    </tr>
    <tr>
        <td class="e">İşe Dönüş Nedeni</td><td>{{ $belge->nedenEtiketi() }}</td>
    </tr>
    <tr>
        <td class="e">Devamsızlık Dönemi</td>
        <td>
            {{ $belge->devamsizlik_baslangic?->format('d.m.Y') ?? '—' }} – {{ $belge->devamsizlik_bitis?->format('d.m.Y') ?? '—' }}
            @if ($belge->devamsizlikGunu()) ({{ $belge->devamsizlikGunu() }} gün) @endif
        </td>
    </tr>
    <tr>
        <td class="e">İşe Dönüş Tarihi</td><td>{{ $belge->ise_donus_tarihi?->format('d.m.Y') ?? '—' }}</td>
    </tr>
</table>

<h2>İŞYERİ HEKİMİ DEĞERLENDİRMESİ</h2>
<div class="kutu">
    <span class="uygun-{{ $belge->uygunluk }}">{{ $belge->uygunlukEtiketi() }}</span>

    @if (filled($belge->kisitlamalar))
        <div style="margin-top:6px;font-weight:bold">Geçici İş Kısıtlamaları:</div>
        <ul class="kisit">
            @foreach ($belge->kisitlamalar as $k)
                <li>{{ $k }}</li>
            @endforeach
        </ul>
    @endif

    @if ($belge->hekim_gorusu)
        <div style="margin-top:6px"><strong>Hekim Görüşü / Açıklama:</strong> {{ $belge->hekim_gorusu }}</div>
    @endif

    @if ($belge->kontrol_muayene_tarihi)
        <div style="margin-top:6px"><strong>Kontrol Muayenesi Tarihi:</strong> {{ $belge->kontrol_muayene_tarihi->format('d.m.Y') }}</div>
    @endif
</div>

<p class="taahhut">
    Yukarıda kimliği belirtilen çalışan, işyeri hekimi tarafından muayene edilmiş olup işe
    dönüşünde belirtilen uygunluk durumu ve varsa geçici iş kısıtlamaları geçerlidir. Kısıtlamalar
    kontrol muayenesine kadar veya hekimin bildireceği süre boyunca uygulanır. İşveren, çalışanı
    bu değerlendirmeye aykırı işlerde çalıştıramaz.
</p>

<table class="imza">
    <tr>
        <td>
            <div class="rol">İşyeri Hekimi</div>
            <div class="kutu2">
                @if ($belge->hekim_kase && is_file(storage_path('app/public/'.$belge->hekim_kase)))
                    <img src="{{ storage_path('app/public/'.$belge->hekim_kase) }}">
                @endif
            </div>
            <div style="font-size:8.5px;margin-top:2px">{{ $belge->hekim_adi ?: '' }}</div>
        </td>
        <td>
            <div class="rol">Çalışan (Bilgilendirildi)</div>
            <div class="kutu2"></div>
            <div style="font-size:8.5px;margin-top:2px">{{ $belge->calisan_ad_soyad }}</div>
        </td>
    </tr>
</table>

<p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 15 ve İşyeri Hekimi ve Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik uyarınca düzenlenmiştir. Düzenleme: {{ $belge->belge_tarihi?->format('d.m.Y') ?? now()->format('d.m.Y') }}</p>

</body>
</html>
