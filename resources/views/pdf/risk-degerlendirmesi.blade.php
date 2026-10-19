<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 46px 25px 78px 25px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    /* KAPAK — Risk_Degerlendirmesi_Kapak.docx: ortalı başlık, açık gri etiket hücreli tablolar */
    .kapak { margin: 0 35px; page-break-after: always; position: relative; }
    /* Sabit şeritleri kapakta örter: @page marjları üst 46px / alt 78px, yatay 25px; içerik yüksekliği 1123-46-78 = 999px. */
    .kapak .k-ort { position: absolute; left: -60px; right: -60px; background: #fff; z-index: 50; }
    .kapak .k-ort-ust { top: -46px; height: 46px; }
    .kapak .k-ort-alt { top: 999px; height: 78px; }
    .kapak .k-logo { text-align: center; font-size: 12px; margin-top: 4px; }
    .kapak .k-logo img { max-height: 54px; max-width: 220px; }
    .kapak .k-ust { text-align: center; font-size: 13px; margin-top: 22px; }
    .kapak h1 { text-align: center; font-size: 30px; font-weight: bold; margin: 10px 0 0; letter-spacing: .5px; }
    .kapak .k-firma { text-align: center; font-size: 15px; font-weight: bold; margin-top: 22px; }
    .kapak .k-baslik { font-size: 12px; font-weight: bold; margin: 22px 0 9px; }
    table.k-tablo { width: 100%; border-collapse: collapse; font-size: 10px; }
    table.k-tablo td { border-top: 1px solid #c9ced3; border-bottom: 1px solid #c9ced3; padding: 0 6px; height: 23px; vertical-align: middle; text-align: left; }
    table.k-tablo td.e { background: #edf0f3; font-weight: bold; border-left: 1px solid #c9ced3; border-right: 1px solid #c9ced3; }
    table.k-tablo td.e2 { font-weight: bold; border-left: 1px solid #c9ced3; border-right: 1px solid #c9ced3; }
    table.k-ekip tr.bas td { background: #edf0f3; font-weight: bold; }
    table.k-ekip td { border-left: 1px solid #c9ced3; border-right: 1px solid #c9ced3; }
    table.k-ekip td.imza img { max-height: 18px; max-width: 45%; margin-right: 3px; }
    .kunye { margin: 20px 0; width: 100%; border-collapse: collapse; font-size: 11px; }
    .kunye td { border: 1px solid #999; padding: 6px 10px; text-align: left; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 35%; }
    .sayfa { padding: 25px 30px; page-break-after: always; }
    /* Prosedür → künye/yöntem → risk tablosu arasında zorunlu sayfa sonu yok:
       bölüm bitince sayfanın kalanı boş kalıyordu (kullanıcı 14.10.2026). */
    .sayfa.akis { page-break-after: auto; padding-bottom: 0; }
    h2 { font-size: 15px; border-bottom: 2px solid #7c3aed; padding-bottom: 4px; color: #7c3aed; page-break-after: avoid; }
    table.olcek { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9.5px; page-break-inside: avoid; }
    table.olcek th, table.olcek td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
    table.olcek th { background: #f0f0f0; }
    table.bant td:first-child { font-weight: bold; color: #fff; text-align: center; width: 22%; }
    /* table-layout:fixed + <colgroup> genişlikleri: otomatik düzende uzun kelimeler
       tabloyu sayfanın sağ kenarından taşırıyordu. */
    table.risk { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8px; table-layout: fixed; }
    table.risk th, table.risk td { border: 1px solid #999; padding: 3px 3px; text-align: center; vertical-align: middle; word-wrap: break-word; }
    table.risk thead th { background: #7c3aed; color: #fff; }
    table.risk .col-no { text-align: center; }
    table.risk .col-duzey { padding: 2px 1px; text-align: center; vertical-align: middle; }
    table.risk th.col-duzey { font-size: 6px; }
    table.risk .sayi { text-align: center; padding: 3px 1px; }
    .kisaltma-acik { font-size: 8px; color: #333; margin: 4px 0 0; }
    .kisaltma-acik span { display: inline-block; color: #fff; font-weight: bold; padding: 0 3px; border-radius: 2px; }
    table.ekip { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
    table.ekip th, table.ekip td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
    table.ekip th { background: #f0f0f0; }
    .duzey { color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 7.5px; white-space: nowrap; }
    /* Dikey düzey yazısı: transform:rotate KULLANILMAZ — dompdf'te rotate edilen
       kutu kendi hücresinin satırına göre kayabiliyor. Harf harf alt alta yazmak
       normal tablo akışında kalır, satırla her zaman hizalı kalması garanti olur.
       Renk artık span'e değil kutunun tamamını dolduran <td>'ye uygulanıyor
       (col-duzey vertical-align:middle ile satırın ortasında kalıyor). */
    .duzey-dikey { color: #fff; font-size: 8px; font-weight: bold; line-height: 1.1;
        white-space: pre-line; display: block; text-align: center; }
    .imza { margin-top: 50px; width: 100%; }
    .imza td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #111; font-size: 10px; }
    table.onay { width: 100%; border-collapse: collapse; margin-top: 30px; table-layout: fixed; }
    table.onay td { width: 33.33%; text-align: center; padding: 45px 6px 6px; border-top: 1px solid #111; font-size: 10px; vertical-align: bottom; }
    table.onay .rol { font-weight: bold; display: block; margin-bottom: 10px; font-size: 9.5px; }
    table.onay img { max-height: 30px; max-width: 90%; display: block; margin: 0 auto 2px; }
    table.onay .ad { font-weight: normal; display: block; }
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

    /* HER SAYFANIN ÜST BİLGİSİ — Firma Adı / Sicil No / Tarih / Adres (@page
       margin-top bunun için 25px'ten 46px'e çıkarıldı). Aynı sabit-konum
       tekniği (.sayfa-alt ile simetrik): -46px = @page'in üst marjıyla aynı,
       şerit sayfanın gerçek üst kenarına yaslanır. dompdf flexbox'u güvenilir
       desteklemediğinden (proje genelindeki diğer şeritler gibi) tek satırlık
       table ile hizalanır. */
    .sayfa-ust {
        position: fixed; top: -46px; left: 0; right: 0;
        border-bottom: 1px solid #999; padding-bottom: 3px; margin-bottom: 4px;
    }
    table.ust-bilgi { width: 100%; border-collapse: collapse; font-size: 8px; color: #333; }
    /* 1. satır: unvan tam genişlik; 2. satır: adres solda, sicil/tarih sağda —
       uzun unvanla sicil/tarih aynı satırda üst üste biniyor, tarih kesiliyordu. */
    table.ust-bilgi td { padding: 0; white-space: nowrap; overflow: hidden; }
    table.ust-bilgi td.sag { text-align: right; width: 40%; }
    .sayfa-ust b { color: #111; }
    .sayfa-ust .adres { font-size: 7px; color: #666; }
    table.alt-imza { width: 100%; border-collapse: collapse; font-size: 6.5px; table-layout: fixed; }
    table.alt-imza td { border: 1px solid #ccc; text-align: center; padding: 2px 2px 4px; vertical-align: bottom; }
    table.alt-imza .rol { font-weight: bold; display: block; margin-bottom: 12px; }
    table.alt-imza img { max-height: 22px; max-width: 90%; display: block; margin: 0 auto 1px; }
    table.alt-imza .ad { font-weight: normal; }
</style>
</head>
<body>

{{-- KAPAK — kullanıcının "Risk_Degerlendirmesi_Kapak.docx" şablonu birebir.
     Üst bilgi / imza şeritleri (position:fixed) dompdf'te her sayfaya basılır;
     kapakta şablon gereği görünmemeleri için üstlerine beyaz örtü (.k-ort) konur. "Toplam sayfa sayısı" hücresi (#kapak-toplam-sayfa)
     RiskDegerlendirmesiUretici'nde page_script ile doldurulur (tek render). --}}
@php
    $bosTarih = '…… / …… / …………';
    // Kapsam notu boşsa inşaat firmalarında şantiye faaliyetleri yazılır (kullanıcı 19.10.2026).
    $kapsam = filled($rd->kapsam_notu) ? $rd->kapsam_notu
        : (\App\Support\NaceGrubu::bul($firma?->nace_kodu ?: $rd->firma_nace) === 'insaat' ? 'Kazı çalışmaları, kalıp, demir, beton ve kamp alanı' : '');
    $kapakEkip = [
        ['İşveren / işveren vekili', $isverenAdi ?? '', []],
        ['İş Güvenliği Uzmanı', $iguAdi ?? '', []],
        ['İşyeri Hekimi', $hekimAdi ?? '', []],
        ['Çalışan Temsilcisi', $temsilci['ad'] ?? '', []],
        ['Destek Elemanı', $destekElemani['ad'] ?? '', []],
        ['Bilgi Sahibi Personel', $bilgiSahibiCalisan['ad'] ?? '', []],
    ];
    // firma_nace bazen açıklamayı da içerir ("22.12.01 Plastik ...") → kodu firmadan al.
    $naceKod = $firma?->nace_kodu ?: $rd->firma_nace;
    $faaliyet = collect([$firma?->nace_aciklama, $naceKod])->filter()->unique()->implode(' / ');
    $tehlike = collect([$firma?->tehlikeSinifiEtiketi(), $firma?->calisan_sayisi ? $firma->calisan_sayisi.' çalışan' : null])->filter()->implode(' / ');
    // Puanlar Türkçe ondalıkla: 0.2 → "0,2", 120.0 → "120".
    $sayi = fn ($v) => $v === null || $v === '' ? '—' : str_replace('.', ',', rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'));
    $ucEksen = $rd->ucEksenliMi();
    // Sütun genişlikleri (%), yatay A4: üç eksenli yöntemde iki F sütunu (2×2,5) eklenir,
    // Tehlike ve Risk'ten düşülür. Tehlike ile Risk ayrı sütun (kullanıcı 14.10.2026).
    $kolonlar = array_filter([
        2.5, 8, $ucEksen ? 11 : 13.5, $ucEksen ? 10 : 12.5, // No, Bölüm/Faaliyet, Tehlike, Risk
        2.5, $ucEksen ? 2.5 : null, 2.5, 3.5, 2,           // O, F, Ş, Puan, Düzey
        20, 7, 6,                                         // Öneri, Sorumlu, Termin
        2.5, $ucEksen ? 2.5 : null, 2.5, 3.5, 2,           // Son O, F, Ş, Puan, Düzey
        9.5,                                              // Açıklama
    ]);
    $bantlar = collect($metodoloji['bantlar'] ?? [])->map(fn ($b) => $b + ['kisa' => \App\Support\RiskSkorlama::kisaltma($rd->yontem, $b['ad'])]);
@endphp
@if (($bolum ?? null) !== 'yatay')
<div class="kapak">
    <div class="k-ort k-ort-ust"></div>
    <div class="k-ort k-ort-alt"></div>
    {{-- Şablondaki "FİRMA LOGOSU / FİRMA UNVANI": logo varsa logo; yoksa unvan zaten aşağıda büyük yazıldığı için boş. --}}
    <div class="k-logo">
        @if ($firma?->logo && is_file(storage_path('app/public/'.$firma->logo)))
            <img src="{{ storage_path('app/public/'.$firma->logo) }}">
        @else
            &nbsp;
        @endif
    </div>
    <div class="k-ust">İŞ SAĞLIĞI VE GÜVENLİĞİ</div>
    <h1>RİSK DEĞERLENDİRMESİ</h1>
    <div class="k-firma">{{ $rd->firma_unvan ?: $firma?->unvan }}</div>

    <div class="k-baslik">İŞYERİ BİLGİLERİ</div>
    <table class="k-tablo">
        <tr><td class="e" style="width:28.8%">İşyeri / proje adı</td><td>{{ $rd->proje_adi }}</td></tr>
        <tr><td class="e">İşyeri adresi</td><td style="height:34px">{{ $rd->firma_adres }}</td></tr>
        <tr><td class="e">SGK işyeri sicil numarası</td><td>{{ $rd->firma_sgk_sicil_no }}</td></tr>
        <tr><td class="e">Faaliyet konusu / NACE kodu</td><td>{{ $faaliyet }}</td></tr>
        <tr><td class="e">Tehlike sınıfı / çalışan sayısı</td><td>{{ $tehlike }}</td></tr>
        <tr><td class="e">Kapsam / değerlendirilen bölümler</td><td>{{ $kapsam }}</td></tr>
        <tr><td class="e">Toplam sayfa sayısı</td><td id="kapak-toplam-sayfa">&nbsp;</td></tr>
    </table>

    <div class="k-baslik">DOKÜMAN BİLGİLERİ</div>
    <table class="k-tablo">
        <tr>
            <td class="e" style="width:23.4%">Doküman numarası</td><td style="width:24%">{{ $rd->belge_no ?: 'RD-……' }}</td>
            <td class="e2" style="width:20.3%">Revizyon numarası</td><td>{{ $rd->revizyon_no ?: '……' }}</td>
        </tr>
        <tr>
            <td class="e">Değerlendirme tarihi</td><td>{{ $rd->rapor_tarihi?->format('d.m.Y') ?? $bosTarih }}</td>
            <td class="e2">Revizyon tarihi</td><td>{{ $bosTarih }}</td>
        </tr>
        <tr>
            <td class="e">Planlanan yenileme tarihi</td><td>{{ $rd->gecerlilik_tarihi?->format('d.m.Y') ?? $bosTarih }}</td>
            <td class="e2">Yöntem</td><td>{{ $rd->yontemEtiketi() }}</td>
        </tr>
    </table>

    <div class="k-baslik">RİSK DEĞERLENDİRMESİ EKİBİ</div>
    <table class="k-tablo k-ekip">
        <tr class="bas"><td style="width:33.8%">Görevi</td><td style="width:43.7%">Adı soyadı</td><td>İmza</td></tr>
        @foreach ($kapakEkip as [$gorev, $ad])
            <tr>
                <td>{{ $gorev }}</td>
                <td>{{ $ad }}</td>
                <td class="imza">&nbsp;</td>
            </tr>
        @endforeach
    </table>
</div>
@endif

{{-- HER SAYFANIN ÜST BİLGİSİ (firma adı / sicil no / tarih / adres) --}}
<div class="sayfa-ust">
    <table class="ust-bilgi">
        <tr><td colspan="2"><b>{{ $firma?->unvan }}</b>@if($rd->proje_adi) &nbsp;·&nbsp; {{ $rd->proje_adi }}@endif</td></tr>
        <tr>
            <td class="adres">{{ Illuminate\Support\Str::limit($rd->firma_adres, 90, '') }}</td>
            <td class="sag">SGK Sicil No: {{ $rd->firma_sgk_sicil_no ?: '—' }} &nbsp;·&nbsp; Tarih: {{ $rd->rapor_tarihi?->format('d.m.Y') }}</td>
        </tr>
    </table>
</div>

{{-- HER SAYFANIN ALT BİLGİSİ (imza/kaşe şeridi) --}}
<div class="sayfa-alt">
    <table class="alt-imza">
        <tr>
            <td>
                <span class="rol">İŞVEREN / İŞVEREN VEKİLİ</span>
                <span class="ad">{{ $isverenAdi ?? '—' }}</span>
            </td>
            <td>
                <span class="rol">İŞ GÜVENLİĞİ UZMANI</span>
                <span class="ad">{{ $iguAdi ?? '—' }}</span>
            </td>
            <td>
                <span class="rol">İŞYERİ HEKİMİ</span>
                <span class="ad">{{ $hekimAdi ?? '—' }}</span>
            </td>
            <td>
                <span class="rol">ÇALIŞAN TEMSİLCİSİ</span>
                <span class="ad">{{ $temsilci['ad'] ?? '—' }}</span>
            </td>
            <td>
                <span class="rol">DESTEK ELEMANI</span>
                <span class="ad">{{ $destekElemani['ad'] ?? '—' }}</span>
            </td>
            <td>
                <span class="rol">BİLGİ SAHİBİ PERSONEL</span>
                <span class="ad">{{ $bilgiSahibiCalisan['ad'] ?? '—' }}</span>
            </td>
        </tr>
    </table>
</div>

{{-- Dikey parça: prosedür + künye / yöntem --}}
@if (($bolum ?? null) !== 'yatay')
{{-- RİSK ANALİZİ PROSEDÜRÜ (seçilen yönteme göre) --}}
@if ($prosedur)
    <div class="sayfa akis prosedur">
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
<div class="sayfa akis">
    <h2>1. İŞYERİ KÜNYESİ</h2>
    <table class="kunye">
        <tr><td>Ticari Unvan</td><td>{{ $rd->firma_unvan }}</td></tr>
        <tr><td>SGK Sicil No</td><td>{{ $rd->firma_sgk_sicil_no ?: '—' }}</td></tr>
        <tr><td>NACE Kodu</td><td>{{ $rd->firma_nace ?: '—' }}</td></tr>
        <tr><td>Tehlike Sınıfı</td><td>{{ $firma?->tehlikeSinifiEtiketi() }}</td></tr>
        <tr><td>Adres</td><td>{{ $rd->firma_adres ?: '—' }}</td></tr>
    </table>

    @if ($rd->yontem !== 'hazop')
    <h2 style="margin-top:20px">2. DEĞERLENDİRME YÖNTEMİ</h2>
    <p style="font-size:10.5px">{{ $metodoloji['aciklama'] }}</p>

    <table class="olcek">
        <tr><th>Olasılık</th><th>Açıklama</th></tr>
        @foreach ($metodoloji['olasilik'] as $puan => $aciklama)
            <tr><td>{{ $sayi($puan) }}</td><td>{{ $aciklama }}</td></tr>
        @endforeach
    </table>

    @if (isset($metodoloji['frekans']))
        <table class="olcek">
            <tr><th>{{ $eksenEtiketleri['frekans'] }}</th><th>Açıklama</th></tr>
            @foreach ($metodoloji['frekans'] as $puan => $aciklama)
                <tr><td>{{ $sayi($puan) }}</td><td>{{ $aciklama }}</td></tr>
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
        <tr><th>Düzey</th><th>Kısaltma</th><th>Puan aralığı</th><th>Yapılacak eylem</th></tr>
        @foreach ($bantlar as $bant)
            <tr>
                <td style="background:{{ $bant['renk'] }}">{{ $bant['ad'] }}</td>
                <td style="text-align:center;font-weight:bold">{{ $bant['kisa'] }}</td>
                <td>≥ {{ $sayi($bant['min']) }}</td>
                <td>{{ $bant['eylem'] }}</td>
            </tr>
        @endforeach
    </table>
    @endif
</div>

@endif
{{-- Yatay parça: risk tablosu (yeni sayfadan) + ekip / onay — RiskDegerlendirmesiUretici iki parçayı birleştirir. --}}
@if (($bolum ?? null) !== 'dikey')
{{-- RİSK TABLOSU --}}
<div class="sayfa">
    <h2>3. TEHLİKELERİN TANIMLANMASI VE RİSK DEĞERLENDİRME TABLOSU</h2>
    @if ($rd->yontem === 'hazop')
        @include('pdf.partials.hazop-tablo')
    @else
    @php $fBaslik = $rd->yontem === 'fmea' ? 'D' : 'F'; @endphp
    <p class="kisaltma-acik">Düzey kısaltmaları:
        @foreach ($bantlar as $bant)
            <span style="background:{{ $bant['renk'] }}">{{ $bant['kisa'] }}</span> {{ $bant['ad'] }}@if(! $loop->last) &nbsp; @endif
        @endforeach
    </p>
    @php
        $basliklar = array_values(array_filter([
            ['col-no', 'No'], ['', 'Bölüm / Faaliyet'], ['', 'Tehlike'], ['', 'Risk'],
            ['sayi', 'O'], $ucEksen ? ['sayi', $fBaslik] : null, ['sayi', 'Ş'], ['sayi', 'Puan'], ['col-duzey', 'Dü<br>zey'],
            ['col-oneri', 'Öneri'], ['col-sorumlu', 'Sorumlu'], ['', 'Termin'],
            ['sayi', 'Son O'], $ucEksen ? ['sayi', 'Son '.$fBaslik] : null, ['sayi', 'Son Ş'], ['sayi', 'Son Puan'], ['col-duzey', 'Son<br>Dü<br>zey'],
            ['col-aciklama', 'Açıklama'],
        ]));
        $kolonlar = array_values($kolonlar);
        $sira = 0;
    @endphp
    {{-- dompdf'te tek uzun tablo yavaş: 100'er maddelik ardışık tablolar süreyi yaklaşık yarıya
         indirir (14.10.2026 yerel ölçüm: 400 madde 58 → 34 sn, 352 madde 37 → 27 sn). Her parça yeni sayfadan
         başlar; yoksa parçanın başlık satırı sayfa ortasında görünüyordu (kullanıcı 14.10.2026). --}}
    @foreach ($rd->maddeler->chunk($parcaBoyutu ?? 100) as $parca)
    <table class="risk" @unless($loop->first) style="margin-top:0;page-break-before:always" @endunless>
        {{-- dompdf <col> genişliğini yok sayar; fixed düzende ilk satır (başlık) hücrelerinin genişliği geçerlidir. --}}
        <thead>
        <tr>
            @foreach ($basliklar as $i => [$sinif, $etiket])
                <th class="{{ $sinif }}" style="width:{{ $kolonlar[$i] }}%">{!! $etiket !!}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($parca as $m)
            <tr>
                <td class="col-no">{{ ++$sira }}</td>
                <td>{{ $m->bolum }}@if($m->faaliyet)<br><em>{{ $m->faaliyet }}</em>@endif</td>
                <td>{{ $m->tehlike }}</td>
                <td>{{ $m->risk }}</td>
                <td class="sayi">{{ $sayi($m->olasilik) }}</td>
                @if ($ucEksen)
                    <td class="sayi">{{ $sayi($m->frekans) }}</td>
                @endif
                <td class="sayi">{{ $sayi($m->siddet) }}</td>
                <td class="sayi">{{ $sayi($m->puan) }}</td>
                <td class="col-duzey" style="background:{{ $m->rengi() }}"><span class="duzey-dikey">{{ $m->duzeyDikey() }}</span></td>
                <td class="col-oneri">{{ $m->oneri ?: '—' }}</td>
                <td class="col-sorumlu">{{ $m->sorumluMetni() }}</td>
                <td>{{ $m->termin ?: \App\Models\RiskMaddesi::VARSAYILAN_TERMIN }}</td>
                <td class="sayi">{{ $sayi($m->son_olasilik) }}</td>
                @if ($ucEksen)
                    <td class="sayi">{{ $sayi($m->son_frekans) }}</td>
                @endif
                <td class="sayi">{{ $sayi($m->son_siddet) }}</td>
                <td class="sayi">{{ $m->son_puan ? $sayi($m->son_puan) : '—' }}</td>
                <td class="col-duzey" @if($m->son_duzey) style="background:{{ \App\Support\RiskSkorlama::bant($rd->yontem, (float) $m->son_puan)['renk'] }}" @endif>@if($m->son_duzey)<span class="duzey-dikey">{{ $m->sonDuzeyDikey() }}</span>@else — @endif</td>
                <td class="col-aciklama">{{ $m->aciklamaMetni() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endforeach
    @endif
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
    <table class="onay">
        <tr>
            <td>
                <span class="rol">İŞVEREN / İŞVEREN VEKİLİ</span>
                <span class="ad">{{ $isverenAdi ?? '' }}</span>
            </td>
            <td>
                <span class="rol">İŞ GÜVENLİĞİ UZMANI</span>
                <span class="ad">{{ $iguAdi ?? '' }}</span>
            </td>
            <td>
                <span class="rol">İŞYERİ HEKİMİ</span>
                <span class="ad">{{ $hekimAdi ?? '' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="rol">ÇALIŞAN TEMSİLCİSİ</span>
                <span class="ad">{{ $temsilci['ad'] ?? '' }}</span>
            </td>
            <td>
                <span class="rol">DESTEK ELEMANI</span>
                <span class="ad">{{ $destekElemani['ad'] ?? '' }}</span>
            </td>
            <td>
                <span class="rol">BİLGİ SAHİBİ PERSONEL</span>
                <span class="ad">{{ $bilgiSahibiCalisan['ad'] ?? '' }}</span>
            </td>
        </tr>
    </table>
</div>
@endif

</body>
</html>
