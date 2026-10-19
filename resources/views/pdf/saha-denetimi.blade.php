<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    /* Örnek "Şantiye Denetim ve Değerlendirme" çıktısının düzeni (11.10.2026):
       dikey A4, her sayfada koyu başlık bandı + turuncu çizgi, sağ altta sayfa no. */
    @page { margin: 66px 34px 44px 34px; }
    * { font-family: DejaVu Serif, serif; }
    body { margin: 0; color: #2b2724; font-size: 8px; }

    .ust { position: fixed; top: -66px; left: -34px; right: -34px; height: 38px;
           background: #4a4540; color: #fff; text-align: center; padding-top: 7px;
           border-bottom: 4px solid #e8762c; }
    .ust h1 { font-size: 12.5px; font-weight: normal; margin: 0; letter-spacing: .3px; }
    .ust div { font-size: 7.5px; margin-top: 1px; }

    table { border-collapse: collapse; width: 100%; }

    table.kunye { font-size: 8px; margin-bottom: 8px; }
    table.kunye td { border: 1px solid #d4cec6; padding: 3px 6px; }
    table.kunye td.etiket { background: #f3efe9; width: 13%; }

    table.ana { font-size: 7.3px; margin-bottom: 10px; }
    table.ana th { background: #4a4540; color: #fff; font-weight: normal; padding: 4px 5px; text-align: center; }
    table.ana td { border: 1px solid #ddd8d1; padding: 1.5px 5px; vertical-align: middle; }
    table.ana td.orta { text-align: center; }
    table.ana td.foto { text-align: center; }
    table.ana img.satir-foto { max-width: 46%; max-height: 34px; margin: 0 1px; }
    tr.kategori td { background: #f6f2ec; text-align: center; padding: 2px; }
    .kritik { color: #c0392b; }
    .sonuc-uygun_degil { color: #c0392b; }

    table.ekip { font-size: 7.5px; margin-bottom: 10px; }
    table.ekip th { background: #e8762c; color: #fff; font-weight: normal; padding: 3px 6px; text-align: center; }
    table.ekip td { border: 1px solid #ddd8d1; padding: 3px 6px; }

    table.uyarilar { font-size: 6.6px; margin-bottom: 10px; }
    table.uyarilar th { background: #4a4540; color: #fff; font-weight: normal; padding: 3px 6px; }
    table.uyarilar td { border: 1px solid #ddd8d1; padding: 2px 5px; width: 33.33%; }

    table.alt td { vertical-align: top; font-size: 8.5px; }
    .imza { width: 220px; margin-left: auto; text-align: center; }
    .imza img { max-height: 70px; max-width: 190px; }
    .imza .cizgi { border-top: 1px solid #2b2724; margin-top: 4px; padding-top: 2px; font-size: 7px; color: #6b635b; }

    .kanit { page-break-before: always; }
    .kanit h2 { font-size: 12px; font-weight: normal; margin: 0 0 8px; }
    .kanit .alt-baslik { font-size: 9px; margin-bottom: 12px; }
    .kanit img { max-width: 100%; max-height: 640px; }
    .kanit .kimlik { font-size: 7px; margin-top: 10px; }
</style>
</head>
<body>

<div class="ust">
    <h1>{{ mb_strtoupper(str_replace('i', 'İ', config('isg.saha_denetimi.sablon_adi')), 'UTF-8') }}</h1>
    <div>{{ $denetim->belgeAdi() }}</div>
</div>

<table class="kunye">
    <tr>
        <td class="etiket">Firma</td><td style="width:37%">{{ $firma?->unvan }}</td>
        <td class="etiket">Şantiye</td><td>{{ $denetim->santiye_adi ?: '-' }}</td>
    </tr>
    <tr>
        <td class="etiket">İşin Tanımı</td><td>{{ $denetim->is_tanimi ?: '-' }}</td>
        <td class="etiket">Referans No</td><td>{{ $denetim->is_referans_no ?: '-' }}</td>
    </tr>
    @if ($denetim->sektorEtiketi())
    <tr>
        <td class="etiket">Sektör</td><td colspan="3">{{ $denetim->sektorEtiketi() }}</td>
    </tr>
    @endif
    <tr>
        <td class="etiket">Tarih / Saat</td><td>{{ $denetim->denetim_tarihi?->format('d.m.Y') }} · {{ $denetim->denetim_saati }}</td>
        <td class="etiket">Sorumlu</td><td>{{ $denetim->santiye_sorumlusu ?: '-' }}</td>
    </tr>
    <tr>
        <td class="etiket">Denetçi</td><td>İş Güvenliği Uzmanı</td>
        <td class="etiket">Sonuç</td><td>{{ $denetim->sonucEtiketi() }}</td>
    </tr>
</table>

<table class="ana">
    <tr>
        <th style="width:11%">Kategori / Durum</th>
        <th style="width:5%">Kod</th>
        <th style="width:44%">Kontrol İfadesi</th>
        <th style="width:9%">Sonuç</th>
        <th style="width:10%">Foto</th>
        <th style="width:21%">Açıklama</th>
    </tr>
    @php
        $mevcutKategori = null;
        // Uygulanamaz maddeler rapor tablosunda yer kaplamasın — istatistiklere zaten dahil değil.
        $gosterilecekCevaplar = collect($denetim->cevaplar ?? [])->reject(fn ($c) => ($c['sonuc'] ?? null) === 'uygulanamaz');
    @endphp
    @forelse ($gosterilecekCevaplar as $c)
        @if ($c['kategori_ad'] !== $mevcutKategori)
            @php $mevcutKategori = $c['kategori_ad']; @endphp
            <tr class="kategori"><td colspan="6">{{ mb_strtoupper(str_replace('i', 'İ', $mevcutKategori), 'UTF-8') }}</td></tr>
        @endif
        @php $fotolar = \App\Models\SahaDenetimi::maddeFotolari($c); @endphp
        <tr>
            <td class="orta">@if ($c['kritik'])<span class="kritik">KRİTİK</span>@endif</td>
            <td class="orta">{{ $c['kod'] }}</td>
            <td>{{ $c['ifade'] }}@if (filled($c['dayanak'] ?? null))<br><span style="font-size:7px;color:#666">Dayanak: {{ $c['dayanak'] }}</span>@endif</td>
            <td class="orta sonuc-{{ $c['sonuc'] ?? '' }}">{{ config('isg.saha_denetimi.sonuc_secenekleri.'.($c['sonuc'] ?? ''), '-') }}</td>
            <td class="foto">
                @forelse ($fotolar as $yol)
                    <img class="satir-foto" src="{{ storage_path('app/public/'.$yol) }}">
                @empty
                    -
                @endforelse
            </td>
            <td @if (blank($c['aciklama'] ?? null)) class="orta" @endif>{{ $c['aciklama'] ?: '-' }}@if (filled($c['oneri'] ?? null) && ($c['sonuc'] ?? null) === 'uygun_degil' && ! str_contains((string) ($c['aciklama'] ?? ''), $c['oneri']))<br><span style="font-size:7px;color:#666">Öneri: {{ $c['oneri'] }}</span>@endif
                @if (filled($c['risk'] ?? null) || filled($c['sorumlu'] ?? null) || filled($c['termin'] ?? null))
                    <br><span style="font-size:7px;color:#444">{{ collect([
                        filled($c['risk'] ?? null) ? 'Risk: '.config('isg.saha_denetimi.risk_seviyeleri.'.$c['risk'], $c['risk']) : null,
                        filled($c['sorumlu'] ?? null) ? 'Sorumlu: '.$c['sorumlu'] : null,
                        filled($c['termin'] ?? null) ? 'Termin: '.\Illuminate\Support\Carbon::parse($c['termin'])->format('d.m.Y') : null,
                    ])->filter()->implode(' · ') }}</span>
                @endif</td>
        </tr>
    @empty
        <tr><td colspan="6" class="orta" style="color:#888">Gösterilecek madde yok.</td></tr>
    @endforelse
</table>

<table class="ekip">
    <tr><th style="width:33%">Ekip Üyesi</th><th style="width:22%">Görev</th><th>Kullanılan KKD</th></tr>
    @forelse (($denetim->ekip_uyeleri ?? []) as $e)
        <tr><td>{{ $e['ad_soyad'] ?? '' }}</td><td style="text-align:center">{{ $e['gorev'] ?: '-' }}</td><td style="text-align:center">{{ $e['kkd'] ?: '-' }}</td></tr>
    @empty
        <tr><td>Ekip bilgisi girilmedi</td><td style="text-align:center">-</td><td style="text-align:center">-</td></tr>
    @endforelse
</table>

@php
    $uyarilar = array_values(config('isg.saha_denetimi.guvenlik_uyarilari'));
    $satirSayisi = (int) ceil(count($uyarilar) / 3);
@endphp
<div style="page-break-inside: avoid">
<table class="uyarilar">
    <tr><th colspan="3">Sabit Güvenlik Uyarıları</th></tr>
    @for ($s = 0; $s < $satirSayisi; $s++)
        <tr>
            @for ($k = 0; $k < 3; $k++)
                @php $i = $k * $satirSayisi + $s; @endphp
                <td>@isset($uyarilar[$i]){{ $i + 1 }}. {{ $uyarilar[$i] }}@endisset</td>
            @endfor
        </tr>
    @endfor
</table>

<table class="alt">
    <tr>
        <td style="width:58%">Genel Notlar: &nbsp; {{ $denetim->genel_notlar ?: '-' }}</td>
        <td>
            <div class="imza">
                @if ($denetim->denetci_kase && ($imzali ?? true))
                    <img src="{{ storage_path('app/public/'.$denetim->denetci_kase) }}">
                @else
                    <div style="height:50px"></div>
                @endif
                <div class="cizgi">Denetçi Kaşe / İmza</div>
            </div>
        </td>
    </tr>
</table>

</div>

@foreach ($denetim->kanitlar() as $k)
    <div class="kanit">
        <h2>FOTOĞRAF KANITLARI</h2>
        <div class="alt-baslik">Kanıt {{ $k['sira'] }} · Madde {{ $k['kod'] }}</div>
        <img src="{{ storage_path('app/public/'.$k['yol']) }}">
        <div class="kimlik">Kanıt kimliği: {{ $k['kimlik'] }}</div>
    </div>
@endforeach

</body>
</html>
