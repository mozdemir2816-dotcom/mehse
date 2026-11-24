<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 11px; }
    .sayfa { padding: 30px 36px; }
    .ust { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
    .ust .sol { display: table-cell; }
    .ust .sag { display: table-cell; text-align: right; font-size: 9px; color: #555; vertical-align: top; }
    .ust h1 { font-size: 14px; margin: 4px 0 0; }
    .ust .firma { font-size: 11px; font-weight: bold; }
    .adres { margin-bottom: 12px; }
    .govde { font-size: 11px; line-height: 1.6; text-align: justify; margin-bottom: 12px; }
    .kunye { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 14px; }
    .kunye td { border: 1px solid #999; padding: 6px 8px; }
    .kunye td:first-child { background: #f0f0f0; font-weight: bold; width: 34%; }
    .imza { margin-top: 26px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .imza td { text-align: center; padding: 6px 8px; font-size: 9.5px; vertical-align: bottom; }
    .imza .alan { height: 42px; border-bottom: 1px solid #111; margin: 0 10px 4px; }
    .oylama { width: 100%; border-collapse: collapse; font-size: 9.5px; margin: 10px 0 12px; }
    .oylama th, .oylama td { border: 1px solid #999; padding: 4px 7px; text-align: left; }
    .oylama th { background: #f0f0f0; font-weight: bold; }
    .yasal { margin-top: 14px; font-size: 8.5px; color: #666; }
    .cizgi { display: inline-block; border-bottom: 1px solid #111; }
</style>
</head>
<body>
@php
    $bosMi = $bos ?? false;
    $secilenler = $secim->secilenAdaylar();
    $aday = $secilenler[0] ?? ($secim->secilenAday() ?? []);
    $coklu = count($secilenler) > 1;
    $tire = fn ($deger) => filled($deger) ? $deger : ($bosMi ? '' : '—');
    $gorevTarihi = $secim->gorevlendirme_tarihi ?: $secim->secim_tarihi;
    $gecerlilikBitis = $gorevTarihi?->copy()->addYears(5);
    $adayListesi = collect($secim->adaylar ?? [])->filter(fn ($a) => filled($a['ad_soyad'] ?? null))->values();
    $toplamOy = $adayListesi->sum(fn ($a) => (int) ($a['oy'] ?? $a['oy_sayisi'] ?? 0));
@endphp
<div class="sayfa">

    <div class="ust">
        <div class="sol">
            <div class="firma">@if ($bosMi && ! $firma)<span class="cizgi" style="min-width:260px">&nbsp;</span>@else{{ $firma?->unvan }}@endif</div>
            <h1>ÇALIŞAN TEMSİLCİSİ SEÇİM SONUÇ VE ATAMA TUTANAĞI</h1>
        </div>
        <div class="sag">
            Belge No: {{ $secim->dokuman_no }}<br>
            Düzenleme Tarihi: {{ ($gorevTarihi ?: $secim->ilan_tarihi)?->format('d.m.Y') }}
        </div>
    </div>

    <div class="adres">İşyeri Adresi: @if ($bosMi && ! $firma)<span class="cizgi" style="min-width:320px">&nbsp;</span>@else{{ $firma?->adres }}@endif</div>

    <div class="govde">
        @if ($bosMi && ! $firma)<span class="cizgi" style="min-width:220px">&nbsp;</span>@else{{ $firma?->unvan }}@endif işyerinde gerçekleştirilen gizli oy açık sayım usulü çalışan temsilcisi seçimi sonucunda aşağıda bilgileri yer
        alan {{ $coklu ? 'çalışanlar' : 'çalışan' }}, 6331 sayılı İş Sağlığı ve Güvenliği Kanununun 20 nci maddesi ve ilgili Tebliğ uyarınca <strong>5 (beş) yıl</strong> süreyle çalışan temsilcisi olarak görevlendirilmiştir.
    </div>

    <table class="kunye">
        @if ($coklu)
            <tr>
                <td>Atanan Çalışan Temsilcileri ({{ count($secilenler) }})</td>
                <td>
                    @foreach ($secilenler as $idx => $s)
                        <div>{{ $idx + 1 }}. <strong>{{ $s['ad_soyad'] }}</strong>@if (! empty($s['unvan'])) — {{ $s['unvan'] }}@endif @if (! empty($s['bas_temsilci'])) <strong>(Baş Temsilci — 6331 m.20/2)</strong>@endif</div>
                    @endforeach
                </td>
            </tr>
        @else
            <tr><td>Atanan Çalışanın Adı Soyadı</td><td>{{ $tire($aday['ad_soyad'] ?? null) }}</td></tr>
            <tr><td>Unvanı / Görevi</td><td>{{ $tire($aday['unvan'] ?? null) }}</td></tr>
        @endif
        <tr><td>Seçim / Görevlendirme Tarihi</td><td>{{ $tire($gorevTarihi?->format('d.m.Y')) }}</td></tr>
        <tr><td>Görev Geçerlilik Süresi (5 Yıl — Tebliğ m.8/3)</td><td>{{ $gecerlilikBitis ? $gecerlilikBitis->format('d.m.Y').' tarihine kadar' : ($bosMi ? '' : '5 Yıl') }}</td></tr>
        <tr><td>İşyeri Çalışan / Zorunlu Temsilci Sayısı</td><td>{{ $tire($secim->isyeri_calisan_sayisi) }} çalışan / {{ $tire($secim->zorunlu_temsilci_sayisi) }} temsilci (6331 m.20/1)</td></tr>
    </table>

    @if ($adayListesi->isNotEmpty())
        <table class="oylama">
            <tr>
                <th style="width:6%">#</th>
                <th>Aday Adı Soyadı</th>
                <th style="width:28%">Görevi / Bölümü</th>
                <th style="width:14%">Alınan Oy</th>
                <th style="width:18%">Sonuç</th>
            </tr>
            @foreach ($adayListesi as $idx => $ad)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $ad['ad_soyad'] ?? '' }}</strong></td>
                    <td>{{ $ad['unvan'] ?? '—' }}</td>
                    <td>{{ $ad['oy'] ?? $ad['oy_sayisi'] ?? '—' }}</td>
                    <td>{{ ! empty($ad['secildi']) ? (! empty($ad['bas_temsilci']) ? 'Seçildi (Baş Temsilci)' : 'Seçildi') : 'Aday' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" style="font-size:8.5px;color:#444">
                    Toplam Geçerli Oy: <strong>{{ $toplamOy ?: '........' }}</strong> &nbsp;·&nbsp; Geçersiz Oy: ........ &nbsp;·&nbsp; Seçim geçerlilik şartı (Tebliğ m.8/2 — çalışanların en az yarıdan bir fazlasının katılımı): ☐ Sağlandı
                </td>
            </tr>
        </table>
    @endif

    <div class="govde">
        Çalışan temsilcisi; iş sağlığı ve güvenliği ile ilgili çalışmalara katılmaya, çalışmaları izlemeye, tehlike
        kaynağının yok edilmesi veya riskin azaltılması için işverenden tedbir alınmasını istemeye ve çalışanları
        temsil etmeye yetkilidir. Görevini yürütmesi nedeniyle hakları kısıtlanamaz; öğrendiği işyeri sırlarını ve çalışanlara ait özel bilgileri gizli tutmakla yükümlüdür.
    </div>

    <table class="imza">
        <tr>
            @forelse ($secilenler as $s)
                <td>
                    <div class="alan"></div>
                    <strong>{{ ! empty($s['bas_temsilci']) ? 'BAŞ TEMSİLCİ' : 'ÇALIŞAN TEMSİLCİSİ' }}</strong><br>
                    {{ $s['ad_soyad'] ?? '' }}<br>İmza
                </td>
            @empty
                <td>
                    <div class="alan"></div>
                    <strong>ÇALIŞAN TEMSİLCİSİ</strong><br>
                    {{ $aday['ad_soyad'] ?? '' }}<br>İmza
                </td>
            @endforelse
            <td>
                <div class="alan"></div>
                <strong>SEÇİM KURULU / İGU</strong><br>
                {{ $firma?->igu?->ad_soyad ?: '' }}<br>İmza
            </td>
            <td>
                <div class="alan"></div>
                <strong>İŞVEREN / İŞVEREN VEKİLİ</strong><br>
                {{ $firma?->isveren_vekili ?: ($firma?->isveren_ad ?: '') }}<br>Kaşe / İmza
            </td>
        </tr>
    </table>

    <p class="yasal">Dayanak: 6331 sayılı Kanun m.20 ve Çalışan Temsilcisinin Nitelikleri ve Seçilme Usul ve Esaslarına İlişkin Tebliğ (m.8 — 5 yıl geçerlidir).</p>

</div>
</body>
</html>
