<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 26px 32px; }
    .baslik { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 4px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
    .kunye td { border: 1px solid #999; padding: 4px 8px; vertical-align: top; }
    .kunye td.k { background: #f0f0f0; font-weight: bold; width: 18%; }
    h2 { font-size: 11.5px; margin: 14px 0 5px; color: #7c3aed; border-bottom: 1px solid #7c3aed; padding-bottom: 3px; }
    p.metin { font-size: 10px; text-align: justify; margin: 0 0 6px; }
    table.liste { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 8px; }
    table.liste th, table.liste td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.liste th { background: #f0f0f0; }
    ol.nedenler { margin: 2px 0 8px; padding-left: 18px; font-size: 10px; }
    ol.nedenler li { margin-bottom: 3px; }
    .rozet { display: inline-block; padding: 1px 6px; border: 1px solid #7c3aed; border-radius: 8px; font-size: 9px; }
    .eksik { background: #fff7d6; border: 1px solid #e8c95a; padding: 6px 9px; font-size: 9.5px; margin-bottom: 10px; }
    .uyari { background: #fde8e8; border: 1px solid #e59a9a; color: #8b1c1c; padding: 5px 9px; font-size: 9.5px; margin-bottom: 4px; }
    .etki td { width: 33.3%; }
    table.imza { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 8px; }
    table.imza th { background: #222; color: #fff; text-align: left; padding: 5px 7px; }
    table.imza td { border: 1px solid #999; padding: 6px 7px; height: 46px; vertical-align: middle; }
    table.imza img { max-height: 40px; max-width: 90px; margin-right: 4px; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa img { max-width: 100%; max-height: 880px; }
</style>
</head>
<body>
@php
    $imzali = $imzali ?? true;
    $deger = fn ($v) => filled($v) ? $v : '—';
    $evetHayir = fn (bool $b) => $b ? 'Evet' : 'Hayır';
    $uzman = $firma?->igu;
    $hekim = $firma?->isyeriHekimi;
    $n = 0;
@endphp
<div class="sayfa">

    <div class="baslik">
        <h1>{{ $kayit->raporBasligi() }}</h1>
        <div style="font-size:11px">{{ $firma?->unvan }}</div>
        <div style="font-size:9.5px;color:#555;margin-top:2px">
            Form No: {{ $kayit->belge_no }} · Durum: {{ $kayit->durumEtiketi() }} · Düzenleme: {{ now()->format('d.m.Y H:i') }}
        </div>
    </div>

    @if ($eksikler = $kayit->eksikUyarilari())
        <div class="eksik">
            <strong>UYARI:</strong> Bu raporda eksik alanlar vardır:
            @foreach ($eksikler as $e)
                <br>• {{ $e }}
            @endforeach
        </div>
    @endif

    <h2>{{ ++$n }}. İŞYERİ / OLAY BİLGİLERİ</h2>
    <table class="kunye">
        <tr>
            <td class="k">Olay Tipi</td><td>{{ $kayit->tipEtiketi() }}</td>
            <td class="k">Tarih / Saat</td><td>{{ $kayit->olay_tarihi?->format('d.m.Y') ?: '—' }} {{ $kayit->olay_saati }}</td>
        </tr>
        <tr>
            <td class="k">İşyeri</td><td>{{ $deger($firma?->unvan) }}</td>
            <td class="k">Tehlike Sınıfı</td><td>{{ $firma?->tehlike_sinifi ? $firma->tehlikeSinifiEtiketi() : '—' }}</td>
        </tr>
        <tr>
            <td class="k">Adres</td><td>{{ $deger($firma?->adres) }}</td>
            <td class="k">SGK Sicil No</td><td>{{ $deger($firma?->sgk_sicil_no) }}</td>
        </tr>
        <tr>
            <td class="k">Olay Yeri</td><td>{{ $deger($kayit->olay_yeri) }}</td>
            <td class="k">Bölüm / Alan</td><td>{{ $deger(collect([$kayit->bolum, $kayit->alan])->filter()->implode(' / ')) }}</td>
        </tr>
        <tr>
            <td class="k">Yapılan İş</td><td>{{ $deger($kayit->yapilan_is) }}</td>
            <td class="k">Sınıflandırma</td><td>{{ $kayit->siniflandirma ? $kayit->siniflandirmaEtiketi() : '—' }}</td>
        </tr>
        <tr>
            <td class="k">Kullanılan Ekipman</td><td>{{ $deger($kayit->ekipman) }}</td>
            <td class="k">Kimyasal Madde</td><td>{{ $deger($kayit->kimyasal) }}</td>
        </tr>
        <tr>
            <td class="k">Bildiren</td><td>{{ $deger($kayit->bildiren_ad_soyad) }}</td>
            <td class="k">Bildirim Tarihi</td><td>{{ $kayit->bildirim_tarihi?->format('d.m.Y') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">İlgili Kişi</td><td>{{ $deger($kayit->etkilenen_ad_soyad) }} {{ $kayit->etkilenen_gorev ? '('.$kayit->etkilenen_gorev.')' : '' }}</td>
            <td class="k">Tanık Var mı</td><td>{{ $evetHayir(filled($kayit->taniklar)) }}</td>
        </tr>
        <tr>
            <td class="k">Etkilenen Unsurlar</td><td colspan="3">{{ implode(', ', $kayit->etkilenenEtiketleri()) ?: '—' }}</td>
        </tr>
    </table>

    <h2>{{ ++$n }}. OLAY AÇIKLAMASI</h2>
    <p class="metin"><strong>Özet:</strong> {{ $deger($kayit->olay_ozeti) }}</p>
    @if (filled($kayit->olay_detayi))
        <p class="metin"><strong>Detay:</strong> {{ $kayit->olay_detayi }}</p>
    @endif

    <table class="liste etki">
        @foreach (collect(config('isg.olay.etkiler'))->chunk(3) as $satir)
            <tr>
                @foreach ($satir as $anahtar => $etki)
                    <td><strong>{{ $etki['ad'] }}:</strong> {{ $evetHayir($kayit->etkiVar($anahtar)) }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>

    @if ($kayit->isKazasiMi())
        <table class="liste">
            <tr>
                <th style="width:25%">Kaza Türü</th><th style="width:25%">Yaralanma Türü</th>
                <th style="width:25%">SGK Bildirimi</th><th style="width:25%">Kolluk Bildirimi</th>
            </tr>
            <tr>
                <td>{{ $kayit->kaza_turu ? $kayit->kazaTuruEtiketi() : '—' }}</td>
                <td>{{ $deger($kayit->yaralanma_turu) }}</td>
                <td>{{ $kayit->sgk_bildirimi_yapildi ? 'Evet'.($kayit->sgk_bildirim_tarihi ? ' ('.$kayit->sgk_bildirim_tarihi->format('d.m.Y').')' : '') : 'Hayır' }}</td>
                <td>{{ $evetHayir((bool) $kayit->kolluk_bildirimi_yapildi) }}</td>
            </tr>
        </table>
        <p class="metin"><strong>Rapor Süresi (Kayıp Gün):</strong> {{ $kayit->kayip_gun_sayisi ?? 0 }}</p>
    @endif
    @if (filled($kayit->mudahale_detayi))
        <p class="metin"><strong>Müdahale Detayı:</strong> {{ $kayit->mudahale_detayi }}</p>
    @endif

    @if ($uyarilar = $kayit->otomatikUyarilar())
        <h2>{{ ++$n }}. OTOMATİK UYARI</h2>
        @foreach ($uyarilar as $u)
            <div class="uyari">{{ $u }}</div>
        @endforeach
    @endif

    <h2>{{ ++$n }}. KÖK NEDEN ANALİZİ — 5 NEDEN (5N)</h2>
    @if ($kayit->nedenZinciri())
        <ol class="nedenler">
            @foreach ($kayit->nedenZinciri() as $neden)
                <li>{{ $neden }}</li>
            @endforeach
        </ol>
    @else
        <p class="metin">5N zinciri girilmedi.</p>
    @endif

    <p class="metin"><strong>Kök Neden:</strong> {{ $deger($kayit->kok_neden) }}</p>
    @if ($kayit->kokNedenEtiketleri())
        <p class="metin"><strong>Kategori:</strong> {{ implode(', ', $kayit->kokNedenEtiketleri()) }}</p>
    @endif
    @if (filled($kayit->sistemsel_eksiklik))
        <p class="metin"><strong>Sistemsel Eksiklik:</strong> {{ $kayit->sistemsel_eksiklik }}</p>
    @endif

    <h2>{{ ++$n }}. RİSK DEĞERLENDİRME</h2>
    <table class="liste">
        <tr>
            <th style="width:20%">Sonuç Türü</th>
            <th style="width:20%">Olasılık</th>
            <th style="width:20%">Şiddet</th>
            <th style="width:20%">Potansiyel Risk</th>
            <th style="width:20%">Risk Analizinde</th>
        </tr>
        <tr>
            <td>{{ $kayit->sonucEtiketi() }}</td>
            <td>{{ $kayit->olasilikEtiketi() }}</td>
            <td>{{ $kayit->siddetEtiketi() }}</td>
            <td><span class="rozet">{{ $kayit->potansiyel_skor ?? '—' }} — {{ $kayit->potansiyelSeviye() }}</span></td>
            <td>{{ $kayit->riskAnalizindeEtiketi() }}</td>
        </tr>
    </table>
    @if (filled($kayit->risk_analizi_notu))
        <p class="metin"><strong>Risk Analizi Notu:</strong> {{ $kayit->risk_analizi_notu }}</p>
    @endif
    <p class="metin"><strong>Acil Durum İlgisi:</strong> {{ $kayit->acilDurumEtiketi() }}@if (filled($kayit->acil_durum_notu)) — {{ $kayit->acil_durum_notu }}@endif</p>

    <h2>{{ ++$n }}. DÜZELTİCİ / ÖNLEYİCİ FAALİYETLER</h2>
    @if (filled($kayit->duzeltici_faaliyet) || ! $kayit->dofRaporu)
        <p class="metin">{{ $kayit->duzeltici_faaliyet ?: 'Düzeltici / önleyici faaliyet tanımlanmamış.' }}</p>
    @endif
    @if ($kayit->dofRaporu)
        <table class="liste">
            <tr>
                <th style="width:11%">DÖF No</th><th style="width:25%">Tespit</th><th style="width:30%">Düzeltici / Önleyici</th>
                <th style="width:13%">Sorumlu</th><th style="width:10%">Termin</th><th style="width:11%">Durum</th>
            </tr>
            @forelse (($kayit->dofRaporu->maddeler ?? []) as $i => $m)
                <tr>
                    <td>{{ $kayit->dofRaporu->belge_no }}/{{ $i + 1 }}</td>
                    <td>{{ $m['tespit'] ?? '' }}</td>
                    <td>{!! nl2br(e($m['oneri'] ?? '—')) !!}</td>
                    <td>{{ ($m['sorumlu'] ?? null) ?: '—' }}</td>
                    <td>{{ filled($m['termin'] ?? null) ? \Illuminate\Support\Carbon::parse($m['termin'])->format('d.m.Y') : '—' }}</td>
                    <td>{{ \App\Models\DofRaporu::durumEtiketi($m['durum'] ?? null) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="color:#888">{{ $kayit->dofRaporu->belge_no }} raporunda madde yok.</td></tr>
            @endforelse
        </table>
    @endif

    <h2>{{ ++$n }}. GENEL DEĞERLENDİRME</h2>
    <p class="metin">{{ $kayit->genel_degerlendirme ?: 'Genel değerlendirme metni oluşturulmamış.' }}</p>

    @if (filled($kayit->taniklar))
        <h2>{{ ++$n }}. TANIKLAR</h2>
        <table class="liste">
            <tr><th style="width:5%">#</th><th>Ad Soyad</th><th>Görevi</th></tr>
            @foreach ($kayit->taniklar as $i => $t)
                <tr><td>{{ $i + 1 }}</td><td>{{ $t['ad_soyad'] ?? '' }}</td><td>{{ $t['gorev'] ?? '—' }}</td></tr>
            @endforeach
        </table>
    @endif

    <h2>{{ ++$n }}. İMZA ALANLARI</h2>
    <table class="imza">
        <tr><th style="width:30%">Unvan</th><th style="width:35%">Ad Soyad</th><th style="width:35%">Kaşe / İmza</th></tr>
        <tr><td>Olayı Bildiren</td><td>{{ $kayit->bildiren_ad_soyad }}</td><td></td></tr>
        <tr>
            <td>İş Güvenliği Uzmanı</td><td></td>
            <td>
                @if ($imzali && $kayit->rapor_hazirlayan_kase)<img src="{{ storage_path('app/public/'.$kayit->rapor_hazirlayan_kase) }}">@endif
                @if ($imzali && $uzman?->imza_gorseli)<img src="{{ storage_path('app/public/'.$uzman->imza_gorseli) }}">@endif
            </td>
        </tr>
        <tr>
            <td>İşyeri Hekimi</td><td></td>
            <td>
                @if ($imzali && $hekim && $hekim->ad_soyad === $kayit->isyeri_hekimi)
                    @if ($hekim->kase_gorseli)<img src="{{ storage_path('app/public/'.$hekim->kase_gorseli) }}">@endif
                    @if ($hekim->imza_gorseli)<img src="{{ storage_path('app/public/'.$hekim->imza_gorseli) }}">@endif
                @endif
            </td>
        </tr>
        <tr>
            <td>İşveren / İşveren Vekili</td><td></td>
            <td>
                @if ($imzali && $firma?->isveren_kase_gorseli)<img src="{{ storage_path('app/public/'.$firma->isveren_kase_gorseli) }}">@endif
                @if ($imzali && $firma?->isveren_imza_gorseli)<img src="{{ storage_path('app/public/'.$firma->isveren_imza_gorseli) }}">@endif
            </td>
        </tr>
    </table>

    <p class="yasal">
        6331 Sayılı İş Sağlığı ve Güvenliği Kanunu m.14 uyarınca; işveren, iş kazalarının ve
        meslek hastalıklarının yanı sıra "ramak kala" olaylarını da kayıt altına alır ve
        gerekli incelemeleri yaparak raporlarını düzenler. İş kazası, 5510 sayılı Kanun m.13
        uyarınca kolluk kuvvetlerine derhal, SGK'ya en geç kazadan sonraki üç iş günü içinde bildirilir.
    </p>
</div>

@foreach (($kayit->fotograflar ?? []) as $i => $foto)
    <div class="sayfa foto-sayfa">
        <div class="baslik">
            <h1>OLAY KAYIT VE İNCELEME FORMU</h1>
            <div style="font-size:11px">{{ $firma?->unvan }} — Fotoğraf {{ $i + 1 }}</div>
        </div>
        <img src="{{ storage_path('app/public/'.$foto) }}">
    </div>
@endforeach

</body>
</html>
