{{-- İşveren / Yönetici için Tek Sayfa Aylık İSG Özeti ve Uygunluk Karnesi PDF'i --}}
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; box-sizing: border-box; }
    @page { margin: 24px 28px; }
    body { margin: 0; color: #1e293b; font-size: 9.5px; line-height: 1.4; }
    .baslik { border-bottom: 3px solid #0f766e; padding-bottom: 8px; margin-bottom: 12px; }
    .baslik table { width: 100%; border-collapse: collapse; }
    .baslik h1 { font-size: 13.5px; margin: 0 0 2px; color: #0f766e; }
    .baslik .firma { font-size: 11px; font-weight: bold; color: #0f172a; }
    .baslik .alt { font-size: 8.5px; color: #64748b; margin-top: 2px; }
    .rozet { display: inline-block; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 9px; color: #fff; }
    .r-uygun { background: #15803d; }
    .r-islem { background: #dc2626; }
    .kpi-tablo { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .kpi-tablo td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: center; background: #f8fafc; width: 20%; }
    .kpi-deger { font-size: 14px; font-weight: bold; color: #0f172a; }
    .kpi-etiket { font-size: 8px; color: #64748b; text-transform: uppercase; margin-top: 2px; }
    h2 { font-size: 10px; margin: 10px 0 4px; color: #fff; background: #0f766e; padding: 4px 8px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 8.8px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #cbd5e1; padding: 4px 6px; vertical-align: top; }
    table.liste th { background: #f1f5f9; font-size: 8.2px; text-align: left; color: #334155; }
    .d-tamam { color: #15803d; font-weight: bold; }
    .d-eksik { color: #b91c1c; font-weight: bold; }
    .d-yakin { color: #b45309; font-weight: bold; }
    .imza-kutusu { margin-top: 16px; width: 100%; border-collapse: collapse; }
    .imza-kutusu td { width: 50%; vertical-align: top; padding: 6px 10px; border: 1px solid #cbd5e1; height: 65px; font-size: 8.5px; }
    .altbilgi { margin-top: 10px; font-size: 7.8px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px; }
</style>
</head>
<body>

@php
    $g = $rapor['gostergeler'];
    $u = $rapor['uygunluk'];
    $aksiyonlar = $rapor['aksiyonlar'];
    $checklist = collect(\App\Support\PortfoyKarne::firmaChecklistDetay($firma))->where('takip', false)->where('hazir', true);
    $tamamSayi = $checklist->where('tamam', true)->count();
    $toplamSayi = $checklist->count();
    $uyumOrani = $toplamSayi > 0 ? round($tamamSayi / $toplamSayi * 100) : 0;
    $uzman = \App\Support\FirmaImza::uzman($firma);
    $isveren = \App\Support\FirmaImza::isveren($firma);
@endphp

<div class="baslik">
    <table>
        <tr>
            <td>
                <h1>İŞYERİ İSG YÖNETİCİ ÖZETİ VE YASAL UYGUNLUK KARNESİ</h1>
                <div class="firma">{{ $firma->unvan }}</div>
                <div class="alt">
                    SGK Sicil: {{ $firma->sgk_sicil_no ?: '—' }} · NACE: {{ $firma->nace_kodu ?: '—' }} ·
                    Tehlike Sınıfı: {{ $firma->tehlikeSinifiEtiketi() }} · Rapor Tarihi: {{ now()->format('d.m.Y') }}
                </div>
            </td>
            <td style="text-align:right;vertical-align:top">
                <span class="rozet {{ $u['durum'] === 'Uygun' ? 'r-uygun' : 'r-islem' }}">
                    {{ $u['durum'] === 'Uygun' ? 'YASAL DURUM: UYGUN' : 'İŞLEM / AKSİYON GEREKLİ' }}
                </span>
                <div style="font-size:11px;font-weight:bold;margin-top:4px;color:#0f766e">
                    Evrak Uyumu: %{{ $uyumOrani }} ({{ $tamamSayi }}/{{ $toplamSayi }})
                </div>
            </td>
        </tr>
    </table>
</div>

<table class="kpi-tablo">
    <tr>
        <td>
            <div class="kpi-deger">{{ $g['aktif_calisan'] > 0 ? $g['aktif_calisan'] : (($g['bildirilen_calisan'] ?? 0) ?: 0) }}</div>
            <div class="kpi-etiket">{{ $g['aktif_calisan'] > 0 ? 'Aktif Çalışan' : 'Çalışan Sayısı' }}</div>
        </td>
        <td>
            <div class="kpi-deger" style="color:{{ $uyumOrani >= 75 ? '#15803d' : '#b45309' }}">%{{ $uyumOrani }}</div>
            <div class="kpi-etiket">Yasal Evrak Uyumu</div>
        </td>
        <td>
            <div class="kpi-deger" style="color:{{ $g['acik_risk'] > 0 ? '#dc2626' : '#15803d' }}">{{ $g['acik_risk'] }}</div>
            <div class="kpi-etiket">Açık Yüksek Risk</div>
        </td>
        <td>
            <div class="kpi-deger" style="color:{{ $g['yaklasan_geciken'] > 0 ? '#dc2626' : '#15803d' }}">{{ $g['yaklasan_geciken'] }}</div>
            <div class="kpi-etiket">Yaklaşan / Geciken Süre</div>
        </td>
        <td>
            <div class="kpi-deger">{{ $g['kkd_zimmet'] }}</div>
            <div class="kpi-etiket">Aktif KKD Zimmeti</div>
        </td>
    </tr>
</table>

<h2>1. Öncelikli Aksiyonlar ve Süresi Yaklaşan / Geciken Yükümlülükler ({{ $aksiyonlar->count() }})</h2>
@if ($aksiyonlar->isEmpty())
    <p style="font-size:9px;color:#15803d;font-weight:bold;margin:6px 0">
        ✓ İşyerinde süresi geçmiş veya acil müdahale gerektiren açık İSG aksiyonu bulunmamaktadır.
    </p>
@else
    <table class="liste">
        <tr>
            <th style="width:24px">#</th>
            <th style="width:170px">Konu / Başlık</th>
            <th>Detay ve Açıklama</th>
            <th style="width:70px">Durum</th>
            <th style="width:130px">Yasal Dayanak</th>
        </tr>
        @foreach ($aksiyonlar->take(12) as $i => $a)
            <tr>
                <td style="text-align:center">{{ $i + 1 }}</td>
                <td><strong>{{ $a['baslik'] }}</strong></td>
                <td>{{ $a['alt'] }}</td>
                <td class="{{ in_array($a['durum'], ['gecikmis', 'eksik'], true) ? 'd-eksik' : 'd-yakin' }}">
                    {{ match ($a['durum']) { 'gecikmis' => 'GECİKMİŞ', 'cok_yakin' => 'ÇOK YAKIN', 'yaklasiyor' => 'YAKLAŞIYOR', default => 'EKSİK' } }}
                </td>
                <td style="font-size:8px;color:#475569">{{ $a['dayanak']['ad'] ?? '6331 s. Kanun' }}</td>
            </tr>
        @endforeach
    </table>
    @if ($aksiyonlar->count() > 12)
        <div style="font-size:8px;color:#64748b;margin-bottom:6px">
            * Toplam {{ $aksiyonlar->count() }} aksiyondan en öncelikli ilk 12 kayıt listelenmiştir.
        </div>
    @endif
@endif

<h2>2. Temel Yasal İSG Dosyası ve Süreç Kontrol Özeti</h2>
<table class="liste">
    <tr>
        <th>Yasal Belge / Süreç</th>
        <th style="width:85px;text-align:center">Durum</th>
        <th>Yasal Belge / Süreç</th>
        <th style="width:85px;text-align:center">Durum</th>
    </tr>
    @foreach ($checklist->values()->chunk(2) as $cift)
        @php $items = $cift->values(); @endphp
        <tr>
            <td>{{ $items[0]['ad'] }}</td>
            <td style="text-align:center" class="{{ $items[0]['muaf'] ? '' : ($items[0]['tamam'] ? 'd-tamam' : 'd-eksik') }}">
                {{ $items[0]['muaf'] ? 'MUAF' : ($items[0]['tamam'] ? 'TAMAM' : 'EKSİK') }}
            </td>
            @if (isset($items[1]))
                <td>{{ $items[1]['ad'] }}</td>
                <td style="text-align:center" class="{{ $items[1]['muaf'] ? '' : ($items[1]['tamam'] ? 'd-tamam' : 'd-eksik') }}">
                    {{ $items[1]['muaf'] ? 'MUAF' : ($items[1]['tamam'] ? 'TAMAM' : 'EKSİK') }}
                </td>
            @else
                <td></td><td></td>
            @endif
        </tr>
    @endforeach
</table>

<table class="imza-kutusu">
    <tr>
        <td>
            <strong>Hazırlayan İş Güvenliği Uzmanı</strong><br>
            {{ $uzman['ad'] ?: '—' }}@if (!empty($uzman['sinif'])) ({{ $uzman['sinif'] }} Sınıfı İSG Uzmanı)@endif
            @if (!empty($uzman['belge_no']))<br>Belge No: {{ $uzman['belge_no'] }}@endif
        </td>
        <td>
            <strong>İşveren / İşveren Vekili (Bilgi ve Tebellüğ)</strong><br>
            {{ $isveren ?: $firma->unvan }}<br>
            <span style="color:#64748b;font-size:8px">Tarih / Kaşe / İmza</span>
        </td>
    </tr>
</table>

<div class="altbilgi">
    6331 sayılı İş Sağlığı ve Güvenliği Kanunu kapsamında işyerinin güncel İSG süreçlerini ve öncelikli aksiyonlarını işverene bildirmek amacıyla üretilmiştir. Kişisel sağlık/klinik veriler bu raporda yer almaz.
</div>

</body>
</html>
