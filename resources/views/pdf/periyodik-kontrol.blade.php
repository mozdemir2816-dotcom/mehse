{{-- İş Ekipmanları Periyodik Kontrol Takip Listesi — İş Ekipmanlarının Kullanımında
     Sağlık ve Güvenlik Şartları Yönetmeliği EK-3. A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 22px 26px; }
    body { margin: 0; color: #111; font-size: 8.5px; line-height: 1.35; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 10px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 12%; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th, table.liste td { border: 1px solid #777; padding: 3px 4px; vertical-align: top; }
    table.liste th { background: #eee; text-align: left; font-size: 7.5px; }
    .s-uygun { color: #15803d; font-weight: bold; }
    .s-uygun_degil { color: #b91c1c; font-weight: bold; }
    .s-sartli { color: #b45309; font-weight: bold; }
    .s-bekliyor { color: #6b7280; }
    .yasal { margin-top: 10px; font-size: 7.5px; color: #555; }
    .imza { margin-top: 26px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; }
</style>
</head>
<body>

<h1>İŞ EKİPMANLARI PERİYODİK KONTROL TAKİP LİSTESİ</h1>
<div class="alt">İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği EK-3 uyarınca</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Liste Tarihi</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    @if ($kontrol->genel_not)
        <tr><td class="e">Not</td><td colspan="5">{{ $kontrol->genel_not }}</td></tr>
    @endif
</table>

<table class="liste">
    <thead>
        <tr>
            <th style="width:16px">#</th>
            <th>Ekipman</th>
            <th style="width:80px">Kategori</th>
            <th style="width:26px">Adet</th>
            <th>Tanım (kapasite / seri / konum)</th>
            <th style="width:34px">Periyot (ay)</th>
            <th style="width:52px">Son Kontrol</th>
            <th style="width:90px">Kontrol Eden / Rapor No</th>
            <th style="width:70px">Sonuç</th>
            <th style="width:52px">Sonraki Kontrol</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($kontrol->ekipmanlar ?? [] as $i => $e)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $e['ad'] ?? '' }}</td>
                <td>{{ $e['kategori'] ?? '' }}</td>
                <td>{{ $e['adet'] ?? 1 }}</td>
                <td>{{ $e['tanim'] ?? '' }}</td>
                <td style="text-align:center">{{ $e['periyot_ay'] ?? '' }}</td>
                <td>{{ !empty($e['son_kontrol_tarihi']) ? \Illuminate\Support\Carbon::parse($e['son_kontrol_tarihi'])->format('d.m.Y') : '—' }}</td>
                <td>
                    {{ $e['kontrol_eden'] ?? '—' }}
                    @if (!empty($e['rapor_no'])) <br><span style="color:#555">{{ $e['rapor_no'] }}</span> @endif
                </td>
                <td class="s-{{ $e['sonuc'] ?? 'bekliyor' }}">{{ $sonuclar[$e['sonuc'] ?? 'bekliyor'] ?? ($e['sonuc'] ?? '') }}</td>
                <td>{{ !empty($e['sonraki_kontrol_tarihi']) ? \Illuminate\Support\Carbon::parse($e['sonraki_kontrol_tarihi'])->format('d.m.Y') : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center;color:#888">Ekipman girilmemiş.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    Periyodik kontroller, ilgili standartlarda aksi belirtilmediği sürece yılda bir kez ve
    EK-3'te öngörülen yöntemlere göre yetkili kişilerce (basınçlı kaplar/kaldırma ekipmanları:
    makine mühendisi/tekniker; elektrik tesisatı: elektrik mühendisi/tekniker) yapılır. "Uygun
    Değil" bulunan ekipman, gerekli düzeltme yapılıp yeniden kontrol edilene kadar kullanılamaz.
</p>

<table class="imza">
    <tr>
        <td>Kontrolü Yapan<br>(Yetkili Kişi / Kuruluş)</td>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
