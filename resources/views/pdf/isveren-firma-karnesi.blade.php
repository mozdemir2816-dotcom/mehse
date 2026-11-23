<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İşveren İSG Uyum Karnesi — {{ $firma->unvan }}</title>
    <style>
        @page { margin: 20px 24px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.6px; color: #111827; line-height: 1.35; }
        .ust { border: 1.5px solid #1d4ed8; background: #eff6ff; border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
        .ust-tablo { width: 100%; border-collapse: collapse; }
        .baslik { font-size: 12.5px; font-weight: bold; color: #1e3a8a; }
        .alt { font-size: 8px; color: #374151; margin-top: 2px; }
        .puan-kutu { text-align: center; border-radius: 6px; padding: 6px 10px; font-weight: bold; color: #fff; }
        .puan-yuzde { font-size: 15px; line-height: 1.1; }
        .puan-alt { font-size: 7.5px; font-weight: normal; }
        .ozet-tablo { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 8px -5px; }
        .ozet-hucre { border: 1px solid #d1d5db; border-radius: 5px; padding: 5px 7px; background: #f9fafb; vertical-align: top; }
        .ozet-etiket { font-size: 7px; color: #6b7280; font-weight: bold; }
        .ozet-deger { font-size: 10px; font-weight: bold; color: #111827; margin-top: 1px; }
        .ozet-not { font-size: 7.2px; color: #4b5563; margin-top: 1px; }
        .bolum-baslik { font-size: 9.2px; font-weight: bold; color: #1e3a8a; background: #f3f4f6; border-left: 3px solid #1d4ed8; padding: 4px 8px; margin: 7px 0 4px 0; }
        table.liste { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.liste th { text-align: left; background: #f9fafb; border-bottom: 1px solid #d1d5db; padding: 3.5px 5px; font-size: 7.5px; color: #374151; }
        table.liste td { border-bottom: 1px solid #e5e7eb; padding: 3px 5px; vertical-align: middle; }
        .rozet { display: inline-block; padding: 1px 6px; border-radius: 999px; font-size: 7px; font-weight: bold; }
        .r-tamam { background: #dcfce7; color: #166534; }
        .r-eksik { background: #fee2e2; color: #991b1b; }
        .r-yakin { background: #fef3c7; color: #92400e; }
        .r-muaf { background: #f3f4f6; color: #4b5563; }
        .alt-bilgi { margin-top: 8px; padding-top: 5px; border-top: 1px solid #d1d5db; font-size: 7.2px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @php
        $oran = (int) ($ozet['oran'] ?? 0);
        $puanBg = $oran >= 75 ? '#15803d' : ($oran >= 40 ? '#b45309' : '#b91c1c');
    @endphp

    <div class="ust">
        <table class="ust-tablo">
            <tr>
                <td style="width:76%;vertical-align:top">
                    <div class="baslik">{{ $firma->unvan }} — İŞVEREN İSG UYUM KARNESİ</div>
                    <div class="alt">
                        <strong>Tehlike Sınıfı:</strong> {{ $firma->tehlikeSinifiEtiketi() }}
                        &nbsp;|&nbsp; <strong>Çalışan:</strong> {{ $calisan }} kişi
                        @if ($firma->sgk_sicil_no) &nbsp;|&nbsp; <strong>SGK:</strong> {{ $firma->sgk_sicil_no }} @endif
                        @if ($firma->nace_kodu) &nbsp;|&nbsp; <strong>NACE:</strong> {{ $firma->nace_kodu }} @endif
                        @if ($firma->isg_katip_sozlesme_no) &nbsp;|&nbsp; <strong>İSG-KATİP:</strong> {{ $firma->isg_katip_sozlesme_no }} @endif
                    </div>
                    <div class="alt">
                        <strong>İSG Uzmanı:</strong> {{ $firma->igu_hizmeti_yok ? 'Kapsam Dışı / Muaf' : ($firma->igu?->ad_soyad ? $firma->igu->ad_soyad.($firma->igu->sertifika_no ? ' (Belge: '.$firma->igu->sertifika_no.')' : '') : 'Atanmadı') }}
                        &nbsp;|&nbsp; <strong>İşyeri Hekimi:</strong> {{ $firma->hekim_hizmeti_yok ? 'Kapsam Dışı / Muaf' : ($firma->isyeriHekimi?->ad_soyad ? $firma->isyeriHekimi->ad_soyad.($firma->isyeriHekimi->sertifika_no ? ' (Belge: '.$firma->isyeriHekimi->sertifika_no.')' : '') : 'Atanmadı') }}
                        &nbsp;|&nbsp; <strong>Tarih:</strong> {{ $tarih }}
                    </div>
                </td>
                <td style="width:24%;vertical-align:middle">
                    <div class="puan-kutu" style="background:{{ $puanBg }}">
                        <div class="puan-yuzde">%{{ $oran }}</div>
                        <div class="puan-alt">Yasal Uyum ({{ $ozet['tamam'] }} Tamam / {{ $ozet['eksik'] }} Eksik)</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="ozet-tablo">
        <tr>
            <td class="ozet-hucre" style="width:34%">
                <div class="ozet-etiket">Aylık İSG-KATİP Hizmet Süreleri</div>
                <div class="ozet-deger">İGU: {{ $sureler['igu_dk'] }} dk · Hekim: {{ $sureler['hekim_dk'] }} dk</div>
                <div class="ozet-not">DSP: {{ $sureler['dsp_dk'] > 0 ? $sureler['dsp_dk'].' dk/ay' : 'Kapsam Dışı / Muaf' }}</div>
            </td>
            <td class="ozet-hucre" style="width:33%">
                <div class="ozet-etiket">Acil Durum &amp; İlk Yardım Kontenjanı</div>
                @if ($kontenjan)
                    <div class="ozet-deger">
                        İlk Yardım: {{ $kontenjan['ilkyardim_gecerli_belge'] ?? $kontenjan['ilkyardim_atanan'] }}/{{ $kontenjan['ilkyardim_gereken'] }}
                        · Destek: {{ $kontenjan['destek_atanan'] }}/{{ $kontenjan['destek_toplam_gereken'] }}
                    </div>
                    <div class="ozet-not">
                        {{ ($kontenjan['ilkyardim_eksik'] + $kontenjan['destek_eksik']) > 0
                            ? 'Açık: '.$kontenjan['ilkyardim_eksik'].' geçerli ilk yardımcı, '.$kontenjan['destek_eksik'].' destek elemanı'
                            : 'Yasal ekip kontenjanları tam' }}
                        @if (($kontenjan['ilkyardim_suresi_dolmus'] ?? 0) > 0)
                            · ({{ $kontenjan['ilkyardim_suresi_dolmus'] }} belgesi dolmuş)
                        @endif
                    </div>
                @else
                    <div class="ozet-deger">—</div>
                    <div class="ozet-not">Kontenjan verisi hesaplanmadı</div>
                @endif
            </td>
            <td class="ozet-hucre" style="width:33%">
                <div class="ozet-etiket">Evrak Tamamlanma Özeti</div>
                <div class="ozet-deger">{{ $ozet['tamam'] }} Hazır · {{ $ozet['eksik'] }} Eksik</div>
                <div class="ozet-not">{{ $ozet['muaf'] > 0 ? $ozet['muaf'].' kriter kapsam dışı (muaf)' : 'Tüm temel kriterler kapsamda' }}</div>
            </td>
        </tr>
    </table>

    <div class="bolum-baslik">1. FİRMA YASAL İSG EVRAKLARI VE GEÇERLİLİK DURUMU</div>
    <table class="liste">
        <thead>
            <tr>
                <th style="width:52%">Yasal Evrak / Yükümlülük</th>
                <th style="width:24%">Durum</th>
                <th style="width:24%">Vade / Geçerlilik</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($firmaEvraklari as $e)
                @php
                    $sinif = match (true) {
                        ! empty($e['muaf']) => 'r-muaf',
                        ! empty($e['tamam']) => 'r-tamam',
                        ! empty($e['takip']) => 'r-muaf',
                        ($e['durum'] ?? '') === 'yakin' => 'r-yakin',
                        default => 'r-eksik',
                    };
                    $etiket = match (true) {
                        ! empty($e['muaf']) => 'Muaf',
                        ! empty($e['tamam']) => 'Hazır',
                        ! empty($e['takip']) => 'Olay Bazlı',
                        ($e['durum'] ?? '') === 'yakin' => 'Vadesi Yakın',
                        default => 'Eksik',
                    };
                @endphp
                <tr>
                    <td><strong>{{ $e['ad'] }}</strong></td>
                    <td><span class="rozet {{ $sinif }}">{{ $etiket }}</span></td>
                    <td>{{ ! empty($e['vade_tarihi']) ? $e['vade_tarihi']->format('d.m.Y') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bolum-baslik">2. ÇALIŞAN ÖZLÜK VE SAHA İSG KAYITLARI ÖZETİ</div>
    <table class="liste">
        <thead>
            <tr>
                <th style="width:42%">Evrak Türü</th>
                <th style="width:44%">Açıklama</th>
                <th style="width:14%;text-align:right">Kayıt Adedi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($calisanEvraklari as $c)
                <tr>
                    <td><strong>{{ $c['ad'] }}</strong></td>
                    <td style="color:#4b5563">{{ $c['aciklama'] }}</td>
                    <td style="text-align:right;font-weight:bold">{{ $c['adet'] }} adet</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width:100%;border-collapse:collapse;margin-top:12px;page-break-inside:avoid;font-size:8px">
        <tr>
            <td style="width:50%;border:1px solid #d1d5db;padding:6px 8px;vertical-align:top">
                <strong>Hazırlayan (İSG Uzmanı)</strong><br>
                {{ $firma->igu?->ad_soyad ?: '—' }}@if ($firma->igu?->sertifika_no) ({{ $firma->igu->sertifika_no }})@endif
                <div style="height:26px"></div>
                <span style="color:#6b7280">İmza / Kaşe · {{ $tarih }}</span>
            </td>
            <td style="width:50%;border:1px solid #d1d5db;padding:6px 8px;vertical-align:top">
                <strong>Tebellüğ Eden (İşveren / İşveren Vekili)</strong><br>
                {{ $firma->isveren_vekili ?: ($firma->isveren_ad ?: '—') }}
                <div style="height:26px"></div>
                <span style="color:#6b7280">İmza / Kaşe · Tarih: .... / .... / ........</span>
            </td>
        </tr>
    </table>

    <div class="alt-bilgi">
        Bu karne 6331 Sayılı İş Sağlığı ve Güvenliği Kanunu kapsamında işveren bilgilendirmesi amacıyla salt-okunur özet olarak <strong>mehse.com</strong> üzerinden oluşturulmuştur ({{ $tarih }}).
    </div>
</body>
</html>
