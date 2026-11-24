<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İş Kazası ve Ramak Kala İstatistik Raporu</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1f2937; line-height: 1.35; }
        .baslik-kutu { border: 1.5px solid #991b1b; background: #fef2f2; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; }
        .baslik { font-size: 13px; font-weight: bold; color: #991b1b; margin: 0; }
        .alt-baslik { font-size: 9px; color: #4b5563; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .kpi-tablo td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: center; background: #f9fafb; width: 16.6%; }
        .kpi-sayi { font-size: 14px; font-weight: bold; color: #111827; }
        .kpi-etiket { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .izgara-tablo { width: 100%; margin-top: 8px; }
        .izgara-hucre { width: 49%; vertical-align: top; padding: 4px; }
        .kart { border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 6px; }
        .kart-baslik { background: #f3f4f6; padding: 4px 8px; font-weight: bold; font-size: 9px; border-bottom: 1px solid #d1d5db; color: #111827; }
        .icerik-tablo td { padding: 3px 8px; border-bottom: 1px solid #f3f4f6; font-size: 8.5px; }
        .alt-not { margin-top: 10px; font-size: 8px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="baslik-kutu">
        <div class="baslik">İŞ KAZASI VE RAMAK KALA İSTATİSTİK &amp; KIRILIM RAPORU (6331 s.K. Madde 14)</div>
        <div class="alt-baslik">
            <strong>Firma / Kapsam:</strong> {{ $firma ? $firma->unvan.' ('.$firma->tehlikeSinifiEtiketi().')' : 'Tüm Portföy Geneli' }}
            &nbsp;|&nbsp; <strong>Rapor Tarihi:</strong> {{ $tarih }}
        </div>
    </div>

    <table class="kpi-tablo">
        <tr>
            <td>
                <div class="kpi-sayi">{{ $kpi['toplam'] }}</div>
                <div class="kpi-etiket">Toplam Kaza Kaydı</div>
            </td>
            <td>
                <div class="kpi-sayi" style="color:#dc2626">{{ $kpi['kayip_gun'] }}</div>
                <div class="kpi-etiket">Toplam Kayıp Gün</div>
            </td>
            <td>
                <div class="kpi-sayi" style="color:#d97706">{{ $kpi['ramak_kala'] }}</div>
                <div class="kpi-etiket">Kayıp Günsüz / Hafif</div>
            </td>
            <td>
                <div class="kpi-sayi" style="color:#991b1b">{{ $kpi['agir_kaza'] }}</div>
                <div class="kpi-etiket">Ağır / 15+ Gün Kayıp</div>
            </td>
            <td>
                <div class="kpi-sayi" style="color:#16a34a">{{ $kpi['sgk_bildirilen'] }}</div>
                <div class="kpi-etiket">SGK Bildirimi Yapılan</div>
            </td>
            <td>
                <div class="kpi-sayi" style="color:#2563eb">{{ $kpi['acik_dof'] }}</div>
                <div class="kpi-etiket">Açık DÖF Aksiyonu</div>
            </td>
        </tr>
    </table>

    @php
        $liste = array_values($kirilimlar);
        $ciftler = array_chunk($liste, 2);
    @endphp

    <table class="izgara-tablo">
        @foreach ($ciftler as $cift)
            <tr>
                @foreach ($cift as $k)
                    <td class="izgara-hucre">
                        <div class="kart">
                            <div class="kart-baslik">{{ $k['baslik'] }}</div>
                            <table class="icerik-tablo">
                                @forelse ($k['satirlar'] as $etiket => $adet)
                                    <tr>
                                        <td>{{ $etiket }}</td>
                                        <td style="text-align:right;font-weight:bold;width:50px">{{ $adet }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" style="color:#9ca3af;font-style:italic">Veri yok</td>
                                    </tr>
                                @endforelse
                            </table>
                        </div>
                    </td>
                @endforeach
                @if (count($cift) === 1)
                    <td class="izgara-hucre"></td>
                @endif
            </tr>
        @endforeach
    </table>

    <div class="alt-not">
        Bu istatistik raporu 6331 sayılı İş Sağlığı ve Güvenliği Kanunu m.14 ve İSG Kurulları Hakkında Yönetmelik m.8 kapsamında kurul değerlendirmesi ve yönetim gözden geçirmesi için üretilmiştir.
    </div>

    <table style="width:100%;border-collapse:collapse;margin-top:16px;page-break-inside:avoid;font-size:8.5px">
        <tr>
            <td style="width:33.33%;text-align:center;padding:6px">
                <div style="height:34px;border-bottom:1px solid #334155;margin:0 12px 4px"></div>
                <strong>İş Güvenliği Uzmanı</strong><br>{{ $firma?->igu?->ad_soyad ?: '' }}
            </td>
            <td style="width:33.33%;text-align:center;padding:6px">
                <div style="height:34px;border-bottom:1px solid #334155;margin:0 12px 4px"></div>
                <strong>İşyeri Hekimi</strong><br>{{ $firma?->isyeriHekimi?->ad_soyad ?: '' }}
            </td>
            <td style="width:33.33%;text-align:center;padding:6px">
                <div style="height:34px;border-bottom:1px solid #334155;margin:0 12px 4px"></div>
                <strong>İşveren / İşveren Vekili</strong><br>{{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '') }}
            </td>
        </tr>
    </table>
</body>
</html>
