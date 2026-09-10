{{-- Kaza İstatistikleri — 6331 s.K. Madde 14 ve yıllık değerlendirme kapsamında.
     A4 dikey. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 26px 30px; }
    body { margin: 0; color: #111; font-size: 9px; line-height: 1.4; }
    h1 { font-size: 14px; text-align: center; margin: 0 0 2px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 12px; }
    table { border-collapse: collapse; width: 100%; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; font-size: 8.5px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 24%; }
    .kpi { margin: 12px 0; }
    .kpi td { border: 1px solid #999; padding: 6px; text-align: center; width: 16.6%; vertical-align: top; }
    .kpi .v { font-size: 15px; font-weight: bold; display: block; }
    .kpi .l { font-size: 7.5px; color: #555; }
    h2 { font-size: 10px; margin: 14px 0 4px; border-bottom: 1px solid #bbb; padding-bottom: 2px; }
    .veri th, .veri td { border: 1px solid #888; padding: 3px 5px; font-size: 8px; text-align: center; }
    .veri th { background: #eee; }
    .veri td.l { text-align: left; }
    .formul { margin-top: 10px; font-size: 7.5px; color: #555; }
    .imza { margin-top: 30px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; font-size: 8px; }
</style>
</head>
<body>

@php
    $siklik = $kayit->siklikOrani();
    $agirlik = $kayit->agirlikOrani();
    $carpanMetni = number_format($kayit->carpan(), 0, ',', '.');
@endphp

<h1>KAZA İSTATİSTİKLERİ</h1>
<div class="alt">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 14 ve yıllık değerlendirme kapsamında</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">Yıl</td><td>{{ $kayit->yil }}</td>
    </tr>
    <tr>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Hesaplama Standardı</td><td>{{ $kayit->standartEtiketi() }}</td>
    </tr>
    <tr>
        <td class="e">Ortalama Çalışan Sayısı</td><td>{{ $kayit->toplamOrtalamaCalisan() }}</td>
        <td class="e">Toplam Çalışma Saati</td><td>{{ number_format($kayit->toplamCalismaSaati(), 0, ',', '.') }}</td>
    </tr>
</table>

<table class="kpi">
    <tr>
        <td><span class="v">{{ $kayit->kazaSayisi() }}</span><span class="l">Hesaba Dahil Kaza</span></td>
        <td><span class="v">{{ $kayit->kayipZamanliKazaSayisi() }}</span><span class="l">Kayıp Zamanlı Kaza</span></td>
        <td><span class="v">{{ $kayit->olumluKazaSayisi() }}</span><span class="l">Ölümlü Kaza</span></td>
        <td><span class="v">{{ $kayit->toplamKayipGun() }}</span><span class="l">Toplam Kayıp Gün</span></td>
        <td><span class="v">{{ $siklik !== null ? $siklik : '—' }}</span><span class="l">Kaza Sıklık Oranı</span></td>
        <td><span class="v">{{ $agirlik !== null ? $agirlik : '—' }}</span><span class="l">Kaza Ağırlık Oranı</span></td>
    </tr>
</table>

<h2>Aylık Çalışma Verileri</h2>
<table class="veri">
    <thead>
        <tr><th>Ay</th><th>Ortalama Çalışan</th><th>Çalışma Saati</th></tr>
    </thead>
    <tbody>
        @foreach ($kayit->aylik_veriler ?? [] as $ay => $veri)
            <tr>
                <td class="l">{{ $aylar[$ay] ?? ($ay + 1) }}</td>
                <td>{{ (int) ($veri['ort_calisan'] ?? 0) }}</td>
                <td>{{ number_format((int) ($veri['calisma_saati'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr style="font-weight:bold;background:#f3f3f3">
            <td class="l">TOPLAM</td>
            <td>—</td>
            <td>{{ number_format($kayit->toplamCalismaSaati(), 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<h2>Hesaba Dahil Kazalar</h2>
<table class="veri">
    <thead>
        <tr><th style="width:70px">Tarih</th><th style="width:110px">Kaynak</th><th style="width:60px">Kayıp Gün</th><th style="width:50px">Ölümlü</th><th>Açıklama</th></tr>
    </thead>
    <tbody>
        @forelse ($kayit->tumKazalar() as $k)
            <tr>
                <td>{{ $k['tarih'] ? \Illuminate\Support\Carbon::parse($k['tarih'])->format('d.m.Y') : '—' }}</td>
                <td>{{ $k['kaynak'] }}</td>
                <td>{{ $k['kayip_gunu'] }}</td>
                <td>{{ $k['olumlu'] ? 'Evet' : '' }}</td>
                <td class="l">{{ $k['aciklama'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:#888">Bu yıl için kaza kaydı bulunmuyor.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="formul">
    <strong>Kaza Sıklık Oranı</strong> = (Hesaba Dahil Kaza Sayısı × {{ $carpanMetni }}) / Toplam Çalışma Saati &nbsp;—&nbsp;
    <strong>Kaza Ağırlık Oranı</strong> = (Toplam Kayıp Gün × {{ $carpanMetni }}) / Toplam Çalışma Saati.
    Kazalar; İş Kazası Raporları, Olay Kayıtları (iş kazası tipi) ve elle eklenen harici kayıtlardan birleştirilir.
    @if ($kayit->not) <br><strong>Not:</strong> {{ $kayit->not }} @endif
</p>

<table class="imza">
    <tr>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşyeri Hekimi</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
