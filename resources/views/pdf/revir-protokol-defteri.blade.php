<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 14mm 12mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #111; }
    h1 { font-size: 13px; margin: 0 0 3px; text-align: center; }
    .alt { text-align: center; font-size: 8px; color: #555; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; }
    th { background: #f1f5f9; font-weight: bold; text-align: left; }
    .kunye td { border: 1px solid #cbd5e1; padding: 4px 6px; }
    .e { font-weight: bold; background: #f8fafc; width: 14%; }
</style>
</head>
<body>
<h1>İŞYERİ REVİR / POLİKLİNİK PROTOKOL DEFTERİ</h1>
<div class="alt">6331 Sayılı Kanun ve İşyeri Hekimi ile Diğer Sağlık Personelinin Görev, Yetki, Sorumluluk ve Eğitimleri Hakkında Yönetmelik</div>

<table class="kunye">
    <tr>
        <td class="e">İşyeri</td><td>{{ $firma->unvan }}</td>
        <td class="e">SGK Sicil No</td><td>{{ $firma->sgk_sicil_no ?: '—' }}</td>
        <td class="e">Tarih</td><td>{{ now()->format('d.m.Y') }}</td>
    </tr>
    <tr>
        <td class="e">İşyeri Hekimi</td><td>{{ $firma->isyeriHekimi?->ad_soyad ?: '—' }}</td>
        <td class="e">DSP</td><td>{{ $firma->dsp?->ad_soyad ?: '—' }}</td>
        <td class="e">Toplam Kayıt</td><td>{{ $kayitlar->count() }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width:65px">Protokol No</th>
            <th style="width:68px">Tarih / Saat</th>
            <th style="width:105px">Ad Soyad</th>
            <th style="width:105px">Başvuru Nedeni</th>
            <th>Bulgu / Tanı &amp; Yapılan İşlem</th>
            <th style="width:80px">Sonuç</th>
            <th style="width:80px">Hekim / DSP</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($kayitlar as $k)
            <tr>
                <td><strong>{{ $k->protokol_no }}</strong></td>
                <td>{{ $k->tarih?->format('d.m.Y H:i') }}</td>
                <td>{{ $k->calisan_ad_soyad }}</td>
                <td>{{ $k->basvuru_nedeni }}</td>
                <td>
                    @if ($k->bulgu_tani)<strong>Tanı:</strong> {{ $k->bulgu_tani }}<br>@endif
                    @if ($k->yapilan_islem)<strong>İşlem:</strong> {{ $k->yapilan_islem }}@endif
                </td>
                <td>{{ $k->sonucEtiketi() }}@if ($k->istirahat_gun) ({{ $k->istirahat_gun }} gün)@endif</td>
                <td>{{ $k->hekim_dsp ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#64748b">Henüz revir / poliklinik kaydı bulunmuyor.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
