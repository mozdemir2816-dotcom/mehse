<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 10px; }
    .sayfa { padding: 24px 30px; }
    .baslik { text-align: center; border-bottom: 3px double #b91c1c; padding-bottom: 8px; margin-bottom: 12px; }
    .baslik h1 { font-size: 15px; margin: 0 0 3px; color: #b91c1c; }
    .baslik .alt { font-size: 8.5px; color: #666; }
    .baslik .firma { font-size: 10px; margin-top: 3px; }
    h2 { font-size: 10.5px; margin: 14px 0 5px; color: #b91c1c; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
    table.kunye { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 4px; }
    table.kunye td { border: 1px solid #999; padding: 4px 7px; }
    table.kunye td.e { background: #f0f0f0; font-weight: bold; width: 16%; }
    p.ozet { font-size: 9.5px; text-align: justify; border: 1px solid #ccc; padding: 6px 8px; margin: 0 0 4px; background: #fafafa; }
    table.tbl { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 6px; }
    table.tbl th, table.tbl td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.tbl th { background: #b91c1c; color: #fff; font-size: 8.5px; }
    table.tbl tr.kok td { background: #fef9c3; font-weight: bold; }
    .fb { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 4px; }
    .fb td { vertical-align: top; padding: 0; }
    .fb .kat { border: 1px solid #666; border-radius: 3px; margin: 2px; padding: 3px 5px; }
    .fb .kat h3 { margin: 0 0 2px; font-size: 8px; background: #ecf0f1; padding: 2px 3px; }
    .fb .kat ul { margin: 1px 0 0; padding-left: 11px; font-size: 7.4px; line-height: 1.25; }
    .fb .kat .bos { color: #aaa; font-style: italic; font-size: 7px; }
    .fb .spine { background: #34495e; color: #fff; text-align: center; font-weight: bold; padding: 4px; border-radius: 3px; margin: 2px; font-size: 7.5px; }
    .fb .problem { border: 2px solid #b91c1c; color: #b91c1c; border-radius: 3px; text-align: center; font-weight: bold; padding: 6px 3px; margin: 2px; font-size: 8px; }
    .durum-acik { color: #b45309; font-weight: bold; }
    .durum-tamamlandi { color: #15803d; font-weight: bold; }
    .imza { margin-top: 22px; width: 100%; border-collapse: collapse; }
    .imza td { width: 33%; text-align: center; font-size: 9px; padding: 0 6px; }
    .imza .rol { font-weight: bold; }
    .imza .unvan { color: #555; font-size: 8px; }
    .imza .kutu { border: 1px solid #999; height: 62px; margin-top: 4px; position: relative; }
    .imza .kutu img { max-height: 56px; max-width: 90%; position: absolute; top: 3px; left: 50%; margin-left: -45%; }
    .yasal { margin-top: 10px; font-size: 8px; color: #666; }
    .foto-sayfa { page-break-before: always; }
    .foto-sayfa img { max-width: 100%; max-height: 860px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>İŞ KAZASI İNCELEME VE KÖK NEDEN ANALİZ RAPORU</h1>
        <div class="alt">5 Neden Analizi • Balık Kılçığı (Ishikawa) • Düzeltici Önleyici Faaliyetler</div>
        <div class="firma">{{ $firma?->unvan }}</div>
    </div>

    {{-- 1. GENEL BİLGİLER --}}
    <h2>1. GENEL BİLGİLER</h2>
    <table class="kunye">
        <tr>
            <td class="e">Kaza Tarihi</td><td>{{ $rapor->kaza_tarihi?->format('d.m.Y') ?: '—' }} {{ $rapor->kaza_saati ?: '' }}</td>
            <td class="e">Kaza Yeri</td><td>{{ $rapor->kaza_yeri ?: '—' }}</td>
        </tr>
        <tr>
            <td class="e">Kazazede</td><td>{{ $rapor->kazazede_ad_soyad ?: '—' }}</td>
            <td class="e">Görev</td><td>{{ $rapor->kazazede_gorev ?: '—' }}</td>
        </tr>
        <tr>
            <td class="e">Kıdem</td><td>{{ $rapor->kazazede_kidem ?: '—' }}</td>
            <td class="e">Durum</td><td>{{ $rapor->durumEtiketi() }}</td>
        </tr>
        <tr>
            <td class="e">Kaza Türü</td><td>{{ $rapor->kazaTuruEtiketi() }}</td>
            <td class="e">Ağırlık / Kayıp Gün</td><td>{{ $rapor->agirlikDerecesiEtiketi() }} @if ($rapor->kayip_gun_sayisi) · {{ $rapor->kayip_gun_sayisi }} gün @endif</td>
        </tr>
        <tr>
            <td class="e">Belge No</td><td>{{ $rapor->belge_no }}</td>
            <td class="e">SGK Bildirimi</td>
            <td>{{ $rapor->sgk_bildirimi_yapildi ? 'Yapıldı' : 'Yapılmadı' }}@if ($rapor->sgk_bildirimi_yapildi && $rapor->sgk_bildirim_tarihi) ({{ $rapor->sgk_bildirim_tarihi->format('d.m.Y') }}) @endif</td>
        </tr>
    </table>
    <div style="font-weight:bold;font-size:8.5px;color:#555;margin:6px 0 2px">KAZA ÖZETİ</div>
    <p class="ozet">{{ $rapor->kaza_tanimi ?: '—' }}</p>

    {{-- 2. 5 NEDEN --}}
    <h2>2. KÖK NEDEN ANALİZİ (5 NEDEN)</h2>
    <table class="tbl">
        <tr><th style="width:22px">#</th><th style="width:38%">Soru</th><th>Yanıt</th></tr>
        @foreach ($rapor->besNedenSatirlari() as $s)
            <tr class="{{ $s['no'] === 5 ? 'kok' : '' }}">
                <td>{{ $s['no'] }}</td>
                <td>{{ $s['soru'] }}</td>
                <td>{{ $s['yanit'] ?: '—' }}</td>
            </tr>
        @endforeach
    </table>

    {{-- 3. BALIK KILÇIĞI --}}
    <h2>3. BALIK KILÇIĞI (ISHIKAWA) — 6M MODELİ</h2>
    @php
        $kats = collect($rapor->balikKilcigiKategorileri())->keyBy('anahtar');
        $blok = function ($k) use ($kats) {
            $c = $kats[$k] ?? ['etiket' => $k, 'nedenler' => []];
            $h = '<div class="kat"><h3>'.e($c['etiket']).'</h3>';
            if (empty($c['nedenler'])) { $h .= '<div class="bos">— neden girilmedi —</div>'; }
            else { $h .= '<ul>'; foreach ($c['nedenler'] as $n) { $h .= '<li>'.e($n).'</li>'; } $h .= '</ul>'; }
            return $h.'</div>';
        };
    @endphp
    <table class="fb">
        <tr>
            <td style="width:31%">{!! $blok('insan') !!}</td>
            <td style="width:31%">{!! $blok('makine') !!}</td>
            <td style="width:31%">{!! $blok('metot') !!}</td>
            <td style="width:7%" rowspan="3"><div class="problem">KAZA<br><span style="font-weight:normal;font-size:6.5px">{{ \Illuminate\Support\Str::limit((string) $rapor->kaza_tanimi, 70) }}</span></div></td>
        </tr>
        <tr><td colspan="3"><div class="spine">◄════  KÖK NEDEN OMURGASI  ════►</div></td></tr>
        <tr>
            <td>{!! $blok('malzeme') !!}</td>
            <td>{!! $blok('olcum') !!}</td>
            <td>{!! $blok('cevre') !!}</td>
        </tr>
    </table>

    {{-- 4. DÖF --}}
    <h2>4. DÜZELTİCİ VE ÖNLEYİCİ FAALİYETLER (DÖF)</h2>
    <table class="tbl">
        <tr><th style="width:22px">#</th><th style="width:80px">Tip</th><th>Açıklama</th><th style="width:80px">Sorumlu</th><th style="width:60px">Hedef Tarih</th><th style="width:60px">Durum</th></tr>
        @forelse ($rapor->dofSatirlari() as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $rapor->dofTipEtiketi($d['tip'] ?? null) }}</td>
                <td>{{ $d['aciklama'] ?? '' }}</td>
                <td>{{ $d['sorumlu'] ?? '—' }}</td>
                <td>{{ !empty($d['hedef_tarih']) ? \Illuminate\Support\Carbon::parse($d['hedef_tarih'])->format('d.m.Y') : '—' }}</td>
                <td class="durum-{{ $d['durum'] ?? 'acik' }}">{{ $rapor->dofDurumEtiketi($d['durum'] ?? null) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="color:#888">Düzeltici faaliyet tanımlanmadı.</td></tr>
        @endforelse
    </table>

    {{-- 5. FOTOĞRAF VE KRİTİK NOTLAR --}}
    <h2>5. FOTOĞRAF VE KRİTİK NOTLAR</h2>
    @if ($rapor->kritik_notlar)
        <p class="ozet">{{ $rapor->kritik_notlar }}</p>
    @endif
    @if ($rapor->taniklar)
        <table class="tbl">
            <tr><th style="width:22px">#</th><th>Tanık Ad Soyad</th><th style="width:35%">Görevi</th></tr>
            @foreach ($rapor->taniklar as $i => $t)
                <tr><td>{{ $i + 1 }}</td><td>{{ $t['ad_soyad'] ?? '' }}</td><td>{{ $t['gorev'] ?? '—' }}</td></tr>
            @endforeach
        </table>
    @endif
    @if (empty($rapor->kritik_notlar) && empty($rapor->taniklar) && empty($rapor->fotograflar))
        <p style="font-size:9px;color:#888">Ek not / tanık / fotoğraf girilmemiş.</p>
    @endif

    <table class="imza">
        <tr>
            <td>
                <div class="rol">HAZIRLAYAN</div><div class="unvan">İş Güvenliği Uzmanı</div>
                <div class="kutu">
                    @if ($rapor->rapor_hazirlayan_kase && is_file(storage_path('app/public/'.$rapor->rapor_hazirlayan_kase)))
                        <img src="{{ storage_path('app/public/'.$rapor->rapor_hazirlayan_kase) }}">
                    @endif
                </div>
                <div style="font-size:8px;margin-top:2px">{{ $rapor->rapor_hazirlayan ?: '' }}</div>
            </td>
            <td>
                <div class="rol">ONAYLAYAN</div><div class="unvan">İşyeri Hekimi</div>
                <div class="kutu">
                    @if ($rapor->isyeri_hekimi_dahil && $rapor->isyeri_hekimi_kase && is_file(storage_path('app/public/'.$rapor->isyeri_hekimi_kase)))
                        <img src="{{ storage_path('app/public/'.$rapor->isyeri_hekimi_kase) }}">
                    @endif
                </div>
                <div style="font-size:8px;margin-top:2px">{{ $rapor->isyeri_hekimi_dahil ? ($rapor->isyeri_hekimi_adi ?: '') : '' }}</div>
            </td>
            <td>
                <div class="rol">İŞVEREN / VEKİLİ</div><div class="unvan">&nbsp;</div>
                <div class="kutu"></div>
            </td>
        </tr>
    </table>

    <p class="yasal">6331 Sayılı İş Sağlığı ve Güvenliği Kanunu Madde 14 uyarınca düzenlenmiştir. Oluşturulma: {{ now()->format('d.m.Y') }}</p>

</div>

@foreach (($rapor->fotograflar ?? []) as $i => $foto)
    <div class="sayfa foto-sayfa">
        <div class="baslik"><h1>İŞ KAZASI İNCELEME RAPORU</h1><div class="firma">{{ $firma?->unvan }}</div></div>
        <div style="font-weight:bold;font-size:10px;margin-bottom:6px">KAZA YERİ FOTOĞRAFI {{ $i + 1 }}</div>
        <img src="{{ storage_path('app/public/'.$foto) }}">
    </div>
@endforeach

</body>
</html>
