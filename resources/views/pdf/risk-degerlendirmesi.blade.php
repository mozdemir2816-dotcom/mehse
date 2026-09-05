<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 25px 25px 78px 25px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .kapak { border:3px double #111; margin: 28px; padding: 60px 40px; text-align: center; page-break-after: always; }
    .kapak h1 { font-size: 24px; margin: 40px 0 8px; letter-spacing: 1px; }
    .kapak .firma { font-size: 15px; font-weight: bold; margin-top: 40px; }
    .kunye { margin: 20px 0; width: 100%; border-collapse: collapse; font-size: 11px; }
    .kunye td { border: 1px solid #999; padding: 6px 10px; text-align: left; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 35%; }
    .sayfa { padding: 25px 30px; page-break-after: always; }
    h2 { font-size: 15px; border-bottom: 2px solid #7c3aed; padding-bottom: 4px; color: #7c3aed; }
    table.olcek { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9.5px; }
    table.olcek th, table.olcek td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.olcek th { background: #f0f0f0; }
    table.bant td:first-child { font-weight: bold; color: #fff; text-align: center; width: 22%; }
    table.risk { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8px; }
    table.risk th, table.risk td { border: 1px solid #999; padding: 3px 4px; text-align: left; vertical-align: top; }
    table.risk thead th { background: #f0f0f0; }
    table.risk .col-no { width: 16px; text-align: center; }
    table.risk .col-duzey { width: 11px; padding: 2px 1px; text-align: center; vertical-align: middle; }
    table.ekip { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
    table.ekip th, table.ekip td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
    table.ekip th { background: #f0f0f0; }
    .duzey { color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 7.5px; white-space: nowrap; }
    /* Dikey düzey yazısı: transform:rotate KULLANILMAZ — dompdf'te rotate edilen
       kutu kendi hücresinin satırına göre kayabiliyor. Harf harf alt alta yazmak
       normal tablo akışında kalır, satırla her zaman hizalı kalması garanti olur.
       Renk artık span'e değil kutunun tamamını dolduran <td>'ye uygulanıyor
       (col-duzey vertical-align:middle ile satırın ortasında kalıyor). */
    .duzey-dikey { color: #fff; font-size: 6px; line-height: 1.05;
        white-space: pre-line; display: block; text-align: center; }
    .imza { margin-top: 50px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    .prosedur h3 { font-size: 12px; color: #7c3aed; margin: 14px 0 4px; }
    .prosedur p { font-size: 10.5px; margin: 2px 0 8px; white-space: pre-line; }

    /* HER SAYFANIN ALT BİLGİSİ — imza/kaşe şeridi (@page margin-bottom bunun için ayrıldı).
       -78px = @page'in alt marjıyla (bottom:78px) aynı: şerit sayfanın gerçek alt
       kenarına dayanır, böylece içerik kutusu ile şerit arasında sayfa numarası
       için boşluk kalır (bkz. RiskDegerlendirmesiUretici::pdf() page_script). */
    .sayfa-alt {
        position: fixed; bottom: -78px; left: 0; right: 0;
        border-top: 1px solid #999; padding-top: 3px;
    }
    table.alt-imza { width: 100%; border-collapse: collapse; font-size: 6.5px; table-layout: fixed; }
    table.alt-imza td { border: 1px solid #ccc; text-align: center; padding: 2px 2px 4px; vertical-align: bottom; }
    table.alt-imza .rol { font-weight: bold; display: block; margin-bottom: 12px; }
    table.alt-imza img { max-height: 22px; max-width: 90%; display: block; margin: 0 auto 1px; }
    table.alt-imza .ad { font-weight: normal; }
</style>
</head>
<body>

{{-- HER SAYFANIN ALT BİLGİSİ (imza/kaşe şeridi) --}}
<div class="sayfa-alt">
    <table class="alt-imza">
        <tr>
            <td>
                <span class="rol">İŞVEREN / İŞVEREN VEKİLİ</span>
                <span class="ad">{{ $firma?->isveren_ad ?: $firma?->isveren_vekili ?: '—' }}</span>
            </td>
            <td>
                <span class="rol">İŞ GÜVENLİĞİ UZMANI</span>
                @if ($uzman?->kase_gorseli)<img src="{{ storage_path('app/public/'.$uzman->kase_gorseli) }}">@endif
                @if ($uzman?->imza_gorseli)<img src="{{ storage_path('app/public/'.$uzman->imza_gorseli) }}">@endif
                <span class="ad">{{ $uzman?->name ?: '—' }}</span>
            </td>
            <td>
                <span class="rol">İŞYERİ HEKİMİ</span>
                @if ($hekim?->kase_gorseli)<img src="{{ storage_path('app/public/'.$hekim->kase_gorseli) }}">@endif
                @if ($hekim?->imza_gorseli)<img src="{{ storage_path('app/public/'.$hekim->imza_gorseli) }}">@endif
                <span class="ad">{{ $hekim?->ad_soyad ?: '—' }}</span>
            </td>
            <td>
                <span class="rol">ÇALIŞAN TEMSİLCİSİ</span>
                <span class="ad">{{ $temsilci['ad'] ?? '—' }}</span>
            </td>
            <td>
                <span class="rol">DESTEK ELEMANI</span>
                <span class="ad">{{ $destekElemani['ad'] ?? '—' }}</span>
            </td>
        </tr>
    </table>
</div>

{{-- KAPAK --}}
<div class="kapak">
    <div style="font-size:12px;color:#666">{{ $firma?->unvan }}</div>
    <h1>RİSK DEĞERLENDİRMESİ</h1>
    <div style="font-size:12px">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu — Risk Değerlendirmesi Yönetmeliği</div>
    <div class="firma">{{ $firma?->unvan }}</div>
    <div style="font-size:11px;margin-top:6px">{{ $rd->firma_adres }}</div>
    <div style="font-size:11px;margin-top:20px">
        SGK Sicil No: {{ $rd->firma_sgk_sicil_no ?: '—' }} &nbsp;·&nbsp; NACE Kodu: {{ $rd->firma_nace ?: '—' }}
    </div>
    <div style="font-size:11px;margin-top:20px">
        Belge No: {{ $rd->belge_no }} &nbsp;·&nbsp; Rev: {{ $rd->revizyon_no }}<br>
        Yöntem: {{ $rd->yontemEtiketi() }}<br>
        Yapılış Tarihi: {{ $rd->rapor_tarihi?->format('d.m.Y') }}<br>
        Geçerlilik Tarihi: {{ $rd->gecerlilik_tarihi?->format('d.m.Y') }}
    </div>
    <div style="font-size:11px;margin-top:20px;color:#444">
        Hazırlayan: {{ $uzman?->name ?: '—' }} @if ($uzman?->unvan) ({{ $uzman->unvanEtiketi() }}) @endif
    </div>
    @if ($toplamSayfa ?? null)
        <div style="font-size:11px;margin-top:6px;color:#444">Toplam Sayfa: {{ $toplamSayfa }}</div>
    @endif
</div>

{{-- RİSK ANALİZİ PROSEDÜRÜ (seçilen yönteme göre) --}}
@if ($prosedur)
    <div class="sayfa prosedur">
        <h2>{{ $prosedur->ad }}</h2>
        @foreach ($prosedur->icerik as $blok)
            @if ($blok['tip'] === 'baslik')
                <h3>{{ $blok['metin'] }}</h3>
            @else
                <p>{{ $blok['metin'] }}</p>
            @endif
        @endforeach
    </div>
@endif

{{-- KÜNYE + METODOLOJİ --}}
<div class="sayfa">
    <h2>1. İŞYERİ KÜNYESİ</h2>
    <table class="kunye">
        <tr><td>Ticari Unvan</td><td>{{ $rd->firma_unvan }}</td></tr>
        <tr><td>SGK Sicil No</td><td>{{ $rd->firma_sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td>NACE Kodu</td><td>{{ $rd->firma_nace ?: '—' }}</td></tr>
        <tr><td>Tehlike Sınıfı</td><td>{{ $firma?->tehlikeSinifiEtiketi() }}</td></tr>
        <tr><td>Adres</td><td>{{ $rd->firma_adres ?: '—' }}</td></tr>
    </table>

    <h2 style="margin-top:20px">2. DEĞERLENDİRME YÖNTEMİ</h2>
    <p style="font-size:10.5px">{{ $metodoloji['aciklama'] }}</p>

    <table class="olcek">
        <tr><th>Olasılık</th><th>Açıklama</th></tr>
        @foreach ($metodoloji['olasilik'] as $puan => $aciklama)
            <tr><td>{{ $puan }}</td><td>{{ $aciklama }}</td></tr>
        @endforeach
    </table>

    @if (isset($metodoloji['frekans']))
        <table class="olcek">
            <tr><th>Frekans (maruz kalma)</th><th>Açıklama</th></tr>
            @foreach ($metodoloji['frekans'] as $puan => $aciklama)
                <tr><td>{{ $puan }}</td><td>{{ $aciklama }}</td></tr>
            @endforeach
        </table>
    @endif

    <table class="olcek">
        <tr><th>Şiddet</th><th>Açıklama</th></tr>
        @foreach ($metodoloji['siddet'] as $puan => $aciklama)
            <tr><td>{{ $puan }}</td><td>{{ $aciklama }}</td></tr>
        @endforeach
    </table>

    <table class="olcek bant">
        <tr><th>Düzey</th><th>Puan aralığı</th><th>Yapılacak eylem</th></tr>
        @foreach ($metodoloji['bantlar'] as $bant)
            <tr>
                <td style="background:{{ $bant['renk'] }}">{{ $bant['ad'] }}</td>
                <td>≥ {{ $bant['min'] }}</td>
                <td>{{ $bant['eylem'] }}</td>
            </tr>
        @endforeach
    </table>
</div>

{{-- RİSK TABLOSU --}}
<div class="sayfa">
    <h2>3. TEHLİKELERİN TANIMLANMASI VE RİSK DEĞERLENDİRME TABLOSU</h2>
    <table class="risk">
        <thead>
        <tr>
            <th class="col-no">No</th>
            <th>Bölüm / Faaliyet</th>
            <th>Tehlike / Risk</th>
            <th>Mevcut Önlem</th>
            @if ($rd->fineKinneyMi())
                <th>O</th><th>F</th>
            @else
                <th>O</th>
            @endif
            <th>Ş</th>
            <th>Puan</th>
            <th class="col-duzey">Düzey</th>
            <th>Öneri</th>
            <th>Sorumlu</th>
            <th>Termin</th>
            <th>O</th>
            <th>Ş</th>
            <th>Son Puan</th>
            <th class="col-duzey">Son Düzey</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rd->maddeler as $m)
            <tr>
                <td class="col-no">{{ $loop->iteration }}</td>
                <td>{{ $m->bolum }}@if($m->faaliyet)<br><em>{{ $m->faaliyet }}</em>@endif</td>
                <td>{{ $m->tehlike }}@if($m->risk)<br>{{ $m->risk }}@endif</td>
                <td>{{ $m->mevcut_onlem ?: '—' }}</td>
                <td>{{ $m->olasilik }}</td>
                @if ($rd->fineKinneyMi())
                    <td>{{ $m->frekans }}</td>
                @endif
                <td>{{ $m->siddet }}</td>
                <td>{{ $m->puan }}</td>
                <td class="col-duzey" style="background:{{ $m->rengi() }}"><span class="duzey-dikey">{{ $m->duzeyDikey() }}</span></td>
                <td>{{ $m->oneri ?: '—' }}</td>
                <td>{{ $m->sorumlu ?: '—' }}</td>
                <td>{{ $m->termin ?: '—' }}</td>
                <td>{{ $m->son_olasilik ?? '—' }}</td>
                <td>{{ $m->son_siddet ?? '—' }}</td>
                <td>{{ $m->son_puan ?: '—' }}</td>
                <td class="col-duzey" @if($m->son_duzey) style="background:{{ \App\Support\RiskSkorlama::bant($rd->yontem, (float) $m->son_puan)['renk'] }}" @endif>@if($m->son_duzey)<span class="duzey-dikey">{{ $m->sonDuzeyDikey() }}</span>@else — @endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- EKİP + ONAY --}}
<div class="sayfa" style="page-break-after:auto">
    <h2>4. RİSK DEĞERLENDİRME EKİBİ</h2>
    <table class="ekip">
        <tr><th>Ad Soyad</th><th>Unvan / Görev</th></tr>
        @foreach ($ekipGosterim ?? [] as $uye)
            <tr><td>{{ $uye['ad'] }}&nbsp;</td><td>{{ $uye['unvan'] }}&nbsp;</td></tr>
        @endforeach
    </table>
    <p style="font-size:9.5px;color:#666;margin-top:6px">
        6331 SK m.6 ve Risk Değerlendirmesi Yönetmeliği uyarınca risk değerlendirmesi
        işveren, iş güvenliği uzmanı, işyeri hekimi, çalışan temsilcisi ve destek
        elemanlarından oluşan bir ekip tarafından gerçekleştirilir.
    </p>

    <h2 style="margin-top:24px">ONAY</h2>
    <table class="imza">
        <tr>
            <td>
                {{ $uzman?->name ?: 'İş Güvenliği Uzmanı' }}
                @if ($uzman?->unvan) <br><span style="font-weight:normal">{{ $uzman->unvanEtiketi() }}</span> @endif
                <br>(İmza – Kaşe)
            </td>
            <td>{{ $firma?->isveren_ad ?: 'İşveren / İşveren Vekili' }}<br>(Ad – Soyad / İmza)</td>
        </tr>
    </table>
</div>

</body>
</html>
