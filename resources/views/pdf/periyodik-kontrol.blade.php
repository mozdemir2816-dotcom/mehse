{{-- Ekipman & Periyodik Kontrol — Müfettiş Teftiş Paketi. İş Ekipmanlarının
     Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği EK-III. A4 yatay. --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    @page { margin: 20px 24px; }
    body { margin: 0; color: #111; font-size: 7.6px; line-height: 1.3; }
    h1 { font-size: 13px; text-align: center; margin: 0 0 3px; }
    .alt { text-align: center; color: #555; font-size: 8px; margin-bottom: 8px; }
    .kunye { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8px; }
    .kunye td { border: 1px solid #999; padding: 3px 6px; }
    .kunye td.e { background: #eee; font-weight: bold; width: 12%; }
    .kpi { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .kpi td { border: 1px solid #999; padding: 5px; text-align: center; width: 25%; }
    .kpi .v { font-size: 14px; font-weight: bold; display: block; }
    .kpi .l { font-size: 7px; color: #555; }
    table.liste { width: 100%; border-collapse: collapse; }
    table.liste th, table.liste td { border: 1px solid #888; padding: 2px 3px; vertical-align: top; }
    table.liste th { background: #eee; font-size: 6.8px; text-align: left; }
    .grp { background: #f2f2f2; font-weight: bold; }
    .d-gecerli { color: #15803d; font-weight: bold; }
    .d-yaklasan { color: #b45309; font-weight: bold; }
    .d-dolmus { color: #b91c1c; font-weight: bold; }
    .d-bekliyor { color: #6b7280; }
    .yasal { margin-top: 8px; font-size: 7px; color: #555; }
    .imza { margin-top: 18px; width: 100%; border-collapse: collapse; font-size: 8px; }
    .imza td { border-top: 1px solid #111; padding-top: 4px; text-align: center; width: 33%; }
</style>
</head>
<body>

@php
    $sayac = ['gecerli' => 0, 'yaklasan' => 0, 'dolmus' => 0, 'bekliyor' => 0];
    foreach ($ekipmanlar as $e) { $sayac[$e->vizeDurumu()]++; }
    $durumEtiket = ['gecerli' => 'Vizesi Geçerli', 'yaklasan' => 'Vize Yaklaşan', 'dolmus' => 'Süresi Dolan', 'bekliyor' => 'Muayene Bekliyor'];
@endphp

<h1>EKİPMAN & PERİYODİK KONTROL — MÜFETTİŞ TEFTİŞ PAKETİ</h1>
<div class="alt">6331 Sayılı Kanun ve İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği EK-III uyarınca</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma?->unvan ?: '—' }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma?->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Paket Tarihi</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    @if ($kontrol->genel_not)
        <tr><td class="e">Not</td><td colspan="5">{{ $kontrol->genel_not }}</td></tr>
    @endif
</table>

<table class="kpi">
    <tr>
        <td><span class="v">{{ count($ekipmanlar) }}</span><span class="l">TOPLAM EKİPMAN</span></td>
        <td><span class="v" style="color:#15803d">{{ $sayac['gecerli'] }}</span><span class="l">VİZESİ GEÇERLİ</span></td>
        <td><span class="v" style="color:#b45309">{{ $sayac['yaklasan'] }}</span><span class="l">VİZE YAKLAŞAN (&lt;30 GÜN)</span></td>
        <td><span class="v" style="color:#b91c1c">{{ $sayac['dolmus'] + $sayac['bekliyor'] }}</span><span class="l">SÜRESİ DOLAN / MUAYENE BEKLEYEN</span></td>
    </tr>
</table>

<table class="liste">
    <thead>
        <tr>
            <th style="width:14px">#</th>
            <th>Ekipman / Tip</th>
            <th style="width:70px">Seri No / Plaka</th>
            <th style="width:70px">Marka / Model</th>
            <th style="width:60px">Konum</th>
            <th style="width:60px">Kapasite</th>
            <th style="width:90px">Yasal Standart</th>
            <th style="width:24px">Per. (Ay)</th>
            <th style="width:50px">Son Muayene</th>
            <th style="width:50px">Sonraki Vize</th>
            <th style="width:60px">Vize Durumu</th>
            <th style="width:80px">Muayene Yapan / Rapor No</th>
            <th style="width:60px">Sonuç</th>
        </tr>
    </thead>
    <tbody>
        @php $sonKat = null; $i = 0; @endphp
        @forelse ($ekipmanlar as $e)
            @if ($e->kategoriAdi() !== $sonKat)
                <tr><td class="grp" colspan="13">{{ $e->kategoriAdi() }}@if ($e->kategoriMevzuati()) &nbsp;—&nbsp; <span style="font-weight:normal;color:#666">{{ $e->kategoriMevzuati() }}</span> @endif</td></tr>
                @php $sonKat = $e->kategoriAdi(); @endphp
            @endif
            @php $i++; $d = $e->vizeDurumu(); @endphp
            <tr>
                <td>{{ $i }}</td>
                <td><strong>{{ $e->ekipman_adi }}</strong>@if ($e->tip && $e->tip !== $e->ekipman_adi)<br><span style="color:#666">{{ $e->tip }}</span>@endif</td>
                <td>{{ $e->seri_no ?: '—' }}</td>
                <td>{{ $e->marka_model ?: '—' }}</td>
                <td>{{ $e->konum ?: '—' }}</td>
                <td>{{ $e->kapasite ?: '—' }}</td>
                <td>{{ $e->yasal_standart ?: '—' }}</td>
                <td style="text-align:center">{{ $e->muayene_periyodu_ay }}</td>
                <td>{{ $e->son_muayene_tarihi?->format('d.m.Y') ?? '—' }}</td>
                <td>{{ $e->sonraki_vize_tarihi?->format('d.m.Y') ?? '—' }}</td>
                <td class="d-{{ $d }}">{{ $durumEtiket[$d] }}@if ($e->kalanGun() !== null && $d !== 'bekliyor')<br><span style="font-weight:normal;color:#666">{{ $e->kalanGun() }} gün</span>@endif</td>
                <td>{{ $e->muayene_yapan ?: '—' }}@if ($e->rapor_no)<br><span style="color:#666">{{ $e->rapor_no }}</span>@endif</td>
                <td>{{ $sonuclar[$e->sonuc] ?? $e->sonuc }}</td>
            </tr>
        @empty
            <tr><td colspan="13" style="text-align:center;color:#888">Tanımlı iş ekipmanı yok.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="yasal">
    Periyodik kontroller EK-III'te öngörülen sürelerde ve yöntemlerde yetkili kişilerce
    (makine/elektrik mühendisi veya tekniker; ilgili A tipi muayene kuruluşu) yapılır. "Süresi
    Dolan" ve "Uygun Değil" bulunan ekipman, gerekli düzeltme yapılıp yeniden muayene edilene
    kadar kullanılamaz. Muayene raporları işyerinde saklanır ve talep hâlinde iş müfettişine sunulur.
</p>

<table class="imza">
    <tr>
        <td>Muayeneyi Yapan (Yetkili Kişi / Kuruluş)</td>
        <td>İş Güvenliği Uzmanı</td>
        <td>İşveren / İşveren Vekili</td>
    </tr>
</table>

</body>
</html>
