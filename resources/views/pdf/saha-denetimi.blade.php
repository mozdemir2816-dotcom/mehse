<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #111; font-size: 9px; }
    .sayfa { padding: 18px 24px; }
    .baslik { background: #57534e; color: #fff; text-align: center; padding: 8px; margin-bottom: 8px; }
    .baslik h1 { font-size: 13px; margin: 0; }
    .baslik div { font-size: 8.5px; opacity: .85 }
    table.kunye { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
    table.kunye td { border: 1px solid #999; padding: 4px 6px; }
    table.kunye td.etiket { font-weight: bold; width: 12%; background: #f5f5f4; }
    table.ana { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 8px; }
    table.ana th { background: #57534e; color: #fff; padding: 4px 6px; text-align: left; }
    table.ana td { border: 1px solid #d6d3d1; padding: 4px 6px; text-align: left; vertical-align: top; }
    tr.kategori td { background: #e7e5e4; font-weight: bold; text-align: center; }
    .kritik { color: #dc2626; font-weight: bold; }
    .sonuc-uygun { color: #16a34a; font-weight: bold; }
    .sonuc-uygun_degil { color: #dc2626; font-weight: bold; }
    .sonuc-uygulanamaz { color: #78716c; }
    table.ekip { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 8px; }
    table.ekip th { background: #ea580c; color: #fff; padding: 4px 6px; text-align: left; }
    table.ekip td { border: 1px solid #d6d3d1; padding: 4px 6px; }
    table.uyarilar { width: 100%; border-collapse: collapse; font-size: 7.5px; margin-bottom: 10px; }
    table.uyarilar th { background: #57534e; color: #fff; padding: 4px 6px; }
    table.uyarilar td { border: 1px solid #d6d3d1; padding: 5px 8px; vertical-align: top; width: 33.33%; }
    .alt { width: 100%; }
    .alt td { vertical-align: bottom; }
    .kase img { max-height: 55px; }
    .foto-sayfa .baslik2 { font-size: 11px; font-weight: bold; margin-bottom: 4px; }
    .foto-sayfa img { max-width: 100%; max-height: 560px; }
</style>
</head>
<body>
<div class="sayfa">

    <div class="baslik">
        <h1>{{ mb_strtoupper(config('isg.saha_denetimi.sablon_adi'), 'UTF-8') }}</h1>
        <div>{{ $denetim->belgeAdi() }}</div>
    </div>

    <table class="kunye">
        <tr>
            <td class="etiket">Firma</td><td style="width:38%">{{ $firma?->unvan }}</td>
            <td class="etiket">Şantiye</td><td>{{ $denetim->santiye_adi ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiket">İşin Tanımı</td><td>{{ $denetim->is_tanimi ?: '—' }}</td>
            <td class="etiket">Referans No</td><td>{{ $denetim->is_referans_no ?: '—' }}</td>
        </tr>
        @if ($denetim->sektorEtiketi())
        <tr>
            <td class="etiket">Sektör</td><td colspan="3">{{ $denetim->sektorEtiketi() }}</td>
        </tr>
        @endif
        <tr>
            <td class="etiket">Tarih / Saat</td><td>{{ $denetim->denetim_tarihi?->format('d.m.Y') }} · {{ $denetim->denetim_saati }}</td>
            <td class="etiket">Sorumlu</td><td>{{ $denetim->santiye_sorumlusu ?: '—' }}</td>
        </tr>
        <tr>
            <td class="etiket">Denetçi</td><td>{{ $denetim->denetci_adi ?: '—' }}</td>
            <td class="etiket">Sonuç</td><td><strong>{{ $denetim->sonucEtiketi() }}</strong> — %{{ $denetim->uygunluk_yuzdesi ?? '—' }} uygunluk</td>
        </tr>
    </table>

    <table class="ana">
        <tr>
            <th style="width:8%">Kategori / Durum</th>
            <th style="width:5%">Kod</th>
            <th style="width:40%">Kontrol İfadesi</th>
            <th style="width:12%">Sonuç</th>
            <th style="width:35%">Açıklama</th>
        </tr>
        @php
            $mevcutKategori = null;
            // Uygulanamaz maddeler rapor tablosunda yer kaplamasın — istatistiklere zaten dahil değil.
            $gosterilecekCevaplar = collect($denetim->cevaplar ?? [])->reject(fn ($c) => ($c['sonuc'] ?? null) === 'uygulanamaz');
        @endphp
        @forelse ($gosterilecekCevaplar as $c)
            @if ($c['kategori_ad'] !== $mevcutKategori)
                @php $mevcutKategori = $c['kategori_ad']; @endphp
                <tr class="kategori"><td colspan="5">{{ mb_strtoupper($mevcutKategori, 'UTF-8') }}</td></tr>
            @endif
            <tr>
                <td style="text-align:center">@if ($c['kritik'])<span class="kritik">KRİTİK</span>@endif</td>
                <td>{{ $c['kod'] }}</td>
                <td>{{ $c['ifade'] }}</td>
                <td class="sonuc-{{ $c['sonuc'] ?? '' }}">{{ config('isg.saha_denetimi.sonuc_secenekleri.'.($c['sonuc'] ?? ''), '—') }}</td>
                <td>{{ $c['aciklama'] ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#888">Gösterilecek madde yok.</td></tr>
        @endforelse
    </table>

    <table class="ekip">
        <tr><th style="width:34%">Ekip Üyesi</th><th style="width:22%">Görev</th><th>Kullanılan KKD</th></tr>
        @forelse (($denetim->ekip_uyeleri ?? []) as $e)
            <tr><td>{{ $e['ad_soyad'] ?? '' }}</td><td>{{ $e['gorev'] ?? '—' }}</td><td>{{ $e['kkd'] ?? '—' }}</td></tr>
        @empty
            <tr><td>Ekip bilgisi girilmedi</td><td>—</td><td>—</td></tr>
        @endforelse
    </table>

    <table class="uyarilar">
        <tr><th colspan="3">Sabit Güvenlik Uyarıları</th></tr>
        <tr>
            @foreach (collect(config('isg.saha_denetimi.guvenlik_uyarilari'))->chunk(3) as $grup)
                <td>
                    @foreach ($grup as $i => $uyari)
                        {{ $i + 1 }}. {{ $uyari }}<br>
                    @endforeach
                </td>
            @endforeach
        </tr>
    </table>

    <table class="alt">
        <tr>
            <td style="width:60%">Genel Notlar: {{ $denetim->genel_notlar ?: '—' }}</td>
            <td class="kase" style="text-align:right">
                @if ($denetim->denetci_kase)
                    <img src="{{ storage_path('app/public/'.$denetim->denetci_kase) }}"><br>
                @endif
                Denetçi Kaşe / İmza
            </td>
        </tr>
    </table>

</div>

@foreach (($denetim->cevaplar ?? []) as $c)
    @if (! empty($c['foto_yolu']))
        <div class="sayfa foto-sayfa">
            <div class="baslik">
                <h1>{{ mb_strtoupper(config('isg.saha_denetimi.sablon_adi'), 'UTF-8') }}</h1>
                <div>{{ $denetim->belgeAdi() }}</div>
            </div>
            <div class="baslik2">FOTOĞRAF KANITI · Madde {{ $c['kod'] }}</div>
            <img src="{{ storage_path('app/public/'.$c['foto_yolu']) }}">
        </div>
    @endif
@endforeach

</body>
</html>
